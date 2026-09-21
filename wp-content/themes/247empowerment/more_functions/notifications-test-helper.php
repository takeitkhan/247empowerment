<?php
/**
 * Notification System Test Helper
 * Create test notifications for all 25+ types
 */

require_once __DIR__ . '/../inc/NotificationManager.php';
require_once __DIR__ . '/../inc/NotificationTypes.php';

class NotificationTestHelper {

    /**
     * Create test notifications for current user
     */
    public static function createTestNotifications() {
        if (!current_user_can('manage_options')) {
            return 'Unauthorized';
        }

        $user_id = get_current_user_id();
        $manager = NotificationManager::getInstance();
        $types = NotificationTypes::getAll();

        $created = 0;
        foreach ($types as $type => $data) {
            $message = $data['description'];
            
            $manager->add($user_id, $type, $message, [
                'action_url' => site_url('/dashboard/'),
                'action_label' => 'View',
                'metadata' => [
                    'test' => true,
                    'created_at' => current_time('mysql'),
                ],
            ]);
            
            $created++;
        }

        return "Created $created test notifications";
    }

    /**
     * Create random notifications for all users
     */
    public static function createRandomNotificationsForAllUsers() {
        if (!current_user_can('manage_options')) {
            return 'Unauthorized';
        }

        $users = get_users(['role' => 'subscriber', 'number' => 0]);
        $manager = NotificationManager::getInstance();
        $types = array_keys(NotificationTypes::getAll());

        $total_created = 0;

        foreach ($users as $user) {
            $random_count = rand(3, 10);
            for ($i = 0; $i < $random_count; $i++) {
                $random_type = $types[array_rand($types)];
                $data = NotificationTypes::get($random_type);

                $manager->add($user->ID, $random_type, $data['description'], [
                    'action_url' => site_url('/dashboard/'),
                    'action_label' => 'View',
                ]);
                $total_created++;
            }
        }

        return "Created $total_created notifications across " . count($users) . " users";
    }

    /**
     * Clear all test notifications
     */
    public static function clearTestNotifications() {
        if (!current_user_can('manage_options')) {
            return 'Unauthorized';
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'notifications';

        $deleted = $wpdb->query("DELETE FROM $table_name WHERE data LIKE '%\"test\":true%'");

        return "Deleted $deleted test notifications";
    }

    /**
     * Get database stats
     */
    public static function getStats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'notifications';

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $unread = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE read_at IS NULL");
        $by_category = $wpdb->get_results("SELECT category, COUNT(*) as count FROM $table_name GROUP BY category");
        $by_type = $wpdb->get_results("SELECT type, COUNT(*) as count FROM $table_name GROUP BY type ORDER BY count DESC LIMIT 10");

        return [
            'total' => $total,
            'unread' => $unread,
            'by_category' => $by_category,
            'by_type' => $by_type,
        ];
    }

    /**
     * Test admin notification creation
     */
    public static function testAdminNotification() {
        $admins = get_users(['role' => 'administrator', 'number' => -1]);
        
        if (empty($admins)) {
            return 'No admin users found';
        }

        $test_user_id = get_current_user_id();
        $test_user = get_userdata($test_user_id);
        
        $admin_count = 0;
        foreach ($admins as $admin) {
            NotificationManager::getInstance()->add(
                $admin->ID,
                'new_follower',
                "TEST: Admin notification received - Test User ({$test_user->user_email})",
                [
                    'action_url' => get_admin_url() . "user-edit.php?user_id={$test_user_id}",
                    'action_label' => 'View User',
                    'metadata' => ['admin_alert' => true, 'test' => true, 'test_user_id' => $test_user_id]
                ]
            );
            $admin_count++;
        }

        return "Test notifications sent to {$admin_count} admin(s)";
    }
}

// AJAX endpoint to create test data
add_action('wp_ajax_mm_create_test_notifications', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    check_ajax_referer('mm_notification_nonce', 'nonce');

    $result = NotificationTestHelper::createTestNotifications();
    wp_send_json_success(['message' => $result]);
});

// AJAX endpoint to create random notifications
add_action('wp_ajax_mm_create_random_notifications', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    check_ajax_referer('mm_notification_nonce', 'nonce');

    $result = NotificationTestHelper::createRandomNotificationsForAllUsers();
    wp_send_json_success(['message' => $result]);
});

// AJAX endpoint to get stats
add_action('wp_ajax_mm_get_notification_stats', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    check_ajax_referer('mm_notification_nonce', 'nonce');

    $stats = NotificationTestHelper::getStats();
    wp_send_json_success($stats);
});

// AJAX endpoint to clear test notifications
add_action('wp_ajax_mm_clear_test_notifications', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    check_ajax_referer('mm_notification_nonce', 'nonce');

    global $wpdb;
    $table_name = $wpdb->prefix . 'notifications';
    $deleted = $wpdb->query("DELETE FROM $table_name WHERE data LIKE '%\"test\":%'");
    
    wp_send_json_success(['message' => "Deleted $deleted test notifications"]);
});

// AJAX endpoint to test admin notifications
add_action('wp_ajax_mm_test_admin_notification', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Unauthorized');
    }

    check_ajax_referer('mm_notification_nonce', 'nonce');

    $result = NotificationTestHelper::testAdminNotification();
    wp_send_json_success(['message' => $result]);
});

// Add test button to admin
add_action('admin_footer', function() {
    if (!current_user_can('manage_options')) {
        return;
    }

    global $pagenow;
    if ($pagenow === 'admin.php' && isset($_GET['page']) && $_GET['page'] === 'mm-notifications-dashboard') {
        ?>
        <div style="position: fixed; bottom: 20px; right: 20px; background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 1000;">
            <button class="button button-primary" id="mm-test-create-btn" style="margin-bottom: 10px; display: block; width: 100%;">
                🧪 Create Test Notifications
            </button>
            <button class="button" id="mm-test-random-btn" style="margin-bottom: 10px; display: block; width: 100%;">
                🎲 Random Notifications
            </button>
            <button class="button button-secondary" id="mm-test-admin-btn" style="margin-bottom: 10px; display: block; width: 100%;">
                👤 Test Admin Notification
            </button>
            <button class="button button-link-delete" id="mm-test-clear-btn" style="display: block; width: 100%; color: #d63638;">
                🗑️ Clear Test Data
            </button>
            <div id="mm-test-result" style="margin-top: 10px; font-size: 12px; color: #666;"></div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#mm-test-create-btn').on('click', function() {
                $.post(ajaxurl, {
                    action: 'mm_create_test_notifications',
                    nonce: '<?php echo wp_create_nonce("mm_notification_nonce"); ?>',
                }, function(response) {
                    if (response.success) {
                        $('#mm-test-result').text(response.data.message).css('color', '#28a745');
                        setTimeout(() => location.reload(), 2000);
                    }
                });
            });

            $('#mm-test-random-btn').on('click', function() {
                $.post(ajaxurl, {
                    action: 'mm_create_random_notifications',
                    nonce: '<?php echo wp_create_nonce("mm_notification_nonce"); ?>',
                }, function(response) {
                    if (response.success) {
                        $('#mm-test-result').text(response.data.message).css('color', '#28a745');
                        setTimeout(() => location.reload(), 2000);
                    }
                });
            });

            $('#mm-test-clear-btn').on('click', function() {
                if (!confirm('Clear all test notifications?')) return;
                $.post(ajaxurl, {
                    action: 'mm_clear_test_notifications',
                    nonce: '<?php echo wp_create_nonce("mm_notification_nonce"); ?>',
                }, function(response) {
                    $('#mm-test-result').text('Cleared').css('color', '#d63638');
                    setTimeout(() => location.reload(), 2000);
                });
            });

            $('#mm-test-admin-btn').on('click', function() {
                $.post(ajaxurl, {
                    action: 'mm_test_admin_notification',
                    nonce: '<?php echo wp_create_nonce("mm_notification_nonce"); ?>',
                }, function(response) {
                    if (response.success) {
                        $('#mm-test-result').text(response.data.message).css('color', '#0073aa');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        $('#mm-test-result').text('Error: ' + response.data).css('color', '#d63638');
                    }
                });
            });
        });
        </script>
        <?php
    }
});
