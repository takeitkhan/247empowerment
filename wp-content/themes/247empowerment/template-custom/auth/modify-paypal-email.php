<?php
/* Template Name: Modify PayPal Email */
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

$current_user = wp_get_current_user();

/**
 * ✅ HANDLE POST FIRST — NO OUTPUT ABOVE THIS
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_paypal_email'])) {
    if (
        !isset($_POST['frontend_paypal_nonce']) ||
        !wp_verify_nonce($_POST['frontend_paypal_nonce'], 'frontend_paypal_update')
    ) {
        wp_die('Security check failed');
    }

    $paypal_email = sanitize_email($_POST['paypal_email']);
    
    if (!is_email($paypal_email)) {
        wp_die('Invalid PayPal email address');
    }

    update_user_meta(get_current_user_id(), 'paypal_email', $paypal_email);

    // ✅ Redirect safely
    wp_safe_redirect(add_query_arg('updated', '1', wp_get_referer() ?: get_permalink()));
    exit;
}
/**
 * ✅ NOW SAFE TO OUTPUT HTML
 */
get_header_based_on_login();

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
    $profile_data_instance = new UserProfileData($user);
    $target_identifier = $user_slug ? $user_slug : $user->user_login;
    $profile_data_instance = new UserProfileData($target_identifier);
    $profile = $profile_data_instance->getProfile();
} else {
    $user = null;
    $profile = null;
}

$profile = $profile ?: [];

// Get saved PayPal email
$paypal_email = get_user_meta($current_user_id, 'paypal_email', true);

// Check if page was updated
$updated = isset($_GET['updated']) ? sanitize_text_field($_GET['updated']) : false;
?>

<div class="container profile-page pt20">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <?php get_template_part('template-custom/auth/common-parts/editprofilemenu', null, ['profile' => $profile]); ?>
            <?php get_template_part('template-custom/auth/profile-parts/navlink', null, ['profile' => $profile]); ?>
        </div>
        <div class="mb-0 rounded-end-0 col-lg-6">
            <div class="bg-white custom-card post-search">
                <div class="gap-3 post-row">
                    <div>
                        <h5 class="pb-4 text-start portal-title">PayPal Email</h5>
                    </div>

                    <?php if ($updated): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            PayPal email updated successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div>
                        <form method="post" id="frontend-paypal-form" class="row g-3">
                            <?php wp_nonce_field('frontend_paypal_update', 'frontend_paypal_nonce'); ?>

                            <div class="col-12">
                                <label class="form-label">PayPal Email Address:</label>
                                <input 
                                    type="email" 
                                    name="paypal_email" 
                                    class="form-control input"
                                    value="<?php echo esc_attr($paypal_email); ?>" 
                                    placeholder="Enter your PayPal email address" 
                                    required>
                                <small class="form-text text-muted">
                                    This email will be used for all withdrawal requests. Make sure it matches your PayPal account email.
                                </small>
                            </div>

                            <div class="col-12">
                                <button type="submit" name="update_paypal_email" class="w-auto custom-btn">
                                    Save PayPal Email
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="mt-4">
                        <h6 class="text-muted">Important:</h6>
                        <ul class="small">
                            <li>Your PayPal email is used for all withdrawal requests.</li>
                            <li>Ensure it matches your active PayPal account email.</li>
                            <li>Update this whenever you change your PayPal email address.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer_based_on_login(); ?>
