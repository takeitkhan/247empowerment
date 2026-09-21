<?php
/**
 * MM SPG Authentication API Endpoints
 * 
 * Provides login and logout endpoints for testing and frontend authentication
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Authentication Endpoints
 */
add_action('rest_api_init', function () {
    
    // Login endpoint
    register_rest_route('api/v1/auth', '/login', [
        'methods'             => 'POST',
        'callback'            => 'mm_spg_api_login',
        'permission_callback' => '__return_true', // Allow anyone to attempt login
        'args'                => [
            'username' => [
                'type'     => 'string',
                'required' => true,
            ],
            'password' => [
                'type'     => 'string',
                'required' => true,
            ],
        ],
    ]);

    // Logout endpoint
    register_rest_route('api/v1/auth', '/logout', [
        'methods'             => 'POST',
        'callback'            => 'mm_spg_api_logout',
        'permission_callback' => '__return_true',
        'args'                => [
            'nonce' => [
                'type'     => 'string',
                'required' => true,
            ],
        ],
    ]);

    // Get current user endpoint
    register_rest_route('api/v1/auth', '/me', [
        'methods'             => 'GET',
        'callback'            => 'mm_spg_api_get_current_user',
        'permission_callback' => '__return_true',
        'args'                => [
            'nonce' => [
                'type'     => 'string',
                'required' => true,
            ],
        ],
    ]);
});

/**
 * Verify nonce and get user ID
 */
function mm_spg_verify_nonce_and_get_user($nonce) {
    // Find user by nonce stored in meta
    $users = get_users([
        'meta_key'     => 'mm_spg_api_nonce',
        'meta_value'   => $nonce,
        'fields'       => 'ID',
    ]);

    if (empty($users)) {
        return null;
    }

    $user_id = $users[0];
    
    // Check if nonce is still valid (store creation time and validate)
    $nonce_time = get_user_meta($user_id, 'mm_spg_api_nonce_time', true);
    $current_time = time();
    
    // Nonce valid for 24 hours
    if ($current_time - $nonce_time > 86400) {
        delete_user_meta($user_id, 'mm_spg_api_nonce');
        delete_user_meta($user_id, 'mm_spg_api_nonce_time');
        return null;
    }

    return $user_id;
}

/**
 * Login API Endpoint
 * 
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function mm_spg_api_login($request) {
    $username = $request->get_param('username');
    $password = $request->get_param('password');

    // Validate input
    if (empty($username) || empty($password)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Username and password are required',
            'code'    => 'missing_credentials',
        ], 400);
    }

    // Attempt to authenticate
    $user = wp_authenticate($username, $password);

    if (is_wp_error($user)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Invalid username or password',
            'code'    => 'invalid_credentials',
        ], 401);
    }

    // Generate nonce
    $nonce = wp_hash($user->ID . time() . wp_rand(), 'nonce');
    
    // Store nonce in user meta
    update_user_meta($user->ID, 'mm_spg_api_nonce', $nonce);
    update_user_meta($user->ID, 'mm_spg_api_nonce_time', time());

    // Log the user in
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true);

    do_action('wp_login', $user->user_login, $user);

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Login successful',
        'data'    => [
            'user_id'    => $user->ID,
            'username'   => $user->user_login,
            'email'      => $user->user_email,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'roles'      => $user->roles,
            'nonce'      => $nonce, // Return nonce for subsequent requests
        ],
    ], 200);
}

/**
 * Logout API Endpoint
 * 
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function mm_spg_api_logout($request) {
    $nonce = $request->get_param('nonce');

    if (empty($nonce)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Nonce is required',
        ], 400);
    }

    $user_id = mm_spg_verify_nonce_and_get_user($nonce);

    if (!$user_id) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Invalid or expired nonce',
            'code'    => 'invalid_nonce',
        ], 401);
    }

    // Clear nonce
    delete_user_meta($user_id, 'mm_spg_api_nonce');
    delete_user_meta($user_id, 'mm_spg_api_nonce_time');

    wp_logout();

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Logged out successfully',
    ], 200);
}

/**
 * Get Current User Endpoint
 * Returns complete user profile with all metadata
 * 
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function mm_spg_api_get_current_user($request) {
    $nonce = $request->get_param('nonce');

    if (empty($nonce)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Nonce is required',
        ], 400);
    }

    $user_id = mm_spg_verify_nonce_and_get_user($nonce);

    if (!$user_id) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Invalid or expired nonce',
            'code'    => 'invalid_nonce',
        ], 401);
    }

    $user = get_user_by('id', $user_id);

    // Get all user meta data
    $user_meta = get_user_meta($user_id);
    
    // Extract key profile data
    $profile = [
        'user_id'       => $user->ID,
        'username'      => $user->user_login,
        'email'         => $user->user_email,
        'first_name'    => $user->first_name,
        'last_name'     => $user->last_name,
        'display_name'  => $user->display_name,
        'roles'         => $user->roles,
        'registered'    => $user->user_registered,
    ];

    // Add gamification data
    $earned_logs = get_user_meta($user_id, 'earned_points_logs', true) ?: [];
    $total_points = 0;
    
    if (!empty($earned_logs) && is_array($earned_logs)) {
        foreach ($earned_logs as $log) {
            if (isset($log['points']) && is_numeric($log['points'])) {
                $total_points += (float)$log['points'];
            }
        }
    }
    
    $profile['gamification'] = [
        'total_points'  => $total_points,
        'points_logs'   => $earned_logs,
        'level'         => (int) (get_user_meta($user_id, 'user_level', true) ?: 1),
        'badges'        => get_user_meta($user_id, 'user_badges', true) ?: [],
        'achievements'  => get_user_meta($user_id, 'user_achievements', true) ?: [],
    ];

    // Add SPG (Sweet Portal Guide) progress
    $profile['guide_progress'] = [
        'current_phase'         => (int) (get_user_meta($user_id, 'mm_spg_status', true) ?: 0),
        'current_step'          => (int) (get_user_meta($user_id, 'mm_spg_step', true) ?: 0),
        'phase_2_completed'     => (bool) get_user_meta($user_id, 'mm_spg_phase_2_completed', true),
        'phase_3_started'       => (bool) get_user_meta($user_id, 'mm_spg_phase_3_started', true),
        'interest_completed'    => (bool) get_user_meta($user_id, 'mm_spg_interest_completed', true),
    ];

    // Add user interests with priorities
    $interests = get_user_meta($user_id, 'user_categories', true) ?: [];
    $priorities = get_user_meta($user_id, 'user_categories_priority', true) ?: [];
    
    $formatted_interests = [];
    if (!empty($interests)) {
        foreach ($interests as $term_id) {
            $term = get_term($term_id);
            if (!is_wp_error($term) && $term) {
                $formatted_interests[] = [
                    'id'       => $term->term_id,
                    'name'     => $term->name,
                    'slug'     => $term->slug,
                    'priority' => (int) ($priorities[$term_id] ?? 0),
                ];
            }
        }
    }

    $profile['interests'] = [
        'selected'      => $formatted_interests,
        'count'         => count($formatted_interests),
        'completed'     => (bool) get_user_meta($user_id, 'mm_spg_interest_completed', true),
    ];

    // Add additional profile fields
    $profile['profile_fields'] = [
        'avatar_url'        => get_avatar_url($user_id, ['size' => 96]),
        'bio'               => get_user_meta($user_id, 'description', true),
        'phone'             => get_user_meta($user_id, 'phone', true),
        'company'           => get_user_meta($user_id, 'company', true),
        'website'           => $user->user_url,
        'location'          => get_user_meta($user_id, 'location', true),
        'social_profiles'   => get_user_meta($user_id, 'social_profiles', true) ?: [],
    ];

    // Add completion status
    $profile['completion_status'] = [
        'interests'         => (bool) get_user_meta($user_id, 'mm_spg_interest_completed', true),
        'business_card'     => (bool) get_user_meta($user_id, 'mm_spg_business_card_completed', true),
        'social_links'      => (bool) get_user_meta($user_id, 'mm_spg_social_links_completed', true),
        'overall_progress'  => mm_spg_calculate_completion_percentage($user_id),
    ];

    return new WP_REST_Response([
        'success' => true,
        'data'    => $profile,
    ], 200);
}

/**
 * Calculate user completion percentage
 */
function mm_spg_calculate_completion_percentage($user_id) {
    $total_steps = 3; // Interests, Business Card, Social Links
    $completed = 0;
    
    if (get_user_meta($user_id, 'mm_spg_interest_completed', true)) {
        $completed++;
    }
    if (get_user_meta($user_id, 'mm_spg_business_card_completed', true)) {
        $completed++;
    }
    if (get_user_meta($user_id, 'mm_spg_social_links_completed', true)) {
        $completed++;
    }
    
    return round(($completed / $total_steps) * 100, 2);
}
