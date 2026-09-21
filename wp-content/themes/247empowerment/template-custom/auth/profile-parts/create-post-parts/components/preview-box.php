<?php
/**
 * Post Preview Box Component
 * Variables (optional):
 * - $preview_id: ID for preview content (default: 'post-preview')
 */

$preview_id = isset($preview_id) ? $preview_id : 'post-preview';
?>

<div class="posting-preview-box">
    <div class="preview-label">
        <i class="bi bi-eye me-2"></i>Preview
    </div>
    <div class="preview-content" id="<?php echo esc_attr($preview_id); ?>">
        <p class="text-muted text-center py-4">Your post preview will appear here</p>
    </div>
</div>
