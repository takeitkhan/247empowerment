<?php
/**
 * Chat Database Manager
 * Handles database operations for chat system
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Chat_Database
{
    /**
     * Create database tables
     */
    public static function create_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Create conversations table
        $conversations_table = $wpdb->prefix . 'chat_conversations';
        $conversations_sql = "CREATE TABLE IF NOT EXISTS $conversations_table (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_1_id BIGINT NOT NULL,
            user_2_id BIGINT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_archived TINYINT DEFAULT 0,
            UNIQUE KEY unique_conversation (user_1_id, user_2_id),
            INDEX idx_user1 (user_1_id),
            INDEX idx_user2 (user_2_id),
            INDEX idx_updated (updated_at)
        ) $charset_collate;";

        // Create messages table
        $messages_table = $wpdb->prefix . 'chat_messages';
        $messages_sql = "CREATE TABLE IF NOT EXISTS $messages_table (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            conversation_id BIGINT NOT NULL,
            sender_id BIGINT NOT NULL,
            message LONGTEXT NOT NULL,
            is_read TINYINT DEFAULT 0,
            read_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (conversation_id) REFERENCES $conversations_table(id) ON DELETE CASCADE,
            INDEX idx_conversation (conversation_id),
            INDEX idx_sender (sender_id),
            INDEX idx_created (created_at),
            INDEX idx_is_read (is_read)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($conversations_sql);
        dbDelta($messages_sql);
    }

    /**
     * Get conversation between two users
     */
    public static function get_conversation($user_1_id, $user_2_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'chat_conversations';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE (user_1_id = %d AND user_2_id = %d) 
                OR (user_1_id = %d AND user_2_id = %d)",
            $user_1_id, $user_2_id, $user_2_id, $user_1_id
        ));
    }

    /**
     * Create or get conversation
     */
    public static function get_or_create_conversation($user_1_id, $user_2_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'chat_conversations';

        // Normalize user IDs (smaller first)
        $normalized_users = self::normalize_user_ids($user_1_id, $user_2_id);
        $user_1_id = $normalized_users[0];
        $user_2_id = $normalized_users[1];

        // Check if conversation exists
        $conversation = self::get_conversation($user_1_id, $user_2_id);
        
        if ($conversation) {
            return $conversation;
        }

        // Create new conversation
        $result = $wpdb->insert(
            $table,
            [
                'user_1_id' => $user_1_id,
                'user_2_id' => $user_2_id,
            ],
            ['%d', '%d']
        );

        if ($result) {
            return self::get_conversation($user_1_id, $user_2_id);
        }

        return false;
    }

    /**
     * Get conversations for user
     */
    public static function get_user_conversations($user_id, $limit = 20, $offset = 0)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'chat_conversations';

        $conversations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE user_1_id = %d OR user_2_id = %d
             ORDER BY updated_at DESC
             LIMIT %d OFFSET %d",
            $user_id, $user_id, $limit, $offset
        ));

        // Enrich with message data
        foreach ($conversations as &$conv) {
            $conv->other_user_id = ($conv->user_1_id == $user_id) ? $conv->user_2_id : $conv->user_1_id;
            $conv->last_message = self::get_last_message($conv->id);
            $conv->unread_count = self::get_unread_count($conv->id, $user_id);
        }

        return $conversations;
    }

    /**
     * Get messages from conversation
     */
    public static function get_messages($conversation_id, $limit = 50, $offset = 0)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'chat_messages';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE conversation_id = %d
             ORDER BY created_at DESC
             LIMIT %d OFFSET %d",
            $conversation_id, $limit, $offset
        ));
    }

    /**
     * Get last message in conversation
     */
    public static function get_last_message($conversation_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'chat_messages';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table 
             WHERE conversation_id = %d
             ORDER BY created_at DESC
             LIMIT 1",
            $conversation_id
        ));
    }

    /**
     * Insert message
     */
    public static function insert_message($conversation_id, $sender_id, $message)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'chat_messages';

        $result = $wpdb->insert(
            $table,
            [
                'conversation_id' => $conversation_id,
                'sender_id' => $sender_id,
                'message' => $message,
            ],
            ['%d', '%d', '%s']
        );

        if ($result) {
            // Update conversation timestamp
            $conv_table = $wpdb->prefix . 'chat_conversations';
            $wpdb->update(
                $conv_table,
                ['updated_at' => current_time('mysql')],
                ['id' => $conversation_id],
                ['%s'],
                ['%d']
            );

            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Mark message as read
     */
    public static function mark_as_read($message_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'chat_messages';

        return $wpdb->update(
            $table,
            [
                'is_read' => 1,
                'read_at' => current_time('mysql'),
            ],
            ['id' => $message_id],
            ['%d', '%s'],
            ['%d']
        );
    }

    /**
     * Mark all messages in conversation as read
     */
    public static function mark_conversation_as_read($conversation_id, $user_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'chat_messages';

        return $wpdb->update(
            $table,
            [
                'is_read' => 1,
                'read_at' => current_time('mysql'),
            ],
            [
                'conversation_id' => $conversation_id,
                'sender_id <>' => $user_id,
                'is_read' => 0,
            ],
            ['%d', '%s'],
            ['%d', '%s', '%d']
        );
    }

    /**
     * Get unread count for conversation
     */
    public static function get_unread_count($conversation_id, $user_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'chat_messages';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table 
             WHERE conversation_id = %d 
             AND sender_id != %d 
             AND is_read = 0",
            $conversation_id, $user_id
        ));

        return intval($count);
    }

    /**
     * Get total unread count for user
     */
    public static function get_total_unread_count($user_id)
    {
        global $wpdb;
        $conv_table = $wpdb->prefix . 'chat_conversations';
        $msg_table = $wpdb->prefix . 'chat_messages';

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $msg_table m
             INNER JOIN $conv_table c ON m.conversation_id = c.id
             WHERE (c.user_1_id = %d OR c.user_2_id = %d)
             AND m.sender_id != %d
             AND m.is_read = 0",
            $user_id, $user_id, $user_id
        ));

        return intval($count);
    }

    /**
     * Normalize user IDs (smaller first)
     */
    private static function normalize_user_ids($user_1_id, $user_2_id)
    {
        if ($user_1_id > $user_2_id) {
            return [$user_2_id, $user_1_id];
        }
        return [$user_1_id, $user_2_id];
    }
}
