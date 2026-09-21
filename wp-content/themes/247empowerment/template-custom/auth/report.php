<?php

/**
 * Template Name: Report Template
 * Custom Report Template
 */
if (!is_user_logged_in()) {
    wp_redirect(home_url('/signin'));
    exit;
}
/* ------------------ FORM PROCESSING SECTION ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_report'])) {
    $name         = sanitize_text_field($_POST['name']);
    $email        = sanitize_email($_POST['email']);
    $phone        = sanitize_text_field($_POST['phone']);
    $subject      = sanitize_text_field($_POST['subject']);
    $problem_type = sanitize_text_field($_POST['problem_type']);
    $description  = sanitize_textarea_field($_POST['description']);
    $steps        = sanitize_textarea_field($_POST['steps']);
    $expected     = sanitize_textarea_field($_POST['expected']);
    $actual       = sanitize_textarea_field($_POST['actual']);
    $datetime     = sanitize_text_field($_POST['issue_datetime']);
    $page_url     = esc_url_raw($_POST['page_url']);
    $consent      = isset($_POST['consent']) ? 'Yes' : 'No';

    $attachment_url = '';
    if (!empty($_FILES['screenshot']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $uploaded = media_handle_upload('screenshot', 0);
        if (!is_wp_error($uploaded)) {
            $attachment_url = wp_get_attachment_url($uploaded);
        }
    }

    $post_id = wp_insert_post([
        'post_type'   => 'issue_report',
        'post_title'  => $subject . ' (' . $name . ')',
        'post_status' => 'publish',
    ]);

    $message = "
                Name: $name
                Email: $email
                Phone: $phone
                Subject: $subject
                Problem Type: $problem_type
                Description: $description
                Steps to Reproduce: $steps
                Expected Behavior: $expected
                Actual Behavior: $actual
                Date & Time: $datetime
                Page URL: $page_url
                Consent Given: $consent
                Attachment: $attachment_url
                ";

    //wp_mail(get_option('admin_email'), 'New Issue Reported: ' . $subject, $message);

    if ($post_id && !is_wp_error($post_id)) {
        update_post_meta($post_id, 'name', $name);
        update_post_meta($post_id, 'email', $email);
        update_post_meta($post_id, 'phone', $phone);
        update_post_meta($post_id, 'problem_type', $problem_type);
        update_post_meta($post_id, 'description', $description);
        update_post_meta($post_id, 'steps', $steps);
        update_post_meta($post_id, 'expected', $expected);
        update_post_meta($post_id, 'actual', $actual);
        update_post_meta($post_id, 'datetime', $datetime);
        update_post_meta($post_id, 'page_url', $page_url);
        update_post_meta($post_id, 'consent', $consent);
        update_post_meta($post_id, 'attachment_url', $attachment_url);

        // Award gamification points
        if (function_exists('mm_award_points_and_notify')) {
            mm_award_points_and_notify(get_current_user_id(), 'report_submitted');
            update_user_meta($user_id, 'report_submitted', 1);
        }
        // ✅ Use current URL safely
        $current_url = home_url(add_query_arg(null, null)); // get the current URL
        $redirect_url = add_query_arg('report', 'success', $current_url);

        wp_redirect($redirect_url);
        exit;
    }
}
?>
<?php

get_header_based_on_login();

$current_user = wp_get_current_user();
$current_user_id = get_current_user_id();
$user_slug = get_query_var('user_profile');

if ($user_slug) {
    $user = get_user_by('slug', $user_slug);
} else {
    $user = get_user_by('ID', $current_user_id);
}

if ($user) {
    $target_identifier = $user_slug ? $user_slug : $user->user_login;
    $profile_data_instance = new UserProfileData($target_identifier);
    $profile = $profile_data_instance->getProfile();
} else {
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
                        <h5 class="pb-4 text-start portal-title">Report an Issue</h5>
                    </div>

                    <div class="">
                        <?php if (isset($_GET['report']) && $_GET['report'] === 'success') : ?>
                            <div class="alert alert-success">Thank you for your report!</div>
                        <?php endif; ?>


                        <form method="post" enctype="multipart/form-data" class="row g-3" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">

                            <div class="col-12 col-md-6">
                                <label class="form-label">Your Name (optional):</label>
                                <input type="text" name="name" class="form-control input" placeholder="Enter your name">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Email Address <span>*</span>:</label>
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
                                <label class="form-label">Problem Type <span>*</span>:</label>
                                <select name="problem_type" class="bg-neutral-color border-0 w-auto input" required>
                                    <option value="" selected disabled>Select Problem Type</option>
                                    <option value="Bug">Bug</option>
                                    <option value="Feature Request">Feature Request</option>
                                    <option value="Account Issue">Account Issue</option>
                                    <option value="Payment Problem">Payment Problem</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Date & Time of Issue (optional):</label>
                                <input type="datetime-local" name="issue_datetime" class="form-control input">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description <span>*</span>:</label>
                                <textarea name="description" class="input" rows="5" placeholder="Describe the issue" required></textarea>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Steps to Reproduce (optional):</label>
                                <textarea name="steps" class="input" rows="3" placeholder="Steps to reproduce the issue"></textarea>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Expected Behavior (optional):</label>
                                <textarea name="expected" class="input" rows="3" placeholder="What you expected"></textarea>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Actual Behavior (optional):</label>
                                <textarea name="actual" class="input" rows="3" placeholder="What actually happened"></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Page URL (optional):</label>
                                <input type="url" name="page_url" class="form-control input" value="<?php echo esc_url(home_url($_SERVER['REQUEST_URI'])); ?>" placeholder="Enter the page URL">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Upload Screenshot / File (optional):</label>
                                <input type="file" name="screenshot" class="form-control input" accept="image/*,.pdf,.txt">
                            </div>

                            <div class="col-12">
                                <div class="mt-2 form-check">
                                    <input class="form-check-input" type="checkbox" name="consent" id="consentCheckbox" required>
                                    <label class="form-check-label" for="consentCheckbox">
                                        I agree to the terms and allow you to contact me regarding this report.
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-3 mt-3 col-12">
                                <button type="button" class="w-auto text-blue-color custom-btn-size" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="submit_report" class="w-auto custom-btn">Submit Report</button>
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