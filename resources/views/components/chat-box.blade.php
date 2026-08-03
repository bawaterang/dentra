<div x-data="chatBox()" x-init="init()" @click.outside="isOpen = false" class="fixed z-[1002] pointer-events-none" :class="isMobile ? 'inset-x-0 bottom-0' : 'bottom-8 left-8'">
    <!-- Floating Button -->
    <button 
        x-cloak
        @click="toggleChat()" 
        class="pointer-events-auto flex items-center justify-center rounded-2xl bg-[#405189] text-white shadow-2xl hover:bg-[#0ab39c] active:scale-95 transition-all duration-300 border border-white/20 backdrop-blur-sm relative"
        :class="isMobile ? 'w-10 h-10 ml-5 mb-[90px]' : 'w-12 h-12'"
        x-show="!isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-50"
        x-transition:enter-end="opacity-100 scale-100"
    >
        <i class="ri-chat-3-line text-2xl md:text-3xl"></i>
        <template x-if="unreadCount > 0">
            <span class="absolute -top-1 -right-1 bg-rose-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full border-2 border-white animate-bounce" x-text="unreadCount"></span>
        </template>
    </button>

    <!-- Chat Window -->
    <div 
        x-show="isOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-10 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        class="pointer-events-auto bg-white rounded-3xl shadow-2xl border border-gray-100 flex flex-col overflow-hidden max-w-[95vw] mx-auto"
        :class="isMobile ? 'h-[80vh] mb-20 w-[95vw]' : 'w-[400px] h-[600px]'"
    >
        <!-- Header -->
        <div class="p-4 bg-gradient-to-r from-[#405189] to-[#0ab39c] text-white flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <template x-if="activeUser">
                    <button @click="activeUser = null" class="hover:bg-white/10 rounded-lg p-1 transition-colors">
                        <i class="ri-arrow-left-s-line text-xl"></i>
                    </button>
                </template>
                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center font-bold">
                    <template x-if="activeUser">
                        <span x-text="activeUser.full_name.charAt(0)"></span>
                    </template>
                    <template x-if="!activeUser">
                        <i class="ri-chat-voice-line text-xl"></i>
                    </template>
                </div>
                <div>
                    <h5 class="text-sm font-bold truncate" x-text="activeUser ? activeUser.full_name : 'Pesan Hubungi'"></h5>
                    <p class="text-[10px] opacity-70 font-medium italic" x-text="activeUser ? (isOnline(activeUser.id) ? 'Online' : 'Offline') : 'Internal Chat'"></p>
                </div>
            </div>
            <button @click="isOpen = false" class="w-8 h-8 rounded-lg flex items-center justify-center hover:bg-white/10 transition-colors">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-hidden flex flex-col relative bg-gray-50/30">
            <!-- User List -->
            <div x-show="!activeUser" class="flex-1 overflow-y-auto p-2 space-y-1">
                <template x-for="user in users" :key="user.id">
                    <button @click="selectUser(user)" class="w-full flex items-center gap-3 p-3 rounded-2xl hover:bg-white hover:shadow-md transition-all group">
                        <div class="relative flex-shrink-0">
                            <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center font-bold text-gray-500 group-hover:bg-indigo-50 group-hover:text-indigo-600 transition-colors">
                                <span x-text="user.full_name.charAt(0)"></span>
                            </div>
                            <template x-if="isOnline(user.id)">
                                <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 border-2 border-white rounded-full"></div>
                            </template>
                        </div>
                        <div class="flex-1 text-left">
                            <div class="flex justify-between items-center mb-0.5">
                                <h6 class="text-sm font-bold text-gray-700 truncate" x-text="user.full_name"></h6>
                                <div class="flex items-center gap-2">
                                    <template x-if="user.unread_count > 0">
                                        <span class="bg-rose-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-lg" x-text="user.unread_count"></span>
                                    </template>
                                    <i class="ri-arrow-right-s-line text-gray-300 group-hover:text-indigo-400"></i>
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-400 font-medium truncate" x-text="isOnline(user.id) ? 'Aktif sekarang' : 'Terakhir dilihat baru saja'"></p>
                        </div>
                    </button>
                </template>
            </div>

            <!-- Chat History -->
            <div x-show="activeUser" class="flex-1 flex flex-col overflow-hidden">
                <div x-ref="messageContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
                    <template x-for="msg in messages" :key="msg.id">
                        <div class="flex" :class="isMe(msg.sender_id) ? 'justify-end' : 'justify-start'">
                            <div class="max-w-[80%]">
                                <div class="px-4 py-2.5 rounded-2xl text-sm shadow-sm relative group"
                                    :class="isMe(msg.sender_id) ? 'bg-[#405189] text-white rounded-tr-none' : 'bg-white text-gray-700 rounded-tl-none border border-gray-100'">
                                    <p class="leading-relaxed" x-text="msg.message"></p>
                                    <div class="mt-1 flex items-center justify-end gap-1.5 opacity-60">
                                        <span class="text-[9px] font-bold" x-text="formatTime(msg.created_at)"></span>
                                        <template x-if="isMe(msg.sender_id)">
                                            <i class="ri-check-double-line text-[10px]" :class="msg.is_read ? 'text-emerald-300' : 'text-white/50'"></i>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div x-show="isTyping" class="flex justify-start animate-pulse">
                        <div class="bg-gray-200 px-4 py-2 rounded-2xl text-[10px] font-bold text-gray-500 italic">
                            Sedang mengetik...
                        </div>
                    </div>
                </div>

                <!-- Input area -->
                <div class="p-3 bg-white border-t border-gray-100 shrink-0">
                    <form @submit.prevent="sendMessage()" class="flex items-center gap-2">
                        <div class="flex-1 relative">
                            <input 
                                type="text" 
                                x-model="newMessage" 
                                placeholder="Tulis pesan..." 
                                class="w-full bg-gray-50 border-none rounded-2xl px-4 py-2.5 text-sm font-medium focus:ring-2 focus:ring-indigo-100 transition-all outline-none"
                            >
                        </div>
                        <button 
                            type="submit" 
                            :disabled="!newMessage"
                            class="w-10 h-10 rounded-2xl bg-[#405189] text-white flex items-center justify-center hover:bg-[#0ab39c] disabled:opacity-30 disabled:hover:bg-[#405189] transition-all"
                        >
                            <i class="ri-send-plane-2-fill text-xl"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function chatBox() {
        return {
            isOpen: false,
            isMobile: window.innerWidth < 768,
            users: [],
            activeUser: null,
            messages: [],
            newMessage: '',
            onlineUsers: [],
            unreadCount: 0,
            isTyping: false,
            currentUserId: {{ auth()->id() }},
            
            init() {
                this._echoInitialized = false;
                
                // Defer the private channel listener so it doesn't block initial render
                setTimeout(() => {
                    this.setupPrivateChannel();
                }, 2000);

                window.addEventListener('resize', () => {
                    this.isMobile = window.innerWidth < 768;
                });
            },

            setupPrivateChannel() {
                if (!window.Echo) return;
                
                // Listen to my private channel for incoming messages
                window.Echo.private(`chat.${this.currentUserId}`)
                    .listen('MessageSent', (e) => {
                        const fromCurrentChat = this.isOpen && this.activeUser && this.activeUser.id === e.sender_id;
                        
                        if (fromCurrentChat) {
                            this.messages.push(e);
                            this.markAsRead(e.sender_id);
                            this.$nextTick(() => this.scrollToBottom());
                        } else {
                            this.unreadCount++;
                            
                            // Update individual user unread count
                            const sender = this.users.find(u => u.id === e.sender_id);
                            if (sender) {
                                sender.unread_count = (sender.unread_count || 0) + 1;
                            }
                            
                            // Play sound effect
                            const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3');
                            audio.play().catch(err => console.log('Audio auto-play blocked:', err));

                            // Premium Toast Notification
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'info',
                                iconColor: '#405189',
                                title: `<span class="font-bold text-indigo-900">${e.sender_name}</span>`,
                                html: `<div class="text-sm text-gray-600 truncate max-w-[200px]">${e.message}</div>`,
                                showConfirmButton: false,
                                timer: 5000,
                                timerProgressBar: true,
                                background: '#ffffff',
                                didOpen: (toast) => {
                                    toast.style.cursor = 'pointer';
                                    toast.addEventListener('mouseenter', Swal.stopTimer)
                                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                                    toast.addEventListener('click', () => {
                                        this.isOpen = true;
                                        this.initEchoPresence();
                                        const senderObj = this.users.find(u => u.id === e.sender_id);
                                        if (senderObj) this.selectUser(senderObj);
                                        Swal.close();
                                    })
                                }
                            });
                        }
                    });
            },

            initEchoPresence() {
                if (this._echoInitialized || !window.Echo) return;
                this._echoInitialized = true;

                // Only join presence channel when chat is actually used
                window.Echo.join('online')
                    .here((users) => {
                        this.onlineUsers = users;
                    })
                    .joining((user) => {
                        this.onlineUsers.push(user);
                    })
                    .leaving((user) => {
                        this.onlineUsers = this.onlineUsers.filter(u => u.id !== user.id);
                    });
            },

            toggleChat() {
                this.isOpen = !this.isOpen;
                if (this.isOpen) {
                    this.unreadCount = 0;
                    // Lazy-load users and presence channel on first open
                    if (this.users.length === 0) {
                        this.fetchUsers();
                    }
                    this.initEchoPresence();
                }
            },

            fetchUsers() {
                fetch('/chat/users')
                    .then(res => res.json())
                    .then(data => {
                        this.users = data;
                        this.unreadCount = data.reduce((sum, u) => sum + (u.unread_count || 0), 0);
                    });
            },

            selectUser(user) {
                if (user.unread_count > 0) {
                    this.unreadCount = Math.max(0, this.unreadCount - user.unread_count);
                    user.unread_count = 0;
                }
                this.activeUser = user;
                this.messages = [];
                this.fetchMessages(user.id);
                this.markAsRead(user.id);
            },

            fetchMessages(userId) {
                fetch(`/chat/messages/${userId}`)
                    .then(res => res.json())
                    .then(data => {
                        this.messages = data;
                        this.$nextTick(() => this.scrollToBottom());
                    });
            },

            sendMessage() {
                if (!this.newMessage.trim()) return;

                const msg = {
                    receiver_id: this.activeUser.id,
                    message: this.newMessage
                };

                // Add to local immediately for UX
                const localMsg = {
                    id: Date.now(),
                    sender_id: this.currentUserId,
                    message: this.newMessage,
                    created_at: new Date().toISOString(),
                    is_read: false
                };
                this.messages.push(localMsg);
                const tempNewMessage = this.newMessage;
                this.newMessage = '';
                this.$nextTick(() => this.scrollToBottom());

                fetch('/chat/messages', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(msg)
                })
                .then(res => res.json())
                .then(data => {
                    // Update temp id with real id from server
                    const index = this.messages.findIndex(m => m.id === localMsg.id);
                    if (index !== -1) {
                        this.messages[index] = data.message;
                    }
                });
            },

            markAsRead(senderId) {
                fetch(`/chat/read/${senderId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
            },

            isOnline(userId) {
                return this.onlineUsers.some(u => u.id === userId);
            },

            isMe(senderId) {
                return senderId === this.currentUserId;
            },

            formatTime(dateStr) {
                const date = new Date(dateStr);
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            },

            scrollToBottom() {
                const container = this.$refs.messageContainer;
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            }
        };
    }
    </script>

    <style>

    .scrollbar-thin::-webkit-scrollbar {
        width: 4px;
    }
    .scrollbar-thin::-webkit-scrollbar-track {
        background: transparent;
    }
    .scrollbar-thin::-webkit-scrollbar-thumb {
        background: #e5e7eb;
        border-radius: 10px;
    }
    </style>
</div>
