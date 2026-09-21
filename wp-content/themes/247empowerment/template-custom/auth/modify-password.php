<?php
/* Template Name: Change Password */
ob_start(); // Start output buffering
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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!isset($_POST['change_password_nonce']) || !wp_verify_nonce($_POST['change_password_nonce'], 'change_password_action')) {
        echo '<div class="alert alert-danger">Security check failed.</div>';
    } else {
        $new_password = sanitize_text_field($_POST['new_password']);
        $confirm_password = sanitize_text_field($_POST['confirm_password']);

        if (empty($new_password) || empty($confirm_password)) {
            echo '<div class="alert alert-danger">All fields are required.</div>';
        } elseif ($new_password !== $confirm_password) {
            echo '<div class="alert alert-danger">Passwords do not match.</div>';
        } else {
            wp_set_password($new_password, $current_user->ID);
            wp_logout();
            wp_redirect(wp_login_url());
            exit;
        }
    }
}
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
                    <div class="mb-3">
                        <h5 class="pb-4 text-start portal-title">Modify Password</h5>
                        <span class="text-danger fs15 fw-bold">You will be logged out immediately once you change your password.</span>
                    </div>

                    <div>
                        <form method="post" class="row g-3">
                            <?php wp_nonce_field('change_password_action', 'change_password_nonce'); ?>

                            <!-- New Password -->
                            <div class="position-relative col-12">
                                <label class="form-label">New Password</label>
                                <div class="position-relative">
                                    <input type="password" name="new_password" class="pe-5 form-control input" id="newPassword" placeholder="Enter new password" required>
                                    <i class="top-50 position-absolute me-3 translate-middle-y bi bi-eye-slash end-0 toggle-password" data-target="newPassword" style="cursor: pointer;"></i>
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="position-relative col-12">
                                <label class="form-label">Confirm Password</label>
                                <div class="position-relative">
                                    <input type="password" name="confirm_password" class="pe-5 form-control input" id="confirmPassword" placeholder="Enter confirm password" required>
                                    <i class="top-50 position-absolute me-3 translate-middle-y bi bi-eye-slash end-0 toggle-password" data-target="confirmPassword" style="cursor: pointer;"></i>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-3 mt-3 col-12">
                                <button type="submit" name="change_password" class="w-auto custom-btn">Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="rounded-start-0 col-lg-3">
            <?php get_template_part('template-custom/auth/editprofile-parts/profile-photo-form', null, ['profile' => $profile, 'user' => $user]); ?>
        </div>
    </div>
</div>


<script>
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', function() {
            const target = document.getElementById(this.dataset.target);
            const type = target.getAttribute('type') === 'password' ? 'text' : 'password';
            target.setAttribute('type', type);
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
    });
</script>
<?php ob_end_flush(); ?>
<?php get_footer_based_on_login(); ?>