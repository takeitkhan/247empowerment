<?php
/**
 * Notification Manager - CRUD Operations for Notifications
 * Stores and manages all notifications in custom database table
 */

require_once __DIR__ . '/NotificationTypes.php';

class NotificationManager {

    private static $instance = null;
    const TABLE_NAME = 'notifications';
    const PER_PAGE = 20;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Create notifications table on activation
     */
    public static function createTable() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            type VARCHAR(100) NOT NULL,
            category VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message LONGTEXT NOT NULL,
            icon VARCHAR(10),
            color VARCHAR(20),
            action_url VARCHAR(500),
            action_label VARCHAR(100),
            data LONGTEXT COMMENT 'JSON serialized extra data',
            read_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY user_id (user_id),
            KEY type (type),
            KEY category (category),
            KEY created_at (created_at),
            KEY read_at (read_at),
            KEY user_read (user_id, read_at)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Add a notification
     */
    public function add($user_id, $type, $message, $data = []) {
        global $wpdb;

        // Allow user_id = 0 for admin group notifications
        if (($user_id === null || $user_id === '') || !NotificationTypes::isValid($type)) {
            error_log("Invalid notification: user_id=$user_id, type=$type");
            return false;
        }

        $notification_type = NotificationTypes::get($type);
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $insert_data = [
            'user_id' => (int)$user_id,
            'type' => $type,
            'category' => $notification_type['category'],
            'title' => $notification_type['label'],
            'message' => sanitize_textarea_field($message),
            'icon' => $notification_type['icon'],
            'color' => $notification_type['color'],
            'action_url' => isset($data['action_url']) ? esc_url($data['action_url']) : '',
            'action_label' => isset($data['action_label']) ? sanitize_text_field($data['action_label']) : '',
            'data' => isset($data['metadata']) ? wp_json_encode($data['metadata']) : '',
            'created_at' => current_time('mysql'),
        ];

        $result = $wpdb->insert($table_name, $insert_data);

        if ($result) {
            $notif_id = $wpdb->insert_id;
            
            // Fire action hook
            do_action('notification_created', $notif_id, $user_id, $type, $insert_data);
            
            // Trigger real-time notification if user is logged in
            if (is_user_logged_in() && get_current_user_id() == $user_id) {
                do_action('notification_realtime', $notif_id);
            }
            
            return $notif_id;
        }

        return false;
    }

    /**
     * Add notification for admin group (visible to all admins)
     * Uses special user_id = 0 to represent admin group
     */
    public function addForAdmins($type, $message, $data = []) {
        // Send to special admin group user_id (0)
        return $this->add(0, $type, $message, $data);
    }

    /**
     * Get notifications with filtering and pagination
     * For admins: includes admin group notifications (user_id = 0)
     */
    public function get($user_id, $args = []) {
        global $wpdb;

        $defaults = [
            'page' => 1,
            'per_page' => self::PER_PAGE,
            'type' => '',
            'category' => '',
            'read' => '',
            'orderby' => 'created_at',
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);
        $table_name = $wpdb->prefix . self::TABLE_NAME;

        // Check if user is admin - if so, include admin group (user_id = 0) notifications
        $user_data = get_userdata($user_id);
        $is_admin = $user_data && in_array('administrator', $user_data->roles);

        // Build WHERE clause
        if ($is_admin) {
            // Admin gets their own notifications + admin group notifications
            $where = $wpdb->prepare("WHERE (user_id = %d OR user_id = 0)", (int)$user_id);
        } else {
            // Regular user gets only their own
            $where = $wpdb->prepare("WHERE user_id = %d", (int)$user_id);
        }

        if (!empty($args['type'])) {
            $where .= $wpdb->prepare(" AND type = %s", $args['type']);
        }

        if (!empty($args['category'])) {
            $where .= $wpdb->prepare(" AND category = %s", $args['category']);
        }

        if ($args['read'] === 'unread') {
            $where .= " AND read_at IS NULL";
        } elseif ($args['read'] === 'read') {
            $where .= " AND read_at IS NOT NULL";
        }

        // Order
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        $orderby = in_array($args['orderby'], ['created_at', 'updated_at', 'id']) ? $args['orderby'] : 'created_at';

        // Count total
        $count_sql = "SELECT COUNT(*) FROM $table_name $where";
        $total = $wpdb->get_var($count_sql);

        // Pagination
        $page = max(1, (int)$args['page']);
        $per_page = max(1, (int)$args['per_page']);
        $offset = ($page - 1) * $per_page;

        // Get results
        $sql = "SELECT * FROM $table_name $where ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $results = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));

        return [
            'notifications' => $results,
            'total' => (int)$total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page),
        ];
    }

    /**
     * Get unread count
     */
    public function getUnreadCount($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND read_at IS NULL",
            (int)$user_id
        ));

        return (int)$count;
    }

    /**
     * Mark as read
     */
    public function markAsRead($notification_id, $user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        $result = $wpdb->update(
            $table_name,
            ['read_at' => current_time('mysql')],
            [
                'id' => (int)$notification_id,
                'user_id' => (int)$user_id,
            ]
        );

        if ($result) {
            do_action('notification_marked_read', $notification_id, $user_id);
        }

        return $result !== false;
    }

    /**
     * Mark all as read
     */
    public function markAllAsRead($user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        return $wpdb->update(
            $table_name,
            ['read_at' => current_time('mysql')],
            [
                'user_id' => (int)$user_id,
                'read_at' => null,
            ]
        ) !== false;
    }

    /**
     * Delete notification
     */
    public function delete($notification_id, $user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        return $wpdb->delete(
            $table_name,
            [
                'id' => (int)$notification_id,
                'user_id' => (int)$user_id,
            ]
        ) !== false;
    }

    /**
     * Delete all notifications
     */
    public function deleteAll($user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        return $wpdb->delete(
            $table_name,
            ['user_id' => (int)$user_id]
        ) !== false;
    }

    /**
     * Get single notification
     */
    public function getById($notification_id, $user_id = null) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        if ($user_id) {
            return $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
                (int)$notification_id,
                (int)$user_id
            ));
        }

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            (int)$notification_id
        ));
    }

    /**
     * Get stats by type
     */
    public function getStatsByType($user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT type, COUNT(*) as count FROM $table_name WHERE user_id = %d GROUP BY type ORDER BY count DESC",
            (int)$user_id
        ));
    }

    /**
     * Get stats by category
     */
    public function getStatsByCategory($user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT category, COUNT(*) as count FROM $table_name WHERE user_id = %d GROUP BY category ORDER BY count DESC",
            (int)$user_id
        ));
    }

    /**
     * Clean old notifications (older than 90 days)
     */
    public function cleanOldNotifications($days = 90) {
        global $wpdb;

        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$days days"));

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < %s",
            $cutoff_date
        ));
    }
}
