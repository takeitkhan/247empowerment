/**
 * MM AI Chat Widget JavaScript
 */
(function($) {
    'use strict';

    const MMChatWidget = {
        conversationId: null,
        sessionId: null,
        mode: 'ai',
        isOpen: false,
        isWaitingForResponse: false,
        _initialized: false,
        _initializing: false,
        _agentPollTimer: null,
        _seenMessageIds: {},
        _hasConversation: false,
        _escalated: false,
        _agentNoticeShown: false,

        settings: function() {
            const d = typeof mmAiChatData !== 'undefined' ? mmAiChatData : {};
            const m = typeof mmChat !== 'undefined' ? mmChat : {};
            return {
                escalationEnabled: this.isTruthy(d.escalationEnabled !== undefined ? d.escalationEnabled : m.aiEscalationEnabled),
                offlineEnabled: this.isTruthy(d.offlineEnabled !== undefined ? d.offlineEnabled : m.aiOfflineEnabled),
                userEmail: d.userEmail || m.userEmail || '',
                userName: d.userName || m.userName || ''
            };
        },

        isTruthy: function(val) {
            return val === true || val === 1 || val === '1';
        },

        apiConfig: function() {
            if (typeof mmAiChatData !== 'undefined' && mmAiChatData.restUrl) {
                return {
                    restUrl: mmAiChatData.restUrl.replace(/\/$/, ''),
                    nonce: mmAiChatData.nonce || (mmAiChatData.chatNonce || '')
                };
            }
            if (typeof mmChat !== 'undefined' && mmChat.aiRestUrl) {
                return {
                    restUrl: mmChat.aiRestUrl.replace(/\/$/, ''),
                    nonce: mmChat.aiRestNonce || ''
                };
            }
            return { restUrl: '', nonce: '' };
        },

        clearSession: function() {
            this.conversationId = null;
            this.sessionId = null;
            this.mode = 'ai';
            this._escalated = false;
            this._agentNoticeShown = false;
            localStorage.removeItem('mm_ai_chat_session');
            this.stopAgentPolling();
            this.updateModeBar();
        },

        init: function() {
            if (this._initialized) {
                return;
            }
            this._initialized = true;
            this.setupEventListeners();
            this.restoreSession();
            this.setupUnloadHandler();
        },

        payload: function(response) {
            if (!response || typeof response !== 'object') {
                return {};
            }
            return response.data && typeof response.data === 'object' ? response.data : response;
        },

        setupEventListeners: function() {
            const self = this;

            $(document).on('click', '#mm-ai-chat-toggle', function() {
                self.toggleWidget();
            });

            $(document).on('click', '#mm-ai-chat-close', function(e) {
                e.preventDefault();
                self.closeWidget();
            });

            $(document).on('click', '#mm-ai-chat-send', function(e) {
                e.preventDefault();
                self.sendMessage();
            });

            $(document).on('keypress', '#mm-ai-chat-input', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });

            $(document).on('click', '#mm-ai-chat-offline-btn', function(e) {
                e.preventDefault();
                self.showOfflineForm();
            });

            $(document).on('click', '#mm-ai-chat-offline-cancel', function(e) {
                e.preventDefault();
                $('#mm-ai-chat-offline-form').hide();
                $('.mm-ai-chat-escalation-actions').show();
            });

            $(document).on('click', '#mm-ai-chat-offline-submit', function(e) {
                e.preventDefault();
                self.submitOfflineQuestion();
            });

            $(document).on('click', '#mm-ai-chat-agent-btn', function(e) {
                e.preventDefault();
                self.escalateToAgent();
            });

            $(document).on('click', '#mm-ai-chat-back-ai', function(e) {
                e.preventDefault();
                self.startNewAiChat();
            });
        },

        restoreSession: function() {
            const stored = localStorage.getItem('mm_ai_chat_session');
            if (!stored) {
                return;
            }
            try {
                const session = JSON.parse(stored);
                this.conversationId = session.conversationId || null;
                this.sessionId = session.sessionId || null;
                this.mode = session.mode || 'ai';
                if (this.mode === 'agent') {
                    this.startAgentPolling();
                }
                this.updateModeBar();
            } catch (e) {
                console.error('Error restoring session:', e);
            }
        },

        setupUnloadHandler: function() {
            const self = this;
            $(window).on('beforeunload', function() {
                if (self.conversationId) {
                    localStorage.setItem('mm_ai_chat_session', JSON.stringify({
                        conversationId: self.conversationId,
                        sessionId: self.sessionId,
                        mode: self.mode
                    }));
                }
            });
        },

        toggleWidget: function() {
            if (this.isOpen) {
                this.closeWidget();
            } else {
                this.openWidget();
            }
        },

        openWidget: function() {
            $('#mm-ai-chat-widget').slideDown(300);
            $('#mm-ai-chat-toggle').hide();
            this.isOpen = true;

            if (!this.conversationId) {
                $('#mm-ai-chat-messages').empty();
                this.initializeChat();
            } else {
                this.loadMessages();
                if (this.mode === 'agent') {
                    this.startAgentPolling();
                }
                this.updateModeBar();
            }
        },

        closeWidget: function() {
            $('#mm-ai-chat-widget').slideUp(300);
            $('#mm-ai-chat-toggle').hide();
            this.isOpen = false;
            this.stopAgentPolling();
        },

        initializeChat: function() {
            const self = this;
            const api = this.apiConfig();

            if (!api.restUrl) {
                this.displaySystemMessage('AI chat is not configured on this page.');
                return;
            }
            if (this._initializing) {
                return;
            }
            this._initializing = true;

            $.ajax({
                url: api.restUrl + '/chat/initiate',
                type: 'POST',
                dataType: 'json',
                processData: false,
                contentType: 'application/json',
                headers: {
                    'X-WP-Nonce': api.nonce
                },
                data: JSON.stringify({
                    page_context: {
                        page_url: window.location.href,
                        page_title: document.title,
                        referrer: document.referrer
                    }
                }),
                success: function(response) {
                    self._initializing = false;
                    const data = self.payload(response);
                    if (data.success && data.conversation_id) {
                        self.conversationId = data.conversation_id;
                        self.sessionId = data.session_id;

                        localStorage.setItem('mm_ai_chat_session', JSON.stringify({
                            conversationId: self.conversationId,
                            sessionId: self.sessionId,
                            mode: 'ai'
                        }));

                        $('#mm-ai-chat-messages').empty();
                        if (data.message && data.message.content) {
                            self.displayMessage(data.message);
                        } else {
                            self.displaySystemMessage('Welcome! How can I help you today?');
                        }
                        if (data.api_warning) {
                            self.displaySystemMessage('Note: ' + data.api_warning);
                        }
                        self.updateEscalationVisibility();
                    } else {
                        self.clearSession();
                        self.displaySystemMessage(data.error || data.message || 'Unable to initialize chat.');
                    }
                },
                error: function(xhr) {
                    self._initializing = false;
                    self.clearSession();
                    console.error('Chat initialization error:', xhr.status, xhr.responseText);
                    var msg = 'Error initializing chat.';
                    if (xhr.status === 403) {
                        msg = 'Session expired — please refresh the page and try again.';
                    } else if (xhr.status === 404) {
                        msg = 'AI chat API not found. Confirm MM AI Chat plugin is active.';
                    }
                    self.displaySystemMessage(msg);
                }
            });
        },

        loadMessages: function() {
            const self = this;
            const api = this.apiConfig();

            $.ajax({
                url: api.restUrl + '/chat/messages/' + this.conversationId,
                type: 'GET',
                dataType: 'json',
                headers: {
                    'X-WP-Nonce': api.nonce
                },
                success: function(response) {
                    const data = self.payload(response);
                    if (data.success) {
                        const messages = data.messages || [];
                        $('#mm-ai-chat-messages').empty();
                        self._seenMessageIds = {};
                        messages.forEach(function(msg) {
                            self.displayMessage(msg, true);
                        });
                        self._hasConversation = messages.some(function(m) {
                            return (m.sender_type || m.type) === 'user';
                        });
                        self.updateEscalationVisibility();
                        self.scrollToBottom();
                    }
                },
                error: function(xhr) {
                    console.error('Error loading messages:', xhr.status);
                    if (xhr.status === 404 || xhr.status === 403) {
                        self.clearSession();
                        $('#mm-ai-chat-messages').empty();
                        self.initializeChat();
                    }
                }
            });
        },

        sendMessage: function() {
            const message = $('#mm-ai-chat-input').val().trim();
            const api = this.apiConfig();

            if (!message || this.isWaitingForResponse) {
                return;
            }
            if (!this.conversationId) {
                if (this._initializing) {
                    return;
                }
                this.initializeChat();
                return;
            }
            if (this.mode === 'agent') {
                if (!this._agentNoticeShown) {
                    this._agentNoticeShown = true;
                    this.displaySystemMessage('A portal agent is handling this chat. Tap "Back to AI Chat" below to talk with AI again.');
                }
                return;
            }
            if (this._escalated) {
                return;
            }

            this._hasConversation = true;
            this.displayMessage({
                sender_type: 'user',
                content: message,
                created_at: new Date().toISOString()
            });

            this._hasConversation = true;
            this.updateEscalationVisibility();

            $('#mm-ai-chat-input').val('');
            $('#mm-ai-chat-send').prop('disabled', true);
            this.isWaitingForResponse = true;

            const self = this;
            this.displayTypingIndicator();

            $.ajax({
                url: api.restUrl + '/chat/message',
                type: 'POST',
                dataType: 'json',
                processData: false,
                contentType: 'application/json',
                headers: {
                    'X-WP-Nonce': api.nonce
                },
                data: JSON.stringify({
                    conversation_id: this.conversationId,
                    message: message
                }),
                success: function(response) {
                    self.removeTypingIndicator();
                    const data = self.payload(response);

                    if (data.success && data.ai_response) {
                        self.displayMessage({
                            sender_type: 'ai',
                            content: data.ai_response,
                            created_at: new Date().toISOString()
                        });
                        if (data.kb_sources && data.kb_sources.length) {
                            self.displayKBSources(data.kb_sources);
                        }
                    } else {
                        self.displaySystemMessage(data.error || data.message || 'Unable to process message');
                    }

                    self.updateEscalationVisibility();
                    $('#mm-ai-chat-send').prop('disabled', false);
                    self.isWaitingForResponse = false;
                    self.scrollToBottom();
                },
                error: function(xhr) {
                    self.removeTypingIndicator();
                    console.error('Send message error:', xhr.status, xhr.responseText);
                    self.displaySystemMessage('Error sending message. Please try again.');
                    self.updateEscalationVisibility();
                    $('#mm-ai-chat-send').prop('disabled', false);
                    self.isWaitingForResponse = false;
                }
            });
        },

        displayMessage: function(message, skipEscalation) {
            const msgType = message.type || message.sender_type || 'ai';
            const content = message.content || '';
            const time = message.timestamp || message.created_at;
            const msgId = message.id || message.message_id || ('local_' + Date.now() + '_' + Math.random());
            const messagesContainer = $('#mm-ai-chat-messages');

            if (this._seenMessageIds[msgId]) {
                return;
            }
            this._seenMessageIds[msgId] = true;

            const messageDiv = $('<div></div>')
                .addClass('mm-ai-chat-message')
                .addClass('msg-' + msgType)
                .addClass(msgType)
                .attr('data-msg-id', msgId);

            const contentDiv = $('<div></div>')
                .addClass('mm-ai-chat-message-content')
                .html(this.sanitizeHtml(content));

            messageDiv.append(contentDiv);

            if (time) {
                messageDiv.append(
                    $('<div></div>')
                        .addClass('mm-ai-chat-message-time')
                        .text(this.formatTime(time))
                );
            }

            messagesContainer.append(messageDiv);
            if (!skipEscalation && msgType === 'user') {
                this._hasConversation = true;
            }
            this.scrollToBottom();
        },

        displaySystemMessage: function(message) {
            this.displayMessage({
                sender_type: 'system',
                content: message,
                created_at: new Date().toISOString()
            });
        },

        displayKBSources: function(sources) {
            if (!sources || !sources.length) {
                return;
            }

            const sourcesDiv = $('<div></div>')
                .addClass('mm-ai-chat-kb-sources')
                .append('<small>Related topics:</small>');

            const sourcesList = $('<ul></ul>');
            sources.forEach(function(source) {
                sourcesList.append($('<li></li>').text(source.question || source.title || ''));
            });

            sourcesDiv.append(sourcesList);
            $('#mm-ai-chat-messages').append(sourcesDiv);
            this.scrollToBottom();
        },

        displayTypingIndicator: function() {
            $('#mm-ai-chat-messages').append(
                $('<div></div>')
                    .addClass('mm-ai-chat-message msg-ai mm-ai-chat-typing')
                    .append(
                        $('<div></div>')
                            .addClass('mm-ai-chat-message-content')
                            .text('AI is typing…')
                    )
            );
            this.scrollToBottom();
        },

        removeTypingIndicator: function() {
            $('.mm-ai-chat-typing').remove();
        },

        scrollToBottom: function() {
            const container = $('#mm-ai-chat-messages');
            if (container.length && container[0]) {
                container.scrollTop(container[0].scrollHeight);
            }
        },

        formatTime: function(dateStr) {
            try {
                return new Date(dateStr).toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });
            } catch (e) {
                return '';
            }
        },

        sanitizeHtml: function(html) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(html || '').replace(/[&<>"']/g, function(m) {
                return map[m];
            });
        },

        updateEscalationVisibility: function() {
            const cfg = this.settings();
            const $panel = $('#mm-ai-chat-escalation');
            if (!$panel.length) {
                return;
            }
            if (!this.conversationId || this._escalated || this.mode === 'agent' || !this._hasConversation) {
                $panel.hide();
                return;
            }

            const showOffline = cfg.offlineEnabled;
            const showAgent = cfg.escalationEnabled;
            if (!showOffline && !showAgent) {
                $panel.hide();
                return;
            }

            $('#mm-ai-chat-offline-btn').toggle(showOffline);
            $('#mm-ai-chat-agent-btn').toggle(showAgent);
            $panel.show();
            $('#mm-ai-chat-offline-form').hide();
            $('.mm-ai-chat-escalation-actions').show();
        },

        getLastUserMessage: function() {
            let last = '';
            $('#mm-ai-chat-messages .msg-user .mm-ai-chat-message-content').each(function() {
                last = $(this).text();
            });
            return last.trim();
        },

        showOfflineForm: function() {
            const question = this.getLastUserMessage();
            $('#mm-ai-chat-offline-question').val(question);
            $('.mm-ai-chat-escalation-actions').hide();
            $('#mm-ai-chat-offline-form').show();
        },

        submitOfflineQuestion: function() {
            const self = this;
            const api = this.apiConfig();
            const cfg = this.settings();
            const question = $('#mm-ai-chat-offline-question').val().trim();

            if (!question) {
                alert('Please describe your question.');
                return;
            }
            if (!this.conversationId) {
                return;
            }

            $('#mm-ai-chat-offline-submit').prop('disabled', true);

            $.ajax({
                url: api.restUrl + '/chat/escalate/offline',
                type: 'POST',
                dataType: 'json',
                processData: false,
                contentType: 'application/json',
                headers: { 'X-WP-Nonce': api.nonce },
                data: JSON.stringify({
                    conversation_id: this.conversationId,
                    question: question,
                    email: cfg.userEmail || ''
                }),
                success: function(response) {
                    const data = self.payload(response);
                    if (data.success) {
                        self._escalated = true;
                        self.hideEscalationPanel();
                        self.displaySystemMessage(data.message || 'Your question was submitted. Our team will respond within 24 hours.');
                        self.updateModeBar();
                    } else {
                        self.displaySystemMessage(data.error || 'Could not submit offline question.');
                    }
                },
                error: function(xhr) {
                    console.error('Offline escalate error:', xhr.status, xhr.responseText);
                    self.displaySystemMessage('Error submitting offline question. Please try again.');
                },
                complete: function() {
                    $('#mm-ai-chat-offline-submit').prop('disabled', false);
                    $('#mm-ai-chat-offline-form').hide();
                }
            });
        },

        escalateToAgent: function() {
            const self = this;
            const api = this.apiConfig();

            if (!this.conversationId || this._escalated) {
                return;
            }

            const reason = this.getLastUserMessage() || 'User requested portal agent';
            $('#mm-ai-chat-agent-btn').prop('disabled', true);
            this.displaySystemMessage('Connecting you to a portal agent…');

            $.ajax({
                url: api.restUrl + '/chat/escalate/agent',
                type: 'POST',
                dataType: 'json',
                processData: false,
                contentType: 'application/json',
                headers: { 'X-WP-Nonce': api.nonce },
                data: JSON.stringify({
                    conversation_id: this.conversationId,
                    reason: reason
                }),
                success: function(response) {
                    const data = self.payload(response);
                    if (data.success) {
                        self.mode = 'agent';
                        self._escalated = true;
                        self.hideEscalationPanel();
                        self.persistSession();

                        let msg = data.message || 'An agent will be with you shortly.';
                        if (data.agent_name) {
                            msg = 'You are now connected with ' + data.agent_name + '.';
                            $('.mm-ai-chat-header h3').text('Portal Agent');
                        } else if (data.status === 'waiting_for_agent') {
                            $('.mm-ai-chat-header h3').text('Waiting for agent');
                        }
                        self.displaySystemMessage(msg);
                        self.startAgentPolling();
                        self.updateModeBar();
                    } else {
                        self.displaySystemMessage(data.error || 'Could not connect to an agent.');
                        $('#mm-ai-chat-agent-btn').prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    console.error('Agent escalate error:', xhr.status, xhr.responseText);
                    self.displaySystemMessage('Error connecting to agent. Please try again.');
                    $('#mm-ai-chat-agent-btn').prop('disabled', false);
                }
            });
        },

        hideEscalationPanel: function() {
            $('#mm-ai-chat-escalation').hide();
        },

        startNewAiChat: function() {
            this.stopAgentPolling();
            this.conversationId = null;
            this.sessionId = null;
            this.mode = 'ai';
            this._escalated = false;
            this._hasConversation = false;
            this._agentNoticeShown = false;
            this._seenMessageIds = {};
            this._initializing = false;
            localStorage.removeItem('mm_ai_chat_session');
            $('.mm-ai-chat-header h3').text('Chat with us');
            $('#mm-ai-chat-messages').empty();
            this.hideEscalationPanel();
            this.updateModeBar();
            this.initializeChat();
        },

        updateModeBar: function() {
            let $bar = $('#mm-ai-chat-mode-bar');
            if (!$bar.length) {
                return;
            }

            const $input = $('#mm-ai-chat-input');
            const $send = $('#mm-ai-chat-send');

            if (this.mode === 'agent') {
                $bar.html(
                    '<p class="mm-ai-chat-mode-text">You are connected to a <strong>portal agent</strong>. Wait here for their reply.</p>' +
                    '<button type="button" id="mm-ai-chat-back-ai" class="mm-ai-chat-esc-btn mm-ai-chat-back-ai">← Back to AI Chat</button>'
                ).show();
                $input.prop('disabled', true).attr('placeholder', 'Waiting for agent reply…');
                $send.prop('disabled', true);
                return;
            }

            if (this._escalated) {
                $bar.html(
                    '<p class="mm-ai-chat-mode-text">Your question was sent for an <strong>offline answer</strong>.</p>' +
                    '<button type="button" id="mm-ai-chat-back-ai" class="mm-ai-chat-esc-btn mm-ai-chat-back-ai">← Start new AI Chat</button>'
                ).show();
                $input.prop('disabled', true).attr('placeholder', 'Offline request submitted');
                $send.prop('disabled', true);
                return;
            }

            $bar.hide().empty();
            $input.prop('disabled', false).attr('placeholder', 'Type your message...');
            if (!this.isWaitingForResponse) {
                $send.prop('disabled', false);
            }
        },

        persistSession: function() {
            if (!this.conversationId) {
                return;
            }
            localStorage.setItem('mm_ai_chat_session', JSON.stringify({
                conversationId: this.conversationId,
                sessionId: this.sessionId,
                mode: this.mode
            }));
        },

        startAgentPolling: function() {
            const self = this;
            this.stopAgentPolling();
            this.pollAgentMessages();
            this._agentPollTimer = setInterval(function() {
                if (self.isOpen && self.mode === 'agent') {
                    self.pollAgentMessages();
                }
            }, 5000);
        },

        stopAgentPolling: function() {
            if (this._agentPollTimer) {
                clearInterval(this._agentPollTimer);
                this._agentPollTimer = null;
            }
        },

        pollAgentMessages: function() {
            const self = this;
            const api = this.apiConfig();
            if (!this.conversationId) {
                return;
            }

            $.ajax({
                url: api.restUrl + '/chat/messages/' + this.conversationId,
                type: 'GET',
                dataType: 'json',
                headers: { 'X-WP-Nonce': api.nonce },
                success: function(response) {
                    const data = self.payload(response);
                    if (!data.success || !data.messages) {
                        return;
                    }
                    let hasNewAgent = false;
                    data.messages.forEach(function(msg) {
                        const type = msg.sender_type || msg.type;
                        if (type === 'agent') {
                            const before = Object.keys(self._seenMessageIds).length;
                            self.displayMessage(msg);
                            if (Object.keys(self._seenMessageIds).length > before) {
                                hasNewAgent = true;
                            }
                        }
                    });
                    if (hasNewAgent) {
                        self.scrollToBottom();
                    }
                }
            });
        }
    };

    $(document).ready(function() {
        MMChatWidget.init();
    });

    window.MMChatWidget = MMChatWidget;

})(jQuery);
