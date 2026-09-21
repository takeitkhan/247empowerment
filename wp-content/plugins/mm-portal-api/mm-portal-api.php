<?php
/**
 * Plugin Name: MM Portal API
 * Description: REST API endpoints for the 247 Empowerment portal: users, referral partners, recent chats and 1:1 chat (with long-poll realtime). Uses WP cookie + nonce auth.
 * Version:     1.0.0
 * Author:      MM Team
 * License:     MIT
 * Text Domain: mm-portal-api
 *
 * Depends on:  mm-referral-chat (DB + chat helpers), 247empowerment theme (UserProfileData).
 *
 * All routes are registered under the `mm/v1` namespace.
 *
 *   GET  /wp-json/mm/v1/users              ?page&per_page&search&interest
 *   GET  /wp-json/mm/v1/referrals
 *   GET  /wp-json/mm/v1/chats              ?page&per_page
 *   POST /wp-json/mm/v1/chats              {user_id}
 *   GET  /wp-json/mm/v1/chats/{id}/messages?limit&before_id&since_id&wait
 *   POST /wp-json/mm/v1/chats/{id}/messages {message}
 *   POST /wp-json/mm/v1/chats/{id}/read
 *   GET  /wp-json/mm/v1/chats/unread-count
 *
 * Authentication: any logged-in user. Browser clients must send the standard
 * WordPress REST nonce header `X-WP-Nonce` (created via wp_create_nonce('wp_rest')).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MM_PORTAL_API_VERSION', '1.0.0' );
define( 'MM_PORTAL_API_PATH', plugin_dir_path( __FILE__ ) );
define( 'MM_PORTAL_API_NAMESPACE', 'mm/v1' );

require_once MM_PORTAL_API_PATH . 'includes/auth.php';
require_once MM_PORTAL_API_PATH . 'includes/formatter.php';
require_once MM_PORTAL_API_PATH . 'includes/rest-users.php';
require_once MM_PORTAL_API_PATH . 'includes/rest-chats.php';

if ( is_admin() ) {
    require_once MM_PORTAL_API_PATH . 'includes/admin-docs.php';
}

/**
 * Register all REST routes.
 */
add_action( 'rest_api_init', function () {
    MM_Portal_REST_Users::register_routes();
    MM_Portal_REST_Chats::register_routes();
} );

/**
 * Convenience: expose the `wp_rest` nonce to logged-in front-end pages so
 * a JS client can read it from `window.MM_PORTAL_API`.
 *
 * Front-end usage:
 *   fetch('/wp-json/mm/v1/users', {
 *       credentials: 'same-origin',
 *       headers: { 'X-WP-Nonce': MM_PORTAL_API.nonce }
 *   })
 */
add_action( 'wp_enqueue_scripts', function () {
    if ( ! is_user_logged_in() ) {
        return;
    }

    // Tiny inline handle just to carry the localized object; no JS file needed.
    wp_register_script( 'mm-portal-api-bridge', '', [], MM_PORTAL_API_VERSION, true );
    wp_enqueue_script( 'mm-portal-api-bridge' );
    wp_localize_script( 'mm-portal-api-bridge', 'MM_PORTAL_API', [
        'root'      => esc_url_raw( rest_url( MM_PORTAL_API_NAMESPACE . '/' ) ),
        'namespace' => MM_PORTAL_API_NAMESPACE,
        'nonce'     => wp_create_nonce( 'wp_rest' ),
        'user_id'   => get_current_user_id(),
    ] );
} );
