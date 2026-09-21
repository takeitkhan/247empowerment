<?php
/**
 * Chat Manager
 * Handles chat operations and referral verification
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Chat_Manager
{
    /**
     * Check if two users can chat (referral relationship)
     * 
     * Two users can chat if:
     * 1. One user referred the other (via 'referrer' user meta)
     * 2. Both are direct referrals of each other
     * 3. They have indirect connection (optional, can be extended)
     */
    public static function can_chat($user_1_id, $user_2_id)
    {
        // Basic validation
        if (!self::user_exists($user_1_id) || !self::user_exists($user_2_id)) {
            return false;
        }

        // Cannot chat with self
        if ($user_1_id === $user_2_id) {
            return false;
        }

        // Check if there's a referral relationship
        return self::has_referral_relationship($user_1_id, $user_2_id);
    }

    /**
     * Check if there's a referral relationship between two users
     */
    private static function has_referral_relationship($user_1_id, $user_2_id)
    {
        // Get both users' data
        $user_1 = get_user_by('id', $user_1_id);
        $user_2 = get_user_by('id', $user_2_id);

        if (!$user_1 || !$user_2) {
            return false;
        }

        // Check if user_2 was referred by user_1
        $user_2_referrer = get_user_meta($user_2_id, 'referrer', true);
        
        if (!empty($user_2_referrer)) {
            // Referrer could be user ID or login name
            if ($user_2_referrer == $user_1_id || $user_2_referrer === $user_1->user_login) {
                return true;
            }
        }

        // Check if user_1 was referred by user_2
        $user_1_referrer = get_user_meta($user_1_id, 'referrer', true);
        
        if (!empty($user_1_referrer)) {
            if ($user_1_referrer == $user_2_id || $user_1_referrer === $user_2->user_login) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all users that current user can chat with (referral partners)
     */
    public static function get_chat_partners($user_id, $limit = 20)
    {
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            return [];
        }

        $partners = [];

        // Get users referred by this user
        $referred_users = self::get_referred_users($user_id);
        if (is_array($referred_users)) {
            // Filter valid WP_User objects
            $referred_users = array_filter($referred_users, function($u) {
                return $u instanceof WP_User;
            });
            $partners = array_merge($partners, array_values($referred_users));
        }

        // Get the user(s) who referred this user
        $referrer_id = get_user_meta($user_id, 'referrer', true);
        if (!empty($referrer_id)) {
            $referrer = null;
            
            // Try to get by ID first
            if (is_numeric($referrer_id)) {
                $referrer = get_user_by('id', intval($referrer_id));
            }
            
            // Try to get by login
            if (!$referrer && is_string($referrer_id)) {
                $referrer = get_user_by('login', $referrer_id);
            }
            
            if ($referrer) {
                // Check if already in partners list
                $already_added = false;
                foreach ($partners as $partner) {
                    if ($partner->ID === $referrer->ID) {
                        $already_added = true;
                        break;
                    }
                }
                
                if (!$already_added) {
                    $partners[] = $referrer;
                }
            }
        }

        // Limit results
        return array_slice($partners, 0, $limit);
    }

    /**
     * Get users referred by a user (using UserProfileData if available)
     */
    private static function get_referred_users($user_id)
    {
        $referred = [];

        // Try to use UserProfileData class if available
        if (class_exists('UserProfileData')) {
            $user = get_user_by('id', $user_id);
            if ($user) {
                $referred = UserProfileData::getReferredUsersBy($user);
                
                // Convert to WP_User objects if they're arrays
                $referred = array_map(function($u) {
                    if (is_array($u) && isset($u['id'])) {
                        return get_user_by('id', $u['id']);
                    }
                    return $u;
                }, $referred);
            }
        } else {
            // Fallback: query by meta
            $user = get_user_by('id', $user_id);
            if (!$user) {
                return [];
            }
            
            $args = [
                'meta_query' => [
                    [
                        'key'     => 'referrer',
                        'value'   => [$user_id, $user->user_login],
                        'compare' => 'IN'
                    ]
                ],
                'number' => 50,
            ];

            $referred = get_users($args);
        }

        return is_array($referred) ? $referred : [];
    }

    /**
     * Start a new conversation
     */
    public static function start_conversation($user_1_id, $user_2_id)
    {
        // Verify they can chat
        if (!self::can_chat($user_1_id, $user_2_id)) {
            return false;
        }

        // Create or get existing conversation
        return MM_Chat_Database::get_or_create_conversation($user_1_id, $user_2_id);
    }

    /**
     * Get conversation details
     */
    public static function get_conversation($conversation_id, $current_user_id)
    {
        // Get conversation
        global $wpdb;
        $table = $wpdb->prefix . 'chat_conversations';
        $conversation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $conversation_id
        ));

        if (!$conversation) {
            return false;
        }

        // Verify current user is part of this conversation
        if ($conversation->user_1_id != $current_user_id && $conversation->user_2_id != $current_user_id) {
            return false;
        }

        // Enrich with user data
        $other_user_id = ($conversation->user_1_id == $current_user_id) 
            ? $conversation->user_2_id 
            : $conversation->user_1_id;

        $other_user = get_user_by('id', $other_user_id);

        if (!$other_user) {
            return false;
        }

        return [
            'id' => $conversation->id,
            'other_user_id' => $other_user_id,
            'other_user' => [
                'id' => $other_user->ID,
                'name' => $other_user->display_name,
                'email' => $other_user->user_email,
                'username' => $other_user->user_login,
                'avatar' => get_user_meta($other_user->ID, 'profile_photo', true),
            ],
            'created_at' => $conversation->created_at,
            'updated_at' => $conversation->updated_at,
        ];
    }

    /**
     * Check if user exists
     */
    private static function user_exists($user_id)
    {
        return get_user_by('id', $user_id) !== false;
    }

    /**
     * Get referral info for display
     */
    public static function get_referral_info($user_id)
    {
        $referrer_id = get_user_meta($user_id, 'referrer', true);
        $referred_users = self::get_referred_users($user_id);

        return [
            'referrer_id' => $referrer_id,
            'referred_count' => count($referred_users),
            'referred_users' => $referred_users,
        ];
    }
}
