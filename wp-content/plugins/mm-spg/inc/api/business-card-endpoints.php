<?php
/**
 * MM SPG Business Card API Endpoints
 * Handles business card details (name, title, keywords, social links)
 * Prefix: api/v1/spg/business-card
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register REST API routes for business card
 */
add_action('rest_api_init', function () {
    
    // GET: Fetch available business card fields template
    register_rest_route('api/v1/spg', '/business-card/fields', [
        'methods'             => 'GET',
        'callback'            => 'mm_spg_api_get_business_card_fields',
        'permission_callback' => '__return_true',
        'args'                => [
            'nonce' => [
                'type'     => 'string',
                'required' => true,
            ],
        ],
    ]);

    // POST: Save user business card details
    register_rest_route('api/v1/spg', '/business-card/save', [
        'methods'             => 'POST',
        'callback'            => 'mm_spg_api_save_business_card',
        'permission_callback' => '__return_true',
        'args'                => [
            'nonce' => [
                'type'     => 'string',
                'required' => true,
            ],
        ],
    ]);

    // GET: Fetch user's saved business card
    register_rest_route('api/v1/spg', '/business-card/user', [
        'methods'             => 'GET',
        'callback'            => 'mm_spg_api_get_user_business_card',
        'permission_callback' => '__return_true',
        'args'                => [
            'nonce' => [
                'type'     => 'string',
                'required' => true,
            ],
        ],
    ]);

    // DELETE: Clear user business card
    register_rest_route('api/v1/spg', '/business-card/clear', [
        'methods'             => 'DELETE',
        'callback'            => 'mm_spg_api_clear_business_card',
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
 * GET /api/v1/spg/business-card/fields
 * Return available business card fields template
 */
function mm_spg_api_get_business_card_fields(WP_REST_Request $request) {
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

    $fields = [
        [
            'name'        => 'full_name',
            'label'       => 'Full Name',
            'type'        => 'text',
            'required'    => true,
            'placeholder' => 'Your full name',
            'maxlength'   => 100,
        ],
        [
            'name'        => 'job_title',
            'label'       => 'Job Title',
            'type'        => 'text',
            'required'    => true,
            'placeholder' => 'Your professional title',
            'maxlength'   => 100,
        ],
        [
            'name'        => 'company_name',
            'label'       => 'Company Name',
            'type'        => 'text',
            'required'    => false,
            'placeholder' => 'Your company name',
            'maxlength'   => 100,
        ],
        [
            'name'        => 'keywords',
            'label'       => 'Keywords/Expertise',
            'type'        => 'textarea',
            'required'    => true,
            'placeholder' => 'Your key skills or expertise (comma-separated)',
            'maxlength'   => 500,
            'hint'        => 'e.g., Web Development, UI Design, Marketing Strategy',
        ],
        [
            'name'        => 'phone',
            'label'       => 'Phone Number',
            'type'        => 'tel',
            'required'    => false,
            'placeholder' => '+1 (555) 123-4567',
            'maxlength'   => 20,
        ],
        [
            'name'        => 'email',
            'label'       => 'Email Address',
            'type'        => 'email',
            'required'    => true,
            'placeholder' => 'your.email@example.com',
            'maxlength'   => 100,
        ],
        [
            'name'        => 'website',
            'label'       => 'Website URL',
            'type'        => 'url',
            'required'    => false,
            'placeholder' => 'https://yourwebsite.com',
            'maxlength'   => 200,
        ],
    ];

    return new WP_REST_Response([
        'success' => true,
        'data'    => $fields,
        'count'   => count($fields),
    ], 200);
}

/**
 * POST /api/v1/spg/business-card/save
 * Save user business card details
 */
function mm_spg_api_save_business_card(WP_REST_Request $request) {
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

    // Get request body
    $full_name = sanitize_text_field($request->get_param('full_name'));
    $job_title = sanitize_text_field($request->get_param('job_title'));
    $company_name = sanitize_text_field($request->get_param('company_name'));
    $keywords = sanitize_textarea_field($request->get_param('keywords'));
    $phone = sanitize_text_field($request->get_param('phone'));
    $email = sanitize_email($request->get_param('email'));
    $website = esc_url_raw($request->get_param('website'));

    // Validation
    if (empty($full_name)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Full name is required',
            'code'    => 'missing_full_name',
        ], 400);
    }

    if (empty($job_title)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Job title is required',
            'code'    => 'missing_job_title',
        ], 400);
    }

    if (empty($keywords)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Keywords/expertise is required',
            'code'    => 'missing_keywords',
        ], 400);
    }

    if (empty($email) || !is_email($email)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Valid email address is required',
            'code'    => 'invalid_email',
        ], 400);
    }

    // Prepare business card data
    $business_card = [
        'full_name'    => $full_name,
        'job_title'    => $job_title,
        'company_name' => $company_name,
        'keywords'     => $keywords,
        'phone'        => $phone,
        'email'        => $email,
        'website'      => $website,
    ];

    // Save to user meta
    update_user_meta($user_id, 'mm_spg_business_card', $business_card);
    update_user_meta($user_id, 'mm_spg_business_card_completed', 1);

    // Update user first/last name if not already set
    if (!empty($full_name)) {
        $name_parts = explode(' ', $full_name, 2);
        wp_update_user([
            'ID'         => $user_id,
            'first_name' => $name_parts[0],
            'last_name'  => $name_parts[1] ?? '',
        ]);
    }

    // Update phase info
    update_user_meta($user_id, 'mm_spg_phase_3_started', 1);

    // Award points
    if (function_exists('mm_award_points_and_notify')) {
        mm_award_points_and_notify($user_id, 'business_card_completed');
    }

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Business card saved successfully',
        'data'    => [
            'business_card'  => $business_card,
            'saved_at'       => current_time('mysql'),
            'phase_3_started' => true,
        ],
    ], 200);
}

/**
 * GET /api/v1/spg/business-card/user
 * Fetch user's saved business card details
 */
function mm_spg_api_get_user_business_card(WP_REST_Request $request) {
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

    $business_card = get_user_meta($user_id, 'mm_spg_business_card', true) ?: [];
    $is_completed = (bool) get_user_meta($user_id, 'mm_spg_business_card_completed', true);

    if (empty($business_card)) {
        return new WP_REST_Response([
            'success' => true,
            'message' => 'No business card saved yet',
            'data'    => [
                'business_card' => [],
                'completed'     => false,
            ],
        ], 200);
    }

    return new WP_REST_Response([
        'success' => true,
        'data'    => [
            'business_card' => $business_card,
            'completed'     => $is_completed,
            'saved_at'      => get_user_meta($user_id, 'mm_spg_business_card_saved_at', true),
        ],
    ], 200);
}

/**
 * DELETE /api/v1/spg/business-card/clear
 * Clear user's business card details
 */
function mm_spg_api_clear_business_card(WP_REST_Request $request) {
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

    delete_user_meta($user_id, 'mm_spg_business_card');
    delete_user_meta($user_id, 'mm_spg_business_card_completed');
    delete_user_meta($user_id, 'mm_spg_business_card_saved_at');

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Business card cleared successfully',
    ], 200);
}
