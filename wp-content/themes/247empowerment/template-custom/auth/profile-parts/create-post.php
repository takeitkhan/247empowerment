<?php
/**
 * Create Post Component - Using Phase 1 Redesigned Modal (Organized Structure)
 * Features: Modern UI, Character Counter, Privacy Selector, Image Preview, Emoji Picker
 * Updated: Supports posting on friends' profiles
 */

// Get profile if passed as argument
$profile = $args['profile'] ?? null;

// Only show create-post if:
// 1. User is posting on their own profile, OR
// 2. User is a friend/referral partner of the profile owner
if ($profile) {
    $current_user_id = get_current_user_id();
    $profile_user_id = $profile['id'] ?? 0;
    
    $can_post_on_profile = false;
    
    // Check if posting on own profile
    if ($current_user_id === (int)$profile_user_id) {
        $can_post_on_profile = true;
    } else {
        // Check if current user is a referral partner (friend) of the profile owner
        $profile_instance = new UserProfileData($profile_user_id);
        $referral_partners = $profile_instance->getReferredUsers();
        foreach ($referral_partners as $partner) {
            if ($partner->ID === $current_user_id) {
                $can_post_on_profile = true;
                break;
            }
        }
    }
    
    // Only show modal if user can post on this profile
    if (!$can_post_on_profile) {
        return; // Don't display create-post form
    }
}

// Include the reorganized modal template and pass profile info
get_template_part('template-custom/auth/profile-parts/create-post-redesigned-v2', null, ['profile' => $profile]);
?>