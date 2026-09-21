<?php
/**
 * Authentication bridge.
 *
 * Adds two ways for a client to authenticate against the `mm/v1` REST API:
 *
 *   1. The existing mm-spg nonce token  →  send `?nonce=<token>` (query / body)
 *                                          or header `X-MM-Nonce: <token>`.
 *      The token is the one returned by  POST /wp-json/api/v1/auth/login
 *      (from the mm-spg plugin). We resolve it via
 *      `mm_spg_verify_nonce_and_get_user()` and short-circuit
 *      `determine_current_user`, so `is_user_logged_in()` returns true and
 *      every existing permission_callback continues to work unchanged.
 *
 *   2. The standard WordPress cookie session + `X-WP-Nonce` header — used by
 *      the in-browser front-end. We don't have to do anything for this; WP
 *      handles it natively.
 *
 * This file is intentionally tiny — the rest of the plugin doesn't need to
 * know which path the request authenticated through.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Resolve the mm-spg-style token to a user id BEFORE permission callbacks run.
 *
 * We hook TWO places on purpose:
 *
 *   1. `determine_current_user` (priority 20)
 *        — works for the common case where nothing else has called
 *          `wp_get_current_user()` yet on this request.
 *
 *   2. `rest_authentication_errors` (priority 5)
 *        — safety net. If a caching/security plugin called
 *          `wp_get_current_user()` very early (during `plugins_loaded`,
 *          before our plugin loaded), WordPress caches that result as
 *          "guest" and the `determine_current_user` filter is never
 *          re-evaluated on subsequent calls. Here we forcefully call
 *          `wp_set_current_user()` which overrides that cached value.
 *
 * Either hook on its own is enough most of the time; together they cover
 * every production environment we've seen.
 */
add_filter( 'determine_current_user',    'mm_portal_api_authenticate', 20 );
add_filter( 'rest_authentication_errors', 'mm_portal_api_rest_auth',    5 );

function mm_portal_api_authenticate( $user_id ) {
    // Already authenticated by cookie / app password / another filter — leave it.
    if ( ! empty( $user_id ) ) {
        return $user_id;
    }

    if ( ! mm_portal_api_is_our_request() ) {
        return $user_id;
    }

    $resolved = mm_portal_api_resolve_token();
    return $resolved ?: $user_id;
}

/**
 * Runs at REST dispatch — guaranteed to fire after all plugins have loaded.
 * If we can resolve a token but `is_user_logged_in()` is still false, we
 * force-set the current user. Returning `null` means "no error, proceed".
 */
function mm_portal_api_rest_auth( $result ) {
    // Another plugin already produced an auth error — don't override.
    if ( is_wp_error( $result ) ) {
        return $result;
    }

    if ( ! mm_portal_api_is_our_request() ) {
        return $result;
    }

    // Already authenticated through cookies / app password — done.
    if ( is_user_logged_in() ) {
        return $result;
    }

    $resolved = mm_portal_api_resolve_token();
    if ( $resolved ) {
        wp_set_current_user( (int) $resolved );
        return $result;
    }

    // No token at all → let the friendlier 401 below handle it.
    // Token present but invalid → reject explicitly with a clear message.
    $token = mm_portal_api_extract_token();
    if ( $token !== '' ) {
        return new WP_Error(
            'mm_portal_api_invalid_token',
            __( 'Invalid or expired token. Re-authenticate via /wp-json/api/v1/auth/login.', 'mm-portal-api' ),
            [ 'status' => 401 ]
        );
    }

    return $result;
}

/**
 * Resolve the token (if any) to a user id. Returns 0 when no/invalid token.
 */
function mm_portal_api_resolve_token() {
    $token = mm_portal_api_extract_token();
    if ( $token === '' ) {
        return 0;
    }
    if ( ! function_exists( 'mm_spg_verify_nonce_and_get_user' ) ) {
        return 0;
    }
    $uid = mm_spg_verify_nonce_and_get_user( $token );
    return $uid ? (int) $uid : 0;
}

/**
 * True only for REST requests in this plugin's namespace.
 */
function mm_portal_api_is_our_request() {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
    return ( strpos( $uri, '/wp-json/' . MM_PORTAL_API_NAMESPACE ) !== false )
        || ( strpos( $uri, 'rest_route=/' . MM_PORTAL_API_NAMESPACE ) !== false );
}

/**
 * Pull the token from (in order): X-MM-Nonce header, ?nonce= query, body 'nonce'.
 */
function mm_portal_api_extract_token() {
    if ( ! empty( $_SERVER['HTTP_X_MM_NONCE'] ) ) {
        return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_MM_NONCE'] ) );
    }
    if ( isset( $_REQUEST['nonce'] ) && $_REQUEST['nonce'] !== '' ) {
        return sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) );
    }
    // JSON body case: WP only populates $_POST for form-encoded payloads.
    $raw = file_get_contents( 'php://input' );
    if ( $raw && ( $json = json_decode( $raw, true ) ) && ! empty( $json['nonce'] ) ) {
        return sanitize_text_field( $json['nonce'] );
    }
    return '';
}

/**
 * Friendlier 401 message when NO auth was provided. Registered at high priority
 * so it runs after `mm_portal_api_rest_auth` has had a chance to authenticate.
 */
add_filter( 'rest_authentication_errors', 'mm_portal_api_auth_error', 99 );

function mm_portal_api_auth_error( $result ) {
    if ( ! empty( $result ) ) {
        return $result;
    }
    if ( is_user_logged_in() ) {
        return $result;
    }
    if ( ! mm_portal_api_is_our_request() ) {
        return $result;
    }

    return new WP_Error(
        'mm_portal_api_unauthenticated',
        __( 'Authentication required. Send the mm-spg login nonce as ?nonce=<token>, header X-MM-Nonce, or use a logged-in browser session with X-WP-Nonce.', 'mm-portal-api' ),
        [ 'status' => 401 ]
    );
}

/* ---------------------------------------------------------------------------
 * Public diagnostic endpoint — DOES NOT require auth.
 *
 *   GET /wp-json/mm/v1/diagnostic?nonce=<token>
 *
 * Reports whether the auth bridge is wired correctly and whether the supplied
 * token (if any) resolves to a user. Safe to leave enabled — returns no PII
 * beyond the resolved user id / login.
 * ------------------------------------------------------------------------- */

add_action( 'rest_api_init', function () {
    register_rest_route( MM_PORTAL_API_NAMESPACE, '/diagnostic', [
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'callback'            => function ( WP_REST_Request $req ) {
            $token = mm_portal_api_extract_token();
            $resolved_id = 0;
            $resolved_login = null;

            if ( $token !== '' && function_exists( 'mm_spg_verify_nonce_and_get_user' ) ) {
                $r = mm_spg_verify_nonce_and_get_user( $token );
                if ( $r ) {
                    $resolved_id = (int) $r;
                    $u = get_user_by( 'id', $resolved_id );
                    $resolved_login = $u ? $u->user_login : null;
                }
            }

            return [
                'plugin_version'           => defined( 'MM_PORTAL_API_VERSION' ) ? MM_PORTAL_API_VERSION : null,
                'auth_php_loaded'          => function_exists( 'mm_portal_api_authenticate' ),
                'rest_auth_bridge_loaded'  => function_exists( 'mm_portal_api_rest_auth' ),
                'file_modified'            => date( 'Y-m-d H:i:s', filemtime( __FILE__ ) ),
                'mm_spg_function_exists'   => function_exists( 'mm_spg_verify_nonce_and_get_user' ),
                'mm_spg_plugin_active'     => is_plugin_active_helper( 'mm-spg/mm-spg.php' ),
                'mm_referral_chat_active'  => is_plugin_active_helper( 'mm-referral-chat/mm-referral-chat.php' ),
                'token_received'           => $token !== '',
                'token_length'             => strlen( $token ),
                'token_source'             => mm_portal_api_token_source(),
                'token_resolved_to_user'   => $resolved_id > 0,
                'resolved_user_id'         => $resolved_id ?: null,
                'resolved_user_login'      => $resolved_login,
                'is_user_logged_in'        => is_user_logged_in(),
                'current_user_id'          => get_current_user_id(),
                // Force-set the user RIGHT NOW and report if it sticks.
                'force_set_test'           => mm_portal_api_force_set_test( $resolved_id ),
                'request_uri'              => isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : null,
                'server_time'              => current_time( 'mysql' ),
            ];
        },
    ] );
} );

/**
 * Diagnostic helper: force-set the current user and report whether it stuck.
 * If `is_user_logged_in()` is FALSE after `wp_set_current_user()`, something
 * on this site is actively un-setting the current user (caching plugin,
 * security plugin, mu-plugin, theme code, etc.) — that's the real culprit.
 */
function mm_portal_api_force_set_test( $user_id ) {
    if ( ! $user_id ) {
        return [ 'attempted' => false, 'reason' => 'no_resolved_user' ];
    }
    wp_set_current_user( (int) $user_id );
    $u = wp_get_current_user();
    return [
        'attempted'             => true,
        'after_is_logged_in'    => is_user_logged_in(),
        'after_get_current_id'  => get_current_user_id(),
        'wp_get_current_user_id'=> $u ? (int) $u->ID : 0,
    ];
}

/**
 * Helper used only by the diagnostic — avoids requiring wp-admin includes.
 */
function is_plugin_active_helper( $plugin_file ) {
    $active = (array) get_option( 'active_plugins', [] );
    if ( in_array( $plugin_file, $active, true ) ) {
        return true;
    }
    // Network-activated?
    $network = (array) get_site_option( 'active_sitewide_plugins', [] );
    return isset( $network[ $plugin_file ] );
}

/**
 * Report which channel the diagnostic picked the token up from.
 */
function mm_portal_api_token_source() {
    if ( ! empty( $_SERVER['HTTP_X_MM_NONCE'] ) ) {
        return 'header:X-MM-Nonce';
    }
    if ( isset( $_GET['nonce'] ) && $_GET['nonce'] !== '' ) {
        return 'query:nonce';
    }
    if ( isset( $_POST['nonce'] ) && $_POST['nonce'] !== '' ) {
        return 'body:nonce(form)';
    }
    $raw = file_get_contents( 'php://input' );
    if ( $raw && ( $json = json_decode( $raw, true ) ) && ! empty( $json['nonce'] ) ) {
        return 'body:nonce(json)';
    }
    return 'none';
}
