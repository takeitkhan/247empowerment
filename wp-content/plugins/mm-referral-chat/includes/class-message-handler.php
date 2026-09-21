<?php
/**
 * Message Handler
 * Handles message sending, reading, and retrieval
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Message_Handler
{
    /**
     * Send a message
     */
    public static function send_message($conversation_id, $sender_id, $message)
    {
        // Validate inputs
        if (empty($conversation_id) || empty($sender_id) || empty($message)) {
            return [
                'success' => false,
                'error' => 'Invalid input parameters',
            ];
        }

        $conversation_id = intval($conversation_id);
        $sender_id = intval($sender_id);

        // Verify sender is part of conversation
        global $wpdb;
        $conv_table = $wpdb->prefix . 'chat_conversations';
        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $conv_table WHERE id = %d",
            $conversation_id
        ));

        if (!$conversation) {
            return [
                'success' => false,
                'error' => 'Conversation not found',
            ];
        }

        if ($conversation->user_1_id != $sender_id && $conversation->user_2_id != $sender_id) {
            return [
                'success' => false,
                'error' => 'Not authorized to send message in this conversation',
            ];
        }

        // Sanitize message
        $message = sanitize_textarea_field($message);

        // Insert message
        $message_id = MM_Chat_Database::insert_message($conversation_id, $sender_id, $message);

        if (!$message_id) {
            return [
                'success' => false,
                'error' => 'Failed to send message',
            ];
        }

        // Get and return message with sender info
        $message_data = self::get_message($message_id);

        return [
            'success' => true,
            'message' => $message_data,
        ];
    }

    /**
     * Get a single message
     */
    public static function get_message($message_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'chat_messages';

        $message = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $message_id
        ));

        if (!$message) {
            return null;
        }

        // Enrich with sender info
        $sender = get_user_by('id', $message->sender_id);

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'sender' => $sender ? [
                'id' => $sender->ID,
                'name' => $sender->display_name,
                'avatar' => get_user_meta($sender->ID, 'profile_photo', true),
            ] : null,
            'message' => wp_kses_post($message->message),
            'is_read' => (bool) $message->is_read,
            'created_at' => $message->created_at,
            'read_at' => $message->read_at,
        ];
    }

    /**
     * Get messages from a conversation
     */
    public static function get_messages($conversation_id, $current_user_id, $limit = 50, $offset = 0)
    {
        // Verify user is part of conversation
        global $wpdb;
        $conv_table = $wpdb->prefix . 'chat_conversations';
        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $conv_table WHERE id = %d",
            $conversation_id
        ));

        if (!$conversation) {
            return null;
        }

        if ($conversation->user_1_id != $current_user_id && $conversation->user_2_id != $current_user_id) {
            return null;
        }

        // Get messages (ordered ascending for chat display)
        $messages = MM_Chat_Database::get_messages($conversation_id, $limit, $offset);

        // Reverse to show newest last
        $messages = array_reverse($messages);

        // Enrich messages
        $enriched_messages = [];
        foreach ($messages as $msg) {
            $enriched_messages[] = self::get_message($msg->id);
        }

        return $enriched_messages;
    }

    /**
     * Mark message as read
     */
    public static function mark_as_read($message_id, $reader_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'chat_messages';

        // Verify the reader is not the sender
        $message = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $message_id
        ));

        if (!$message) {
            return false;
        }

        // Only mark as read if the reader is not the sender
        if ($message->sender_id == $reader_id) {
            return false;
        }

        return MM_Chat_Database::mark_as_read($message_id);
    }

    /**
     * Mark all messages in a conversation as read
     */
    public static function mark_conversation_as_read($conversation_id, $user_id)
    {
        // Verify user is part of conversation
        global $wpdb;
        $conv_table = $wpdb->prefix . 'chat_conversations';
        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $conv_table WHERE id = %d",
            $conversation_id
        ));

        if (!$conversation) {
            return false;
        }

        if ($conversation->user_1_id != $user_id && $conversation->user_2_id != $user_id) {
            return false;
        }

        return MM_Chat_Database::mark_conversation_as_read($conversation_id, $user_id);
    }

    /**
     * Get unread count
     */
    public static function get_unread_count($user_id)
    {
        return MM_Chat_Database::get_total_unread_count($user_id);
    }

    /**
     * Search messages
     */
    public static function search_messages($conversation_id, $current_user_id, $search_term, $limit = 50)
    {
        // Verify user is part of conversation
        global $wpdb;
        $conv_table = $wpdb->prefix . 'chat_conversations';
        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $conv_table WHERE id = %d",
            $conversation_id
        ));

        if (!$conversation) {
            return [];
        }

        if ($conversation->user_1_id != $current_user_id && $conversation->user_2_id != $current_user_id) {
            return [];
        }

        $msg_table = $wpdb->prefix . 'chat_messages';
        $search_term = '%' . $wpdb->esc_like($search_term) . '%';

        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $msg_table 
             WHERE conversation_id = %d AND message LIKE %s
             ORDER BY created_at DESC
             LIMIT %d",
            $conversation_id, $search_term, $limit
        ));

        // Enrich messages
        $enriched_messages = [];
        foreach ($messages as $msg) {
            $enriched_messages[] = self::get_message($msg->id);
        }

        return $enriched_messages;
    }
}
