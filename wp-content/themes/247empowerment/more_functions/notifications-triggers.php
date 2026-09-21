<?php
/**
 * Notification Triggers - Automatic notification system for user actions
 * Hooks into key events throughout the theme to send notifications
 */

if (!defined('ABSPATH')) exit;

// Ensure required classes are loaded
if (!class_exists('NotificationManager')) {
    require_once get_template_directory() . '/inc/NotificationManager.php';
}

// ========== USER REGISTRATION & SECURITY ==========

/**
 * Send welcome notification to new user on registration
 * Hook: user_register (WordPress standard hook)
 * Priority: 100 to ensure it runs after other user_register handlers
 */
add_action('user_register', function($user_id) {
    if (!$user_id || !class_exists('NotificationManager')) return;
    
    $user = get_userdata($user_id);
    if (!$user) return;
    
    try {
        NotificationManager::getInstance()->add(
            $user_id,
            'profile_updated',
            'Welcome to 247 Empowerment! Your account has been created successfully. Start exploring opportunities today.',
            [
                'action_url' => get_home_url() . '/portal',
                'action_label' => 'View Profile',
                'metadata' => ['welcome' => true]
            ]
        );
    } catch (Exception $e) {
        error_log('Notification error on user_register welcome: ' . $e->getMessage());
    }
}, 100);

/**
 * Send admin alert on new user registration
 * Hook: user_register (WordPress standard hook)
 * Single notification visible to all admins using admin group (user_id = 0)
 */
add_action('user_register', function($user_id) {
    if (!$user_id || !class_exists('NotificationManager')) return;
    
    $user = get_userdata($user_id);
    if (!$user) return;
    
    try {
        $user_name = !empty($user->display_name) ? $user->display_name : $user->user_email;
        $user_email = $user->user_email;
        
        error_log("Sending admin group notification for new user signup: {$user_id} ({$user_name})");
        
        // Send ONE notification to admin group (visible to all admins)
        NotificationManager::getInstance()->addForAdmins(
            'new_follower',
            "New user has signed up: {$user_name} ({$user_email})",
            [
                'action_url' => get_admin_url() . "user-edit.php?user_id={$user_id}",
                'action_label' => 'View User',
                'metadata' => ['admin_alert' => true, 'new_user_id' => $user_id]
            ]
        );
    } catch (Exception $e) {
        error_log('Notification error on user_register admin alert: ' . $e->getMessage());
    }
}, 100);

/**
 * Fallback: Also listen to custom mm_user_register hook (if fired by authentication.php)
 * This ensures compatibility with the theme's custom registration flow
 */
add_action('mm_user_register', function($user_id) {
    if (!$user_id || !class_exists('NotificationManager')) return;
    
    $user = get_userdata($user_id);
    if (!$user) return;
    
    try {
        // Welcome notification
        NotificationManager::getInstance()->add(
            $user_id,
            'profile_updated',
            'Welcome to 247 Empowerment! Your account has been created successfully. Start exploring opportunities today.',
            [
                'action_url' => get_home_url() . '/portal',
                'action_label' => 'View Profile',
                'metadata' => ['welcome' => true]
            ]
        );
        
        // Admin notifications
        $admins = get_users(['role' => 'administrator', 'number' => -1]);
        if (!empty($admins)) {
            $user_name = !empty($user->display_name) ? $user->display_name : $user->user_email;
            $user_email = $user->user_email;
            
            error_log("mm_user_register: Sending admin notifications for new user: {$user_id}");
            
            foreach ($admins as $admin) {
                NotificationManager::getInstance()->add(
                    $admin->ID,
                    'new_follower',
                    "New user has signed up: {$user_name} ({$user_email})",
                    [
                        'action_url' => get_admin_url() . "user-edit.php?user_id={$user_id}",
                        'action_label' => 'View User',
                        'metadata' => ['admin_alert' => true, 'new_user_id' => $user_id]
                    ]
                );
            }
        }
    } catch (Exception $e) {
        error_log('Notification error on mm_user_register: ' . $e->getMessage());
    }
}, 10);

// ========== PAYMENT & COURSE PURCHASES ==========

/**
 * Send subscription active notification
 * Hook: mm_subscription_activated (from PayPal webhook)
 */
add_action('mm_subscription_activated', function($user_id, $sub_id, $event) {
    if (!$user_id) return;
    
    $subs = get_user_meta($user_id, 'active_subscriptions', true);
    if (!is_array($subs) || !isset($subs[$sub_id])) return;
    
    $course_id = $subs[$sub_id]['course_id'];
    $course = get_post($course_id);
    $course_title = $course ? $course->post_title : 'Course';
    
    NotificationManager::getInstance()->add(
        $user_id,
        'subscription_active',
        "Your subscription to {$course_title} is now active. Access all course content.",
        [
            'action_url' => get_permalink($course_id),
            'action_label' => 'View Course',
            'metadata' => ['subscription_id' => $sub_id, 'course_id' => $course_id]
        ]
    );
}, 10, 3);

/**
 * Send subscription status changed notification
 * Hook: mm_subscription_status_changed (from PayPal webhook)
 */
add_action('mm_subscription_status_changed', function($user_id, $sub_id, $status, $event) {
    if (!$user_id) return;
    
    $subs = get_user_meta($user_id, 'active_subscriptions', true);
    if (!is_array($subs) || !isset($subs[$sub_id])) return;
    
    $course_id = $subs[$sub_id]['course_id'];
    $course = get_post($course_id);
    $course_title = $course ? $course->post_title : 'Course';
    
    $status_messages = [
        'CANCELLED' => 'Your subscription to ' . $course_title . ' has been cancelled.',
        'EXPIRED' => 'Your subscription to ' . $course_title . ' has expired. Renew to continue access.',
        'SUSPENDED' => 'Your subscription to ' . $course_title . ' has been suspended.'
    ];
    
    $message = $status_messages[$status] ?? 'Your subscription status has changed.';
    
    NotificationManager::getInstance()->add(
        $user_id,
        $status === 'CANCELLED' ? 'subscription_cancelled' : 'subscription_renewal_upcoming',
        $message,
        [
            'action_url' => get_permalink($course_id),
            'action_label' => 'View Course',
            'metadata' => ['subscription_id' => $sub_id, 'course_id' => $course_id, 'status' => $status]
        ]
    );
}, 10, 4);

/**
 * Send subscription renewal notification
 * Hook: mm_subscription_renewed (from PayPal webhook on PAYMENT.SALE.COMPLETED)
 */
add_action('mm_subscription_renewed', function($user_id, $sub_id, $resource) {
    if (!$user_id) return;
    
    $subs = get_user_meta($user_id, 'active_subscriptions', true);
    if (!is_array($subs) || !isset($subs[$sub_id])) return;
    
    $course_id = $subs[$sub_id]['course_id'];
    $course = get_post($course_id);
    $course_title = $course ? $course->post_title : 'Course';
    $amount = isset($resource['amount']['total']) ? $resource['amount']['total'] : 'N/A';
    
    NotificationManager::getInstance()->add(
        $user_id,
        'course_purchase_success',
        "Your subscription to {$course_title} has been renewed for \${$amount}.",
        [
            'action_url' => get_permalink($course_id),
            'action_label' => 'View Course',
            'metadata' => ['subscription_id' => $sub_id, 'course_id' => $course_id, 'amount' => $amount, 'txn_id' => $resource['id'] ?? null]
        ]
    );
}, 10, 3);

// ========== WITHDRAWAL & PAYOUT ==========

/**
 * Send withdrawal submitted notification
 * Hook: payout_withdrawal_requested (from PayoutSystem.php)
 */
add_action('payout_withdrawal_requested', function($user_id, $withdrawal_id, $amount, $paypal_email) {
    if (!$user_id) return;
    
    NotificationManager::getInstance()->add(
        $user_id,
        'withdrawal_submitted',
        "Your withdrawal request for \${$amount} has been submitted and is pending admin approval.",
        [
            'action_url' => get_home_url() . '/portal/wallet',
            'action_label' => 'View Status',
            'metadata' => ['withdrawal_id' => $withdrawal_id, 'amount' => $amount, 'paypal_email' => $paypal_email]
        ]
    );
}, 10, 4);

/**
 * Send withdrawal approved notification
 * Hook: mm_withdrawal_approved (added to PayoutSystem.php)
 */
add_action('mm_withdrawal_approved', function($user_id, $withdrawal_id, $amount) {
    if (!$user_id) return;
    
    NotificationManager::getInstance()->add(
        $user_id,
        'withdrawal_approved',
        "Your withdrawal request for \${$amount} has been approved! Processing will begin shortly.",
        [
            'action_url' => get_home_url() . '/portal/wallet',
            'action_label' => 'View Details',
            'metadata' => ['withdrawal_id' => $withdrawal_id, 'amount' => $amount]
        ]
    );
}, 10, 3);

/**
 * Send withdrawal rejected notification
 * Hook: mm_withdrawal_rejected (added to PayoutSystem.php)
 */
add_action('mm_withdrawal_rejected', function($user_id, $withdrawal_id, $reason) {
    if (!$user_id) return;
    
    NotificationManager::getInstance()->add(
        $user_id,
        'withdrawal_rejected',
        "Your withdrawal request has been rejected. Reason: {$reason}",
        [
            'action_url' => get_home_url() . '/portal/wallet',
            'action_label' => 'View Details',
            'metadata' => ['withdrawal_id' => $withdrawal_id, 'rejection_reason' => $reason]
        ]
    );
}, 10, 3);

/**
 * Send withdrawal paid notification
 * Hook: mm_withdrawal_paid
 */
add_action('mm_withdrawal_paid', function($user_id, $withdrawal_id, $amount) {
    if (!$user_id) return;
    
    NotificationManager::getInstance()->add(
        $user_id,
        'withdrawal_paid',
        "Your withdrawal of \${$amount} has been transferred to your PayPal account!",
        [
            'action_url' => get_home_url() . '/portal/wallet',
            'action_label' => 'View History',
            'metadata' => ['withdrawal_id' => $withdrawal_id, 'amount' => $amount, 'paid_date' => current_time('mysql')]
        ]
    );
}, 10, 3);

/**
 * Send withdrawal failed notification
 * Hook: mm_withdrawal_failed
 */
add_action('mm_withdrawal_failed', function($user_id, $withdrawal_id, $error) {
    if (!$user_id) return;
    
    NotificationManager::getInstance()->add(
        $user_id,
        'withdrawal_failed',
        "Your withdrawal could not be processed. Error: {$error}. Please contact support.",
        [
            'action_url' => get_home_url() . '/portal/wallet',
            'action_label' => 'Contact Support',
            'metadata' => ['withdrawal_id' => $withdrawal_id, 'error' => $error]
        ]
    );
}, 10, 3);

/**
 * Send balance updated notification
 * Hook: mm_balance_updated
 */
add_action('mm_balance_updated', function($user_id, $old_balance, $new_balance, $change_reason) {
    if (!$user_id) return;
    
    $difference = $new_balance - $old_balance;
    $symbol = $difference >= 0 ? '+' : '';
    
    NotificationManager::getInstance()->add(
        $user_id,
        'balance_updated',
        "Your balance has been updated. Change: {$symbol}\${$difference}. Reason: {$change_reason}",
        [
            'action_url' => get_home_url() . '/portal/wallet',
            'action_label' => 'View Wallet',
            'metadata' => ['old_balance' => $old_balance, 'new_balance' => $new_balance, 'change_reason' => $change_reason]
        ]
    );
}, 10, 4);

// ========== CONTENT PUBLISHING ==========

/**
 * Send notification when new blog post published
 * Hook: publish_post WordPress hook
 */
add_action('publish_post', function($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'post') return;
    
    $categories = get_the_category($post_id);
    if (empty($categories)) return;
    
    $category_ids = wp_list_pluck($categories, 'term_id');
    $users = get_users(['meta_key' => 'followed_categories', 'meta_compare' => 'EXISTS']);
    
    foreach ($users as $user) {
        $followed_cats = get_user_meta($user->ID, 'followed_categories', true);
        if (!is_array($followed_cats)) continue;
        
        if (array_intersect($followed_cats, $category_ids)) {
            NotificationManager::getInstance()->add(
                $user->ID,
                'blog_post_published',
                "New Blog Post: {$post->post_title} - " . wp_trim_words($post->post_excerpt ?: $post->post_content, 20),
                [
                    'action_url' => get_permalink($post_id),
                    'action_label' => 'Read Post',
                    'metadata' => ['post_id' => $post_id, 'category_ids' => $category_ids]
                ]
            );
        }
    }
});

/**
 * Send notification when new video playlist added
 * Hook: publish_video WordPress hook
 */
add_action('publish_video', function($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'video') return;
    
    $all_users = get_users(['exclude' => get_post_field('post_author', $post_id)]);
    
    foreach ($all_users as $user) {
        NotificationManager::getInstance()->add(
            $user->ID,
            'video_playlist_added',
            "New Video: {$post->post_title} - Check out this new video playlist added to our library.",
            [
                'action_url' => get_permalink($post_id),
                'action_label' => 'Watch Video',
                'metadata' => ['post_id' => $post_id]
            ]
        );
    }
});

/**
 * Send notification when new job posted
 * Hook: publish_job WordPress hook
 */
add_action('publish_job', function($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'job') return;
    
    $job_followers = get_users(['meta_key' => 'interested_in_jobs', 'meta_value' => '1']);
    
    foreach ($job_followers as $user) {
        NotificationManager::getInstance()->add(
            $user->ID,
            'job_posted',
            "New Job Opportunity: {$post->post_title} - A new job matching your interests has been posted.",
            [
                'action_url' => get_permalink($post_id),
                'action_label' => 'View Job',
                'metadata' => ['post_id' => $post_id]
            ]
        );
    }
});

/**
 * Send notification when new event created
 * Hook: publish_event WordPress hook
 */
add_action('publish_event', function($post_id) {
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'event') return;
    
    $all_users = get_users();
    
    foreach ($all_users as $user) {
        NotificationManager::getInstance()->add(
            $user->ID,
            'event_created',
            "New Event: {$post->post_title} - A new event has been created. Don't miss out!",
            [
                'action_url' => get_permalink($post_id),
                'action_label' => 'View Event',
                'metadata' => ['post_id' => $post_id]
            ]
        );
    }
});

// ========== SOCIAL MEDIA ACTIONS ==========

/**
 * Send notification when user connects Facebook
 * Hook: mm_facebook_connected
 */
add_action('mm_facebook_connected', function($user_id, $facebook_id) {
    if (!$user_id) return;
    
    NotificationManager::getInstance()->add(
        $user_id,
        'facebook_connected',
        "Your Facebook account has been linked. You can now share content to Facebook.",
        [
            'action_url' => get_home_url() . '/portal/social-settings',
            'action_label' => 'Manage Social',
            'metadata' => ['facebook_id' => $facebook_id]
        ]
    );
});

/**
 * Send notification when user connects LinkedIn
 * Hook: mm_linkedin_connected
 */
add_action('mm_linkedin_connected', function($user_id, $linkedin_id) {
    if (!$user_id) return;
    
    NotificationManager::getInstance()->add(
        $user_id,
        'linkedin_connected',
        "Your LinkedIn account has been linked. You can now share content to LinkedIn.",
        [
            'action_url' => get_home_url() . '/portal/social-settings',
            'action_label' => 'Manage Social',
            'metadata' => ['linkedin_id' => $linkedin_id]
        ]
    );
});

/**
 * Send notification when post is shared to social media
 * Hook: mm_post_shared_to_social
 */
add_action('mm_post_shared_to_social', function($user_id, $post_id, $platform) {
    if (!$user_id) return;
    
    $post = get_post($post_id);
    $platform_label = ucfirst($platform);
    
    NotificationManager::getInstance()->add(
        $user_id,
        'social_post_shared',
        "Your post '{$post->post_title}' has been successfully shared to {$platform_label}.",
        [
            'action_url' => get_permalink($post_id),
            'action_label' => 'View Post',
            'metadata' => ['post_id' => $post_id, 'platform' => $platform]
        ]
    );
});

/**
 * Send notification when social account is disconnected
 * Hook: mm_social_account_disconnected
 */
add_action('mm_social_account_disconnected', function($user_id, $platform) {
    if (!$user_id) return;
    
    $platform_label = ucfirst($platform);
    
    NotificationManager::getInstance()->add(
        $user_id,
        'social_account_disconnected',
        "Your {$platform_label} account has been successfully disconnected.",
        [
            'action_url' => get_home_url() . '/portal/social-settings',
            'action_label' => 'Reconnect',
            'metadata' => ['platform' => $platform]
        ]
    );
});

// ========== GAMIFICATION & ACHIEVEMENTS ==========

/**
 * Send notification when achievement unlocked
 * Hook: mm_achievement_unlocked
 */
add_action('mm_achievement_unlocked', function($user_id, $achievement_id, $achievement_name) {
    if (!$user_id) return;
    
    NotificationManager::getInstance()->add(
        $user_id,
        'achievement_unlocked',
        "Congratulations! You've unlocked a new achievement: {$achievement_name}",
        [
            'action_url' => get_home_url() . '/portal/achievements',
            'action_label' => 'View Achievements',
            'metadata' => ['achievement_id' => $achievement_id, 'achievement_name' => $achievement_name]
        ]
    );
});

/**
 * Send notification when user levels up
 * Hook: mm_user_leveled_up
 */
add_action('mm_user_leveled_up', function($user_id, $new_level, $previous_level) {
    if (!$user_id) return;
    
    NotificationManager::getInstance()->add(
        $user_id,
        'level_up',
        "Great progress! You've advanced to Level {$new_level}. Keep up the great work!",
        [
            'action_url' => get_home_url() . '/portal/profile',
            'action_label' => 'View Profile',
            'metadata' => ['new_level' => $new_level, 'previous_level' => $previous_level]
        ]
    );
});

/**
 * Send notification when milestone is reached
 * Hook: mm_milestone_reached
 */
add_action('mm_milestone_reached', function($user_id, $milestone_name, $milestone_value) {
    if (!$user_id) return;
    
    NotificationManager::getInstance()->add(
        $user_id,
        'milestone_reached',
        "Excellent! You've reached the {$milestone_name} milestone ({$milestone_value}).",
        [
            'action_url' => get_home_url() . '/portal/profile',
            'action_label' => 'View Stats',
            'metadata' => ['milestone_name' => $milestone_name, 'milestone_value' => $milestone_value]
        ]
    );
});

/**
 * Send referral reward notification
 * Hook: mm_referral_reward_earned
 */
add_action('mm_referral_reward_earned', function($user_id, $referred_user_id, $reward_amount) {
    if (!$user_id) return;
    
    $referred_user = get_userdata($referred_user_id);
    $referrer_name = $referred_user ? $referred_user->display_name : "Someone";
    
    NotificationManager::getInstance()->add(
        $user_id,
        'referral_reward',
        "You earned \${$reward_amount} for referring {$referrer_name}!",
        [
            'action_url' => get_home_url() . '/portal/referrals',
            'action_label' => 'View Referrals',
            'metadata' => ['referred_user_id' => $referred_user_id, 'reward_amount' => $reward_amount]
        ]
    );
});

// ========== SOCIAL ENGAGEMENT ==========

/**
 * Send notification when someone comments on user's post
 * Hook: comment_post WordPress hook
 */
add_action('comment_post', function($comment_id, $comment_approved) {
    if ($comment_approved !== 1) return;
    
    $comment = get_comment($comment_id);
    $post = get_post($comment->comment_post_ID);
    $post_author_id = $post->post_author;
    
    if ($comment->user_id == $post_author_id) return;
    
    $commenter_name = $comment->comment_author;
    
    NotificationManager::getInstance()->add(
        $post_author_id,
        'comment_on_post',
        "{$commenter_name} commented: " . wp_trim_words($comment->comment_content, 20),
        [
            'action_url' => get_comment_link($comment_id),
            'action_label' => 'View Comment',
            'metadata' => ['comment_id' => $comment_id, 'post_id' => $comment->comment_post_ID]
        ]
    );
}, 10, 2);

/**
 * Send notification when someone follows/connects with user
 * Hook: mm_new_connection
 */
add_action('mm_new_connection', function($follower_id, $following_id) {
    if (!$following_id) return;
    
    $follower = get_userdata($follower_id);
    $follower_name = $follower ? $follower->display_name : "Someone";
    
    NotificationManager::getInstance()->add(
        $following_id,
        'new_follower',
        "{$follower_name} started following you!",
        [
            'action_url' => get_author_posts_url($follower_id),
            'action_label' => 'View Profile',
            'metadata' => ['follower_id' => $follower_id]
        ]
    );
});

// ========== SECURITY NOTIFICATIONS ==========

/**
 * Send notification on login from new device
 * Hook: wp_login WordPress hook
 */
add_action('wp_login', function($user_login, $user) {
    if (!$user || !$user->ID) return;
    
    $last_login_ips = get_user_meta($user->ID, 'last_login_ips', true);
    if (!is_array($last_login_ips)) {
        $last_login_ips = [];
    }
    
    $current_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
    
    if ($current_ip && !in_array($current_ip, $last_login_ips)) {
        $last_login_ips[] = $current_ip;
        update_user_meta($user->ID, 'last_login_ips', $last_login_ips);
        
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : 'Unknown Device';
        
        NotificationManager::getInstance()->add(
            $user->ID,
            'login_new_device',
            "Your account was accessed from a new device: {$user_agent}. If this wasn't you, change your password.",
            [
                'action_url' => get_home_url() . '/portal/security',
                'action_label' => 'Manage Security',
                'metadata' => ['ip_address' => $current_ip, 'user_agent' => $user_agent, 'login_time' => current_time('mysql')]
            ]
        );
    }
}, 10, 2);

/**
 * Send notification on profile update
 * Hook: profile_update WordPress hook
 */
add_action('profile_update', function($user_id, $old_user_data) {
    if (!$user_id || !class_exists('NotificationManager')) return;
    
    $user = get_userdata($user_id);
    if (!$user) return;
    
    try {
        NotificationManager::getInstance()->add(
            $user_id,
            'profile_updated',
            "Your profile information has been updated successfully.",
            [
                'action_url' => get_home_url() . '/portal/profile',
                'action_label' => 'View Profile',
                'metadata' => ['user_id' => $user_id, 'update_time' => current_time('mysql')]
            ]
        );
    } catch (Exception $e) {
        error_log('Notification error on profile_update: ' . $e->getMessage());
    }
}, 10, 2);
