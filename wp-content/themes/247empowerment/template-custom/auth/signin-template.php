<?php

/**
 * Template Name: Signin Page
 * Template Post Type: page
 */

defined('ABSPATH') || exit;

get_header_based_on_login();

$message = get_transient('custom_user_message');
$old_input = $message['old_input'] ?? [];
?>

<div class="container">
    <div class="justify-content-center row">
        <div class="col-12">
            <div class="row g-md-4">
                <!-- Login Section -->
                <div class="py-5 col-lg-3">
                    &nbsp;
                </div>
                <div class="py-5 col-lg-6">
                    <div class="d-flex justify-center align-items-center mb-4 text-center">
                        <div class="d-flex align-items-center gap-2">
                            <img class="logo" src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/logo.png" alt="<?php bloginfo('name'); ?>">
                            <span class="gradient-text"><?php echo get_bloginfo('name'); ?></span>
                            
                        </div>
                            <!-- <button class="d-flex align-items-center gap-2 go-back" onclick="window.history.back();">
                                <img src="<?php //echo get_template_directory_uri(); ?>/assets/img/nd/back.png" alt="Back">
                                <span>Go back</span>
                            </button>                             -->
                        
                    </div>


                    <?php
                    if ($message = get_transient('custom_user_message')) {
                        $type = $message['type']; // success or danger                        
                        $text = $message['text'];

                        $allowed_tags = [
                            'span' => ['style' => []],
                            'strong' => [],
                            'em' => [],
                            'a' => ['href' => [], 'class' => []],
                        ];

                        echo '<div class="alert" style="background-color: #E8EEFB;">';

                        // Show correct title
                        if ($type === 'success') {
                            echo '<p style="color: #E835B0; font-size: 20px;">Thank You for Registering!</p>';
                            echo '<p style="color: #555555; font-size: 20px;">' . wp_kses($text, $allowed_tags) . '</p>';
                        } elseif ($type === 'danger') {                         
                            echo '<p style="color: #F00000; font-size: 20px; ">' . wp_kses($text, $allowed_tags) . '</p>';
                        }

                        
                        echo '</div>';

                        delete_transient('custom_user_message');
                    }
                    ?>


                    <h2 class="mb-3 title">Welcome Back</h2>
                    <p class="mb-4">Log in to continue your empowerment journey.</p>
                    <form method="POST" action="">
                        <!-- Hidden action for custom login -->
                        <input type="hidden" name="action" value="custom_user_login">
                        <?php wp_nonce_field('custom_user_login', 'custom_user_login_nonce'); ?>

                        <div class="mb-3">
                            <label for="username" class="form-label fw-normal">Username <span>*</span></label>
                            <input type="text" class="input" id="username" name="username" placeholder="Enter your username" value="<?= esc_attr($old_input['username'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-normal">Password <span>*</span></label>
                            <div class="position-relative">
                                <input type="password" class="input" id="password" name="password" placeholder="Enter your password" required>
                                <img id="togglePassword" class="pass-show"
                                    src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/pass-close-eye.svg"
                                    alt="Show Password"
                                    style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; width: 20px; height: 20px;">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mb-3">
                            <a href="<?php echo wp_lostpassword_url(); ?>" class="text-decoration-none forgot-text">Forgot your password?</a>
                        </div>

                        <button type="submit" class="mb-3 custom-btn">Sign In</button>

                        <div class="mb-3 text-start">
                            <p class="mb-0">Don't have an account?</p>
                        </div>
                        <div>
                            <a href="<?php echo site_url('/signup'); ?>" class="mb-3 custom-btn-outline-none">Sign Up</a>
                        </div>
                    </form>

                </div>
                <div class="py-5 col-lg-3">
                    &nbsp;
                </div>

                <!-- Right Info Section -->

                <?php /**
                <div class="col-lg-6">
                    <div class="position-relative d-flex flex-column align-items-center justify-content-start px-5 px-md-5 border-singup overflow-hidden text-center">
                        <img class="ellipse-size" src="<?php echo get_template_directory_uri(); ?>/assets/img/nd/Ellipse.png" alt="">
                        <div class="z-1 position-absolute">
                            <p class="mb-3 pt-4 title">Get Started in 4 Easy Steps</p>
                            <p class="mb-5 text-center">Sign in, personalize, explore — and you're in.</p>
                        </div>
                    </div>

                    <div class="position-relative d-flex flex-column align-items-center gap-4 px-4 px-md-5 border-singup overflow-hidden">
                        <img class="d-md-block bottom-0 z-0 position-absolute d-none img-position" src="<?php echo get_template_directory_uri(); ?>/assets/img/Vector.png" alt="">

                        <?php
                        $steps = [
                            ['title' => 'Sign In', 'desc' => 'You already have an account'],
                            ['title' => 'Invite & Share', 'desc' => 'Send your referral link to friends and contacts.'],
                            ['title' => 'Track Progress', 'desc' => 'See updates from your referrals in real time.'],
                            ['title' => 'Empower Yourself', 'desc' => 'Join coaching programs and boost your growth.'],
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

                **/?>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePassword() {
        const passwordInput = document.getElementById("password");
        passwordInput.type = passwordInput.type === "password" ? "text" : "password";
    }
</script>

<?php get_footer_based_on_login(); ?>