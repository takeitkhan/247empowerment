<?php
class Notifications
{
    private static $instance = null;
    private $notifications_key = 'user_notifications';

    // Singleton pattern: getInstance
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Prevent direct object creation
    private function __construct() {}

    // Add a notification for a user
    // $type: string e.g. 'info', 'warning', 'error', 'success'
    // $message: string notification message
    // $user_id: int user ID to associate notification with
    // $data: optional array for any extra data attached to notification
    public function addNotification($user_id, $type, $message, $data = [])
    {
        if (!$user_id || empty($message)) {
            return false;
        }

        $notifications = get_user_meta($user_id, $this->notifications_key, true);
        if (!is_array($notifications)) {
            $notifications = [];
        }

        $notification = [
            'id' => uniqid('notif_', true),
            'type' => $type,
            'message' => $message,
            'data' => $data,
            'created_at' => current_time('mysql'),
            'read' => false,
        ];

        $notifications[] = $notification;
        update_user_meta($user_id, $this->notifications_key, $notifications);

        // Optional: do_action hook for when notification is added
        do_action('notifications_added', $user_id, $notification);

        return $notification['id'];
    }

    // Get all notifications for a user, optionally only unread
    public function getNotifications($user_id, $only_unread = false)
    {
        if (!$user_id) {
            return [];
        }

        $notifications = get_user_meta($user_id, $this->notifications_key, true);
        if (!is_array($notifications)) {
            return [];
        }

        if ($only_unread) {
            $notifications = array_filter($notifications, function ($n) {
                return empty($n['read']);
            });
        }

        return $notifications;
    }

    // Mark a notification as read by ID
    public function markAsRead($user_id, $notification_id)
    {
        if (!$user_id || !$notification_id) {
            return false;
        }

        $notifications = get_user_meta($user_id, $this->notifications_key, true);
        if (!is_array($notifications)) {
            return false;
        }

        foreach ($notifications as &$notification) {
            if ($notification['id'] === $notification_id) {
                $notification['read'] = true;
                update_user_meta($user_id, $this->notifications_key, $notifications);

                do_action('notifications_marked_read', $user_id, $notification_id);
                return true;
            }
        }
        return false;
    }

    // Clear all notifications for a user
    public function clearNotifications($user_id)
    {
        if (!$user_id) {
            return false;
        }
        delete_user_meta($user_id, $this->notifications_key);

        do_action('notifications_cleared', $user_id);
        return true;
    }

    // Add a referrer notification for a user
    // This function checks if the user has a referrer set in their user meta
    function add_referrer_notification_for_user($user_id = null)
    {
        // Use current user if no ID provided
        if (!$user_id) {
            if (!is_user_logged_in()) {
                return false; // no user context
            }
            $user_id = get_current_user_id();
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false; // invalid user
        }

        // Get referrer info from user meta
        $referrer = get_user_meta($user_id, 'referrer', true);
        $referrer_name = '';

        if ($referrer) {
            // Determine if referrer is user ID or username
            if (is_numeric($referrer)) {
                $referrer_user = get_user_by('id', intval($referrer));
            } else {
                $referrer_user = get_user_by('login', sanitize_text_field($referrer));
            }

            if ($referrer_user) {
                $referrer_name = $referrer_user->display_name ?: $referrer_user->user_login;
            }
        }

        // Prepare notification message
        if ($referrer_name) {
            $message = sprintf(
                'You were referred by %s. Welcome in the board!',
                esc_html($referrer_name)
            );
        } else {
            $message = 'Welcome to the site!';
        }

        // Add notification using the Notifications singleton
        $notifications = Notifications::getInstance();
        return $notifications->addNotification(
            $user_id,
            'info',          // notification type
            $message,
            ['referrer' => $referrer_name]
        );
    }

    // Add a referral notification to the referrer when a new user is referred
    // This function should be called when a new user registers and has a referrer set
    function add_referral_notification_to_referrer($referred_user_id)
    {
        if (!$referred_user_id) {
            return false;
        }

        $referred_user = get_user_by('id', $referred_user_id);
        if (!$referred_user) {
            return false;
        }

        // Get the referrer user meta (should be user ID or username)
        $referrer = get_user_meta($referred_user_id, 'referrer', true);
        if (!$referrer) {
            return false; // no referrer set
        }

        // Get referrer user object
        if (is_numeric($referrer)) {
            $referrer_user = get_user_by('id', intval($referrer));
        } else {
            $referrer_user = get_user_by('login', sanitize_text_field($referrer));
        }

        if (!$referrer_user) {
            return false;
        }

        $referrer_user_id = $referrer_user->ID;

        // Build notification message for referrer
        $message = sprintf(
            'You have successfully referred a new user: %s (%s).',
            esc_html($referred_user->display_name ?: $referred_user->user_login),
            esc_html($referred_user->user_login)
        );

        // Add notification to the referrer user
        $notifications = Notifications::getInstance();

        return $notifications->addNotification(
            $referrer_user_id,
            'success',   // type could be 'success' to celebrate referral
            $message,
            ['referred_user_id' => $referred_user_id]
        );
    }

    // Get unread count for notification bubble
    public function getUnreadCount($user_id)
    {
        if (!$user_id) {
            return 0;
        }

        $notifications = get_user_meta($user_id, $this->notifications_key, true);

        if (!is_array($notifications) || empty($notifications)) {
            return 0;
        }

        $unread = array_filter($notifications, function ($n) {
            return empty($n['read']);
        });

        return count($unread);
    }

    // Mark all notifications as read
    public function markAllAsRead($user_id)
    {
        if (!$user_id) {
            return false;
        }

        $notifications = get_user_meta($user_id, $this->notifications_key, true);
        if (!is_array($notifications) || empty($notifications)) {
            return false;
        }

        foreach ($notifications as &$notification) {
            $notification['read'] = true;
        }

        update_user_meta($user_id, $this->notifications_key, $notifications);

        do_action('notifications_all_marked_read', $user_id);
        return true;
    }

    function mm_render_notification_item($notif)
    {
        ob_start();

        $created_time = human_time_diff(
            strtotime($notif['created_at']),
            current_time('timestamp')
        ) . ' ago';

        $user_img = get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg';
?>
        <div class="d-flex align-items-center gap-3 py-2 border-2 border-bottom border-light unread notification-item"
            data-id="<?= esc_attr($notif['id']); ?>">

            <div class="d-flex align-items-center gap10">
                <img src="<?= esc_url(get_template_directory_uri() . '/assets/img/nd/circle-notification.png'); ?>" alt="">
                <div class="position-relative img44">
                    <img src="<?= esc_url($user_img); ?>" class="rounded-circle w-100 h-100 object-fit-cover">
                    <img class="position-absolute active-icon"
                        src="<?= esc_url(get_template_directory_uri() . '/assets/img/nd/active_icon.png'); ?>">
                </div>
            </div>

            <div class="d-flex flex-column post-user">
                <span class="p_name fs16"><?= esc_html($notif['message']); ?></span>
                <span class="mb-0 text-blue-color fs14"><?= esc_html($created_time); ?></span>
            </div>
        </div>
<?php

        return ob_get_clean();
    }
}
