<?php

defined('ABSPATH') || exit;

add_action('save_post_spg_step', function ($post_id) {

    // Autosave & revision
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    // Capability
    if (!current_user_can('edit_post', $post_id)) return;

    // Nonce
    if (
        !isset($_POST['spg_step_blocks_nonce']) ||
        !wp_verify_nonce($_POST['spg_step_blocks_nonce'], 'spg_step_blocks_save')
    ) {
        return;
    }

    /* -------------------------
       Phase & Interest
    -------------------------- */
    if (isset($_POST['spg_phase'])) {
        update_post_meta($post_id, '_spg_phase', (int) $_POST['spg_phase']);
    }

    if (isset($_POST['spg_interest'])) {
        update_post_meta(
            $post_id,
            '_spg_interest',
            sanitize_text_field($_POST['spg_interest'])
        );
    }

    /* -------------------------
       Blocks
    -------------------------- */
    if (!isset($_POST['spg_blocks']) || !is_array($_POST['spg_blocks'])) {
        delete_post_meta($post_id, '_spg_blocks');
        return;
    }

    $clean_blocks = [];

    foreach ($_POST['spg_blocks'] as $block) {

        $clean = [];

        // Block type
        $clean['type'] = sanitize_text_field($block['type'] ?? '');

        // Editor content (AUTO <p> SUPPORT)
        if (isset($block['content'])) {

            $content = wp_kses_post($block['content']);

            // Convert new lines to <p> and <br>
            $content = wpautop($content);

            $clean['content'] = $content;
        }

        // Button label
        if (isset($block['label'])) {
            $clean['label'] = sanitize_text_field($block['label']);
        }

        // Video source
        if (isset($block['src'])) {
            $clean['src'] = esc_url_raw($block['src']);
        }

        // Redirect URL
        if (isset($block['url'])) {
            $clean['url'] = esc_url_raw($block['url']);
        }

        // Shortcode
        if (isset($block['shortcode'])) {
            $clean['shortcode'] = sanitize_text_field($block['shortcode']);
        }

        $clean_blocks[] = $clean;
    }

    update_post_meta($post_id, '_spg_blocks', $clean_blocks);
});


// defined('ABSPATH') || exit;

// add_action('save_post_spg_step', function ($post_id) {

//     if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
//     if (!current_user_can('edit_post', $post_id)) return;

//     if (isset($_POST['spg_phase'])) {
//         update_post_meta($post_id, '_spg_phase', intval($_POST['spg_phase']));
//     }

//     if (isset($_POST['spg_interest'])) {
//         update_post_meta($post_id, '_spg_interest', sanitize_text_field($_POST['spg_interest']));
//     }

//     if (isset($_POST['spg_blocks']) && is_array($_POST['spg_blocks'])) {
//         update_post_meta($post_id, '_spg_blocks', array_values($_POST['spg_blocks']));
//     }
// });
