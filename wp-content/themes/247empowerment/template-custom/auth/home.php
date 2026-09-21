<?php
/**
 * Template Name: Logged In Front Page
 */
get_header_based_on_login();
// Get current logged-in user ID (used as a fallback if no slug is provided)
$current_user_id = get_current_user_id();
$leaderboard_api_nonce = '';
$leaderboard_api_root = esc_url_raw(rest_url('api/v1/spg'));

if ($current_user_id) {
    $leaderboard_api_nonce = (string) get_user_meta($current_user_id, 'mm_spg_api_nonce', true);
    $leaderboard_api_nonce_time = (int) get_user_meta($current_user_id, 'mm_spg_api_nonce_time', true);

    if ($leaderboard_api_nonce === '' || !$leaderboard_api_nonce_time || (time() - $leaderboard_api_nonce_time) > 86400) {
        $leaderboard_api_nonce = wp_hash($current_user_id . time() . wp_rand(), 'nonce');
        update_user_meta($current_user_id, 'mm_spg_api_nonce', $leaderboard_api_nonce);
        update_user_meta($current_user_id, 'mm_spg_api_nonce_time', time());
    }
}

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
    // Pass the WP_User object directly so UserProfileData always resolves
    // correctly regardless of nicename/login differences.
    $profile_data_instance = new UserProfileData($user);
    $profile = $profile_data_instance->getProfile();

    // Final safety: ensure $profile is always an array.
    if (!is_array($profile)) {
        $profile = [];
    }
} else {
    $user    = null;
    $profile = [];
}
?>

<div class="container profile-page pt20">
    <div class="row">
        <div class="col-lg-3">
            <?php get_template_part('template-custom/auth/feed-parts/profile-card', null, ['profile' => $profile]); ?>
            <?php get_template_part('template-custom/auth/profile-parts/navlink', null, ['profile' => $profile]); ?>
        </div>
        <div class="mb-4 col-lg-9">
            <?php
                // echo '<pre>';
                // print_r($profile);
                // echo '</pre>';
            ?>
            <?php get_template_part('template-custom/auth/feed-parts/create-post', null, ['profile' => $profile]); ?>
            <?php get_template_part('template-custom/auth/feed-parts/feeds', null, ['profile' => $profile]); ?>
        </div>
        <!-- <div class="col-lg-3">
            <?php
            // get_template_part('template-custom/auth/feed-parts/upcoming', null, [
            //     'profile'               => $profile,
            //     'leaderboard_api_nonce' => $leaderboard_api_nonce,
            //     'leaderboard_api_root'  => $leaderboard_api_root,
            // ]);
            ?>
        </div> -->
    </div>
</div>
<?php get_footer_based_on_login(); ?>
