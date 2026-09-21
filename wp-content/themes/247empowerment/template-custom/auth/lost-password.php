<?php
/**
 * Template Name: Custom Lost Password
 * Template Post Type: page
 */

defined('ABSPATH') || exit;

if (is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}
if (!is_user_logged_in()) {
    wp_redirect(home_url('/signin'));
    exit;
}

get_header_based_on_login();

$error = '';
$success = '';
$old_input = $_POST ?? [];

// Handle Form Submission
if (isset($_POST['custom_lost_password']) && check_admin_referer('custom_lost_password_action', 'custom_lost_password_nonce')) {

    $user_login = sanitize_text_field($_POST['user_login']);

    if (empty($user_login)) {
        $error = 'Please enter your username or email.';
    } else {
        $reset = retrieve_password($user_login);

        if (is_wp_error($reset)) {
            $error = $reset->get_error_message();
        } else {
            $success = 'Check your email for the password reset link.';
        }
    }
}
?>

<div class="container">
    <div class="justify-content-center row">
        <div class="col-12">
            <div class="row g-md-4">

                <!-- Left Section -->
                <div class="py-5 col-lg-6">

                    <!-- Header / Logo -->
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <img class="logo" src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/logo.png" alt="<?php bloginfo('name'); ?>">
                            <span class="gradient-text"><?php echo get_bloginfo('name'); ?></span>
                        </div>

                        <button class="d-flex align-items-center gap-2 go-back" onclick="window.history.back();">
                            <img src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/back.png" alt="Back">
                            <span>Go back</span>
                        </button>
                    </div>

                    <!-- Messages -->
                    <?php if (!empty($error) || !empty($success)): ?>
                        <div class="alert" style="background-color: #E8EEFB;">

                            <?php if (!empty($success)): ?>
                                <p style="color: #E835B0; font-size: 20px;">Password Reset Email Sent!</p>
                                <p style="color: #555555; font-size: 20px;"><?php echo esc_html($success); ?></p>
                            <?php endif; ?>

                            <?php if (!empty($error)): ?>
                                <p style="color: #F00000; font-size: 20px;"><?php echo esc_html($error); ?></p>
                            <?php endif; ?>

                        </div>
                    <?php endif; ?>

                    <!-- Title -->
                    <h2 class="mb-3 title">Reset Your Password</h2>
                    <p class="mb-4">Enter your username or email to receive a password reset link.</p>

                    <!-- Form -->
                    <?php if (empty($success)): ?>
                        <form method="POST" novalidate>

                            <?php wp_nonce_field('custom_lost_password_action', 'custom_lost_password_nonce'); ?>

                            <div class="mb-3">
                                <label for="user_login" class="form-label fw-normal">
                                    Username or Email <span>*</span>
                                </label>
                                <input type="text"
                                       name="user_login"
                                       id="user_login"
                                       class="input"
                                       placeholder="Enter your username or email"
                                       value="<?= esc_attr($old_input['user_login'] ?? '') ?>"
                                       required>
                            </div>

                            <button type="submit" name="custom_lost_password" class="mb-3 custom-btn">
                                Send Reset Link
                            </button>

                            <div>
                                <p class="mb-3">Remembered your password?</p>
                                <a href="<?php echo site_url('/signin'); ?>" class="custom-btn-outline-none">
                                    Sign In
                                </a>
                            </div>

                        </form>
                    <?php endif; ?>
                </div>

                <!-- Right Section -->
                <div class="col-lg-6">
                    <div class="position-relative d-flex flex-column align-items-center justify-content-start px-5 border-singup overflow-hidden text-center">
                        <img class="ellipse-size" src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/Ellipse.png" alt="">
                        <div class="z-1 position-absolute">
                            <p class="mb-3 pt-4 title">Reset & Reconnect</p>
                            <p class="mb-5 text-center">Recover your access in just a few quick steps.</p>
                        </div>
                    </div>

                    <div class="position-relative d-flex flex-column align-items-center gap-4 px-4 px-md-5 border-singup overflow-hidden">
                        <img class="d-md-block bottom-0 z-0 position-absolute d-none img-position" src="<?php echo get_template_directory_uri(); ?>/assets/img/Vector.png" alt="">

                        <?php
                        $steps = [
                            ['title' => 'Enter Details', 'desc' => 'Provide your username or email.'],
                            ['title' => 'Receive Email', 'desc' => 'We’ll send you a reset link.'],
                            ['title' => 'Create New Password', 'desc' => 'Set a secure new password.'],
                            ['title' => 'Access Again', 'desc' => 'Continue your empowerment journey.'],
                        ];

                        foreach ($steps as $index => $step):
                            $cardClass = 's-card' . ($index > 0 ? ($index + 1) : '');
                        ?>
                            <div class="<?= esc_attr($cardClass); ?>">
                                <div class="d-flex align-items-center gap-4">
                                    <span class="number-sign"><?= $index + 1; ?></span>
                                    <div>
                                        <p class="c-title"><?= esc_html($step['title']); ?></p>
                                        <p class="c-des"><?= esc_html($step['desc']); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php get_footer_based_on_login(); ?>
