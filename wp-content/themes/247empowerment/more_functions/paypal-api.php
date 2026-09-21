<?php
/**
 * PayPal API helpers — access token, Products, Billing Plans.
 * Used by store-variations.php (admin save) and paypalsettings.php (purchase handler).
 */

if (!defined('ABSPATH')) exit;

// Global debug collector
$GLOBALS['mm_pp_debug'] = [];

/**
 * Add a debug entry that will be displayed on the page
 */
function mm_pp_debug($message, $data = null) {
    if (!isset($GLOBALS['mm_pp_debug'])) {
        $GLOBALS['mm_pp_debug'] = [];
    }
    $entry = [
        'time'    => current_time('H:i:s'),
        'message' => $message,
        'data'    => $data,
    ];
    $GLOBALS['mm_pp_debug'][] = $entry;
    error_log('[PayPal] ' . $message . ($data ? ' > ' . wp_json_encode($data) : ''));
}

/**
 * Get collected debug info
 */
function mm_pp_get_debug() {
    return $GLOBALS['mm_pp_debug'] ?? [];
}

/**
 * Clear debug info
 */
function mm_pp_clear_debug() {
    $GLOBALS['mm_pp_debug'] = [];
}

/**
 * Get an OAuth access token. Cached in a transient for its lifetime.
 *
 * @return string|WP_Error
 */
function mm_pp_get_access_token()
{
    $cached = get_transient('mm_pp_access_token');
    if ($cached) {
        // Show token details (first and last 20 chars)
        $token_display = substr($cached, 0, 20) . '...' . substr($cached, -20);
        mm_pp_debug('Using cached access token', ['length' => strlen($cached), 'preview' => $token_display]);
        return $cached;
    }

    $cred = get_paypal_api_credentials();
    if (empty($cred['client_id']) || empty($cred['secret'])) {
        mm_pp_debug('PayPal credentials missing', ['client_id' => empty($cred['client_id']), 'secret' => empty($cred['secret']), 'api_url' => $cred['api_url'] ?? 'not set']);
        return new WP_Error('paypal_missing_creds', 'PayPal credentials are not configured.');
    }

    mm_pp_debug('Credentials loaded', [
        'api_url' => $cred['api_url'],
        'client_id_length' => strlen($cred['client_id']),
        'client_id_prefix' => substr($cred['client_id'], 0, 15),
        'secret_length' => strlen($cred['secret']),
        'secret_prefix' => substr($cred['secret'], 0, 15),
    ]);
    
    $auth_string = "{$cred['client_id']}:{$cred['secret']}";
    $encoded = base64_encode($auth_string);

    mm_pp_debug('Requesting access token', ['url' => $cred['api_url'] . '/v1/oauth2/token', 'auth_method' => 'Basic Auth']);

    $resp = wp_remote_post("{$cred['api_url']}/v1/oauth2/token", [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode("{$cred['client_id']}:{$cred['secret']}"),
        ],
        'body'    => ['grant_type' => 'client_credentials'],
        'timeout' => 20,
    ]);

    if (is_wp_error($resp)) {
        mm_pp_debug('Token request failed', ['error' => $resp->get_error_message()]);
        return $resp;
    }

    $code = wp_remote_retrieve_response_code($resp);
    $body = json_decode(wp_remote_retrieve_body($resp), true);
    
    mm_pp_debug('Token response received', ['code' => $code, 'has_token' => !empty($body['access_token']), 'error' => $body['error'] ?? null]);
    
    if (empty($body['access_token'])) {
        mm_pp_debug('Token error details', $body);
        return new WP_Error('paypal_no_token', 'Could not fetch PayPal access token', $body);
    }

    $ttl = max(60, intval($body['expires_in'] ?? 3600) - 60);
    set_transient('mm_pp_access_token', $body['access_token'], $ttl);
    
    $token_display = substr($body['access_token'], 0, 20) . '...' . substr($body['access_token'], -20);
    mm_pp_debug('Token cached successfully', ['ttl_seconds' => $ttl, 'token_length' => strlen($body['access_token']), 'token_type' => $body['token_type'] ?? 'Bearer', 'preview' => $token_display]);
    
    return $body['access_token'];
}

/**
 * Make an authenticated PayPal API call.
 */
function mm_pp_request($method, $path, $payload = null)
{
    $token = mm_pp_get_access_token();
    if (is_wp_error($token)) {
        mm_pp_debug('Failed to get access token', ['error' => $token->get_error_message()]);
        return $token;
    }

    $cred = get_paypal_api_credentials();
    $args = [
        'method'  => strtoupper($method),
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
            'Prefer'        => 'return=representation',
        ],
        'timeout' => 30,
    ];
    if ($payload !== null) {
        $args['body'] = wp_json_encode($payload);
    }

    $url = "{$cred['api_url']}{$path}";
    mm_pp_debug(strtoupper($method) . ' ' . $path, ['url' => $url]);
    if ($payload) {
        mm_pp_debug('Request payload', $payload);
        mm_pp_debug('Request body (JSON)', wp_json_encode($payload));
    }

    $resp = wp_remote_request($url, $args);
    if (is_wp_error($resp)) {
        mm_pp_debug('Request failed', ['error' => $resp->get_error_message()]);
        return $resp;
    }

    $code = wp_remote_retrieve_response_code($resp);
    $body_raw = wp_remote_retrieve_body($resp);
    $body = json_decode($body_raw, true);

    mm_pp_debug('Response: ' . $code, ['success' => ($code >= 200 && $code < 300)]);
    if ($code >= 200 && $code < 300) {
        mm_pp_debug('Response body', $body);
    } else {
        mm_pp_debug('Error response', $body);
        // Log detailed error info if available
        if (!empty($body['details']) && is_array($body['details'])) {
            foreach ($body['details'] as $idx => $detail) {
                mm_pp_debug("Error detail[$idx]", $detail);
            }
        }
        // On 401, clear cached token so next request will get new one
        if ($code === 401) {
            delete_transient('mm_pp_access_token');
            mm_pp_debug('401 Unauthorized - cleared cached token', $body);
        }
    }

    if ($code < 200 || $code >= 300) {
        return new WP_Error('paypal_api_error', "PayPal API {$code}", $body);
    }
    return $body;
}

/**
 * Get (or create) a PayPal Catalog Product for a course. Result cached in post meta.
 */
function mm_pp_get_or_create_product($course_id)
{
    $existing = get_post_meta($course_id, '_paypal_product_id', true);
    if ($existing) {
        mm_pp_debug('Using cached product', ['id' => $existing]);
        return $existing;
    }

    $post = get_post($course_id);
    if (!$post) {
        mm_pp_debug('Course not found', ['course_id' => $course_id]);
        return new WP_Error('no_course', 'Course not found');
    }

    mm_pp_debug('Creating product for course', ['title' => $post->post_title, 'course_id' => $course_id]);

    // Decode HTML entities and clean up whitespace
    $title = html_entity_decode($post->post_title ?: 'Course', ENT_QUOTES, 'UTF-8');
    $title = preg_replace('/\s+/', ' ', trim($title));
    // Remove problematic Unicode characters (convert to ASCII equivalent or remove)
    $title = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title);
    
    $short_details = get_field('short_details', $course_id) ?: $post->post_title;
    $description = html_entity_decode($short_details, ENT_QUOTES, 'UTF-8');
    $description = wp_strip_all_tags($description);
    $description = preg_replace('/\s+/', ' ', trim($description)); // Remove extra whitespace
    $description = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $description); // Remove problematic Unicode
    
    $payload = [
        'name'        => mb_substr($title, 0, 127),
        'description' => mb_substr($description, 0, 256),
        'type'        => 'SERVICE',
    ];

    $res = mm_pp_request('POST', '/v1/catalogs/products', $payload);
    if (is_wp_error($res)) {
        mm_pp_debug('Product creation failed', ['error' => $res->get_error_message()]);
        return $res;
    }

    $product_id = $res['id'] ?? '';
    if (!$product_id) {
        mm_pp_debug('No product ID in response', $res);
        return new WP_Error('paypal_no_product_id', 'No product id returned', $res);
    }

    update_post_meta($course_id, '_paypal_product_id', $product_id);
    mm_pp_debug('Product created', ['id' => $product_id]);
    return $product_id;
}

/**
 * Create a Billing Plan for a variation.
 *
 * @param string $product_id PayPal catalog product id
 * @param string $label       Plan name (shown on PayPal)
 * @param float  $price       Price
 * @param string $interval    'WEEK', 'MONTH' or 'YEAR'
 * @return string|WP_Error    Plan id
 */
function mm_pp_create_plan($product_id, $label, $price, $interval)
{
    mm_pp_debug('Creating plan', ['label' => $label, 'price' => $price, 'interval' => $interval, 'product_id' => substr($product_id, 0, 8) . '...']);
    
    // Decode HTML entities and clean up whitespace
    $plan_label = html_entity_decode($label, ENT_QUOTES, 'UTF-8');
    $plan_label = preg_replace('/\s+/', ' ', trim($plan_label));
    
    $payload = [
        'product_id'  => $product_id,
        'name'        => mb_substr($plan_label, 0, 127),
        'description' => mb_substr($plan_label . ' subscription', 0, 127),
        'status'      => 'ACTIVE',
        'billing_cycles' => [[
            'frequency'       => [
                'interval_unit'  => $interval,
                'interval_count' => 1,
            ],
            'tenure_type'     => 'REGULAR',
            'sequence'        => 1,
            'total_cycles'    => 0, // infinite
            'pricing_scheme'  => [
                'fixed_price' => [
                    'value'         => number_format((float)$price, 2, '.', ''),
                    'currency_code' => 'USD',
                ],
            ],
        ]],
        'payment_preferences' => [
            'auto_bill_outstanding'     => true,
            'setup_fee'                 => ['value' => '0', 'currency_code' => 'USD'],
            'setup_fee_failure_action'  => 'CONTINUE',
            'payment_failure_threshold' => 3,
        ],
    ];
    
    $res = mm_pp_request('POST', '/v1/billing/plans', $payload);
    if (is_wp_error($res)) {
        mm_pp_debug('Plan creation failed', ['error' => $res->get_error_message(), 'data' => $res->get_error_data()]);
        return $res;
    }
    
    $id = $res['id'] ?? '';
    if (!$id) {
        mm_pp_debug('No plan ID in response', $res);
        return new WP_Error('paypal_no_plan_id', 'No plan id returned', $res);
    }
    
    mm_pp_debug('Plan created', ['id' => $id]);
    return $id;
}

/**
 * Create a Billing Plan (Simplified - without product requirement)
 * Used when product creation fails
 */
function mm_pp_create_plan_simple($label, $price, $interval)
{
    $payload = [
        'name'        => mb_substr($label, 0, 127),
        'description' => mb_substr("{$label} subscription", 0, 127),
        'status'      => 'ACTIVE',
        'billing_cycles' => [[
            'frequency'       => [
                'interval_unit'  => $interval,
                'interval_count' => 1,
            ],
            'tenure_type'     => 'REGULAR',
            'sequence'        => 1,
            'total_cycles'    => 0, // infinite
            'pricing_scheme'  => [
                'fixed_price' => [
                    'value'         => number_format((float)$price, 2, '.', ''),
                    'currency_code' => 'USD',
                ],
            ],
        ]],
        'payment_preferences' => [
            'auto_bill_outstanding'     => true,
            'setup_fee'                 => ['value' => '0', 'currency_code' => 'USD'],
            'setup_fee_failure_action'  => 'CONTINUE',
            'payment_failure_threshold' => 3,
        ],
    ];

    $res = mm_pp_request('POST', '/v1/billing/plans', $payload);
    if (is_wp_error($res)) return $res;

    $id = $res['id'] ?? '';
    if (!$id) return new WP_Error('paypal_no_plan_id', 'No plan id returned', $res);
    return $id;
}

/**
 * Verify a PayPal Subscription by ID. Returns the subscription object on success.
 */
function mm_pp_get_subscription($subscription_id)
{
    return mm_pp_request('GET', '/v1/billing/subscriptions/' . rawurlencode($subscription_id));
}



