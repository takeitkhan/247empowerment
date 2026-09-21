<?php
defined('ABSPATH') || exit;

add_action('add_meta_boxes', function () {
    add_meta_box(
        'spg_step_settings',
        'Step Settings',
        'spg_step_settings_cb',
        'spg_step',
        'side',
        'high'
    );
});

function spg_step_settings_cb($post)
{
    $phase = get_post_meta($post->ID, '_spg_phase', true);
    $interest = get_post_meta($post->ID, '_spg_interest', true);
    ?>
    <p>
        <label><strong>Phase</strong></label>
        <select name="spg_phase" style="width:100%">
            <option value="2" <?= selected($phase, 2); ?>>Phase 2</option>
            <option value="3" <?= selected($phase, 3); ?>>Phase 3</option>
        </select>
    </p>

    <p>
        <label><strong>Interest Slug (Phase 3)</strong></label>
        <input
            type="text"
            name="spg_interest"
            value="<?= esc_attr($interest); ?>"
            placeholder="communications-business-marketing"
            style="width:100%"
        />
    </p>
    <?php
}
