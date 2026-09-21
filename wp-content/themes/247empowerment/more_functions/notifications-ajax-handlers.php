<?php
/**
 * Notification AJAX Handlers
 * Frontend API for notifications
 */

require_once get_template_directory() . '/inc/NotificationManager.php';
require_once get_template_directory() . '/inc/NotificationTypes.php';

// Get notifications for current user
add_action('wp_ajax_mm_get_notifications', 'mm_ajax_get_notifications');
function mm_ajax_get_notifications() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in', 403);
    }

    check_ajax_referer('mm_notification_nonce', 'nonce');

    $user_id = get_current_user_id();
    $manager = NotificationManager::getInstance();

    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
    $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
    $read = isset($_POST['read']) ? sanitize_text_field($_POST['read']) : '';

    $results = $manager->get($user_id, [
        'page' => $page,
        'per_page' => 20,
        'type' => $type,
        'category' => $category,
        'read' => $read,
    ]);

    wp_send_json_success($results);
}

// Get unread count
add_action('wp_ajax_mm_get_unread_count', 'mm_ajax_get_unread_count');
function mm_ajax_get_unread_count() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in', 403);
    }

    check_ajax_referer('mm_notification_nonce', 'nonce');

    $user_id = get_current_user_id();
    $manager = NotificationManager::getInstance();
    $count = $manager->getUnreadCount($user_id);

    wp_send_json_success(['count' => $count]);
}

// Mark as read
add_action('wp_ajax_mm_mark_notification_read', 'mm_ajax_mark_notification_read');
function mm_ajax_mark_notification_read() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in', 403);
    }

    check_ajax_referer('mm_notification_nonce', 'nonce');

    $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
    $user_id = get_current_user_id();

    if (!$notification_id) {
        wp_send_json_error('Invalid notification ID');
    }

    $manager = NotificationManager::getInstance();
    $result = $manager->markAsRead($notification_id, $user_id);

    if ($result) {
        wp_send_json_success(['message' => 'Marked as read']);
    } else {
        wp_send_json_error('Could not mark as read');
    }
}

// Mark all as read
add_action('wp_ajax_mm_mark_all_notifications_read', 'mm_ajax_mark_all_notifications_read');
function mm_ajax_mark_all_notifications_read() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in', 403);
    }

    check_ajax_referer('mm_notification_nonce', 'nonce');

    $user_id = get_current_user_id();
    $manager = NotificationManager::getInstance();
    $result = $manager->markAllAsRead($user_id);

    if ($result) {
        wp_send_json_success(['message' => 'All marked as read']);
    } else {
        wp_send_json_error('Could not mark all as read');
    }
}

// Delete notification
add_action('wp_ajax_mm_delete_notification', 'mm_ajax_delete_notification');
function mm_ajax_delete_notification() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in', 403);
    }

    check_ajax_referer('mm_notification_nonce', 'nonce');

    $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
    $user_id = get_current_user_id();

    if (!$notification_id) {
        wp_send_json_error('Invalid notification ID');
    }

    $manager = NotificationManager::getInstance();
    $result = $manager->delete($notification_id, $user_id);

    if ($result) {
        wp_send_json_success(['message' => 'Notification deleted']);
    } else {
        wp_send_json_error('Could not delete notification');
    }
}

// Delete all notifications
add_action('wp_ajax_mm_delete_all_notifications', 'mm_ajax_delete_all_notifications');
function mm_ajax_delete_all_notifications() {
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in', 403);
    }

    check_ajax_referer('mm_notification_nonce', 'nonce');

    $user_id = get_current_user_id();
    $manager = NotificationManager::getInstance();
    $result = $manager->deleteAll($user_id);

    if ($result) {
        wp_send_json_success(['message' => 'All notifications deleted']);
    } else {
        wp_send_json_error('Could not delete all notifications');
    }
}

// Enqueue notification scripts and localize
add_action('wp_enqueue_scripts', function() {
    if (is_user_logged_in()) {
        wp_enqueue_script(
            'mm-notifications',
            get_template_directory_uri() . '/assets/js/mm-notifications.js',
            ['jquery'],
            filemtime(get_template_directory() . '/assets/js/mm-notifications.js'),
            true
        );

        wp_localize_script('mm-notifications', 'MM_NOTIFICATIONS', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mm_notification_nonce'),
        ]);
    }
});
