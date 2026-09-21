<?php
/**
 * Plugin Name: MM LiveKit Video Conferencing
 * Description: Custom LiveKit integration for WordPress video conferencing portal.
 * Version: 1.0
 * Author: Samrat Khan
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. কনফিগারেশন ফাইল লোড করা
require_once __DIR__ . '/livekit-config.php';

// 2. টোকেন জেনারেশন ফাইল লোড করা
require_once __DIR__ . '/livekit-token.php';

// 3. শর্টকোড ও ফ্রন্টএন্ড UI ফাইল লোড করা
require_once __DIR__ . '/livekit-shortcode.php';
// 4. AJAX হ্যান্ডলার ও সার্ভার-সাইড লজিক
require_once __DIR__ . '/livekit-ajax.php';

// Enqueue assets for the frontend UI
add_action( 'wp_enqueue_scripts', function() {
    $dir = get_template_directory_uri() . '/mm-livekit';
    wp_register_script( 'mm-livekit-client', 'https://cdn.jsdelivr.net/npm/livekit-client/dist/livekit-client.umd.min.js', array(), null, true );
    wp_register_script( 'mm-livekit-ui', $dir . '/mm-livekit-ui.js', array( 'mm-livekit-client', 'jquery' ), null, true );
    wp_localize_script( 'mm-livekit-ui', 'MM_LIVEKIT_AJAX', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'mm_livekit_nonce' ),
    ) );
    wp_enqueue_script( 'mm-livekit-ui' );
    wp_enqueue_script( 'mm-livekit-client' );

    // Minimal inline styles can be kept in shortcode; advanced styling can be added here.
} );