/**
 * MM Referral Chat - Frontend Interface
 */

(function($) {
    'use strict';

    class MMChat {
        constructor() {
            this.currentConversation = null;
            this.conversations = [];
            this.pollingTimer = null;
            this.badgePollingTimer = null;
            this.isOpen = false;
            this.currentTab = 'conversations'; // 'conversations' or 'partners'
            this.allPartners = []; // Store all partners for client-side filtering
            this.lastMessageId = 0; // Track last message to avoid re-rendering
            this.loadedMessages = []; // Cache loaded messages
            // Simple default avatar - base64 encoded light gray circle with question mark
            this.defaultAvatar = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAoAAAAKCAYAAACNMs+9AAAAPUlEQVQYlWNkYGD4z8DAwMjExMjIyMjIxMTMwMDAwMDAwMDAwMDAoKGBgZGBkZGBkZGBgYGBgZGBkYGBkYmJkZERAG+GJWxaKBfSAAAAAElFTkSuQmCC';
            
            this.init();
        }

        init() {
            // Create DOM elements
            this.createChatUI();
            
            // Initialize partners data from window object (pre-loaded from PHP)
            if (mmChat.partners && mmChat.partners.length > 0) {
                this.allPartners = mmChat.partners;
                window.mmChatPartners = mmChat.partners;
            }
            
            // Bind events
            this.bindEvents();
            
            // Load initial data (once on page load)
            this.loadConversations();
            
            // Update unread count badge on initial load
            this.updateUnreadBadge();
            
            // Set up periodic badge update (less frequent, not full polling)
            this.startBadgePolling();
        }

        createChatUI() {
            const html = `
                <!-- Action Buttons Group -->
                <div class="mm-action-buttons-group" id="mm-action-buttons-group">
                    <button class="mm-action-btn" id="mm-chat-toggle" title="Chat Room" data-action="chat">
                        💬
                        <span class="mm-action-badge hidden" id="mm-chat-badge">0</span>
                    </button>
                    <button class="mm-action-btn" id="mm-ai-chat-btn" title="AI Support" data-action="ai">
                        🤖
                    </button>
                </div>
                
                <!-- Main Chat Container -->
                <div class="mm-chat-container hidden" id="mm-chat-container">
                    <!-- Header with Tabs -->
                    <div class="mm-chat-header">
                        <div class="mm-chat-header-title">
                            <h3>Referral Chat</h3>
                        </div>
                        <button class="mm-chat-header-close" id="mm-chat-close">✕</button>
                    </div>

                    <!-- Tab Navigation -->
                    <div class="mm-chat-tabs">
                        <button class="mm-chat-tab-btn active" data-tab="conversations">💬 Conversations</button>
                        <button class="mm-chat-tab-btn" data-tab="partners">➕ Add Chat</button>
                    </div>

                    <!-- Content Area -->
                    <div class="mm-chat-content">
                        <!-- Conversations Tab -->
                        <div class="mm-chat-tab-panel active" data-tab="conversations">
                            <div class="mm-chat-conversations-list">
                                <!-- Loaded via JS -->
                            </div>
                        </div>

                        <!-- Partners Tab -->
                        <div class="mm-chat-tab-panel" data-tab="partners">
                            <div class="mm-partners-search-box">
                                <input type="text" class="mm-chat-partner-search" placeholder="Search referral partners...">
                            </div>
                            <div class="mm-chat-partners-list">
                                <!-- Loaded via JS -->
                            </div>
                        </div>
                    </div>

                    <!-- Message Window (Overlay) -->
                    <div class="mm-chat-message-overlay" id="mm-message-overlay">
                        <div class="mm-message-overlay-header">
                            <button class="mm-message-back-btn" id="mm-message-back-btn">← Back</button>
                            <h3 class="mm-message-user-name"></h3>
                        </div>
                        <div class="mm-chat-messages"></div>
                        <div class="mm-chat-input-area">
                            <div class="mm-chat-input-wrapper">
                                <textarea class="mm-chat-input-field" placeholder="Type a message..." style="resize: none;"></textarea>
                                <button class="mm-chat-send-btn">Send</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(html);
            
            // Hide the original resume guide button
            $('#mm-spg-launcher').hide();
        }

        bindEvents() {
            const self = this;

            // Action buttons handler
            $('.mm-action-btn').on('click', function() {
                const action = $(this).data('action');
                if (action === 'chat') {
                    if (mmChat.chatRoomUrl) {
                        window.location.href = mmChat.chatRoomUrl;
                    } else {
                        self.toggleChat();
                    }
                } else if (action === 'resume') {
                    // Reserved — no link yet
                    return;
                } else if (action === 'ai') {
                    self.openAiChat();
                }
            });

            // Close chat
            $('#mm-chat-close').on('click', function() {
                self.closeChat();
            });

            // Tab switching
            $('.mm-chat-tab-btn').on('click', function() {
                const tab = $(this).data('tab');
                self.switchTab(tab);
            });

            // Back button in message overlay
            $('#mm-message-back-btn').on('click', function() {
                self.closeConversation();
            });

            // Send message
            $(document).on('click', '.mm-chat-send-btn', function() {
                self.sendMessage();
            });

            // Enter to send (use keydown instead of keypress to avoid passive listener warnings)
            $(document).on('keydown', '.mm-chat-input-field', function(e) {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });

            // Open conversation
            $(document).on('click', '.mm-conversation-item', function() {
                const convId = $(this).data('id');
                self.openConversation(convId);
            });

            // Open chat with partner
            $(document).on('click', '.mm-partner-item', function() {
                const userId = $(this).data('id');
                self.startConversation(userId);
            });

            // Search partners - with multiple event listeners for better compatibility
            const searchHandler = function() {
                const searchTerm = $(this).val().toLowerCase().trim();
                self.filterPartners(searchTerm);
            };
            
            $(document).on('keyup', '.mm-chat-partner-search', searchHandler);
            $(document).on('input', '.mm-chat-partner-search', searchHandler);
            $(document).on('change', '.mm-chat-partner-search', searchHandler);
        }

        toggleChat() {
            if (this.isOpen) {
                this.closeChat();
            } else {
                this.openChat();
            }
        }

        openChat() {
            this.isOpen = true;
            $('#mm-chat-container').removeClass('hidden');
            // Highlight the chat button
            $('#mm-chat-toggle').css({
                'box-shadow': '0 8px 24px rgba(0, 123, 255, 0.5)',
                'background': '#0056b3'
            });
            this.loadConversations();
            // Start polling when chat opens
            this.startPolling();
        }


        closeChat() {
            this.isOpen = false;
            $('#mm-chat-container').addClass('hidden');
            // Reset the chat button style
            $('#mm-chat-toggle').css({
                'box-shadow': '0 4px 12px rgba(0, 123, 255, 0.3)',
                'background': 'var(--chat-primary)'
            });
            // Stop polling when chat closes
            this.stopPolling();
        }

        openAiChat() {
            var self = this;

            if (window.MMChatWidget && typeof window.MMChatWidget.openWidget === 'function') {
                try {
                    if (typeof window.MMChatWidget.init === 'function') {
                        window.MMChatWidget.init();
                    }
                    window.MMChatWidget.openWidget();
                    return;
                } catch (e) {
                    console.warn('MMChatWidget failed, using fallback:', e);
                }
            }

            if ($('#mm-ai-chat-widget').length) {
                this.ensureAiFallbackReady();
                this.openAiFallbackWidget();
                return;
            }

            this.loadAiWidgetScript(function() {
                if (window.MMChatWidget && typeof window.MMChatWidget.openWidget === 'function') {
                    window.MMChatWidget.init();
                    window.MMChatWidget.openWidget();
                } else if ($('#mm-ai-chat-widget').length) {
                    self.ensureAiFallbackReady();
                    self.openAiFallbackWidget();
                } else {
                    alert('AI chat widget not found. Go to AI Chat → Settings and enable the plugin, then hard-refresh (Ctrl+F5).');
                }
            });
        }

        loadAiWidgetScript(done) {
            if (!mmChat.aiWidgetScript || document.querySelector('script[src*="chat-widget.js"]')) {
                done();
                return;
            }
            var s = document.createElement('script');
            s.src = mmChat.aiWidgetScript + '?v=' + Date.now();
            s.onload = done;
            s.onerror = function() {
                console.error('Failed to load chat-widget.js');
                done();
            };
            document.body.appendChild(s);
        }

        ensureAiFallbackReady() {
            if (this._aiFallbackReady) {
                return;
            }
            this._aiFallbackReady = true;
            this._aiConversationId = null;
            this._aiWaiting = false;

            var self = this;
            var stored = localStorage.getItem('mm_ai_chat_session');
            if (stored) {
                try {
                    var session = JSON.parse(stored);
                    this._aiConversationId = session.conversationId || null;
                } catch (e) {}
            }

            $(document).off('click.mmAiFallback', '#mm-ai-chat-close').on('click.mmAiFallback', '#mm-ai-chat-close', function(e) {
                e.preventDefault();
                self.closeAiFallbackWidget();
            });

            $(document).off('click.mmAiFallback', '#mm-ai-chat-send').on('click.mmAiFallback', '#mm-ai-chat-send', function(e) {
                e.preventDefault();
                self.sendAiFallbackMessage();
            });

            $(document).off('keypress.mmAiFallback', '#mm-ai-chat-input').on('keypress.mmAiFallback', '#mm-ai-chat-input', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.sendAiFallbackMessage();
                }
            });
        }

        openAiFallbackWidget() {
            $('#mm-ai-chat-widget').stop(true, true).slideDown(300);
            $('#mm-ai-chat-toggle').hide();

            if (!this._aiConversationId) {
                this.initAiFallbackChat();
            } else if ($('#mm-ai-chat-messages').children().length === 0) {
                this.loadAiFallbackMessages();
            }
        }

        closeAiFallbackWidget() {
            $('#mm-ai-chat-widget').stop(true, true).slideUp(300);
            $('#mm-ai-chat-toggle').hide();
        }

        aiPayload(response) {
            if (!response || typeof response !== 'object') {
                return {};
            }
            return response.data && typeof response.data === 'object' ? response.data : response;
        }

        appendAiMessage(type, content) {
            var cls = type === 'user' ? 'msg-user' : 'msg-ai';
            var html = '<div class="mm-ai-chat-message ' + cls + '">' +
                '<div class="mm-ai-chat-message-content">' + $('<div>').text(content).html() + '</div>' +
                '</div>';
            $('#mm-ai-chat-messages').append(html);
            var el = document.getElementById('mm-ai-chat-messages');
            if (el) {
                el.scrollTop = el.scrollHeight;
            }
        }

        initAiFallbackChat() {
            var self = this;
            if (!mmChat.aiRestUrl) {
                this.appendAiMessage('ai', 'AI chat is not configured.');
                return;
            }
            if (this._aiInitializing) {
                return;
            }
            this._aiInitializing = true;
            $('#mm-ai-chat-messages').empty();

            $.ajax({
                url: mmChat.aiRestUrl.replace(/\/$/, '') + '/chat/initiate',
                type: 'POST',
                dataType: 'json',
                processData: false,
                contentType: 'application/json',
                headers: {
                    'X-WP-Nonce': mmChat.aiRestNonce
                },
                data: JSON.stringify({
                    page_context: {
                        page_url: window.location.href,
                        page_title: document.title,
                        referrer: document.referrer
                    }
                }),
                success: function(response) {
                    self._aiInitializing = false;
                    var data = self.aiPayload(response);
                    if (data.success && data.conversation_id) {
                        self._aiConversationId = data.conversation_id;
                        localStorage.setItem('mm_ai_chat_session', JSON.stringify({
                            conversationId: data.conversation_id,
                            sessionId: data.session_id,
                            mode: 'ai'
                        }));
                        if (data.message && data.message.content) {
                            self.appendAiMessage('ai', data.message.content);
                        } else {
                            self.appendAiMessage('ai', 'Welcome! How can I help you today?');
                        }
                        if (data.api_warning) {
                            self.appendAiMessage('ai', 'Note: ' + data.api_warning);
                        }
                    } else {
                        self._aiConversationId = null;
                        localStorage.removeItem('mm_ai_chat_session');
                        self.appendAiMessage('ai', data.error || 'Could not start chat.');
                    }
                },
                error: function(xhr) {
                    self._aiInitializing = false;
                    self._aiConversationId = null;
                    localStorage.removeItem('mm_ai_chat_session');
                    console.error('AI initiate error:', xhr.status, xhr.responseText);
                    self.appendAiMessage('ai', 'Error connecting to AI chat. Hard-refresh (Ctrl+F5) and try again.');
                }
            });
        }

        loadAiFallbackMessages() {
            var self = this;
            $.ajax({
                url: mmChat.aiRestUrl + '/chat/messages/' + this._aiConversationId,
                type: 'GET',
                dataType: 'json',
                headers: { 'X-WP-Nonce': mmChat.aiRestNonce },
                success: function(response) {
                    var data = self.aiPayload(response);
                    if (response.success && data.messages) {
                        $('#mm-ai-chat-messages').empty();
                        data.messages.forEach(function(msg) {
                            self.appendAiMessage(msg.sender_type || msg.type || 'ai', msg.content || '');
                        });
                    }
                }
            });
        }

        sendAiFallbackMessage() {
            var self = this;
            var text = $('#mm-ai-chat-input').val().trim();
            if (!text || this._aiWaiting) {
                return;
            }
            if (!this._aiConversationId) {
                if (!this._aiInitializing) {
                    this.initAiFallbackChat();
                }
                return;
            }

            this.appendAiMessage('user', text);
            $('#mm-ai-chat-input').val('');
            this._aiWaiting = true;
            $('#mm-ai-chat-send').prop('disabled', true);

            $.ajax({
                url: mmChat.aiRestUrl.replace(/\/$/, '') + '/chat/message',
                type: 'POST',
                dataType: 'json',
                processData: false,
                contentType: 'application/json',
                headers: {
                    'X-WP-Nonce': mmChat.aiRestNonce
                },
                data: JSON.stringify({
                    conversation_id: this._aiConversationId,
                    message: text
                }),
                success: function(response) {
                    var data = self.aiPayload(response);
                    if (data.success && data.ai_response) {
                        self.appendAiMessage('ai', data.ai_response);
                    } else {
                        self.appendAiMessage('ai', data.error || 'Unable to get a response.');
                    }
                },
                error: function(xhr) {
                    console.error('AI message error:', xhr.status, xhr.responseText);
                    self.appendAiMessage('ai', 'Error sending message. Please try again.');
                },
                complete: function() {
                    self._aiWaiting = false;
                    $('#mm-ai-chat-send').prop('disabled', false);
                }
            });
        }

        switchTab(tab) {
            this.currentTab = tab;

            // Update active tab button
            $('.mm-chat-tab-btn').removeClass('active');
            $(`.mm-chat-tab-btn[data-tab="${tab}"]`).addClass('active');

            // Show/hide tab panels
            $('.mm-chat-tab-panel').removeClass('active');
            $(`.mm-chat-tab-panel[data-tab="${tab}"]`).addClass('active');

            if (tab === 'partners') {
                this.loadPartners();
            }
        }

        loadConversations() {
            const self = this;

            $.ajax({
                url: mmChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mm_chat_get_conversations',
                    nonce: mmChat.nonce,
                    limit: 20
                },
                success: function(response) {
                    if (response.success) {
                        self.conversations = response.data.conversations;
                        console.log('Conversations loaded:', self.conversations);
                        self.renderConversations();
                        self.updateUnreadBadge();
                    } else {
                        console.warn('Failed to load conversations:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading conversations:', error, xhr.responseText);
                }
            });
        }

        renderConversations() {
            const html = this.conversations.length === 0 
                ? '<div class="mm-chat-empty"><div class="mm-chat-empty-icon">💭</div><p class="mm-chat-empty-text">No conversations yet</p></div>'
                : this.conversations.map(conv => `
                    <div class="mm-conversation-item" data-id="${conv.id}" data-user-name="${conv.other_user.name}" data-user-id="${conv.other_user.id}">
                        <img src="${conv.other_user.avatar || this.defaultAvatar}" 
                             class="mm-conversation-avatar" alt="${conv.other_user.name}" onerror="this.src='${this.defaultAvatar}'">
                        <div class="mm-conversation-info">
                            <p class="mm-conversation-name">${conv.other_user.name}</p>
                            <p class="mm-conversation-preview">${conv.last_message ? conv.last_message.text : 'No messages'}</p>
                        </div>
                        ${conv.unread_count > 0 ? `<span class="mm-conversation-unread">${conv.unread_count}</span>` : ''}
                    </div>
                `).join('');

            $('.mm-chat-conversations-list').html(html);
        }

        loadPartners() {
            const self = this;

            // First, try to get partners from window object if already loaded
            if (window.mmChatPartners && window.mmChatPartners.length > 0) {
                self.allPartners = window.mmChatPartners;
                self.renderPartners(self.allPartners);
                return;
            }

            // Otherwise, fetch via AJAX
            $.ajax({
                url: mmChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mm_chat_get_partners',
                    nonce: mmChat.nonce,
                    limit: 50
                },
                success: function(response) {
                    if (response.success && response.data.partners) {
                        self.allPartners = response.data.partners;
                        window.mmChatPartners = self.allPartners; // Cache for later use
                        self.renderPartners(self.allPartners);
                    }
                },
                error: function() {
                    // If AJAX fails, render empty state
                    self.renderPartners([]);
                }
            });
        }

        renderPartners(partners) {
            const self = this;
            const html = partners.length === 0
                ? '<div class="mm-chat-empty"><div class="mm-chat-empty-icon">👥</div><p class="mm-chat-empty-text">No referral partners yet</p></div>'
                : partners.map(function(partner) {
                    return `
                    <div class="mm-partner-item" data-id="${partner.id}" data-name="${partner.name.toLowerCase()}" data-username="${(partner.username || '').toLowerCase()}">
                        <img src="${partner.avatar || self.defaultAvatar}" 
                             alt="${partner.name}" onerror="this.src='${self.defaultAvatar}'">
                        <div>
                            <p>${partner.name}</p>
                            <p>@${partner.username}</p>
                        </div>
                    </div>
                `;
                }).join('');

            $('.mm-chat-partners-list').html(html);
        }

        filterPartners(searchTerm) {
            if (!searchTerm || searchTerm === '') {
                // Show all partners
                this.renderPartners(this.allPartners);
                return;
            }

            searchTerm = searchTerm.toLowerCase().trim();
            
            // Filter partners from the allPartners array
            const filtered = this.allPartners.filter(partner => {
                const name = (partner.name || '').toLowerCase();
                const username = (partner.username || '').toLowerCase();
                return name.includes(searchTerm) || username.includes(searchTerm);
            });
            
            // Render filtered results
            this.renderFilteredPartners(filtered);
        }

        renderFilteredPartners(partners) {
            const self = this;
            const html = partners.length === 0
                ? '<div class="mm-chat-empty"><div class="mm-chat-empty-icon">🔍</div><p class="mm-chat-empty-text">No partners found</p></div>'
                : partners.map(function(partner) {
                    return `
                    <div class="mm-partner-item" data-id="${partner.id}" data-name="${partner.name.toLowerCase()}" data-username="${(partner.username || '').toLowerCase()}">
                        <img src="${partner.avatar || self.defaultAvatar}" 
                             alt="${partner.name}" onerror="this.src='${self.defaultAvatar}'">
                        <div>
                            <p>${partner.name}</p>
                            <p>@${partner.username}</p>
                        </div>
                    </div>
                `;
                }).join('');

            $('.mm-chat-partners-list').html(html);
        }

        openConversation(conversationId) {
            this.currentConversation = conversationId;
            
            // Show message overlay
            $('#mm-message-overlay').addClass('active');

            // Get the clicked element to extract user info
            const $conversationItem = $(`.mm-conversation-item[data-id="${conversationId}"]`);
            
            // First try to get from this.conversations array
            let userName = null;
            const conv = this.conversations.find(c => c.id === conversationId);
            
            if (conv && conv.other_user) {
                userName = conv.other_user.name;
                console.log('Got user name from array:', userName);
            } else if ($conversationItem.length) {
                // Fallback to data attribute
                userName = $conversationItem.data('user-name');
                console.log('Got user name from data attribute:', userName);
            }
            
            if (!userName) {
                userName = 'User'; // Last resort default
                console.warn('Could not find user name, using default');
            }
            
            console.log('Setting user name to:', userName);
            $('.mm-message-user-name').text(userName);

            // Load messages
            this.loadMessages();
        }

        closeConversation() {
            this.currentConversation = null;
            this.loadedMessages = []; // Clear message cache
            
            // Hide message overlay
            $('#mm-message-overlay').removeClass('active');
            
            // Clear the user name
            $('.mm-message-user-name').text('');
            
            // Reload conversations
            this.loadConversations();
        }

        loadMessages() {
            const self = this;

            if (!this.currentConversation) return;

            $.ajax({
                url: mmChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mm_chat_get_messages',
                    nonce: mmChat.nonce,
                    conversation_id: this.currentConversation,
                    limit: 50
                },
                success: function(response) {
                    if (response.success) {
                        self.renderMessages(response.data.messages);
                    }
                }
            });
        }

        renderMessages(messages) {
            const $messagesDiv = $('#mm-message-overlay .mm-chat-messages');
            
            // If messages are the same as before, don't re-render (prevents blinking)
            if (this.loadedMessages.length === messages.length && 
                this.loadedMessages.length > 0 &&
                messages.length > 0 &&
                this.loadedMessages[this.loadedMessages.length - 1].id === messages[messages.length - 1].id) {
                return; // No new messages, skip rendering
            }
            
            // Only add new messages if we already have some loaded
            if (this.loadedMessages.length > 0 && messages.length > this.loadedMessages.length) {
                // Find new messages
                const newMessages = messages.slice(this.loadedMessages.length);
                this.loadedMessages = messages;
                
                // Append only new messages
                const newHtml = newMessages.map(msg => {
                    const isOwn = msg.sender_id === mmChat.currentUserId;
                    const bubbleClass = isOwn ? 'sent' : 'received';
                    const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                    return `
                        <div class="mm-message-bubble ${bubbleClass}">
                            ${!isOwn ? `<img src="${msg.sender.avatar || mmChat.defaultAvatar}" class="mm-message-avatar" alt="${msg.sender.name}" onerror="this.src='${mmChat.defaultAvatar}'">` : ''}
                            <div>
                                <div class="mm-message-text">${msg.message}</div>
                                <div class="mm-message-time">${time}</div>
                            </div>
                        </div>
                    `;
                }).join('');
                
                $messagesDiv.append(newHtml);
            } else {
                // First load or all messages need re-render
                this.loadedMessages = messages;
                
                const html = messages.map(msg => {
                    const isOwn = msg.sender_id === mmChat.currentUserId;
                    const bubbleClass = isOwn ? 'sent' : 'received';
                    const time = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                    return `
                        <div class="mm-message-bubble ${bubbleClass}">
                            ${!isOwn ? `<img src="${msg.sender.avatar || mmChat.defaultAvatar}" class="mm-message-avatar" alt="${msg.sender.name}" onerror="this.src='${mmChat.defaultAvatar}'">` : ''}
                            <div>
                                <div class="mm-message-text">${msg.message}</div>
                                <div class="mm-message-time">${time}</div>
                            </div>
                        </div>
                    `;
                }).join('');

                $messagesDiv.html(html || '<div class="mm-chat-empty"><p>No messages yet</p></div>');
            }
            
            // Scroll to bottom
            $messagesDiv.scrollTop($messagesDiv[0].scrollHeight);
        }

        sendMessage() {
            const $input = $('.mm-chat-input-field');
            const message = $input.val().trim();

            if (!message || !this.currentConversation) return;

            const self = this;

            $.ajax({
                url: mmChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mm_chat_send_message',
                    nonce: mmChat.nonce,
                    conversation_id: this.currentConversation,
                    message: message
                },
                success: function(response) {
                    if (response.success) {
                        $input.val('');
                        $input.css('height', 'auto');
                        self.loadMessages();
                    }
                }
            });
        }

        startConversation(userId) {
            const self = this;

            $.ajax({
                url: mmChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mm_chat_start_conversation',
                    nonce: mmChat.nonce,
                    user_id: userId
                },
                success: function(response) {
                    if (response.success) {
                        self.switchTab('conversations');
                        self.loadConversations();
                        // Auto-open the new conversation
                        setTimeout(function() {
                            self.openConversation(response.data.conversation.id);
                        }, 500);
                    } else {
                        alert('Error: ' + response.data);
                    }
                },
                error: function() {
                    alert('Failed to start conversation');
                }
            });
        }

        updateUnreadBadge() {
            $.ajax({
                url: mmChat.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mm_chat_get_unread_count',
                    nonce: mmChat.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const count = response.data.unread_count;
                        const $badge = $('#mm-chat-badge');
                        
                        if (count > 0) {
                            $badge.text(count).removeClass('hidden');
                        } else {
                            $badge.addClass('hidden');
                        }
                    }
                }
            });
        }

        startPolling() {
            const self = this;
            
            // Only poll messages when chat is actually open
            this.pollingTimer = setInterval(function() {
                if (self.isOpen) {
                    if (self.currentConversation) {
                        self.loadMessages();
                    } else {
                        self.loadConversations();
                    }
                }
            }, mmChat.pollingInterval);
        }

        startBadgePolling() {
            const self = this;
            
            // Update badge count periodically (less frequent, every 10 seconds)
            this.badgePollingTimer = setInterval(function() {
                self.updateUnreadBadge();
            }, 10000);
        }

        stopPolling() {
            if (this.pollingTimer) {
                clearInterval(this.pollingTimer);
                this.pollingTimer = null;
            }
        }

        stopBadgePolling() {
            if (this.badgePollingTimer) {
                clearInterval(this.badgePollingTimer);
                this.badgePollingTimer = null;
            }
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        window.mmChatInstance = new MMChat();
    });

})(jQuery);
