<?php

/**
 * Template Name: Wallet Page
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/signin'));
    exit;
}

get_header_based_on_login();

$current_user = wp_get_current_user();

// Get current logged-in user ID (used as a fallback if no slug is provided)
$current_user_id = get_current_user_id();

// 1. Get the user slug from the query variable
$user_slug = get_query_var('user_profile');

// 2. Determine the target user
if ($user_slug) {
    // If a slug is present, try to get the user by their slug (login or nicename)
    $user = get_user_by('slug', $user_slug);
} else {
    // If no slug, fall back to the currently logged-in user
    $user = get_user_by('ID', $current_user_id);
}

// 3. Instantiate the UserProfileData class and get the profile array
if ($user) {
    // We pass the WP_User object to the class constructor, or the ID/slug depending on the class's constructor.
    // Given your original line: $profile = (new UserProfileData($user_slug))->getProfile();
    // We'll update it to pass the $user object for better data handling, assuming the class supports it.
    // If the class REQUIRES a slug, use $user_slug or $user->user_login.

    // Option A: If UserProfileData takes a WP_User object (Recommended)
    $profile_data_instance = new UserProfileData($user);

    // Option B: If UserProfileData only takes the slug (Sticking closer to your original code)
    // Use the slug if present, otherwise use the current user's login.
    $target_identifier = $user_slug ? $user_slug : $user->user_login;
    $profile_data_instance = new UserProfileData($target_identifier);

    // Get the profile array
    $profile = $profile_data_instance->getProfile();
} else {
    // Set variables to null if no user could be determined
    $user = null;
    $profile = null;
}


$balance = (float) get_user_meta($current_user_id, 'referral_commission', true);
$logs = get_user_meta($current_user_id, 'referral_logs', true);
$logs = is_array($logs) ? array_reverse($logs) : []; // latest first

$logs_per_page = 20;
$page = isset($_GET['ref_page']) ? (int) $_GET['ref_page'] : 1;
$total_pages = ceil(count($logs) / $logs_per_page);

// Slice logs for the current page
$logs_page = array_slice($logs, ($page - 1) * $logs_per_page, $logs_per_page);


$points_logs = get_user_meta($current_user_id, 'earned_points_logs', true);
$points_logs = is_array($points_logs) ? array_reverse($points_logs) : []; // latest first

$points_per_page = 20; // number of logs per page
$page = isset($_GET['points_page']) ? max(1, (int) $_GET['points_page']) : 1;
$total_points_logs = count($points_logs);
$total_pages_points = ceil($total_points_logs / $points_per_page);

// slice the array for current page
$points_logs_page = array_slice($points_logs, ($page - 1) * $points_per_page, $points_per_page);

// calculate current points (sum of all logs)
$current_points = array_sum(array_column($points_logs, 'points'));
$current_user = get_userdata($current_user_id);
$user_slug = $current_user ? $current_user->user_nicename : '';

$profile = (new UserProfileData($user_slug))->getProfile();

// echo '<pre>';
// print_r($profile); 
// echo '</pre>';

?>
<div class="container profile-page pt20">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <?php get_template_part('template-custom/auth/common-parts/editprofilemenu', null, ['profile' => $profile]); ?>
            <?php get_template_part('template-custom/auth/profile-parts/navlink', null, ['profile' => $profile]); ?>
        </div>

        <!-- Main Feed -->
        <div class="mb-0 rounded-end-0 col-lg-6">
            <?php get_template_part('template-custom/auth/wallet-parts/wallet-count', null, [
                'balance'     => $balance,
                'current_points' => $current_points,
            ]); ?>

            <?php
            $wallet_section = $_GET['wallet_section'] ?? 'how-to-earn-points';

            switch ($wallet_section) {
                case 'referral-commission':
                    get_template_part('template-custom/auth/wallet-parts/referral-commission', null, [
                        'logs'        => $logs_page ?? [],
                        'balance'     => $balance ?? 0,
                        'page'        => $page ?? 1,
                        'total_pages' => $total_pages ?? 1
                    ]);
                    break;

                case 'earned-points':
                    get_template_part('template-custom/auth/wallet-parts/earned-points', null, [
                        'points_logs'    => $points_logs_page ?? [],
                        'current_points' => $current_points ?? 0,
                        'page'           => $page ?? 1,
                        'total_pages'    => $total_pages_points ?? 1
                    ]);
                    break;

                case 'subscriptions':
                    get_template_part('template-custom/auth/wallet-parts/subscriptions');
                    break;

                default:
                    // Default wallet page
                    get_template_part('template-custom/auth/wallet-parts/how-to-earn-points');
                    break;
            }
            ?>

        </div>

        <!-- Upcoming Events -->
        <div class="rounded-start-0 col-lg-3">
            <?php get_template_part('template-custom/auth/editprofile-parts/profile-photo-form', null, ['profile' => $profile, 'user' => $user]); ?>
        </div>

    </div>
</div>
<style>
    #points-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
        padding: 15px 25px;
        border-radius: 8px;
        font-weight: bold;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        z-index: 9999;
        transform: translateY(-50px);
        opacity: 0;
        transition: transform 0.5s ease, opacity 0.5s ease;
    }

    #points-notification.show {
        transform: translateY(0);
        opacity: 1;
    }
</style>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var notify = document.getElementById("points-notification");
        if (notify) {
            // Play sound using theme's unified sound system if available
            if (typeof playNotificationSound === 'function') {
                console.log('🎵 Playing wallet notification sound via theme audio system');
                playNotificationSound();
            } else {
                // Fallback: Play sound directly
                console.log('📻 Playing wallet notification sound directly');
                var audio = new Audio("<?= get_template_directory_uri(); ?>/sounds/coin.mp3");
                audio.volume = 0.5;
                audio.play().catch(err => console.warn('⚠️ Could not play sound:', err.name));
            }

            // Animate notification
            notify.classList.add("show");

            // Hide after 3 seconds
            setTimeout(function() {
                notify.classList.remove("show");
            }, 3000);
        }
    });
</script>

<?php get_footer_based_on_login(); ?>