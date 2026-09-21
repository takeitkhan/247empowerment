<?php
defined('ABSPATH') || exit;

add_action('init', function () {
    register_post_type('spg_step', [
        'label' => 'SPG Steps',
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-controls-forward',
        'supports' => ['title', 'page-attributes'],
    ]);
});