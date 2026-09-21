<?php
/**
 * Social Media Connect AJAX Handlers
 * 
 * Handles disconnection and social media integration
 */

// Include auth handlers
require_once get_template_directory() . '/more_functions/facebook-auth.php';

// ============================================
// AJAX: Disconnect Social Account
// ============================================

add_action('wp_ajax_disconnect_social_account', 'handle_disconnect_social_account');

function handle_disconnect_social_account() {
    // Security checks
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Not authenticated'), 403);
    }
    
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'disconnect_social')) {
        wp_send_json_error(array('message' => 'Security verification failed'), 403);
    }
    
    $provider = sanitize_text_field($_POST['provider'] ?? '');
    $user_id = (int)($_POST['user_id'] ?? 0);
    
    // Verify user is disconnecting their own account
    if ($user_id !== get_current_user_id()) {
        wp_send_json_error(array('message' => 'Unauthorized'), 403);
    }
    
    // Validate provider
    if (!in_array($provider, array('facebook'))) {
        wp_send_json_error(array('message' => 'Invalid provider'), 400);
    }
    
    // Disconnect based on provider
    $disconnected = false;
    
    if ($provider === 'facebook') {
        $disconnected = disconnect_facebook_account($user_id);
    }
    
    if ($disconnected) {
        wp_send_json_success(array(
            'message' => ucfirst($provider) . ' account disconnected successfully'
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to disconnect account'));
    }
}

// ============================================
// OAuth Callback Router
// ============================================

function register_social_auth_pages() {
    // This will be called from functions.php to register the pages
    
    // Check if we're on the callback page
    if (isset($_GET['page']) && $_GET['page'] === 'social-auth-callback') {
        $provider = sanitize_text_field($_GET['provider'] ?? '');
        
        if ($provider === 'facebook') {
            handle_facebook_oauth_callback();
        } else {
            wp_die('Invalid provider');
        }
    }
}

// Handle the callback early
add_action('init', 'register_social_auth_pages', 1);

// ============================================
// OAuth Login Redirects (Frontend)
// ============================================

function handle_social_auth_login() {
    // Check if user is trying to connect (works on frontend)
    if (isset($_GET['page']) && $_GET['page'] === 'social-auth') {
        $provider = sanitize_text_field($_GET['provider'] ?? '');
        
        if (!is_user_logged_in()) {
            wp_redirect(wp_login_url());
            exit;
        }
        
        if ($provider === 'facebook') {
            wp_redirect(get_facebook_login_url());
            exit;
        } else {
            wp_die('Invalid provider');
        }
    }
}

add_action('init', 'handle_social_auth_login', 2);
