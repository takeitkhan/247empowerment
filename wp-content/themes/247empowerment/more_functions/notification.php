<?php
add_action('wp_enqueue_scripts', function () {

    wp_enqueue_script(
        'mm-notifications',
        get_template_directory_uri() . '/assets/js/mm-notifications.js',
        ['jquery'],
        '1.0',
        true
    );

    $user_id = get_current_user_id();
    $push = $_SESSION["mm_push_notify_{$user_id}"] ?? null;

    wp_localize_script('mm-notifications', 'mmLoginPush', [
        'notification' => $push
    ]);

    // Clear session so it fires once
    if ($push) {
        unset($_SESSION["mm_push_notify_{$user_id}"]);
    }
});
