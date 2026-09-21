<?php
/**
 * MM SPG Social Links API Endpoints
 * Handles social media profile links
 * Prefix: api/v1/spg/social-links
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register REST API routes for social links
 */
add_action('rest_api_init', function () {
    
    // GET: Fetch available social platforms
    register_rest_route('api/v1/spg', '/social-links/platforms', [
        'methods'             => 'GET',
        'callback'            => 'mm_spg_api_get_social_platforms',
        'permission_callback' => '__return_true',
        'args'                => [
            'nonce' => [
                'type'     => 'string',
                'required' => true,
            ],
        ],
    ]);

    // POST: Save user social links
    register_rest_route('api/v1/spg', '/social-links/save', [
        'methods'             => 'POST',
        'callback'            => 'mm_spg_api_save_social_links',
        'permission_callback' => '__return_true',
        'args'                => [
            'nonce' => [
                'type'     => 'string',
                'required' => true,
            ],
        ],
    ]);

    // GET: Fetch user's saved social links
    register_rest_route('api/v1/spg', '/social-links/user', [
        'methods'             => 'GET',
        'callback'            => 'mm_spg_api_get_user_social_links',
        'permission_callback' => '__return_true',
        'args'                => [
            'nonce' => [
                'type'     => 'string',
                'required' => true,
            ],
        ],
    ]);

    // DELETE: Clear user social links
    register_rest_route('api/v1/spg', '/social-links/clear', [
        'methods'             => 'DELETE',
        'callback'            => 'mm_spg_api_clear_social_links',
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
 * Get available social platforms
 */
function mm_spg_get_available_platforms() {
    return [
        [
            'platform' => 'linkedin',
            'label'    => 'LinkedIn',
            'icon'     => 'fab fa-linkedin',
            'base_url' => 'https://linkedin.com/in/',
            'placeholder' => 'https://linkedin.com/in/yourprofile',
        ],
        [
            'platform' => 'twitter',
            'label'    => 'Twitter',
            'icon'     => 'fab fa-twitter',
            'base_url' => 'https://twitter.com/',
            'placeholder' => 'https://twitter.com/yourhandle',
        ],
        [
            'platform' => 'facebook',
            'label'    => 'Facebook',
            'icon'     => 'fab fa-facebook',
            'base_url' => 'https://facebook.com/',
            'placeholder' => 'https://facebook.com/yourprofile',
        ],
        [
            'platform' => 'instagram',
            'label'    => 'Instagram',
            'icon'     => 'fab fa-instagram',
            'base_url' => 'https://instagram.com/',
            'placeholder' => 'https://instagram.com/yourhandle',
        ],
        [
            'platform' => 'github',
            'label'    => 'GitHub',
            'icon'     => 'fab fa-github',
            'base_url' => 'https://github.com/',
            'placeholder' => 'https://github.com/yourprofile',
        ],
        [
            'platform' => 'youtube',
            'label'    => 'YouTube',
            'icon'     => 'fab fa-youtube',
            'base_url' => 'https://youtube.com/@',
            'placeholder' => 'https://youtube.com/@yourchannel',
        ],
        [
            'platform' => 'tiktok',
            'label'    => 'TikTok',
            'icon'     => 'fab fa-tiktok',
            'base_url' => 'https://tiktok.com/@',
            'placeholder' => 'https://tiktok.com/@yourhandle',
        ],
        [
            'platform' => 'pinterest',
            'label'    => 'Pinterest',
            'icon'     => 'fab fa-pinterest',
            'base_url' => 'https://pinterest.com/',
            'placeholder' => 'https://pinterest.com/yourprofile',
        ],
    ];
}

/**
 * GET /api/v1/spg/social-links/platforms
 * Return available social media platforms
 */
function mm_spg_api_get_social_platforms(WP_REST_Request $request) {
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

    $platforms = mm_spg_get_available_platforms();

    return new WP_REST_Response([
        'success' => true,
        'data'    => $platforms,
        'count'   => count($platforms),
    ], 200);
}

/**
 * POST /api/v1/spg/social-links/save
 * Save user social links
 */
function mm_spg_api_save_social_links(WP_REST_Request $request) {
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

    $social_links = $request->get_param('social_links') ?: [];

    if (empty($social_links) || !is_array($social_links)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'At least one social link must be provided',
            'code'    => 'empty_social_links',
        ], 400);
    }

    // Validate and sanitize social links
    $valid_platforms = array_map(function($p) { return $p['platform']; }, mm_spg_get_available_platforms());
    $sanitized_links = [];
    $valid_count = 0;

    foreach ($social_links as $platform => $url) {
        if (!in_array($platform, $valid_platforms)) {
            continue; // Skip invalid platforms
        }

        if (empty($url)) {
            continue; // Skip empty URLs
        }

        $url = esc_url_raw($url);
        if (!empty($url)) {
            $sanitized_links[$platform] = $url;
            $valid_count++;
        }
    }

    if ($valid_count === 0) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'At least one valid social link must be provided',
            'code'    => 'no_valid_social_links',
        ], 400);
    }

    // Save to user meta
    update_user_meta($user_id, 'mm_spg_social_links', $sanitized_links);
    update_user_meta($user_id, 'mm_spg_social_links_completed', 1);

    // Mark onboarding complete if all steps done
    $all_completed = false;
    if (get_user_meta($user_id, 'mm_spg_interest_completed', true) &&
        get_user_meta($user_id, 'mm_spg_business_card_completed', true) &&
        get_user_meta($user_id, 'mm_spg_social_links_completed', true)) {
        update_user_meta($user_id, 'mm_spg_onboarding_completed', 1);
        $all_completed = true;
    }

    // Award points
    if (function_exists('mm_award_points_and_notify')) {
        mm_award_points_and_notify($user_id, 'social_links_completed');
        
        // Award bonus points if all steps completed
        if ($all_completed) {
            mm_award_points_and_notify($user_id, 'onboarding_completed');
        }
    }

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Social links saved successfully',
        'data'    => [
            'social_links' => $sanitized_links,
            'saved_at'     => current_time('mysql'),
            'count'        => $valid_count,
        ],
    ], 200);
}

/**
 * GET /api/v1/spg/social-links/user
 * Fetch user's saved social links
 */
function mm_spg_api_get_user_social_links(WP_REST_Request $request) {
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

    $social_links = get_user_meta($user_id, 'mm_spg_social_links', true) ?: [];
    $is_completed = (bool) get_user_meta($user_id, 'mm_spg_social_links_completed', true);

    if (empty($social_links)) {
        return new WP_REST_Response([
            'success' => true,
            'message' => 'No social links saved yet',
            'data'    => [
                'social_links' => [],
                'completed'    => false,
                'count'        => 0,
            ],
        ], 200);
    }

    // Enhance with platform info
    $platforms = array_key_by(mm_spg_get_available_platforms(), 'platform');
    $enhanced_links = [];

    foreach ($social_links as $platform => $url) {
        $enhanced_links[] = [
            'platform'    => $platform,
            'label'       => $platforms[$platform]['label'] ?? $platform,
            'url'         => $url,
            'icon'        => $platforms[$platform]['icon'] ?? '',
        ];
    }

    return new WP_REST_Response([
        'success' => true,
        'data'    => [
            'social_links' => $enhanced_links,
            'completed'    => $is_completed,
            'count'        => count($enhanced_links),
        ],
    ], 200);
}

/**
 * DELETE /api/v1/spg/social-links/clear
 * Clear user's social links
 */
function mm_spg_api_clear_social_links(WP_REST_Request $request) {
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

    delete_user_meta($user_id, 'mm_spg_social_links');
    delete_user_meta($user_id, 'mm_spg_social_links_completed');

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Social links cleared successfully',
    ], 200);
}

/**
 * Helper function to key array by field
 */
function array_key_by($array, $key) {
    return array_reduce($array, function($result, $item) use ($key) {
        if (isset($item[$key])) {
            $result[$item[$key]] = $item;
        }
        return $result;
    }, []);
}
