<?php
/**
 * Privacy Selector Component
 * Variables (optional):
 * - $privacy_prefix: Suffix for IDs when multiple instances needed (e.g., '-schedule')
 */

$privacy_prefix = isset($privacy_prefix) ? $privacy_prefix : '';

// LinkedIn state via MM Social Poster plugin: 'unavailable' | 'none' | 'active' | 'expired'
$linkedin_state = 'unavailable';
if (is_user_logged_in() && function_exists('mm_social_poster_connection_status') && mm_social_poster_is_configured('linkedin')) {
    $linkedin_state = mm_social_poster_connection_status(get_current_user_id(), 'linkedin');
}
?>

<div class="">
    <label class="mb-2 form-label fw-bold">
        <i class="me-2 bi bi-shield-check"></i>Who can see this?
    </label>
    <div class="privacy-options" id="privacyOptionsContainer">
        <!-- Only Me -->
        <div class="privacy-option">
            <input type="radio" name="post_privacy" id="privacy-only-me<?php echo esc_attr($privacy_prefix); ?>" value="only_me" data-audience-label="Only Me" checked>
            <label for="privacy-only-me<?php echo esc_attr($privacy_prefix); ?>" class="privacy-label">
                <span class="privacy-icon">
                    <i class="bi bi-lock-fill"></i>
                </span>
                <span class="privacy-text">
                    <strong>Only Me</strong>
                    <small class="d-block text-muted">Private, only you can see</small>
                </span>
            </label>
        </div>

        <!-- Referral Partners -->
        <div class="privacy-option">
            <input type="radio" name="post_privacy" id="privacy-referral<?php echo esc_attr($privacy_prefix); ?>" value="referral_partners" data-audience-label="Shared with Partners">
            <label for="privacy-referral<?php echo esc_attr($privacy_prefix); ?>" class="privacy-label">
                <span class="privacy-icon">
                    <i class="bi bi-people-fill"></i>
                </span>
                <span class="privacy-text">
                    <strong>Referral Partners</strong>
                    <small class="d-block text-muted">Share with your network</small>
                </span>
            </label>
        </div>

        <!-- Public -->
        <div class="privacy-option">
            <input type="radio" name="post_privacy" id="privacy-public<?php echo esc_attr($privacy_prefix); ?>" value="public" data-audience-label="Public">
            <label for="privacy-public<?php echo esc_attr($privacy_prefix); ?>" class="privacy-label">
                <span class="privacy-icon">
                    <i class="bi bi-globe"></i>
                </span>
                <span class="privacy-text">
                    <strong>Public</strong>
                    <small class="d-block text-muted">Anyone can see this</small>
                </span>
            </label>
        </div>
    </div>

    <!-- Clickrow Affiliate Link Button -->
    <div class="mt-4 mb-3">
        <a href="https://clickgrow.ai/?via=247Empowerment" target="_blank" class="w-100 btn btn-primary">
            <i class="me-2 bi bi-link-45deg"></i>Clickrow Affiliate Link
        </a>
    </div>

    <!-- LinkedIn Share (Only shown for Public posts) -->
    <?php if ($linkedin_state === 'active') { ?>
    <div class="mt-3" id="linkedinShareContainer<?php echo esc_attr($privacy_prefix); ?>" style="display: none;">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="shareToLinkedin<?php echo esc_attr($privacy_prefix); ?>" name="share_to_linkedin" value="1">
            <label class="form-check-label" for="shareToLinkedin<?php echo esc_attr($privacy_prefix); ?>" style="cursor: pointer;">
                <i class="me-1 bi bi-linkedin" style="color: #0a66c2;"></i>
                <strong>Share on LinkedIn</strong>
                <small class="d-block text-muted">Post will be shared to your LinkedIn profile</small>
            </label>
        </div>
    </div>
    <?php } elseif ($linkedin_state === 'none' || $linkedin_state === 'expired') { ?>
    <div class="mt-3 mm-social-connect-hint" id="linkedinShareContainer<?php echo esc_attr($privacy_prefix); ?>" style="display: none;">
        <i class="me-1 bi bi-linkedin" style="color: #0a66c2;"></i>
        <?php if ($linkedin_state === 'expired') { ?>
            <span class="text-muted">Your LinkedIn connection expired.</span>
            <a href="<?php echo esc_url(mm_social_poster_connect_url('linkedin')); ?>">Reconnect LinkedIn</a>
        <?php } else { ?>
            <a href="<?php echo esc_url(mm_social_poster_connect_url('linkedin')); ?>">Connect LinkedIn</a>
            <span class="text-muted">to also share this post there.</span>
        <?php } ?>
    </div>
    <?php } ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const privacyOptions = document.querySelectorAll('input[name="post_privacy<?php echo esc_attr($privacy_prefix); ?>"]');
    const linkedinContainer = document.getElementById('linkedinShareContainer<?php echo esc_attr($privacy_prefix); ?>');
    const linkedinCheckbox = document.getElementById('shareToLinkedin<?php echo esc_attr($privacy_prefix); ?>');
    
    // Container exists when LinkedIn is configured (checkbox only when connected)
    if (!linkedinContainer) {
        return;
    }
    
    function toggleLinkedInOption() {
        const selectedPrivacy = document.querySelector('input[name="post_privacy<?php echo esc_attr($privacy_prefix); ?>"]:checked');
        if (selectedPrivacy && selectedPrivacy.value === 'public') {
            linkedinContainer.style.display = 'block';
        } else {
            linkedinContainer.style.display = 'none';
            if (linkedinCheckbox) {
                linkedinCheckbox.checked = false;
            }
        }
    }
    
    privacyOptions.forEach(option => {
        option.addEventListener('change', toggleLinkedInOption);
    });
    
    // Initial check
    toggleLinkedInOption();
});
</script>

