<?php
defined('ABSPATH') || exit;

add_action('admin_enqueue_scripts', function ($hook) {

    // Only post editor screens
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'spg_step') {
        return;
    }

    wp_enqueue_style(
        'spg-steps-admin',
        plugin_dir_url(__FILE__) . 'assets/css/spg-steps-admin.css',
        [],
        '1.0.1'
    );

    wp_enqueue_script('jquery-ui-sortable');

    wp_enqueue_script(
        'spg-steps-admin',
        plugin_dir_url(__FILE__) . 'assets/js/spg-steps-admin.js',
        ['jquery', 'jquery-ui-sortable'],
        '1.0.1',
        true
    );
});
