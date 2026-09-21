<?php
if (!defined('ABSPATH')) exit;

class MM_Gamification_User
{
    private static $instance = null;

    public static function init()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        // WordPress hooks
        add_action('user_register', [$this, 'handle_user_register']);
        add_action('wp_login', [$this, 'handle_first_login'], 10, 2);
        add_action('wp_login', [$this, 'handle_daily_login'], 11, 2); // run after first login

        // AJAX hooks for uploads
        add_action('wp_ajax_upload_profile_photo', [$this, 'handle_upload_photo']);
        add_action('wp_ajax_upload_cover_photo', [$this, 'handle_upload_photo']);

        // Generic AJAX hook for frontend actions
        add_action('wp_ajax_mm_gamification_action', [$this, 'handle_frontend_action']);

        // Hook to create persistent notifications whenever points are awarded
        add_action('user_earned_points', [$this, 'handle_user_earned_points'], 10, 3);

        // Hook for new posts (concurrent, event, etc.)
        add_action('transition_post_status', [$this, 'handle_new_post_publish'], 10, 3);

        // Hook for profile field updates (birthday, location)
        add_action('profile_update', [$this, 'handle_profile_field_update'], 10, 2);
    }

    public function handle_user_register($user_id)
    {
        // Use the centralized function to award points and handle all notifications.
        if (function_exists('mm_award_points_and_notify')) {
            mm_award_points_and_notify($user_id, 'user_register');
        }
    }

    public function handle_first_login($user_login, $user)
    {
        if (!get_user_meta($user->ID, 'first_login_done', true)) {

            if (function_exists('mm_award_points_and_notify')) {
                mm_award_points_and_notify($user->ID, 'first_login');
            }

            update_user_meta($user->ID, 'first_login_done', 1);

            // Save today's date as last_daily_login so daily login does NOT trigger today
            update_user_meta($user->ID, 'last_daily_login', date('Y-m-d'));
        }
    }


    public function handle_daily_login($user_login, $user)
    {
        $last_daily_login = get_user_meta($user->ID, 'last_daily_login', true);
        $today = date('Y-m-d');

        if ($last_daily_login !== $today) {

            if (function_exists('mm_award_points_and_notify')) {
                mm_award_points_and_notify($user->ID, 'daily_login');
            }

            update_user_meta($user->ID, 'last_daily_login', $today);
        }
    }


    /**
     * Handles awarding points when a user publishes a post of a specific type.
     * It differentiates between the first post and subsequent posts.
     */
    public function handle_new_post_publish($new_status, $old_status, $post)
    {
        // Only trigger on publishing a new post
        if ('publish' !== $new_status || 'publish' === $old_status) {
            return;
        }

        // We only care about 'concurrent' and 'event' post types for now
        $supported_post_types = ['concurrent', 'event'];
        if (!in_array($post->post_type, $supported_post_types)) {
            return;
        }

        $user_id = $post->post_author;
        if (!$user_id || !function_exists('mm_award_points_and_notify')) {
            return;
        }

        // Check if this is the user's first post of this type
        $meta_key = 'has_published_' . $post->post_type;
        $has_published_before = get_user_meta($user_id, $meta_key, true);

        if (!$has_published_before) {
            // This is the first time
            $action_key = 'first_' . $post->post_type . '_post'; // e.g., 'first_concurrent_post'
            mm_award_points_and_notify($user_id, $action_key);
            update_user_meta($user_id, $meta_key, true); // Set the flag
        } else {
            // This is a subsequent post
            $action_key = 'next_' . $post->post_type . '_posts'; // e.g., 'next_concurrent_posts'
            mm_award_points_and_notify($user_id, $action_key);
        }
    }

    /**
     * Handles awarding points when a user updates specific profile fields.
     */
    public function handle_profile_field_update($user_id, $old_user_data)
    {
        if (!function_exists('mm_award_points_and_notify')) {
            return;
        }

        // Fields to track
        $fields = [
            'birthday' => 'birthday_update',
            'location' => 'location_update',
        ];

        foreach ($fields as $meta_key => $action_key) {

            // Get old value from user meta
            $old_value = get_user_meta($user_id, $meta_key, true);

            // Get new submitted value
            $new_value = isset($_POST[$meta_key]) ? sanitize_text_field($_POST[$meta_key]) : '';

            // Skip if empty or unchanged
            if (empty($new_value) || $new_value === $old_value) {
                continue;
            }

            // Check if user has already earned points for this field
            $earned_key = $action_key . '_earned_once';
            $already_earned = get_user_meta($user_id, $earned_key, true);

            if (!$already_earned) {
                // Award points
                mm_award_points_and_notify($user_id, $action_key);

                // Mark as earned once
                update_user_meta($user_id, $earned_key, 1);
            }

            // Update user meta with new value
            update_user_meta($user_id, $meta_key, $new_value);
        }
    }


    // Handle profile or cover photo upload
    public function handle_upload_photo()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Not logged in']);
        }

        $user_id = get_current_user_id();

        $file_input = isset($_FILES['profile_photo']) ? 'profile_photo' : (isset($_FILES['cover_photo']) ? 'cover_photo' : null);
        if (!$file_input) {
            wp_send_json_error(['message' => 'No file uploaded']);
        }

        $meta_name  = $file_input === 'profile_photo' ? 'profile_photo' : 'profile_cover_photo';
        $action_key = $file_input === 'profile_photo' ? 'profile_picture_update' : 'cover_photo_update';

        $file = $_FILES[$file_input];

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $overrides = ['test_form' => false];
        $uploaded = wp_handle_upload($file, $overrides);

        if (!isset($uploaded['url'])) {
            wp_send_json_error(['message' => 'Upload failed']);
        }

        $old_value = get_user_meta($user_id, $meta_name, true);
        update_user_meta($user_id, $meta_name, esc_url($uploaded['url']));

        $notification_data = null;

        // ---- WEEKLY LIMIT LOGIC ----
        $weekly_meta_key = $action_key . '_last_points_at'; // e.g., "profile_picture_update_last_points_at"
        $last_award_date = get_user_meta($user_id, $weekly_meta_key, true);
        $today = date('Y-m-d');

        $can_earn = true;
        if ($last_award_date) {
            $days_since_last = (strtotime($today) - strtotime($last_award_date)) / 86400;
            if ($days_since_last < 7) {
                $can_earn = false;
            }
        }

        // Award points only if the photo changed AND weekly limit passes
        if ($uploaded['url'] !== $old_value && $can_earn && function_exists('mm_award_points_and_notify')) {
            $notification_data = mm_award_points_and_notify($user_id, $action_key);

            // Update last awarded date
            update_user_meta($user_id, $weekly_meta_key, $today);
        }

        wp_send_json_success([
            'url' => $uploaded['url'],
            'notification' => $notification_data
        ]);
    }


    /**
     * Generic AJAX handler for awarding points for frontend actions.
     */
    public function handle_frontend_action()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in to perform this action.'], 403);
        }

        check_ajax_referer('mm_gamification_nonce', 'nonce');

        $user_id = get_current_user_id();
        $action_key = isset($_POST['action_key']) ? sanitize_text_field($_POST['action_key']) : '';

        if (empty($action_key)) {
            wp_send_json_error(['message' => 'No action key provided.'], 400);
        }

        // Prevent awarding points for the same one-time action multiple times.
        $logs = get_user_meta($user_id, 'earned_points_logs', true) ?: [];
        foreach ($logs as $log) {
            if (isset($log['action_key']) && $log['action_key'] === $action_key) {
                wp_send_json_error(['message' => 'Points already awarded for this action.'], 409);
            }
        }

        // Award points and send notification
        if (function_exists('mm_award_points_and_notify')) {
            $notification_data = mm_award_points_and_notify($user_id, $action_key);
            if ($notification_data) {
                wp_send_json_success([
                    'message' => 'Points awarded successfully!',
                    'notification' => $notification_data
                ]);
            }
        }

        wp_send_json_error(['message' => 'Could not award points for this action.'], 500);
    }

    /**
     * Creates a persistent notification when points are earned.
     * This is hooked into 'user_earned_points' which is fired from gamification_award_points().
     */
    public function handle_user_earned_points($user_id, $points, $activity)
    {
        if (!$user_id || !$points || !class_exists('Notifications')) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'gamification_actions';
        $action = $wpdb->get_row(
            $wpdb->prepare("SELECT custom_message, notification_message FROM $table WHERE action_key = %s", $activity)
        );

        // Use the specific notification message if available, otherwise generate a default.
        if ($action && !empty($action->notification_message)) {
            // Replace the {points} placeholder with the actual point value.
            $message = str_replace('{points}', (int)$points, $action->notification_message);
        } else {
            $fallback_message = $action->custom_message ?? ucwords(str_replace('_', ' ', $activity));
            $message = sprintf("You earned %d points for: %s", (int)$points, esc_html($fallback_message));
        }

        $notifications = Notifications::getInstance();
        $notifications->addNotification(
            $user_id,
            'success', // Or 'points_earned'
            $message,
            [
                'points'   => $points,
                'activity' => $activity
            ]
        );
    }
}

// Initialize
MM_Gamification_User::init();
