<?php
/**
 * Chat AJAX Handlers
 * Handles all AJAX requests for chat functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Chat_AJAX
{
    /**
     * Initialize AJAX handlers
     */
    public static function init()
    {
        // Logged-in users
        add_action('wp_ajax_mm_chat_get_partners', [__CLASS__, 'get_partners']);
        add_action('wp_ajax_mm_chat_get_conversations', [__CLASS__, 'get_conversations']);
        add_action('wp_ajax_mm_chat_get_messages', [__CLASS__, 'get_messages']);
        add_action('wp_ajax_mm_chat_send_message', [__CLASS__, 'send_message']);
        add_action('wp_ajax_mm_chat_mark_read', [__CLASS__, 'mark_read']);
        add_action('wp_ajax_mm_chat_get_unread_count', [__CLASS__, 'get_unread_count']);
        add_action('wp_ajax_mm_chat_start_conversation', [__CLASS__, 'start_conversation']);
    }

    /**
     * Get chat partners (referral connections)
     */
    public static function get_partners()
    {
        self::verify_nonce_and_login();

        $current_user_id = get_current_user_id();
        $limit = intval($_POST['limit'] ?? 20);

        $partners = MM_Chat_Manager::get_chat_partners($current_user_id, $limit);

        // Format partners
        $formatted_partners = [];
        foreach ($partners as $partner) {
            if (!$partner || !($partner instanceof WP_User)) {
                continue;
            }

            // Skip current user
            if ($partner->ID === $current_user_id) {
                continue;
            }

            $formatted_partners[] = [
                'id' => $partner->ID,
                'name' => $partner->display_name,
                'username' => $partner->user_login,
                'avatar' => get_user_meta($partner->ID, 'profile_photo', true),
                'profile_url' => home_url('/' . $partner->user_login),
            ];
        }

        wp_send_json_success([
            'partners' => $formatted_partners,
            'count' => count($formatted_partners),
        ]);
    }

    /**
     * Get user's conversations
     */
    public static function get_conversations()
    {
        self::verify_nonce_and_login();

        $current_user_id = get_current_user_id();
        $limit = intval($_POST['limit'] ?? 20);
        $offset = intval($_POST['offset'] ?? 0);

        $conversations = MM_Chat_Database::get_user_conversations($current_user_id, $limit, $offset);

        // Format conversations
        $formatted_conversations = [];
        foreach ($conversations as $conv) {
            $other_user = get_user_by('id', $conv->other_user_id);
            
            if (!$other_user) {
                continue;
            }

            $last_msg = $conv->last_message;

            $formatted_conversations[] = [
                'id' => $conv->id,
                'other_user' => [
                    'id' => $other_user->ID,
                    'name' => $other_user->display_name,
                    'username' => $other_user->user_login,
                    'avatar' => get_user_meta($other_user->ID, 'profile_photo', true),
                ],
                'last_message' => $last_msg ? [
                    'text' => substr($last_msg->message, 0, 100),
                    'sender_id' => $last_msg->sender_id,
                    'created_at' => $last_msg->created_at,
                ] : null,
                'unread_count' => $conv->unread_count,
                'updated_at' => $conv->updated_at,
            ];
        }

        wp_send_json_success([
            'conversations' => $formatted_conversations,
            'count' => count($formatted_conversations),
        ]);
    }

    /**
     * Get messages from a conversation
     */
    public static function get_messages()
    {
        self::verify_nonce_and_login();

        $current_user_id = get_current_user_id();
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        $limit = intval($_POST['limit'] ?? 50);
        $offset = intval($_POST['offset'] ?? 0);

        if (!$conversation_id) {
            wp_send_json_error('Invalid conversation ID');
        }

        $messages = MM_Message_Handler::get_messages($conversation_id, $current_user_id, $limit, $offset);

        if ($messages === null) {
            wp_send_json_error('Not authorized');
        }

        // Mark as read
        MM_Message_Handler::mark_conversation_as_read($conversation_id, $current_user_id);

        wp_send_json_success([
            'messages' => $messages,
            'count' => count($messages),
        ]);
    }

    /**
     * Send a message
     */
    public static function send_message()
    {
        self::verify_nonce_and_login();

        $current_user_id = get_current_user_id();
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        $message = $_POST['message'] ?? '';

        if (!$conversation_id || empty($message)) {
            wp_send_json_error('Invalid parameters');
        }

        $result = MM_Message_Handler::send_message($conversation_id, $current_user_id, $message);

        if (!$result['success']) {
            wp_send_json_error($result['error']);
        }

        wp_send_json_success($result);
    }

    /**
     * Mark message as read
     */
    public static function mark_read()
    {
        self::verify_nonce_and_login();

        $current_user_id = get_current_user_id();
        $message_id = intval($_POST['message_id'] ?? 0);

        if (!$message_id) {
            wp_send_json_error('Invalid message ID');
        }

        $result = MM_Message_Handler::mark_as_read($message_id, $current_user_id);

        if (!$result) {
            wp_send_json_error('Failed to mark as read');
        }

        wp_send_json_success(['marked' => true]);
    }

    /**
     * Get unread count
     */
    public static function get_unread_count()
    {
        self::verify_nonce_and_login();

        $current_user_id = get_current_user_id();
        $unread_count = MM_Message_Handler::get_unread_count($current_user_id);

        wp_send_json_success([
            'unread_count' => $unread_count,
        ]);
    }

    /**
     * Start a new conversation
     */
    public static function start_conversation()
    {
        self::verify_nonce_and_login();

        $current_user_id = get_current_user_id();
        $other_user_id = intval($_POST['user_id'] ?? 0);

        if (!$other_user_id) {
            wp_send_json_error('Invalid user ID');
        }

        // Verify they can chat
        if (!MM_Chat_Manager::can_chat($current_user_id, $other_user_id)) {
            wp_send_json_error('Cannot chat with this user');
        }

        $conversation = MM_Chat_Manager::start_conversation($current_user_id, $other_user_id);

        if (!$conversation) {
            wp_send_json_error('Failed to create conversation');
        }

        $conv_data = MM_Chat_Manager::get_conversation($conversation->id, $current_user_id);

        wp_send_json_success([
            'conversation' => $conv_data,
        ]);
    }

    /**
     * Verify nonce and check if user is logged in
     */
    private static function verify_nonce_and_login()
    {
        if (!is_user_logged_in()) {
            error_log('MM Chat: User not logged in');
            wp_send_json_error('Not logged in', 401);
        }

        $nonce = $_POST['nonce'] ?? '';
        if (!wp_verify_nonce($nonce, 'mm_chat_nonce')) {
            error_log('MM Chat: Nonce verification failed. Nonce: ' . $nonce);
            wp_send_json_error('Security check failed', 403);
        }
    }
}
