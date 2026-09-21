<?php
/* Template Name: Social Media Settings */
get_header_based_on_login();

if (!is_user_logged_in()) {
    wp_redirect(home_url('/signin'));
    exit;
}

$current_user = wp_get_current_user();
$user_slug = get_query_var('user_profile');
$user = $user_slug ? get_user_by('slug', $user_slug) : $current_user;
$profile = $user ? (new UserProfileData($user))->getProfile() : null;
?>
<div class="container profile-page pt20">
    <div class="row">
        <div class="order-3 order-lg-1 col-lg-3">
            <?php get_template_part('template-custom/auth/common-parts/editprofilemenu', null, ['profile' => $profile]); ?>
            <?php get_template_part('template-custom/auth/profile-parts/navlink', null, ['profile' => $profile]); ?>
        </div>
        <div class="order-2 order-lg-2 mb-0 rounded-end-0 col-lg-6">
            <div class="bg-white custom-box-shadow mb-3 p-3 custom-border-radius">
                <div class="row">
                    <div class="col-12">
                        <h5 class="mb-5">Social Management</h5>
                    </div>
                </div>
                <?php
                // Display page content if available, otherwise show the connection shortcode
                if (have_posts()) {
                    while (have_posts()) {
                        the_post();
                        the_content();
                    }
                } else {
                    // Fallback: display connection cards shortcode
                    echo do_shortcode('[mm_social_connect]');
                }
                ?>
            </div>
        </div>
        <div class="order-1 order-lg-3 rounded-start-0 col-lg-3">
            <?php get_template_part('template-custom/auth/editprofile-parts/profile-photo-form', null, ['profile' => $profile, 'user' => $user]); ?>
        </div>
    </div>
</div>

<?php get_footer_based_on_login(); ?>
