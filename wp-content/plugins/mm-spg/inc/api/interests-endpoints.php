<?php
/**
 * MM SPG Interests API Endpoints
 * Prefix: api/v1/spg/interests
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register REST API routes for interests
 */
add_action('rest_api_init', function () {
    // GET: Fetch all available interests
    register_rest_route('api/v1/spg', '/interests', [
        'methods'             => 'GET',
        'callback'            => 'mm_spg_api_get_interests',
        'permission_callback' => '__return_true',
        'args'                => [
            'nonce' => [
                'type'     => 'string',
                'required' => true,
            ],
        ],
    ]);

    // POST: Save user interests with priority
    register_rest_route('api/v1/spg', '/interests/save', [
        'methods'             => 'POST',
        'callback'            => 'mm_spg_api_save_interests',
        'permission_callback' => '__return_true',
        'args'                => [
            'nonce' => [
                'type'     => 'string',
                'required' => true,
            ],
        ],
    ]);

    // GET: Fetch user's saved interests
    register_rest_route('api/v1/spg', '/interests/user', [
        'methods'             => 'GET',
        'callback'            => 'mm_spg_api_get_user_interests',
        'permission_callback' => '__return_true',
        'args'                => [
            'nonce' => [
                'type'     => 'string',
                'required' => true,
            ],
        ],
    ]);

    // DELETE: Clear user interests
    register_rest_route('api/v1/spg', '/interests/clear', [
        'methods'             => 'DELETE',
        'callback'            => 'mm_spg_api_clear_interests',
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
 * GET /api/v1/spg/interests
 * Fetch all available interests (categories)
 */
function mm_spg_api_get_interests(WP_REST_Request $request) {
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

    $interests = get_terms([
        'taxonomy'   => 'category',
        'hide_empty' => false,
    ]);

    if (is_wp_error($interests)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Failed to fetch interests',
            'error'   => $interests->get_error_message()
        ], 400);
    }

    $formatted = array_map(function ($term) {
        return [
            'id'   => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
        ];
    }, $interests);

    return new WP_REST_Response([
        'success' => true,
        'data'    => $formatted,
        'count'   => count($formatted)
    ], 200);
}

/**
 * POST /api/v1/spg/interests/save
 * Save user interests with priorities
 */
function mm_spg_api_save_interests(WP_REST_Request $request) {
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

    $body = json_decode($request->get_body(), true);
    $interests = $body['interests'] ?? [];

    // Validation: At least one interest
    if (empty($interests)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'At least one interest must be selected',
            'code'    => 'empty_interests'
        ], 400);
    }

    // Extract IDs and priorities
    $interest_ids = [];
    $priorities_map = [];
    $has_first_priority = false;

    foreach ($interests as $interest) {
        $id = (int) $interest['id'];
        $priority = (int) $interest['priority'];

        // Validate priority (1-5)
        if ($priority < 1 || $priority > 5) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Invalid priority. Must be 1-5',
                'code'    => 'invalid_priority'
            ], 400);
        }

        $interest_ids[] = $id;
        $priorities_map[$id] = $priority;

        if ($priority === 1) {
            $has_first_priority = true;
        }
    }

    // Validation: At least one first priority
    if (!$has_first_priority) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'At least one interest must be assigned 1st priority',
            'code'    => 'no_first_priority'
        ], 400);
    }

    // Validation: No duplicate priorities
    $unique_priorities = array_unique(array_values($priorities_map));
    if (count($unique_priorities) !== count($priorities_map)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Duplicate priorities not allowed',
            'code'    => 'duplicate_priorities'
        ], 400);
    }

    // Save to user meta
    update_user_meta($user_id, 'user_categories', $interest_ids);
    update_user_meta($user_id, 'user_categories_priority', $priorities_map);
    update_user_meta($user_id, 'mm_spg_interest_completed', 1);

    // Update phase info
    update_user_meta($user_id, 'mm_spg_phase_2_completed', 1);
    update_user_meta($user_id, 'mm_spg_step', 0);
    update_user_meta($user_id, 'mm_spg_status', 'active');
    delete_user_meta($user_id, 'mm_spg_phase_3_started');

    // Award points
    if (function_exists('mm_award_points_and_notify')) {
        mm_award_points_and_notify($user_id, 'interest_completed');
    }

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Interests saved successfully',
        'data'    => [
            'interest_ids' => $interest_ids,
            'priorities'   => $priorities_map,
            'saved_at'     => current_time('mysql'),
            'phase_2_completed' => true
        ]
    ], 200);
}

/**
 * GET /api/v1/spg/interests/user
 * Fetch user's saved interests with priorities and names
 */
function mm_spg_api_get_user_interests(WP_REST_Request $request) {
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

    $interest_ids = get_user_meta($user_id, 'user_categories', true) ?: [];
    $priorities = get_user_meta($user_id, 'user_categories_priority', true) ?: [];
    $is_completed = get_user_meta($user_id, 'mm_spg_interest_completed', true);

    if (empty($interest_ids)) {
        return new WP_REST_Response([
            'success' => true,
            'message' => 'No interests saved yet',
            'data'    => [
                'interests'   => [],
                'completed'   => false,
                'saved_count' => 0
            ]
        ], 200);
    }

    // Get term details
    $interests_formatted = [];
    foreach ($interest_ids as $term_id) {
        $term = get_term($term_id);
        if (!is_wp_error($term) && $term) {
            $interests_formatted[] = [
                'id'       => $term->term_id,
                'name'     => $term->name,
                'slug'     => $term->slug,
                'priority' => $priorities[$term_id] ?? null
            ];
        }
    }

    // Sort by priority
    usort($interests_formatted, function ($a, $b) {
        return $a['priority'] <=> $b['priority'];
    });

    return new WP_REST_Response([
        'success' => true,
        'data'    => [
            'interests'   => $interests_formatted,
            'completed'   => (bool) $is_completed,
            'saved_count' => count($interests_formatted)
        ]
    ], 200);
}

/**
 * DELETE /api/v1/spg/interests/clear
 * Clear user's saved interests
 */
function mm_spg_api_clear_interests(WP_REST_Request $request) {
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

    delete_user_meta($user_id, 'user_categories');
    delete_user_meta($user_id, 'user_categories_priority');
    delete_user_meta($user_id, 'mm_spg_interest_completed');

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Interests cleared successfully'
    ], 200);
}
