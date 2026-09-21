<?php

/**
 * Get current guide state
 */
/* function mm_spg_get_state()
{
    if (!is_user_logged_in()) {
        wp_send_json_error();
    }

    check_ajax_referer('mm_spg_security', 'nonce');

    $user_id = get_current_user_id();

    $status     = get_user_meta($user_id, 'mm_spg_status', true);
    $step       = (int) get_user_meta($user_id, 'mm_spg_step', true);
    $wait_until = (int) get_user_meta($user_id, 'mm_spg_waiting_until', true);

    // Phase detection
    $phase_2_done = (bool) get_user_meta($user_id, 'mm_spg_phase_2_completed', true);
    $phase_3_done = (bool) get_user_meta($user_id, 'mm_spg_phase_3_completed', true);


    // Determine active phase
    $current_phase = $phase_2_done ? 3 : 2;

    $completed = (bool) get_user_meta($user_id, 'mm_spg_completed', true);

    wp_send_json_success([
        'status'        => $status,
        'step'          => $step,
        'wait_until'    => $wait_until,
        'current_phase' => $current_phase,
        'completed'     => $completed,
    ]);
}
add_action('wp_ajax_mm_spg_get_state', 'mm_spg_get_state');
 */


function mm_spg_get_state()
{
    if (!is_user_logged_in()) {
        wp_send_json_error();
    }

    check_ajax_referer('mm_spg_security', 'nonce');

    $user_id = get_current_user_id();

    // Current state
    $status     = get_user_meta($user_id, 'mm_spg_status', true);
    $step       = (int) get_user_meta($user_id, 'mm_spg_step', true);
    $wait_until = (int) get_user_meta($user_id, 'mm_spg_waiting_until', true);

    // Phase detection
    $phase_2_done = (bool) get_user_meta($user_id, 'mm_spg_phase_2_completed', true);
    $phase_3_done = (bool) get_user_meta($user_id, 'mm_spg_phase_3_completed', true);

    // Determine active phase
    $current_phase = $phase_2_done ? 3 : 2;

    // ✅ SINGLE SOURCE OF TRUTH (FINAL)
    $completed = $phase_3_done;

    wp_send_json_success([
        'status'        => $status,
        'step'          => $step,
        'wait_until'    => $wait_until,
        'current_phase' => $current_phase,
        'completed'     => $completed,
    ]);
}
add_action('wp_ajax_mm_spg_get_state', 'mm_spg_get_state');


/**
 * Update guide state
 */
function mm_spg_set_state()
{
    if (!is_user_logged_in()) {
        wp_send_json_error();
    }

    check_ajax_referer('mm_spg_security', 'nonce');

    $user_id = get_current_user_id();
    $status  = sanitize_text_field($_POST['status'] ?? 'paused');
    $step    = isset($_POST['step']) ? (int) $_POST['step'] : 0;

    update_user_meta($user_id, 'mm_spg_status', $status);
    update_user_meta($user_id, 'mm_spg_step', $step);

    wp_send_json_success();
}
add_action('wp_ajax_mm_spg_set_state', 'mm_spg_set_state');


/* ========================
        SOCIAL MANAGEMENT
    ========================= */
add_action('wp_ajax_mm_spg_save_social_links', 'mm_spg_save_social_links');

function mm_spg_save_social_links()
{
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }

    check_ajax_referer('mm_spg_links_save', 'mm_spg_links_nonce');

    $user_id = get_current_user_id();

    $links = $_POST['links'] ?? [];
    $clean_links = [];

    foreach ($links as $link) {
        if (!empty($link['url'])) {
            $clean_links[] = [
                'platform' => sanitize_text_field($link['platform'] ?? ''),
                'label'    => sanitize_text_field($link['label'] ?? ''),
                'url'      => esc_url_raw($link['url']),
            ];
        }
    }

    update_user_meta($user_id, 'custom_social_links', $clean_links);

    // Optional: mark step completed
    update_user_meta($user_id, 'mm_spg_social_links_completed', 1);

    wp_send_json_success('Social links updated successfully.');
}

/**
 * Summary of mm_spg_set_wait
 * @return void
 */
function mm_spg_set_wait()
{
    if (!is_user_logged_in()) {
        wp_send_json_error();
    }

    check_ajax_referer('mm_spg_security', 'nonce');

    $user_id = get_current_user_id();

    update_user_meta($user_id, 'mm_spg_waiting_until', (int) $_POST['wait_until']);
    update_user_meta($user_id, 'mm_spg_step', (int) $_POST['step']);
    update_user_meta($user_id, 'mm_spg_status', 'waiting');

    wp_send_json_success();
}
add_action('wp_ajax_mm_spg_set_wait', 'mm_spg_set_wait');



/**
 * Get current user's selected avatar
 */
function mm_spg_get_user_avatar()
{
    if (!is_user_logged_in()) {
        return '';
    }

    return get_user_meta(get_current_user_id(), 'mm_spg_avatar', true) ?: '';
}


add_action('wp_ajax_mm_spg_complete_phase_2', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error();
    }

    check_ajax_referer('mm_spg_security', 'nonce');

    $user_id = get_current_user_id();

    $phase_3_start_index = count(mm_spg_get_steps());

    update_user_meta($user_id, 'mm_spg_phase_2_completed', 1);
    update_user_meta($user_id, 'mm_spg_phase_3_started', 1);
    update_user_meta($user_id, 'mm_spg_step', $phase_3_start_index);
    update_user_meta($user_id, 'mm_spg_status', 'active');

    wp_send_json_success();
});


add_action('wp_ajax_mm_spg_complete_phase_3', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error();
    }

    check_ajax_referer('mm_spg_security', 'nonce');

    $user_id = get_current_user_id();

    // 🔒 FINAL TERMINAL STATE
    update_user_meta($user_id, 'mm_spg_phase_3_completed', 1);
    update_user_meta($user_id, 'mm_spg_status', 'completed');
    update_user_meta($user_id, 'mm_spg_step', -1);

    wp_send_json_success();
});


add_action('wp_ajax_mm_spg_prepare_phase_3', function () {

    if (!is_user_logged_in()) {
        wp_send_json_error();
    }

    check_ajax_referer('mm_spg_security', 'nonce');

    $user_id = get_current_user_id();

    $phase_3_start_index = count(mm_spg_get_steps());

    update_user_meta($user_id, 'mm_spg_phase_2_completed', 1);
    update_user_meta($user_id, 'mm_spg_phase_3_started', 1);
    update_user_meta($user_id, 'mm_spg_step', $phase_3_start_index);
    update_user_meta($user_id, 'mm_spg_status', 'active');

    wp_send_json_success();
});