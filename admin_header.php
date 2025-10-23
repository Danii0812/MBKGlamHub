<header class="bg-white p-6 flex items-center justify-between sticky top-0 z-30 border-b border-gray-100 ml-64">



    <div class="flex items-center space-x-4 relative">


        <div class="relative">
            <a href="live_chat_admin.php" class="flex items-center justify-center w-10 h-10 text-plum-600 rounded-full hover:bg-plum-200 transition-colors">
                <i class="fas fa-envelope text-lg"></i>
            </a>
            <div id="chat-unread-badge" class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center hidden">0</div>
        </div>

        <!-- 🔔 Notification Bell -->
        <div class="relative" id="notification-container">
            <i id="notification-bell" class="fas fa-bell text-gray-500 text-lg cursor-pointer hover:text-plum-500 transition"></i>

            <!-- 🔔 Notification Dropdown -->
            <div id="notification-dropdown" 
                 class="hidden absolute right-0 mt-4 w-80 bg-white shadow-xl rounded-lg overflow-hidden border border-gray-100 z-50">
                <div id="notifications-list" class="max-h-80 overflow-y-auto divide-y divide-gray-100">
                    <p class="p-4 text-gray-500 text-sm text-center">Loading...</p>
                </div>
                <button id="see-more" class="w-full py-2 bg-gray-50 text-plum-600 hover:bg-gray-100 font-medium hidden">
                    See More
                </button>
            </div>
        </div>

    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const bell = document.getElementById('notification-bell');
    const dropdown = document.getElementById('notification-dropdown');
    const content = document.getElementById('notifications-list'); // ✅ fixed ID
    const seeMoreBtn = document.getElementById('see-more'); // ✅ fixed ID

    let isOpen = false;
    let offset = 0;
    const limit = 5;
    let hasMore = false;

    // 🔔 Toggle dropdown
    bell.addEventListener('click', (e) => {
        e.stopPropagation(); // prevent click from closing dropdown
        isOpen = !isOpen;
        dropdown.classList.toggle('hidden', !isOpen);
        if (isOpen) {
            offset = 0;
            content.innerHTML = '<div class="text-center text-gray-400 py-4">Loading...</div>';
            fetchNotifications(true);
        }
    });

    // 🚀 Fetch notifications
    async function fetchNotifications(reset = false) {
        try {
            const res = await fetch(`fetch_notifications.php?limit=${limit}&offset=${offset}`);
            const data = await res.json();
            hasMore = data.hasMore;

            if (reset) content.innerHTML = '';

            renderNotifications(data.notifications, !reset);
            seeMoreBtn.classList.toggle('hidden', !hasMore);
        } catch (err) {
            console.error(err);
            content.innerHTML = '<div class="text-center text-red-500 py-4">Error loading notifications.</div>';
        }
    }

    // 🧾 Render notifications
    function renderNotifications(notifications, append = false) {
        if (!notifications.length && !append) {
            content.innerHTML = '<div class="text-center text-gray-400 py-4">No recent activity</div>';
            return;
        }

        const html = notifications.map(n => {
            let icon = '📢';
            if (n.type === 'booking') icon = '📅';
            else if (n.type === 'cancellation') icon = '❌';
            else if (n.type === 'review') icon = '💬';
            return `
                <div class="py-2 px-4 hover:bg-gray-50 transition">
                    <div class="flex items-start space-x-2">
                        <span>${icon}</span>
                        <div>
                            <p class="text-sm text-gray-700">${n.message}</p>
                            <p class="text-xs text-gray-400">${new Date(n.created_at).toLocaleString()}</p>
                        </div>
                    </div>
                </div>`;
        }).join('');

        if (append) {
            content.insertAdjacentHTML('beforeend', html);
        } else {
            content.innerHTML = html;
        }
    }

    // 📜 See More button
    seeMoreBtn.addEventListener('click', (e) => {
        e.stopPropagation(); // ✅ prevents closing dropdown
        if (!hasMore) return;
        offset += limit;
        fetchNotifications(false);
    });

    // ❌ Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!bell.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
            isOpen = false;
        }
    });
});
</script>