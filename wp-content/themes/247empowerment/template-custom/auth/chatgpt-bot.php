<?php

/**
 * Template Name: ChatGPT Bot
 */
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}
get_header_based_on_login();

$current_user = wp_get_current_user();
$user_id = get_current_user_id();
?>

<div class="pt-4 pb-4 container profile-page">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3">
            <?php
            $profile = (new UserProfileData($current_user->ID))->getProfile();
            get_template_part('template-custom/auth/common-parts/editprofilemenu', null, ['profile' => $profile]); 
            ?>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Header Section -->
            <div class="bg-white mb-4 p-4 border-bottom rounded">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="mb-2">
                            <i class="bi bi-robot"></i>
                            AI Chat Assistant
                        </h3>
                        <p class="mb-0 text-muted">
                            Chat with our AI assistant powered by OpenRouter. Ask questions, get answers, and explore AI capabilities.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Chat Container -->
            <div class="bg-white border rounded chat-wrapper">
                <!-- Chat Messages Area -->
                <div id="chat-box" class="chat-messages">
                    <div class="chat-welcome">
                        <div class="welcome-icon">
                            <i class="bi bi-robot"></i>
                        </div>
                        <h5>Welcome to AI Chat</h5>
                        <p class="text-muted">Start a conversation by typing a message below</p>
                    </div>
                </div>

                <!-- Chat Input Area -->
                <div class="chat-footer">
                    <div class="input-group">
                        <input 
                            type="text" 
                            id="user-input" 
                            class="form-control chat-input" 
                            placeholder="Ask me anything..." 
                            autocomplete="off"
                        />
                        <button class="btn btn-send" id="send-btn" type="button" title="Send message">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                    <small class="d-block mt-2 text-muted text-center">
                        <i class="bi bi-info-circle"></i>
                        Press Enter or click Send to submit your message
                    </small>
                </div>
            </div>
            <!-- Info Section -->
            <div class="bg-light mt-4 p-4 rounded chat-info">
                <h6 class="mb-3">
                    <i class="bi bi-lightbulb"></i>
                    Tips for Better Responses
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi-chat-left-text bi"></i>
                            </div>
                            <div>
                                <strong>Be Specific</strong>
                                <p class="mb-0 text-muted small">The more details you provide, the better answers you'll get</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="bi bi-question-circle"></i>
                            </div>
                            <div>
                                <strong>Ask Follow-ups</strong>
                                <p class="mb-0 text-muted small">Don't hesitate to ask clarifying questions</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php
            // Render the content of the current Page (the one this "ChatGPT Bot"
            // template is assigned to). Edit that page in WP Admin to update.
            $current_page = get_queried_object();
            if ($current_page instanceof WP_Post && !empty(trim($current_page->post_content))) :
                $chatbot_page_content = apply_filters('the_content', $current_page->post_content);
                ?>
                <div class="bg-white mt-4 p-4 border rounded chatbot-page-content">
                    <article class="chatbot-info-block">
                        <?php echo $chatbot_page_content; ?>
                    </article>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<style>
/* Chat Wrapper */
.chat-wrapper {
    display: flex;
    flex-direction: column;
    min-height: 600px;
    max-height: 700px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

/* Chat Messages Area */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 2rem;
    background: #fff;
    display: flex;
    flex-direction: column;
}

/* Welcome Message */
.chat-welcome {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    height: 100%;
    color: #6c757d;
}

.welcome-icon {
    font-size: 3rem;
    color: #0a66c2;
    margin-bottom: 1rem;
    opacity: 0.7;
}

.welcome-icon i {
    display: inline-block;
}

.chat-welcome h5 {
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.5rem;
}

/* Chat Messages */
.chat-msg {
    margin-bottom: 1rem;
    display: flex;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.chat-msg.user {
    justify-content: flex-end;
}

.chat-msg.bot {
    justify-content: flex-start;
}

.chat-msg-bubble {
    max-width: 75%;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    word-wrap: break-word;
    line-height: 1.5;
    font-size: 0.95rem;
}

.chat-msg.user .chat-msg-bubble {
    background: linear-gradient(135deg, #0a66c2 0%, #005b96 100%);
    color: white;
    border-bottom-right-radius: 4px;
}

.chat-msg.bot .chat-msg-bubble {
    background: #f1f3f5;
    color: #212529;
    border-bottom-left-radius: 4px;
    border: 1px solid #e9ecef;
}

.chat-msg strong {
    display: block;
    margin-bottom: 0.25rem;
    font-size: 0.85rem;
    opacity: 0.8;
}

.chat-msg.user strong {
    text-align: right;
}

/* Chat Footer */
.chat-footer {
    padding: 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
}

.input-group {
    display: flex;
    gap: 0.5rem;
}

.chat-input {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.chat-input:focus {
    border-color: #0a66c2;
    box-shadow: 0 0 0 0.2rem rgba(10, 102, 194, 0.15);
    outline: none;
}

.btn-send {
    background: linear-gradient(135deg, #0a66c2 0%, #005b96 100%);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    min-width: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    cursor: pointer;
    font-weight: 500;
}

.btn-send:hover {
    background: linear-gradient(135deg, #005b96 0%, #003d60 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(10, 102, 194, 0.3);
}

.btn-send:active {
    transform: translateY(0);
}

.btn-send i {
    font-size: 1rem;
}

/* Info Section */
.chat-info {
    border: 1px solid #e9ecef;
}

.info-item {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.info-icon {
    flex-shrink: 0;
    width: 40px;
    height: 40px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0a66c2;
    font-size: 1.2rem;
}

.info-item strong {
    display: block;
    font-size: 0.95rem;
    color: #212529;
}

.info-item p {
    margin: 0;
    color: #6c757d;
}

/* Scrollbar Styling */
.chat-messages::-webkit-scrollbar {
    width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
    background: transparent;
}

.chat-messages::-webkit-scrollbar-thumb {
    background: #dee2e6;
    border-radius: 3px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
    background: #adb5bd;
}

/* Responsive Design */
@media (max-width: 768px) {
    .chat-wrapper {
        min-height: 500px;
        max-height: 600px;
    }

    .chat-msg-bubble {
        max-width: 90%;
    }

    .chat-messages {
        padding: 1.5rem 1rem;
    }

    .chat-footer {
        padding: 1rem;
    }

    .input-group {
        gap: 0.25rem;
    }

    .chat-input {
        font-size: 0.9rem;
        padding: 0.65rem 0.75rem;
    }

    .btn-send {
        padding: 0.65rem 1rem;
        min-width: 45px;
    }
}
</style>

<script>
(function() {
    'use strict';

    // Chat configuration
    const chatConfig = {
        ajaxUrl: "<?php echo admin_url('admin-ajax.php'); ?>",
        action: 'chatgpt_ajax_handler'
    };

    // Initialize chat when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initializeChat();
    });

    function initializeChat() {
        const sendBtn = document.getElementById('send-btn');
        const userInput = document.getElementById('user-input');
        const chatBox = document.getElementById('chat-box');

        // Send button click
        sendBtn.addEventListener('click', () => sendMessage());

        // Enter key press
        userInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Clear welcome message on first message
        let isFirstMessage = true;
        userInput.addEventListener('input', function() {
            if (isFirstMessage && this.value.trim()) {
                const welcome = chatBox.querySelector('.chat-welcome');
                if (welcome) {
                    welcome.remove();
                    isFirstMessage = false;
                }
            }
        });
    }

    async function sendMessage() {
        const input = document.getElementById('user-input');
        const msg = input.value.trim();

        if (!msg) return;

        const chatBox = document.getElementById('chat-box');

        // Remove welcome message if still present
        const welcome = chatBox.querySelector('.chat-welcome');
        if (welcome) welcome.remove();

        // Append user message
        appendMessage(msg, 'user');
        input.value = '';
        input.focus();

        // Show loading indicator
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'chat-msg bot';
        loadingDiv.innerHTML = `
            <div class="chat-msg-bubble">
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `;
        chatBox.appendChild(loadingDiv);
        chatBox.scrollTop = chatBox.scrollHeight;

        try {
            const res = await fetch(chatConfig.ajaxUrl + '?action=' + chatConfig.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: msg })
            });

            const data = await res.json();

            // Remove loading indicator
            loadingDiv.remove();

            if (data.reply) {
                appendMessage(data.reply, 'bot');
            } else {
                appendMessage('⚠️ Sorry, I couldn\'t process that. Please try again.', 'bot');
            }
        } catch (error) {
            console.error('Error:', error);
            loadingDiv.remove();
            appendMessage('⚠️ Connection error. Please try again.', 'bot');
        }
    }

    function appendMessage(text, sender) {
        const chatBox = document.getElementById('chat-box');
        const div = document.createElement('div');
        div.className = `chat-msg ${sender}`;

        const bubble = document.createElement('div');
        bubble.className = 'chat-msg-bubble';
        bubble.textContent = text;

        div.appendChild(bubble);
        chatBox.appendChild(div);
        chatBox.scrollTop = chatBox.scrollHeight;
    }
})();
</script>

<?php get_footer_based_on_login(); ?>