<?php


/**
 * Frontend bootstrap
 */
function mm_spg_save_avatar()
{
    if (!is_user_logged_in()) {
        wp_send_json_error();
    }

    check_ajax_referer('mm_spg_security', 'nonce');

    $avatar = sanitize_text_field($_POST['avatar'] ?? '');

    if (!in_array($avatar, ['male', 'female'], true)) {
        wp_send_json_error();
    }

    update_user_meta(get_current_user_id(), 'mm_spg_avatar', $avatar);

    wp_send_json_success();
}
add_action('wp_ajax_mm_spg_save_avatar', 'mm_spg_save_avatar');

/**
 * Enqueue frontend assets
 */
function mm_spg_enqueue_assets()
{
    if (!is_user_logged_in()) {
        return;
    }

    $user_id = get_current_user_id();

    /* =========================
       BUILD STEPS
    ========================= */

    // Phase 2 steps ONLY
    $steps = mm_spg_get_steps();
    $phase_3_start_index = count($steps);

    // Inject Phase 3 ONLY after Phase 2 completion
    if (get_user_meta($user_id, 'mm_spg_phase_2_completed', true)) {

        $priorities = get_user_meta($user_id, 'user_categories_priority', true);
        $priorities = is_array($priorities) ? $priorities : [];

        $primary_term_id = array_search(1, $priorities, true);

        if ($primary_term_id) {
            $term = get_term($primary_term_id);

            if ($term && !is_wp_error($term)) {
                $phase3 = mm_spg_build_phase_3_steps($term->slug);

                if (!empty($phase3)) {
                    $steps = array_merge($steps, $phase3);
                }
            }
        }
    }

    /* =========================
       ENQUEUE ASSETS
    ========================= */

    wp_enqueue_style(
        'mm-spg-css',
        MM_SPG_URL . 'inc/frontend/assets/css/mm-spg.css',
        [],
        MM_SPG_VERSION
    );

    wp_enqueue_script(
        'mm-spg-js',
        MM_SPG_URL . 'inc/frontend/assets/js/mm-spg.js',
        ['jquery'],
        MM_SPG_VERSION,
        true
    );

    wp_localize_script(
        'mm-spg-js',
        'MM_SPG',
        [
            'ajax_url'            => admin_url('admin-ajax.php'),
            'nonce'               => wp_create_nonce('mm_spg_security'),
            'avatar'              => mm_spg_get_user_avatar(),
            'steps'               => array_values($steps),
            'phase_3_start_index' => $phase_3_start_index,

            // ✅ AVATAR IMAGE URLS
            'avatar_male_url'   => MM_SPG_URL . 'inc/frontend/assets/images/male_avatar.png',
            'avatar_female_url' => MM_SPG_URL . 'inc/frontend/assets/images/female_avatar.png',
        ]
    );
}
add_action('wp_enqueue_scripts', 'mm_spg_enqueue_assets');