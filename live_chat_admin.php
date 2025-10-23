<?php
session_start();

require_once 'db.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$admin_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Chat - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        lavender: {
                            50: '#fafaff',
                            100: '#f5f5fa',
                            200: '#ececf7',
                            300: '#e6e6fa',
                            400: '#d8d1e8',
                            500: '#c2b6d9',
                            600: '#a79dbf',
                            700: '#8e83a3',
                            800: '#756a86',
                            900: '#5d516c'
                        },
                        plum: {
                            50: '#f9f2f7',
                            100: '#f1e3ef',
                            200: '#e0c5dc',
                            300: '#c89ac1',
                            400: '#a06c9e',
                            500: '#804f7e',
                            600: '#673f68',
                            700: '#4b2840',
                            800: '#3c1f33',
                            900: '#2c1726'
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar - Conversations List -->
        <div class="w-1/3 bg-white border-r border-gray-200 flex flex-col">
            <div class="p-4 border-b border-gray-200">
                <h1 class="text-xl font-semibold text-gray-800">Live Chat</h1>
                <p class="text-sm text-gray-600">Customer Support</p>
            </div>
            
            <div id="conversationsList" class="flex-1 overflow-y-auto">
                <div class="p-4 text-center text-gray-500">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                    <p>Loading conversations...</p>
                </div>
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="flex-1 flex flex-col">
            <!-- Chat Header -->
            <div id="chatHeader" class="p-4 border-b border-gray-200 bg-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-user text-gray-600"></i>
                        </div>
                        <div>
                            <div class="font-semibold text-gray-800">Select a conversation</div>
                            <div class="text-sm text-gray-600">Choose a customer to start chatting</div>
                        </div>
                    </div>
                    <a href="admin_dashboard.php" class="text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>

            <!-- Messages Area -->
            <div id="messagesArea" class="flex-1 overflow-y-auto p-4 bg-gray-50">
                <div class="text-center text-gray-500 mt-20">
                    <i class="fas fa-comments text-4xl mb-4"></i>
                    <p>Select a conversation to start chatting</p>
                </div>
            </div>

            <!-- Message Input -->
            <div id="messageInput" class="p-4 border-t border-gray-200 bg-white hidden">
                <div class="flex items-center space-x-2">
                    <input type="text" id="chatInput" placeholder="Type your message..." class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:border-plum-500">
                    <button id="sendBtn" class="bg-gradient-to-r from-plum-500 to-plum-600 text-white px-6 py-2 rounded-lg hover:shadow-md transition-all">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentConversation = null;
        let conversations = [];
        let messages = [];
        const adminId = <?php echo $admin_id; ?>;

        // Load conversations
        async function loadConversations() {
            try {
                const response = await fetch('chat_api.php?action=conversations');
                const data = await response.json();
                
                if (data.success) {
                    conversations = data.conversations;
                    renderConversations();
                } else {
                    console.error('Failed to load conversations:', data.error);
                }
            } catch (error) {
                console.error('Error loading conversations:', error);
            }
        }

        // Render conversations list
        function renderConversations() {
            const conversationsList = document.getElementById('conversationsList');
            conversationsList.innerHTML = '';

            if (conversations.length === 0) {
                conversationsList.innerHTML = `
                    <div class="p-4 text-center text-gray-500">
                        <i class="fas fa-inbox text-2xl mb-2"></i>
                        <p>No conversations yet</p>
                    </div>
                `;
                return;
            }

            conversations.forEach(conversation => {
                const conversationDiv = document.createElement('div');
                conversationDiv.className = `p-4 border-b border-gray-100 cursor-pointer hover:bg-gray-50 ${currentConversation && currentConversation.user_id === conversation.user_id ? 'bg-plum-50 border-plum-200' : ''}`;
                conversationDiv.onclick = () => selectConversation(conversation);

                conversationDiv.innerHTML = `
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-r from-plum-400 to-plum-600 rounded-full flex items-center justify-center text-white font-semibold mr-3">
                            ${conversation.user_name.charAt(0).toUpperCase()}
                        </div>
                        <div class="flex-1">
                            <div class="font-semibold text-gray-800">${conversation.user_name}</div>
                            <div class="text-sm text-gray-600 truncate">${conversation.last_message || 'No messages yet'}</div>
                        </div>
                        ${conversation.unread_count > 0 ? `<div class="bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">${conversation.unread_count}</div>` : ''}
                    </div>
                `;

                conversationsList.appendChild(conversationDiv);
            });
        }

// Select conversation
async function selectConversation(conversation) {
    currentConversation = conversation;

    // Update header
    const chatHeader = document.getElementById('chatHeader');
    chatHeader.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-gradient-to-r from-plum-400 to-plum-600 rounded-full flex items-center justify-center text-white font-semibold mr-3">
                    ${conversation.user_name.charAt(0).toUpperCase()}
                </div>
                <div>
                    <div class="font-semibold text-gray-800">${conversation.user_name}</div>
                    <div class="text-sm text-gray-600">${conversation.email}</div>
                </div>
            </div>
            <a href="admin_dashboard.php" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    `;

    // Show message input
    document.getElementById('messageInput').classList.remove('hidden');

    // Load messages
    await loadMessages(conversation.user_id);

    // 🟢 Mark all messages from this user as read in the database
    await markMessagesAsRead(conversation.user_id);

    // 🔴 Immediately clear unread badge in the sidebar
    conversation.unread_count = 0;
    renderConversations();
}



        // Load messages for conversation
        async function loadMessages(userId) {
            try {
                const response = await fetch(`chat_api.php?action=messages&other_user_id=${userId}`);
                const data = await response.json();
                
                if (data.success) {
                    messages = data.messages;
                    renderMessages();
                    scrollToBottom();
                } else {
                    console.error('Failed to load messages:', data.error);
                }
            } catch (error) {
                console.error('Error loading messages:', error);
            }
        }

        // Render messages
        function renderMessages() {
            const messagesArea = document.getElementById('messagesArea');
            messagesArea.innerHTML = '';

            if (messages.length === 0) {
                messagesArea.innerHTML = `
                    <div class="text-center text-gray-500 mt-20">
                        <i class="fas fa-comments text-4xl mb-4"></i>
                        <p>No messages in this conversation</p>
                    </div>
                `;
                return;
            }

            messages.forEach(message => {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'mb-4';

                // Treat a message as admin-sent if sender_role === 'admin' OR sender_id == 0
                const isAdminMessage = (message.sender_role && message.sender_role === 'admin') || message.sender_id == 0;

                if (isAdminMessage) {
                    let statusText = '';
                    if (message.status === 'sending') {
                        statusText = '<span class="text-xs opacity-75 ml-2">Sending...</span>';
                    } else if (message.status === 'failed') {
                        statusText = '<span class="text-xs text-red-500 ml-2">Failed</span>';
                    }

                    messageDiv.innerHTML = `
                        <div class="flex justify-end">
                            <div class="bg-gradient-to-r from-plum-500 to-plum-600 text-white rounded-lg p-3 max-w-xs">
                                <p class="text-sm">${escapeHtml(message.message)}</p>
                                <div class="text-xs opacity-75 mt-1">
                                    ${formatTime(message.created_at)} ${statusText}
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    messageDiv.innerHTML = `
                        <div class="flex">
                            <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center mr-2 flex-shrink-0">
                                <i class="fas fa-user text-gray-600 text-xs"></i>
                            </div>
                            <div class="bg-white rounded-lg p-3 max-w-xs shadow-sm">
                                <p class="text-sm">${escapeHtml(message.message)}</p>
                                <div class="text-xs text-gray-500 mt-1">${formatTime(message.created_at)}</div>
                            </div>
                        </div>
                    `;
                }

                messagesArea.appendChild(messageDiv);
            });
        }

        // Send message
        async function sendMessage() {
            if (!currentConversation) return;

            const chatInput = document.getElementById('chatInput');
            const message = chatInput.value.trim();
            if (!message) return;

            chatInput.value = '';
            chatInput.disabled = true;

            // Create a temporary message for immediate rendering
            const tempMessage = {
                message_id: 'temp-' + Date.now(),
                sender_id: adminId,
                sender_role: 'admin',
                message: message,
                created_at: new Date().toISOString(),
                status: 'sending'
            };
            messages.push(tempMessage);
            renderMessages();
            scrollToBottom();

            try {
                const response = await fetch('chat_api.php?action=send_message', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        receiver_id: currentConversation.user_id, 
                        message: message
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Replace the temp message with the real one
                    messages = messages.map(m => 
                        m.message_id === tempMessage.message_id ? data.message : m
                    );
                    renderMessages();
                    scrollToBottom();
                    loadConversations();
                } else {
                    // Mark as failed
                    messages = messages.map(m =>
                        m.message_id === tempMessage.message_id ? { ...m, status: 'failed' } : m
                    );
                    renderMessages();
                }
            } catch (error) {
                console.error('Error sending message:', error);
                messages = messages.map(m =>
                    m.message_id === tempMessage.message_id ? { ...m, status: 'failed' } : m
                );
                renderMessages();
            } finally {
                chatInput.disabled = false;
                chatInput.focus();
            }
        }



        // Scroll to bottom
        function scrollToBottom() {
            const messagesArea = document.getElementById('messagesArea');
            messagesArea.scrollTop = messagesArea.scrollHeight;
        }

        // Escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Format time
        function formatTime(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        // Event listeners
        document.getElementById('sendBtn').addEventListener('click', sendMessage);
        document.getElementById('chatInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // Initialize
        loadConversations();
        
        // Auto-refresh every 5 seconds
        setInterval(() => {
            loadConversations();
            if (currentConversation) {
                loadMessages(currentConversation.user_id);
            }
        }, 5000);

        async function markMessagesAsRead(userId) {
    try {
        const response = await fetch('chat_api.php?action=mark_read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ other_user_id: userId })
        });
        const data = await response.json();
        if (!data.success) {
            console.error('Failed to mark messages as read:', data.error);
        }
    } catch (error) {
        console.error('Error marking messages as read:', error);
    }
}

    </script>
</body>
</html>
