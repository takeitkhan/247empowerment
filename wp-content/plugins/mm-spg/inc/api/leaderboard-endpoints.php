<?php
/**
 * MM SPG Leaderboard API Endpoints
 * Handles leaderboard rankings based on user completion progress and points
 * Prefix: api/v1/spg/leaderboard
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register REST API routes for leaderboard
 */
add_action('rest_api_init', function () {
    
    // GET: Fetch global leaderboard
    register_rest_route('api/v1/spg', '/leaderboard', [
        'methods'             => 'GET',
        'callback'            => 'mm_spg_api_get_leaderboard',
        'permission_callback' => '__return_true',
        'args'                => [
            'nonce' => [
                'type'     => 'string',
                'required' => true,
            ],
            'limit' => [
                'type'    => 'integer',
                'default' => 50,
                'minimum' => 1,
                'maximum' => 100,
            ],
            'offset' => [
                'type'    => 'integer',
                'default' => 0,
                'minimum' => 0,
            ],
        ],
    ]);

    // GET: Fetch user's rank and surrounding users
    register_rest_route('api/v1/spg', '/leaderboard/user-rank', [
        'methods'             => 'GET',
        'callback'            => 'mm_spg_api_get_user_rank',
        'permission_callback' => '__return_true',
        'args'                => [
            'nonce' => [
                'type'     => 'string',
                'required' => true,
            ],
            'context_size' => [
                'type'    => 'integer',
                'default' => 5,
                'minimum' => 1,
                'maximum' => 10,
            ],
        ],
    ]);

    // GET: Fetch leaderboard statistics
    register_rest_route('api/v1/spg', '/leaderboard/stats', [
        'methods'             => 'GET',
        'callback'            => 'mm_spg_api_get_leaderboard_stats',
        'permission_callback' => '__return_true',
        'args'                => [
            'nonce' => [
                'type'     => 'string',
                'required' => true,
            ],
        ],
    ]);

    // GET: Fetch current logged-in user's earned points breakdown
    register_rest_route('api/v1/spg', '/user/earned-points', [
        'methods'             => 'GET',
        'callback'            => 'mm_spg_api_get_user_earned_points',
        'permission_callback' => '__return_true',
        'args'                => [
            'nonce' => [
                'type'     => 'string',
                'required' => true,
            ],
        ],
    ]);

    // POST: Award points to user
    register_rest_route('api/v1/spg', '/leaderboard/award-points', [
        'methods'             => 'POST',
        'callback'            => 'mm_spg_api_award_points',
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
 * Helper: Get user's earned points breakdown from earned_points_logs
 */
function mm_spg_get_user_earned_points_breakdown($user_id) {
    $earned_logs = get_user_meta($user_id, 'earned_points_logs', true) ?: [];
    
    if (!is_array($earned_logs)) {
        $earned_logs = [];
    }

    // Group points by action_key
    $breakdown = [];
    $total = 0;

    foreach ($earned_logs as $log) {
        if (isset($log['action_key']) && isset($log['points'])) {
            $action_key = $log['action_key'];
            $points = (float) $log['points'];
            
            if (!isset($breakdown[$action_key])) {
                $breakdown[$action_key] = 0;
            }
            $breakdown[$action_key] += $points;
            $total += $points;
        }
    }

    // Add total
    $breakdown['total'] = $total;

    return $breakdown;
}

/**
 * GET /api/v1/spg/leaderboard
 * Fetch global leaderboard with pagination
 */
function mm_spg_api_get_leaderboard(WP_REST_Request $request) {
    $nonce = $request->get_param('nonce');
    $limit = $request->get_param('limit');
    $offset = $request->get_param('offset');

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

    // Get all users with their points and completion data
    $users = get_users([
        'number' => -1,
        'fields' => 'ID',
    ]);

    if (empty($users)) {
        return new WP_REST_Response([
            'success' => true,
            'data'    => [],
            'pagination' => [
                'total'  => 0,
                'limit'  => $limit,
                'offset' => $offset,
            ],
        ], 200);
    }

    // Build leaderboard data
    $leaderboard_data = [];

    foreach ($users as $user_id_item) {
        $points = (int) get_user_meta($user_id_item, 'mm_spg_points', true) ?: 0;
        $completion = mm_spg_calculate_completion_percentage($user_id_item);
        $earned_points_breakdown = mm_spg_get_user_earned_points_breakdown($user_id_item);

        $leaderboard_data[] = [
            'user_id'           => $user_id_item,
            'username'          => get_userdata($user_id_item)->user_login,
            'display_name'      => get_userdata($user_id_item)->display_name,
            'profile_photo'     => get_user_meta($user_id_item, 'profile_photo', true),
            'points'            => $points,
            'completion'        => $completion,
            'interests'         => (bool) get_user_meta($user_id_item, 'mm_spg_interest_completed', true),
            'business_card'     => (bool) get_user_meta($user_id_item, 'mm_spg_business_card_completed', true),
            'social_links'      => (bool) get_user_meta($user_id_item, 'mm_spg_social_links_completed', true),
            'user_earned_points' => $earned_points_breakdown,
        ];
    }

    // Sort by points (descending), then by completion (descending)
    usort($leaderboard_data, function ($a, $b) {
        if ($b['points'] !== $a['points']) {
            return $b['points'] - $a['points'];
        }
        return $b['completion'] - $a['completion'];
    });

    // Add rank to each user
    foreach ($leaderboard_data as $idx => &$entry) {
        $entry['rank'] = $idx + 1;
    }

    // Apply pagination
    $total = count($leaderboard_data);
    $paginated_data = array_slice($leaderboard_data, $offset, $limit);

    return new WP_REST_Response([
        'success' => true,
        'data'    => $paginated_data,
        'pagination' => [
            'total'  => $total,
            'limit'  => $limit,
            'offset' => $offset,
            'pages'  => ceil($total / $limit),
        ],
    ], 200);
}

/**
 * GET /api/v1/spg/leaderboard/user-rank
 * Get user's rank with surrounding context
 */
function mm_spg_api_get_user_rank(WP_REST_Request $request) {
    $nonce = $request->get_param('nonce');
    $context_size = $request->get_param('context_size');

    if (empty($nonce)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Nonce is required',
        ], 400);
    }

    $current_user_id = mm_spg_verify_nonce_and_get_user($nonce);

    if (!$current_user_id) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Invalid or expired nonce',
            'code'    => 'invalid_nonce',
        ], 401);
    }

    // Get all users
    $users = get_users([
        'number' => -1,
        'fields' => 'ID',
    ]);

    if (empty($users)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'No users found',
        ], 404);
    }

    // Build leaderboard data
    $leaderboard_data = [];

    foreach ($users as $user_id_item) {
        $points = (int) get_user_meta($user_id_item, 'mm_spg_points', true) ?: 0;
        $completion = mm_spg_calculate_completion_percentage($user_id_item);
        $earned_points_breakdown = mm_spg_get_user_earned_points_breakdown($user_id_item);

        $leaderboard_data[] = [
            'user_id'           => $user_id_item,
            'username'          => get_userdata($user_id_item)->user_login,
            'display_name'      => get_userdata($user_id_item)->display_name,
            'profile_photo'     => get_user_meta($user_id_item, 'profile_photo', true),
            'points'            => $points,
            'completion'        => $completion,
            'interests'         => (bool) get_user_meta($user_id_item, 'mm_spg_interest_completed', true),
            'business_card'     => (bool) get_user_meta($user_id_item, 'mm_spg_business_card_completed', true),
            'social_links'      => (bool) get_user_meta($user_id_item, 'mm_spg_social_links_completed', true),
            'user_earned_points' => $earned_points_breakdown,
        ];
    }

    // Sort by points and completion
    usort($leaderboard_data, function ($a, $b) {
        if ($b['points'] !== $a['points']) {
            return $b['points'] - $a['points'];
        }
        return $b['completion'] - $a['completion'];
    });

    // Find current user's rank
    $user_rank = 0;
    $user_data = null;

    foreach ($leaderboard_data as $idx => $entry) {
        if ($entry['user_id'] === $current_user_id) {
            $user_rank = $idx + 1;
            $user_data = $entry;
            $user_data['rank'] = $user_rank;
            break;
        }
    }

    if (!$user_data) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'User not found in leaderboard',
        ], 404);
    }

    // Get surrounding users (context)
    $start_idx = max(0, $user_rank - 1 - $context_size);
    $end_idx = min(count($leaderboard_data), $user_rank + $context_size);

    $surrounding = [];
    for ($i = $start_idx; $i < $end_idx; $i++) {
        $surrounding[] = array_merge($leaderboard_data[$i], ['rank' => $i + 1]);
    }

    return new WP_REST_Response([
        'success' => true,
        'data'    => [
            'user_rank'     => $user_data,
            'surrounding'   => $surrounding,
            'total_users'   => count($leaderboard_data),
        ],
    ], 200);
}

/**
 * GET /api/v1/spg/leaderboard/stats
 * Get leaderboard statistics
 */
function mm_spg_api_get_leaderboard_stats(WP_REST_Request $request) {
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

    // Get all users
    $users = get_users([
        'number' => -1,
        'fields' => 'ID',
    ]);

    if (empty($users)) {
        return new WP_REST_Response([
            'success' => true,
            'data'    => [
                'total_users'           => 0,
                'avg_points'            => 0,
                'max_points'            => 0,
                'avg_completion'        => 0,
                'users_completed'       => 0,
                'users_in_progress'     => 0,
                'users_not_started'     => 0,
            ],
        ], 200);
    }

    // Calculate statistics
    $all_points = [];
    $all_completions = [];
    $completed_count = 0;
    $in_progress_count = 0;
    $not_started_count = 0;

    foreach ($users as $user_id_item) {
        $points = (int) get_user_meta($user_id_item, 'mm_spg_points', true) ?: 0;
        $completion = mm_spg_calculate_completion_percentage($user_id_item);

        $all_points[] = $points;
        $all_completions[] = $completion;

        if ($completion === 100) {
            $completed_count++;
        } elseif ($completion > 0) {
            $in_progress_count++;
        } else {
            $not_started_count++;
        }
    }

    $avg_points = !empty($all_points) ? array_sum($all_points) / count($all_points) : 0;
    $max_points = !empty($all_points) ? max($all_points) : 0;
    $avg_completion = !empty($all_completions) ? array_sum($all_completions) / count($all_completions) : 0;

    return new WP_REST_Response([
        'success' => true,
        'data'    => [
            'total_users'           => count($users),
            'avg_points'            => round($avg_points, 2),
            'max_points'            => $max_points,
            'avg_completion'        => round($avg_completion, 2),
            'users_completed'       => $completed_count,
            'users_in_progress'     => $in_progress_count,
            'users_not_started'     => $not_started_count,
        ],
    ], 200);
}

/**
 * POST /api/v1/spg/leaderboard/award-points
 * Award points to user (e.g., for completing steps)
 */
function mm_spg_api_award_points(WP_REST_Request $request) {
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
    $points = isset($body['points']) ? intval($body['points']) : 0;
    $reason = $body['reason'] ?? 'manual_award';

    // Validation
    if (empty($points) || $points <= 0) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Points must be greater than 0',
        ], 400);
    }

    if ($points > 1000) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Points cannot exceed 1000 per request',
        ], 400);
    }

    // Get current points
    $current_points = (int) get_user_meta($user_id, 'mm_spg_points', true) ?: 0;
    $new_points = $current_points + $points;

    // Update user points
    update_user_meta($user_id, 'mm_spg_points', $new_points);

    // Log the transaction
    $history = get_user_meta($user_id, 'mm_spg_points_history', true) ?: [];
    if (!is_array($history)) {
        $history = [];
    }

    $history[] = [
        'points'    => $points,
        'reason'    => $reason,
        'timestamp' => current_time('mysql'),
        'total'     => $new_points,
    ];

    update_user_meta($user_id, 'mm_spg_points_history', $history);

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Points awarded successfully',
        'data'    => [
            'user_id'      => $user_id,
            'points_added' => $points,
            'total_points' => $new_points,
            'reason'       => $reason,
        ],
    ], 200);
}

/**
 * GET /api/v1/spg/user/earned-points
 * Get current logged-in user's earned points breakdown
 */
function mm_spg_api_get_user_earned_points(WP_REST_Request $request) {
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

    // Get user data
    $user = get_userdata($user_id);
    
    // Get earned points breakdown
    $earned_points_breakdown = mm_spg_get_user_earned_points_breakdown($user_id);
    
    // Get total points from meta
    $total_points = (int) get_user_meta($user_id, 'mm_spg_points', true) ?: 0;
    
    // Get points history
    $points_history = get_user_meta($user_id, 'mm_spg_points_history', true) ?: [];
    if (!is_array($points_history)) {
        $points_history = [];
    }

    return new WP_REST_Response([
        'success' => true,
        'data'    => [
            'user_id'              => $user_id,
            'username'             => $user->user_login,
            'display_name'         => $user->display_name,
            'email'                => $user->user_email,
            'user_earned_points'   => $earned_points_breakdown,
            'total_points'         => $total_points,
            'completion'           => mm_spg_calculate_completion_percentage($user_id),
            'points_history'       => array_slice($points_history, -20), // Last 20 transactions
        ],
    ], 200);
}
