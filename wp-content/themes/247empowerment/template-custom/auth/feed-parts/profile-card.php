<?php
// Ensure $profile is available, assuming it is passed via $args['profile']
$profile = isset($args['profile']) ? $args['profile'] : [];

// --- Data Preparation ---

// 1. Determine Full Name
$first_name = isset($profile['first_name']) ? $profile['first_name'] : '';
$last_name = isset($profile['last_name']) ? $profile['last_name'] : '';
$full_name = trim($first_name . ' ' . $last_name);
if (empty($full_name)) {
    $full_name = isset($profile['display_name']) ? $profile['display_name'] : 'User Profile';
}

// 2. Determine Profile Photo URL
$default_profile_img = get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg';
$profile_photo_url = !empty($profile['profile_photo']) ? $profile['profile_photo'] : $default_profile_img;

// 3. Determine Location Text (Using 'location' from your provided array dump)
$location_text = !empty($profile['location']) ? $profile['location'] : 'Location not set';

// 4. Determine Referral Count (Assuming 'referred_users_count' exists in the full profile data)
$referral_count = isset($profile['referred_users_count']) ? (int)$profile['referred_users_count'] : 0;
?>

<div class="post-left bg-white custom-card">
    <div class="">
        <div class="d-flex align-items-center gap-3 pb-3">
            <div class="position-relative img44">
                <!-- Dynamic Profile Photo -->
                <img
                    src="<?php echo esc_url($profile_photo_url); ?>"
                    class="rounded-circle w-100 h-100 object-fit-cover"
                    alt="<?php echo esc_attr($full_name); ?> Profile">
                <!-- Static active icon -->
                <img
                    class="position-absolute active-icon"
                    src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/active_icon.png"
                    alt="Active Status">
            </div>
            <div class="d-flex flex-column gap-1 post-user">
                <!-- Dynamic Full Name -->
                <span class="p_name"><?php echo esc_html($full_name); ?></span>                
                <p class="n-text">Los Angeles, CA<?php //echo esc_html($location_text); ?></p>
            </div>
        </div>
        <!-- Dynamic Referral Count -->
        <p class='ps-2 pt-4 referal'>
            <span><?php echo esc_html($referral_count); ?></span> referral partners
        </p>
    </div>
</div>