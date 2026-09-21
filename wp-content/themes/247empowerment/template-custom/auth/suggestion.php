<?php

/**
 * Template Name: Suggestion Template
 * Custom Suggestion Template
 */

if (!is_user_logged_in()) {
    wp_redirect(home_url('/signin'));
    exit;
}

if (isset($_POST['submit_suggestion'])) {
    $name         = sanitize_text_field($_POST['name']);
    $email        = sanitize_email($_POST['email']);
    $phone        = sanitize_text_field($_POST['phone']);
    $subject      = sanitize_text_field($_POST['subject']);
    $suggestion_type = sanitize_text_field($_POST['suggestion_type']);
    $description  = sanitize_textarea_field($_POST['description']);
    $datetime     = sanitize_text_field($_POST['datetime']);
    $page_url     = esc_url_raw($_POST['page_url']);
    $consent      = isset($_POST['consent']) ? 'Yes' : 'No';

    $attachment_url = '';
    if (!empty($_FILES['attachment']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $uploaded = media_handle_upload('attachment', 0);
        if (!is_wp_error($uploaded)) {
            $attachment_url = wp_get_attachment_url($uploaded);
        }
    }

    // Insert into WP
    $post_id = wp_insert_post([
        'post_type'   => 'suggestion_report', // Or create custom post type like 'suggestion'
        'post_title'  => $subject . ' (' . $name . ')',
        'post_status' => 'publish',
    ]);

    if ($post_id && !is_wp_error($post_id)) {
        update_post_meta($post_id, 'type', 'suggestion');
        update_post_meta($post_id, 'name', $name);
        update_post_meta($post_id, 'email', $email);
        update_post_meta($post_id, 'phone', $phone);
        update_post_meta($post_id, 'suggestion_type', $suggestion_type);
        update_post_meta($post_id, 'description', $description);
        update_post_meta($post_id, 'datetime', $datetime);
        update_post_meta($post_id, 'page_url', $page_url);
        update_post_meta($post_id, 'consent', $consent);
        update_post_meta($post_id, 'attachment_url', $attachment_url);
    }

    // Award gamification points
    if (function_exists('mm_award_points_and_notify')) {
        mm_award_points_and_notify(get_current_user_id(), 'suggestion_submitted');
        update_user_meta($user_id, 'suggestion_rewarded', 1);
    }

    // ✅ Use current URL safely
    $current_url = home_url(add_query_arg(null, null)); // get the current URL
    $redirect_url = add_query_arg('suggestion', 'success', $current_url);

    wp_redirect($redirect_url);
    exit;
}
?>
<?php

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
                        <h5 class="pb-4 text-start portal-title">Submit a Suggestion</h5>
                    </div>

                    <div class="">
                        <?php if (isset($_GET['suggestion']) && $_GET['suggestion'] === 'success') : ?>
                            <div class="alert alert-success">Thank you for your suggestion!</div>
                        <?php endif; ?>

                        <form method="post" enctype="multipart/form-data" class="row g-3" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Your Name (optional):</label>
                                <input type="text" name="name" class="form-control input" placeholder="Enter your name">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Email <span>*</span>:</label>
                                <input type="email" name="email" class="form-control input" placeholder="Enter your email" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Phone Number (optional):</label>
                                <input type="text" name="phone" class="form-control input" placeholder="Enter your phone number">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Subject / Title <span>*</span>:</label>
                                <input type="text" name="subject" class="form-control input" placeholder="Enter the title of issue" required>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Suggestion Type <span>*</span>:</label>
                                <select name="suggestion_type" class="bg-neutral-color border-0 w-auto input" required>
                                    <option value="" selected disabled>Select Type</option>
                                    <option value="Feature Request">Feature Request</option>
                                    <option value="Improvement Idea">Improvement Idea</option>
                                    <option value="Content Suggestion">Content Suggestion</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Date & Time (optional):</label>
                                <input type="datetime-local" name="datetime" class="form-control input">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Suggestion Details <span>*</span>:</label>
                                <textarea name="description" class="input" rows="5" placeholder="What's on your mind?" required></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Page URL (optional):</label>
                                <input type="url" name="page_url" class="form-control input" value="<?php echo esc_url(home_url($_SERVER['REQUEST_URI'])); ?>" placeholder="Enter the page URL">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Upload File / Screenshot (optional):</label>
                                <input type="file" name="attachment" class="form-control input" accept="image/*,.pdf,.txt">
                            </div>

                            <div class="col-12">
                                <div class="mt-2 form-check">
                                    <input class="form-check-input" type="checkbox" name="consent" id="consentCheckbox" required>
                                    <label class="form-check-label" for="consentCheckbox">
                                        I agree to the terms and allow you to contact me regarding this suggestion.
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-3 mt-3 col-12">
                                <button type="button" class="w-auto text-blue-color custom-btn-size" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="submit_suggestion" class="w-auto custom-btn">Submit Suggestion</button>
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

<?php get_footer_based_on_login(); ?>