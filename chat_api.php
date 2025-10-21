<?php
session_start();
require 'db.php';

$current_user_id = $_SESSION['user_id'] ?? null;
$is_admin = ($_SESSION['role'] ?? '') === 'admin';

try {
    $createTableSQL = "
        CREATE TABLE IF NOT EXISTS messages (
            message_id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            receiver_id INT NOT NULL DEFAULT 0, -- 0 = unified admin inbox
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE
            -- no FK on receiver_id to allow 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    $pdo->exec($createTableSQL);
} catch (Exception $e) {
    error_log('Failed to create messages table: ' . $e->getMessage());
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Not logged in"]);
    exit;
}

$currentUserId = $_SESSION['user_id'];

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get the request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Handle authentication check
if ($action === 'check_auth') {
    echo json_encode(['authenticated' => isset($_SESSION['user_id'])]);
    exit;
}

// Ensure user is logged in for other actions
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'authenticated' => false]);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

switch ($method) {
    case 'GET':
        if ($action === 'conversations') {
            if ($current_user_id === null) {
            echo json_encode(['success' => false, 'error' => 'Not logged in']);
            exit;
        } 
          getConversations($pdo, $current_user_id, $is_admin);
        } elseif ($action === 'messages') {
            getMessages($pdo, $current_user_id, $_GET['other_user_id'] ?? 1, $is_admin);
        } elseif ($action === 'unread_count') {
            getUnreadCount($pdo, $current_user_id);
        }
        break;
    
    case 'POST':
        error_log("POST request received. Action: " . $action);
        if ($action === 'send_message') {
            $input = json_decode(file_get_contents('php://input'), true);
            error_log("Input data: " . json_encode($input));
            sendMessage($pdo, $current_user_id, $input);
        } elseif ($action === 'mark_read') {
            $input = json_decode(file_get_contents('php://input'), true);
            markMessagesAsRead($pdo, $current_user_id, $input['other_user_id'] ?? 1);
        }
        break;
    
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getConversations($pdo, $user_id, $is_admin = false) {
    try {
        if ($is_admin) {
            $sql = "
                SELECT 
                    u.user_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS user_name,
                    u.email,
                    (
                        SELECT m.message
                        FROM messages m
                        WHERE (m.sender_id = u.user_id AND m.receiver_id = 0)
                           OR (m.sender_id = 0 AND m.receiver_id = u.user_id)
                           OR (m.sender_id IN (SELECT user_id FROM users WHERE role='admin') AND m.receiver_id = u.user_id)
                        ORDER BY m.created_at DESC 
                        LIMIT 1
                    ) AS last_message,
                    (
                        SELECT MAX(m.created_at)
                        FROM messages m
                        WHERE (m.sender_id = u.user_id AND m.receiver_id = 0)
                           OR (m.sender_id = 0 AND m.receiver_id = u.user_id)
                           OR (m.sender_id IN (SELECT user_id FROM users WHERE role='admin') AND m.receiver_id = u.user_id)
                    ) AS last_message_time,
                    (
                        SELECT COUNT(*)
                        FROM messages m
                        WHERE m.receiver_id = 0 
                          AND m.sender_id = u.user_id 
                          AND m.is_read = 0
                    ) AS unread_count
                FROM users u
                WHERE u.user_id IN (
                    SELECT DISTINCT sender_id FROM messages WHERE receiver_id = 0
                    UNION
                    SELECT DISTINCT receiver_id FROM messages WHERE sender_id = 0
                )
                ORDER BY last_message_time DESC
            ";
            $stmt = $pdo->query($sql);

        } else {
            $sql = "
                SELECT 
                    u.user_id, 
                    CONCAT(u.first_name, ' ', COALESCE(u.last_name, '')) AS user_name, 
                    u.email,
                    (
                        SELECT m.message 
                        FROM messages m 
                        WHERE (m.sender_id = u.user_id OR m.receiver_id = u.user_id)
                        ORDER BY m.created_at DESC LIMIT 1
                    ) AS last_message,
                    (
                        SELECT MAX(m.created_at)
                        FROM messages m
                        WHERE (m.sender_id = u.user_id OR m.receiver_id = u.user_id)
                    ) AS last_message_time,
                    (
                        SELECT COUNT(*) 
                        FROM messages m 
                        WHERE m.receiver_id = :user_id 
                          AND m.sender_id = u.user_id 
                          AND m.is_read = 0
                    ) AS unread_count
                FROM users u
                WHERE u.role = 'admin'
                ORDER BY last_message_time DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
        }

        echo json_encode(['success' => true, 'conversations' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        error_log("getConversations error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}


function getMessages($pdo, $current_user_id, $other_user_id, $is_admin) {
    try {
        // We'll LEFT JOIN users as "sender_u" to get sender role/name.
        // Use COALESCE(sender_u.role, 'admin') to treat missing sender (0) as 'admin'.
        if ($is_admin) {
            // Admin viewing conversation with a specific user:
            $sql = "
                SELECT 
                    m.message_id,
                    m.sender_id,
                    m.receiver_id,
                    m.message,
                    m.is_read,
                    m.created_at,
                    CONCAT(COALESCE(sender_u.first_name, ''), ' ', COALESCE(sender_u.last_name, '')) AS sender_name,
                    COALESCE(sender_u.role, 'admin') AS sender_role
                FROM messages m
                LEFT JOIN users sender_u ON m.sender_id = sender_u.user_id
                WHERE 
                    -- messages from the user to the unified inbox
                    (m.sender_id = :user_id AND m.receiver_id = 0)
                    -- OR messages from any admin (or sender_id = 0) to the user
                    OR (m.receiver_id = :user_id AND (sender_u.role = 'admin' OR m.sender_id = 0))
                ORDER BY m.created_at ASC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':user_id' => $other_user_id]);
        } else {
            // Normal user: fetch all messages where they are sender or receiver,
            // include sender_role so frontend can mark admin messages.
            $sql = "
                SELECT 
                    m.message_id,
                    m.sender_id,
                    m.receiver_id,
                    m.message,
                    m.is_read,
                    m.created_at,
                    CONCAT(COALESCE(sender_u.first_name, ''), ' ', COALESCE(sender_u.last_name, '')) AS sender_name,
                    COALESCE(sender_u.role, 'admin') AS sender_role
                FROM messages m
                LEFT JOIN users sender_u ON m.sender_id = sender_u.user_id
                WHERE m.sender_id = :user_id OR m.receiver_id = :user_id
                ORDER BY m.created_at ASC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':user_id' => $current_user_id]);
        }

        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'messages' => $messages]);
    } catch (Exception $e) {
        http_response_code(500);
        error_log("getMessages error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}


function sendMessage($pdo, $current_user_id, $input) {
    try {
        $receiver_id = $input['receiver_id'] ?? 0; // 0 = unified admin inbox
        $message = trim($input['message'] ?? '');
        
        if (empty($message)) {
            http_response_code(400);
            echo json_encode(['error' => 'Message cannot be empty']);
            return;
        }

        $sql = "INSERT INTO messages (sender_id, receiver_id, message, created_at) 
                VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_user_id, $receiver_id, $message]);

        $message_id = $pdo->lastInsertId();

        $sql = "
            SELECT 
                m.message_id, m.sender_id, m.receiver_id, m.message, m.is_read, m.created_at,
                CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) as sender_name
            FROM messages m
            LEFT JOIN users u ON m.sender_id = u.user_id
            WHERE m.message_id = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$message_id]);
        $new_message = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'message' => $new_message]);
    } catch (Exception $e) {
        error_log("Chat API Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function markMessagesAsRead($pdo, $current_user_id, $other_user_id) {
    try {
        $sql = "UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$other_user_id, $current_user_id]);
        
        echo json_encode(['success' => true, 'updated_count' => $stmt->rowCount()]);
    } catch (Exception $e) {
        error_log("Chat API Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getUnreadCount($pdo, $current_user_id) {
    try {
        $sql = "SELECT COUNT(*) as unread_count FROM messages WHERE receiver_id = ? AND is_read = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'unread_count' => (int)$result['unread_count']]);
    } catch (Exception $e) {
        error_log("Chat API Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
