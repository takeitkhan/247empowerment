/**
 * Admin Dashboard JavaScript
 */

(function($) {
    'use strict';

    const Dashboard = {
        /**
         * Initialize dashboard
         */
        init: function() {
            this.setupTabs();
            this.loadPendingSessions();
            this.loadActiveSessions();
            this.loadOfflineQuestions();
            this.setupAutoRefresh();
        },

        /**
         * Setup tab switching
         */
        setupTabs: function() {
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                const tabId = $(this).attr('href');

                // Hide all tabs
                $('.tab-content').hide();
                $('.nav-tab').removeClass('nav-tab-active');

                // Show selected tab
                $(tabId).show();
                $(this).addClass('nav-tab-active');
            });

            // Show first tab by default
            if ($('.nav-tab-active').length === 0) {
                $('.nav-tab:first').addClass('nav-tab-active');
                $('.tab-content:first').show();
            }
        },

        /**
         * Load pending sessions
         */
        loadPendingSessions: function() {
            const self = this;

            $.ajax({
                url: mmAiChat.restUrl + '/admin/sessions?status=waiting_for_agent',
                type: 'GET',
                headers: {
                    'X-WP-Nonce': mmAiChat.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.displayPendingSessions(response.sessions);
                    }
                }
            });
        },

        /**
         * Display pending sessions
         */
        displayPendingSessions: function(sessions) {
            const tbody = $('#pending-chats-body');
            tbody.empty();

            if (sessions.length === 0) {
                tbody.append('<tr><td colspan="5">No pending chats</td></tr>');
                return;
            }

            sessions.forEach(function(session) {
                const row = $('<tr></tr>')
                    .append('<td>' + session.user_name + ' (' + session.user_email + ')</td>')
                    .append('<td>' + Dashboard.formatTime(session.initiated_at) + '</td>')
                    .append('<td>' + (session.last_user_message || 'N/A') + '</td>')
                    .append('<td><a href="' + session.page_url + '" target="_blank">View</a></td>')
                    .append('<td><button class="button accept-chat" data-session-id="' + session.session_id + '">Accept</button></td>');

                tbody.append(row);
            });

            // Setup accept handlers
            $('.accept-chat').on('click', function() {
                const sessionId = $(this).data('session-id');
                Dashboard.acceptSession(sessionId);
            });
        },

        /**
         * Load active sessions
         */
        loadActiveSessions: function() {
            const self = this;

            $.ajax({
                url: mmAiChat.restUrl + '/admin/sessions?status=agent_assigned',
                type: 'GET',
                headers: {
                    'X-WP-Nonce': mmAiChat.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.displayActiveSessions(response.sessions);
                    }
                }
            });
        },

        /**
         * Display active sessions
         */
        displayActiveSessions: function(sessions) {
            const tbody = $('#active-chats-body');
            tbody.empty();

            if (sessions.length === 0) {
                tbody.append('<tr><td colspan="5">No active chats</td></tr>');
                return;
            }

            sessions.forEach(function(session) {
                const row = $('<tr></tr>')
                    .append('<td>' + session.user_name + '</td>')
                    .append('<td>' + (session.assigned_agent_name || 'Unassigned') + '</td>')
                    .append('<td>' + Dashboard.formatTime(session.initiated_at) + '</td>')
                    .append('<td><a href="#" data-session-id="' + session.session_id + '" class="view-chat">View Chat</a></td>')
                    .append('<td><button class="button button-small" data-session-id="' + session.session_id + '">Close</button></td>');

                tbody.append(row);
            });
        },

        /**
         * Load offline questions
         */
        loadOfflineQuestions: function() {
            const self = this;

            $.ajax({
                url: mmAiChat.restUrl + '/admin/offline-questions?status=pending',
                type: 'GET',
                headers: {
                    'X-WP-Nonce': mmAiChat.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.displayOfflineQuestions(response.questions);
                    }
                }
            });
        },

        /**
         * Display offline questions
         */
        displayOfflineQuestions: function(questions) {
            const tbody = $('#offline-questions-body');
            tbody.empty();

            if (questions.length === 0) {
                tbody.append('<tr><td colspan="5">No pending offline questions</td></tr>');
                return;
            }

            questions.forEach(function(question) {
                const row = $('<tr></tr>')
                    .append('<td>' + question.user_name + '</td>')
                    .append('<td>' + question.question.substring(0, 50) + '...</td>')
                    .append('<td>' + Dashboard.formatTime(question.created_at) + '</td>')
                    .append('<td><span class="priority-' + question.priority + '">' + question.priority.toUpperCase() + '</span></td>')
                    .append('<td><button class="button answer-question" data-question-id="' + question.question_id + '">Answer</button></td>');

                tbody.append(row);
            });

            // Setup answer handlers
            $('.answer-question').on('click', function() {
                const questionId = $(this).data('question-id');
                Dashboard.answerQuestion(questionId);
            });
        },

        /**
         * Accept session
         */
        acceptSession: function(sessionId) {
            $.ajax({
                url: mmAiChat.restUrl + '/admin/sessions/' + sessionId + '/accept',
                type: 'POST',
                headers: {
                    'X-WP-Nonce': mmAiChat.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Session accepted');
                        Dashboard.loadPendingSessions();
                        Dashboard.loadActiveSessions();
                    }
                }
            });
        },

        /**
         * Answer question
         */
        answerQuestion: function(questionId) {
            const answer = prompt('Enter your answer:');
            if (!answer) return;

            $.ajax({
                url: mmAiChat.restUrl + '/admin/offline-questions/' + questionId + '/answer',
                type: 'POST',
                dataType: 'json',
                headers: {
                    'X-WP-Nonce': mmAiChat.nonce,
                    'Content-Type': 'application/json'
                },
                data: JSON.stringify({ answer: answer }),
                success: function(response) {
                    if (response.success) {
                        alert('Question answered successfully');
                        Dashboard.loadOfflineQuestions();
                    }
                }
            });
        },

        /**
         * Format time
         */
        formatTime: function(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleString();
        },

        /**
         * Setup auto-refresh
         */
        setupAutoRefresh: function() {
            setInterval(function() {
                Dashboard.loadPendingSessions();
                Dashboard.loadActiveSessions();
                Dashboard.loadOfflineQuestions();
            }, 10000); // Refresh every 10 seconds
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        Dashboard.init();
    });

    // Make it globally accessible
    window.Dashboard = Dashboard;

})(jQuery);
