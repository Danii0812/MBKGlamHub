<?php
session_start();
require_once 'db.php';

echo "<h2>Database Connection Test</h2>";

try {
    // Test database connection
    echo "✅ Database connection successful<br>";
    
    // Check if messages table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'messages'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Messages table exists<br>";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE messages");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<strong>Messages table structure:</strong><br>";
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']}<br>";
        }
    } else {
        echo "❌ Messages table does not exist<br>";
    }
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Users table exists<br>";
        
        // Show sample users
        $stmt = $pdo->query("SELECT user_id, first_name, last_name, email FROM users LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<strong>Sample users:</strong><br>";
        foreach ($users as $user) {
            echo "- ID: {$user['user_id']}, Name: {$user['first_name']} {$user['last_name']}, Email: {$user['email']}<br>";
        }
    } else {
        echo "❌ Users table does not exist<br>";
    }
    
    // Check session
    echo "<br><strong>Session Info:</strong><br>";
    if (isset($_SESSION['user_id'])) {
        echo "✅ User logged in: ID = {$_SESSION['user_id']}<br>";
        if (isset($_SESSION['user_name'])) {
            echo "✅ User name: {$_SESSION['user_name']}<br>";
        }
    } else {
        echo "❌ No user logged in<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
