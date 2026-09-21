<?php
/**
 * Template Name: Change Username Page
 * Template Post Type: page
 * 
 * Allow users to change their username
 */

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

get_header_based_on_login();

$current_user = wp_get_current_user();
$user_id = get_current_user_id();

// Get messages from transient
$message = get_transient('change_username_message');
delete_transient('change_username_message');

// Get username change restrictions
$total_changes = (int) get_user_meta($user_id, 'username_change_count', true);
$last_change = get_user_meta($user_id, 'username_last_changed', true);
$remaining_changes = max(0, 5 - $total_changes);
$can_change = true;
$next_eligible_date = null;

// Check 3-month cooldown
if ($last_change) {
    $last_change_time = strtotime($last_change);
    $three_months_ago = strtotime('-3 months', current_time('timestamp'));
    
    if ($last_change_time > $three_months_ago) {
        $can_change = false;
        $next_eligible_date = date('F j, Y', strtotime('+3 months', $last_change_time));
    }
}

// Check if hit max
if ($remaining_changes <= 0) {
    $can_change = false;
}
?>

<div class="pt-4 pb-4 container profile-page">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3">
            <?php 
            $profile = (new UserProfileData($current_user->ID))->getProfile();
            get_template_part('template-custom/auth/common-parts/editprofilemenu', null, ['profile' => $profile]); 
            ?>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <!-- Header Section -->
            <div class="bg-white mb-4 p-4 border-bottom rounded">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="mb-2">
                            <i class="bi bi-person-badge"></i>
                            Change Username
                        </h3>
                        <p class="mb-0 text-muted">
                            Update your username to something more unique
                        </p>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="bg-white p-4 rounded">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo esc_attr($message['type']); ?> alert-dismissible fade show" role="alert">
                        <?php echo wp_kses_post($message['text']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Username Change Limits Section -->
                <div class="mb-4 alert alert-info">
                    <h6 class="mb-3">
                        <i class="bi bi-info-circle"></i> Username Change Policy
                    </h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Changes Used:</strong>
                                <span class="bg-primary badge"><?php echo $total_changes; ?>/5</span>
                            </p>
                            <p class="mb-0">
                                <strong>Changes Remaining:</strong>
                                <span class="badge bg-<?php echo $remaining_changes > 0 ? 'success' : 'danger'; ?>">
                                    <?php echo $remaining_changes; ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Frequency Limit:</strong> Once every 3 months
                            </p>
                            <?php if ($last_change && $next_eligible_date): ?>
                                <p class="mb-0">
                                    <strong>Last Changed:</strong> <?php echo esc_html(date('F j, Y', strtotime($last_change))); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Disabled State Warning -->
                <?php if (!$can_change): ?>
                    <div class="alert alert-warning">
                        <h6 class="mb-2">
                            <i class="bi bi-exclamation-triangle"></i> Username Change Not Available
                        </h6>
                        <?php if ($remaining_changes <= 0): ?>
                            <p class="mb-0">
                                You have reached the maximum number of username changes allowed (<strong>5 changes total</strong>). You cannot change your username again.
                            </p>
                        <?php elseif ($next_eligible_date): ?>
                            <p class="mb-0">
                                You can change your username once every 3 months. Your next eligible change date is <strong><?php echo esc_html($next_eligible_date); ?></strong>.
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Change Username Form -->
                <form method="POST" action="" <?php echo !$can_change ? 'style="opacity: 0.6; pointer-events: none;"' : ''; ?>>
                    <?php wp_nonce_field('change_username_action', 'change_username_nonce'); ?>
                    
                    <div class="mb-4">
                        <label for="current_username" class="form-label fw-600">Current Username</label>
                        <input type="text" class="form-control" id="current_username" value="<?php echo esc_attr($current_user->user_login); ?>" disabled>
                        <small class="text-muted">Your current username cannot be changed here</small>
                    </div>

                    <div class="mb-4">
                        <label for="new_username" class="form-label fw-600">New Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new_username" name="new_username" 
                            placeholder="Enter new username (lowercase, letters, numbers, underscores only)"
                            required autocapitalize="off" autocorrect="off" <?php echo !$can_change ? 'disabled' : ''; ?>>
                        <small id="username-feedback" class="d-block mt-2"></small>
                        <small class="d-block mt-2 text-muted">
                            • Minimum 3 characters<br>
                            • Only lowercase letters (a-z), numbers (0-9), and underscores (_)<br>
                            • Cannot be an email address
                        </small>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label fw-600">Confirm Your Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                            placeholder="Enter your password to confirm" required <?php echo !$can_change ? 'disabled' : ''; ?>>
                        <small class="text-muted">We need your password to confirm this change</small>
                    </div>

                    <div class="mb-4">
                        <button type="submit" name="change_username" value="1" class="btn btn-primary" <?php echo !$can_change ? 'disabled' : ''; ?>>
                            <i class="bi bi-check-circle"></i> Change Username
                        </button>
                        <a href="<?php echo esc_url(wp_get_referer() ?: home_url()); ?>" class="ms-2 btn btn-secondary">Cancel</a>
                    </div>
                </form>

                <!-- Important Notice -->
                <div class="mt-5 pt-4 border-top">
                    <div class="alert alert-info">
                        <h6 class="mb-2">
                            <i class="bi bi-info-circle"></i> Important Information
                        </h6>
                        <ul class="mb-0">
                            <li>Your username will change across the entire platform</li>
                            <li>Your profile URL will update accordingly</li>
                            <li>All referral links using your username will still work</li>
                            <li>This action is logged for security purposes</li>
                            <li><strong>⏱️ You can change your username once every 3 months</strong></li>
                            <li><strong>📊 You have a maximum of 5 total username changes in your lifetime</strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.username-feedback-error {
    color: #dc3545;
    font-size: 0.875rem;
}

.username-feedback-success {
    color: #28a745;
    font-size: 0.875rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const usernameInput = document.getElementById('new_username');
    const feedbackEl = document.getElementById('username-feedback');
    let validationTimer = null;

    function validateUsernameFormat() {
        const value = usernameInput.value.trim();
        const regex = /^[a-z0-9_]+$/;
        const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);

        if (value === '') {
            feedbackEl.textContent = '';
            feedbackEl.className = '';
            return false;
        }

        if (value.length < 3) {
            feedbackEl.textContent = '⚠️ Username must be at least 3 characters long';
            feedbackEl.className = 'username-feedback-error';
            return false;
        }

        if (isEmail) {
            feedbackEl.textContent = '❌ Email addresses cannot be used as usernames';
            feedbackEl.className = 'username-feedback-error';
            return false;
        }

        if (!regex.test(value)) {
            feedbackEl.textContent = '❌ Username can only contain lowercase letters (a-z), numbers (0-9), and underscores (_)';
            feedbackEl.className = 'username-feedback-error';
            return false;
        }

        // Check availability
        checkUsernameAvailability(value);
        return true;
    }

    function checkUsernameAvailability(username) {
        feedbackEl.textContent = '⏳ Checking availability...';
        feedbackEl.className = 'username-feedback-error';

        fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
                action: "mm_validate_username",
                nonce: "<?php echo wp_create_nonce('custom_user_registration'); ?>",
                username: username
            })
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                feedbackEl.textContent = '❌ ' + data.data;
                feedbackEl.className = 'username-feedback-error';
            } else {
                feedbackEl.textContent = '✓ Username is available';
                feedbackEl.className = 'username-feedback-success';
            }
        })
        .catch(err => {
            feedbackEl.textContent = '⚠️ Error checking availability';
            feedbackEl.className = 'username-feedback-error';
        });
    }

    usernameInput.addEventListener('input', function() {
        clearTimeout(validationTimer);
        validationTimer = setTimeout(validateUsernameFormat, 500);
    });

    // Validate on form submit
    document.querySelector('form').addEventListener('submit', function(e) {
        if (!validateUsernameFormat()) {
            e.preventDefault();
            usernameInput.focus();
        }
    });
});
</script>

<?php get_footer_based_on_login(); ?>
