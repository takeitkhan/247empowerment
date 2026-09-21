<?php
/**
 * Notification Types - 25+ Predefined Notification Categories
 * All based on user actions within the system
 */

class NotificationTypes {

    /**
     * Get all notification types with metadata
     */
    public static function getAll() {
        return [
            // ========== PAYMENT & COURSE (6 types) ==========
            'course_purchase_success' => [
                'label' => 'Course Purchase Successful',
                'icon' => '🛒',
                'color' => 'success',
                'description' => 'User purchased a course',
                'category' => 'payment',
            ],
            'course_purchase_failed' => [
                'label' => 'Course Purchase Failed',
                'icon' => '❌',
                'color' => 'danger',
                'description' => 'Course purchase transaction failed',
                'category' => 'payment',
            ],
            'subscription_active' => [
                'label' => 'Subscription Active',
                'icon' => '✅',
                'color' => 'success',
                'description' => 'User subscription started',
                'category' => 'payment',
            ],
            'subscription_renewal_upcoming' => [
                'label' => 'Subscription Renewal Upcoming',
                'icon' => '⏰',
                'color' => 'warning',
                'description' => 'Next subscription renewal is coming',
                'category' => 'payment',
            ],
            'subscription_cancelled' => [
                'label' => 'Subscription Cancelled',
                'icon' => '🚫',
                'color' => 'danger',
                'description' => 'User cancelled subscription',
                'category' => 'payment',
            ],
            'refund_processed' => [
                'label' => 'Refund Processed',
                'icon' => '💰',
                'color' => 'info',
                'description' => 'Refund successfully processed',
                'category' => 'payment',
            ],

            // ========== PAYOUT & FINANCIAL (6 types) ==========
            'withdrawal_submitted' => [
                'label' => 'Withdrawal Request Submitted',
                'icon' => '📤',
                'color' => 'info',
                'description' => 'User submitted a withdrawal request',
                'category' => 'payout',
            ],
            'withdrawal_approved' => [
                'label' => 'Withdrawal Approved',
                'icon' => '✅',
                'color' => 'success',
                'description' => 'Admin approved withdrawal request',
                'category' => 'payout',
            ],
            'withdrawal_rejected' => [
                'label' => 'Withdrawal Rejected',
                'icon' => '❌',
                'color' => 'danger',
                'description' => 'Admin rejected withdrawal request',
                'category' => 'payout',
            ],
            'withdrawal_paid' => [
                'label' => 'Withdrawal Paid',
                'icon' => '💸',
                'color' => 'success',
                'description' => 'Withdrawal amount transferred to user',
                'category' => 'payout',
            ],
            'withdrawal_failed' => [
                'label' => 'Withdrawal Failed',
                'icon' => '⚠️',
                'color' => 'danger',
                'description' => 'Payout transaction failed',
                'category' => 'payout',
            ],
            'balance_updated' => [
                'label' => 'Balance Updated',
                'icon' => '💳',
                'color' => 'info',
                'description' => 'Account balance has been updated',
                'category' => 'payout',
            ],

            // ========== CONTENT & ENGAGEMENT (7 types) ==========
            'blog_post_published' => [
                'label' => 'New Blog Post Published',
                'icon' => '📝',
                'color' => 'info',
                'description' => 'New blog post published in your category',
                'category' => 'content',
            ],
            'video_playlist_added' => [
                'label' => 'New Video Playlist',
                'icon' => '🎥',
                'color' => 'info',
                'description' => 'New video playlist added',
                'category' => 'content',
            ],
            'job_posted' => [
                'label' => 'New Job Opportunity',
                'icon' => '💼',
                'color' => 'info',
                'description' => 'New job posting available',
                'category' => 'content',
            ],
            'event_created' => [
                'label' => 'New Event',
                'icon' => '📅',
                'color' => 'info',
                'description' => 'New event created',
                'category' => 'content',
            ],
            'comment_on_post' => [
                'label' => 'Comment on Your Post',
                'icon' => '💬',
                'color' => 'info',
                'description' => 'Someone commented on your post',
                'category' => 'engagement',
            ],
            'post_liked' => [
                'label' => 'Post Liked',
                'icon' => '❤️',
                'color' => 'warning',
                'description' => 'Your post received a like',
                'category' => 'engagement',
            ],
            'new_follower' => [
                'label' => 'New Follower',
                'icon' => '👤',
                'color' => 'success',
                'description' => 'You have a new follower',
                'category' => 'engagement',
            ],

            // ========== SOCIAL MEDIA (5 types) ==========
            'facebook_connected' => [
                'label' => 'Facebook Connected',
                'icon' => '📘',
                'color' => 'info',
                'description' => 'Facebook account successfully connected',
                'category' => 'social',
            ],
            'linkedin_connected' => [
                'label' => 'LinkedIn Connected',
                'icon' => '💼',
                'color' => 'info',
                'description' => 'LinkedIn account successfully connected',
                'category' => 'social',
            ],
            'linkedin_reconnect_required' => [
                'label' => 'LinkedIn Reconnect Required',
                'icon' => '💼',
                'color' => 'warning',
                'description' => 'LinkedIn connection expired or was revoked',
                'category' => 'social',
            ],
            'social_post_shared' => [
                'label' => 'Post Shared to Social Media',
                'icon' => '🔗',
                'color' => 'success',
                'description' => 'Your post shared to social media',
                'category' => 'social',
            ],
            'social_account_disconnected' => [
                'label' => 'Social Account Disconnected',
                'icon' => '🔌',
                'color' => 'warning',
                'description' => 'Social media account disconnected',
                'category' => 'social',
            ],
            'connection_removed' => [
                'label' => 'Connection Removed',
                'icon' => '❌',
                'color' => 'warning',
                'description' => 'Someone unfollowed you',
                'category' => 'social',
            ],

            // ========== GAMIFICATION & ACHIEVEMENTS (3 types) ==========
            'achievement_unlocked' => [
                'label' => 'Achievement Unlocked',
                'icon' => '🏆',
                'color' => 'success',
                'description' => 'You earned a new badge/achievement',
                'category' => 'gamification',
            ],
            'level_up' => [
                'label' => 'Level Up',
                'icon' => '⬆️',
                'color' => 'success',
                'description' => 'You reached a new level',
                'category' => 'gamification',
            ],
            'milestone_reached' => [
                'label' => 'Milestone Reached',
                'icon' => '🎯',
                'color' => 'success',
                'description' => 'You reached an important milestone',
                'category' => 'gamification',
            ],

            // ========== ACCOUNT & SECURITY (3 types) ==========
            'profile_updated' => [
                'label' => 'Profile Updated',
                'icon' => '✏️',
                'color' => 'info',
                'description' => 'Your profile was successfully updated',
                'category' => 'account',
            ],
            'referral_reward' => [
                'label' => 'Referral Reward',
                'icon' => '🎁',
                'color' => 'success',
                'description' => 'You received a referral bonus',
                'category' => 'account',
            ],
            'login_new_device' => [
                'label' => 'Login from New Device',
                'icon' => '🔐',
                'color' => 'warning',
                'description' => 'Your account was accessed from a new device',
                'category' => 'security',
            ],
        ];
    }

    /**
     * Get notification by type
     */
    public static function get($type) {
        $all = self::getAll();
        return $all[$type] ?? null;
    }

    /**
     * Get all notification types for a category
     */
    public static function getByCategory($category) {
        $all = self::getAll();
        $filtered = [];
        foreach ($all as $type => $data) {
            if ($data['category'] === $category) {
                $filtered[$type] = $data;
            }
        }
        return $filtered;
    }

    /**
     * Get all categories
     */
    public static function getCategories() {
        return [
            'payment' => 'Payment & Courses',
            'payout' => 'Payout & Financial',
            'content' => 'Content & Updates',
            'engagement' => 'Engagement',
            'social' => 'Social Media',
            'gamification' => 'Gamification',
            'account' => 'Account',
            'security' => 'Security',
        ];
    }

    /**
     * Validate notification type
     */
    public static function isValid($type) {
        return isset(self::getAll()[$type]);
    }
}
