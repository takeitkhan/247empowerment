<?php
/**
 * PayPal Settings & Course Subscription Management
 * Handles subscription billing (one-time, weekly, monthly, yearly)
 * and tracks all subscription data in a custom table
 */

// ======================
// 0️⃣ Initialize Subscription Tracking Table
// ======================

add_action('after_setup_theme', function () {
    global $wpdb;
    $table = $wpdb->prefix . 'course_subscriptions';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        course_id BIGINT(20) UNSIGNED NOT NULL,
        variation_index INT DEFAULT -1,
        variation_label VARCHAR(255),
        paypal_subscription_id VARCHAR(50) NOT NULL UNIQUE,
        paypal_plan_id VARCHAR(50),
        billing_type ENUM('onetime', 'weekly', 'monthly', 'yearly') DEFAULT 'onetime',
        price DECIMAL(10, 2),
        status ENUM('ACTIVE', 'SUSPENDED', 'CANCELLED', 'EXPIRED') DEFAULT 'ACTIVE',
        started_at DATETIME,
        updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
        cancelled_at DATETIME,
        cancellation_reason VARCHAR(500),
        paypal_payer_id VARCHAR(50),
        paypal_payer_email VARCHAR(255),
        next_billing_date DATE,
        failed_attempts INT DEFAULT 0,
        last_payment_date DATETIME,
        last_payment_amount DECIMAL(10, 2),
        
        KEY user_id (user_id),
        KEY course_id (course_id),
        KEY status (status),
        KEY started_at (started_at),
        KEY user_course (user_id, course_id)
    ) $charset;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}, 1); // Priority 1 for early initialization

// ======================
// 1️⃣ PayPal Settings Page
// ======================

add_action('admin_menu', function () {
    add_options_page(
        'PayPal Settings',
        'PayPal Settings',
        'manage_options',
        'paypal-settings',
        'render_paypal_settings_page'
    );

    // ✨ New: Subscription Dashboard
    add_menu_page(
        'Course Subscriptions',
        'Subscriptions',
        'manage_options',
        'mm-subscriptions',
        'render_subscriptions_dashboard',
        'dashicons-chart-line',
        25
    );
});

function render_paypal_settings_page()
{
?>
    <div class="wrap">
        <h1>PayPal Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('paypal_settings_group');
            do_settings_sections('paypal-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', function () {
    register_setting('paypal_settings_group', 'paypal_environment');
    register_setting('paypal_settings_group', 'paypal_sandbox_client_id');
    register_setting('paypal_settings_group', 'paypal_sandbox_secret');
    register_setting('paypal_settings_group', 'paypal_live_client_id');
    register_setting('paypal_settings_group', 'paypal_live_secret');
    register_setting('paypal_settings_group', 'paypal_referral_commission'); // ✅ New
    register_setting('paypal_settings_group', 'paypal_webhook_id');

    add_settings_section('paypal_main_section', 'PayPal API Configuration', '', 'paypal-settings');

    // Environment
    add_settings_field('paypal_environment', 'Environment', function () {
        $env = get_option('paypal_environment', 'sandbox');
    ?>
        <select name="paypal_environment">
            <option value="sandbox" <?= selected($env, 'sandbox'); ?>>Sandbox</option>
            <option value="live" <?= selected($env, 'live'); ?>>Live</option>
        </select>
    <?php
    }, 'paypal-settings', 'paypal_main_section');

    // Sandbox Client ID
    add_settings_field('paypal_sandbox_client_id', 'Sandbox Client ID', function () {
    ?>
        <input type="text" name="paypal_sandbox_client_id" value="<?= esc_attr(get_option('paypal_sandbox_client_id')); ?>" class="regular-text" />
    <?php
    }, 'paypal-settings', 'paypal_main_section');

    // Sandbox Secret
    add_settings_field('paypal_sandbox_secret', 'Sandbox Secret', function () {
    ?>
        <input type="password" name="paypal_sandbox_secret" value="<?= esc_attr(get_option('paypal_sandbox_secret')); ?>" class="regular-text" />
    <?php
    }, 'paypal-settings', 'paypal_main_section');

    // Live Client ID
    add_settings_field('paypal_live_client_id', 'Live Client ID', function () {
    ?>
        <input type="text" name="paypal_live_client_id" value="<?= esc_attr(get_option('paypal_live_client_id')); ?>" class="regular-text" />
    <?php
    }, 'paypal-settings', 'paypal_main_section');

    // Live Secret
    add_settings_field('paypal_live_secret', 'Live Secret', function () {
    ?>
        <input type="password" name="paypal_live_secret" value="<?= esc_attr(get_option('paypal_live_secret')); ?>" class="regular-text" />
<?php
    }, 'paypal-settings', 'paypal_main_section');

    // ✅ Referral Commission Percentage
    add_settings_field('paypal_referral_commission', 'Referral Commission (%)', function () {
        $commission = get_option('paypal_referral_commission', '0');
    ?>
        <input type="number" name="paypal_referral_commission" value="<?= esc_attr($commission); ?>" class="small-text" min="0" max="100" step="0.1" /> %
        <p class="description">Enter the referral commission percentage. Example: 10 = 10%</p>
    <?php
    }, 'paypal-settings', 'paypal_main_section');

    // Webhook ID (for subscription events)
    add_settings_field('paypal_webhook_id', 'Webhook ID', function () {
        $wh = get_option('paypal_webhook_id', '');
    ?>
        <input type="text" name="paypal_webhook_id" value="<?= esc_attr($wh); ?>" class="regular-text" placeholder="WH-XXXXXXXXXXX" />
        <p class="description">
            Create a webhook in the PayPal dashboard pointing to:
            <code><?= esc_url(home_url('/?mm_paypal_webhook=1')); ?></code><br>
            Subscribe to: <code>BILLING.SUBSCRIPTION.ACTIVATED, CANCELLED, EXPIRED, SUSPENDED</code> and <code>PAYMENT.SALE.COMPLETED</code>.
        </p>
    <?php
    }, 'paypal-settings', 'paypal_main_section');

});

// ======================
// 2️⃣ PayPal API Credentials Helper
// ======================

function get_paypal_api_credentials()
{
    $env = get_option('paypal_environment', 'sandbox');
    return [
        'client_id' => $env === 'live' ? get_option('paypal_live_client_id') : get_option('paypal_sandbox_client_id'),
        'secret'    => $env === 'live' ? get_option('paypal_live_secret') : get_option('paypal_sandbox_secret'),
        'api_url'   => $env === 'live' ? 'https://api.paypal.com' : 'https://api.sandbox.paypal.com',
    ];
}

// Load PayPal REST API helpers (access token, products, plans, subscriptions)
require_once __DIR__ . '/paypal-api.php';

// ======================
// 3️⃣ Handle AJAX Purchase
// ======================

add_action('wp_ajax_handle_course_purchase', 'handle_course_purchase');
add_action('wp_ajax_nopriv_handle_course_purchase', 'handle_course_purchase');

function handle_course_purchase()
{
    $course_id       = intval($_POST['course_id']);
    $amount          = sanitize_text_field($_POST['amount']);
    $order_id        = sanitize_text_field($_POST['orderID'] ?? '');
    $variation_index = isset($_POST['variation_index']) ? intval($_POST['variation_index']) : -1;
    $variation_label = sanitize_text_field($_POST['variation_label'] ?? '');

    if (!$order_id) {
        wp_send_json_error(['message' => 'Missing PayPal order ID']);
    }

    // -----------------------------------------------------------
    // Server-side variation price check (defense against tampered amount)
    // -----------------------------------------------------------
    if ($variation_index >= 0 && function_exists('mm_get_course_variation')) {
        $variation = mm_get_course_variation($course_id, $variation_index);
        if (!$variation) {
            wp_send_json_error(['message' => 'Invalid variation selected']);
        }
        if (floatval($variation['price']) !== floatval($amount)) {
            wp_send_json_error(['message' => 'Amount does not match selected variation price']);
        }
        // Override label with server-stored value
        $variation_label = $variation['label'];
    }

    $paypal = get_paypal_api_credentials();

    // Get Access Token
    $response = wp_remote_post("{$paypal['api_url']}/v1/oauth2/token", [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode("{$paypal['client_id']}:{$paypal['secret']}"),
        ],
        'body' => ['grant_type' => 'client_credentials'],
    ]);

    if (is_wp_error($response)) wp_send_json_error(['message' => 'PayPal API connection error']);

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $access_token = $body['access_token'] ?? '';

    if (!$access_token) wp_send_json_error(['message' => 'PayPal access token missing']);

    // Get Order Details
    $order_response = wp_remote_get("{$paypal['api_url']}/v2/checkout/orders/{$order_id}", [
        'headers' => [
            'Authorization' => "Bearer $access_token",
            'Content-Type' => 'application/json',
        ]
    ]);

    if (is_wp_error($order_response)) wp_send_json_error(['message' => 'Unable to verify PayPal order']);

    $order_data = json_decode(wp_remote_retrieve_body($order_response), true);

    if (($order_data['status'] ?? '') === 'COMPLETED' &&
        floatval($order_data['purchase_units'][0]['amount']['value'] ?? 0) == floatval($amount)
    ) {
        // -----------------------------------------------------------
        // Persist purchase (legacy-compatible)
        // -----------------------------------------------------------
        $user_id = get_current_user_id();
        if ($user_id) {
            // Legacy array: list of course IDs purchased
            $purchased = get_user_meta($user_id, 'purchased_courses', true);
            if (!is_array($purchased)) $purchased = [];
            if (!in_array($course_id, $purchased, true)) {
                $purchased[] = $course_id;
                update_user_meta($user_id, 'purchased_courses', $purchased);
            }

            // Detailed log: which variation, how much, when, PayPal order
            $log = get_user_meta($user_id, 'course_purchase_log', true);
            if (!is_array($log)) $log = [];
            $log[] = [
                'course_id'       => $course_id,
                'variation_index' => $variation_index,
                'variation_label' => $variation_label,
                'amount'          => $amount,
                'order_id'        => $order_id,
                'timestamp'       => current_time('mysql'),
            ];
            update_user_meta($user_id, 'course_purchase_log', $log);
        }

        wp_send_json_success([
            'message'         => 'Payment verified, course purchased',
            'variation_label' => $variation_label,
        ]);
    } else {
        wp_send_json_error(['message' => 'Payment verification failed']);
    }

    wp_die();
}

// ======================
// 4️⃣ Handle Subscription Activation (monthly/yearly)
// ======================

add_action('wp_ajax_handle_course_subscription', 'handle_course_subscription');
add_action('wp_ajax_nopriv_handle_course_subscription', 'handle_course_subscription');

function handle_course_subscription()
{
    $course_id       = intval($_POST['course_id']);
    $variation_index = isset($_POST['variation_index']) ? intval($_POST['variation_index']) : -1;
    $subscription_id = sanitize_text_field($_POST['subscriptionID'] ?? '');

    if (!$subscription_id || $variation_index < 0) {
        wp_send_json_error(['message' => 'Missing subscription data']);
    }

    if (!function_exists('mm_get_course_variation')) {
        wp_send_json_error(['message' => 'Variation system unavailable']);
    }

    $variation = mm_get_course_variation($course_id, $variation_index);
    if (!$variation) {
        wp_send_json_error(['message' => 'Invalid variation']);
    }

    $billing = $variation['billing'] ?? 'onetime';
    if ($billing === 'onetime') {
        wp_send_json_error(['message' => 'Variation is not a subscription']);
    }

    // Verify with PayPal
    $sub = mm_pp_get_subscription($subscription_id);
    if (is_wp_error($sub)) {
        wp_send_json_error([
            'message' => 'Could not verify subscription: ' . $sub->get_error_message(),
        ]);
    }

    $status = $sub['status'] ?? '';
    if (!in_array($status, ['ACTIVE', 'APPROVAL_PENDING', 'APPROVED'], true)) {
        wp_send_json_error(['message' => "Subscription not active (status: $status)"]);
    }

    // Verify that the subscription's plan_id matches the variation's plan_id
    $sub_plan = $sub['plan_id'] ?? '';
    if (!empty($variation['plan_id']) && $sub_plan && $sub_plan !== $variation['plan_id']) {
        wp_send_json_error(['message' => 'Subscription plan mismatch']);
    }

    // Persist
    $user_id = get_current_user_id();
    if ($user_id) {
        // Legacy: still mark course as purchased so the "already purchased" UI hides the button
        $purchased = get_user_meta($user_id, 'purchased_courses', true);
        if (!is_array($purchased)) $purchased = [];
        if (!in_array($course_id, $purchased, true)) {
            $purchased[] = $course_id;
            update_user_meta($user_id, 'purchased_courses', $purchased);
        }

        // 📊 Log to subscription tracking table
        $payer_id = $sub['subscriber']['id'] ?? '';
        $payer_email = $sub['subscriber']['email_address'] ?? '';
        mm_log_subscription(
            $user_id,
            $course_id,
            $variation_index,
            $variation['label'],
            $subscription_id,
            $variation['plan_id'] ?? '',
            $billing,
            $variation['price'],
            $payer_id,
            $payer_email
        );

        // Track active subscriptions separately so we can handle cancel/renew later
        $subs = get_user_meta($user_id, 'active_subscriptions', true);
        if (!is_array($subs)) $subs = [];
        $subs[$subscription_id] = [
            'course_id'       => $course_id,
            'variation_index' => $variation_index,
            'variation_label' => $variation['label'],
            'billing'         => $billing,
            'price'           => $variation['price'],
            'plan_id'         => $variation['plan_id'] ?? '',
            'status'          => $status,
            'started'         => current_time('mysql'),
        ];
        update_user_meta($user_id, 'active_subscriptions', $subs);

        // Also log in the purchase log
        $log = get_user_meta($user_id, 'course_purchase_log', true);
        if (!is_array($log)) $log = [];
        $log[] = [
            'type'            => 'subscription',
            'course_id'       => $course_id,
            'variation_index' => $variation_index,
            'variation_label' => $variation['label'],
            'billing'         => $billing,
            'amount'          => $variation['price'],
            'subscription_id' => $subscription_id,
            'timestamp'       => current_time('mysql'),
        ];
        update_user_meta($user_id, 'course_purchase_log', $log);
    }

    wp_send_json_success([
        'message'         => 'Subscription activated',
        'subscription_id' => $subscription_id,
        'status'          => $status,
    ]);
}

// ======================
// 5️⃣ Cancel a Subscription (user-initiated)
// ======================

add_action('wp_ajax_cancel_course_subscription', 'cancel_course_subscription');

function cancel_course_subscription()
{
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Not logged in']);
    }
    check_ajax_referer('mm_sub_cancel', 'nonce');

    $subscription_id = sanitize_text_field($_POST['subscription_id'] ?? '');
    $reason          = sanitize_text_field($_POST['reason'] ?? 'User requested cancellation');
    if (!$subscription_id) {
        wp_send_json_error(['message' => 'Missing subscription id']);
    }

    $user_id = get_current_user_id();
    $subs    = get_user_meta($user_id, 'active_subscriptions', true);
    if (!is_array($subs) || !isset($subs[$subscription_id])) {
        wp_send_json_error(['message' => 'Subscription not found for this user']);
    }

    // Call PayPal to cancel
    $res = mm_pp_request(
        'POST',
        '/v1/billing/subscriptions/' . rawurlencode($subscription_id) . '/cancel',
        ['reason' => $reason]
    );
    if (is_wp_error($res)) {
        // PayPal returns empty body on success; WP_Error is returned only on real failure.
        wp_send_json_error(['message' => 'PayPal cancel failed: ' . $res->get_error_message()]);
    }

    // Update local state immediately (webhook will also fire later)
    $subs[$subscription_id]['status']  = 'CANCELLED';
    $subs[$subscription_id]['updated'] = current_time('mysql');
    update_user_meta($user_id, 'active_subscriptions', $subs);

    // 📊 Update database table
    $sub_record = mm_get_subscription_by_paypal_id($subscription_id);
    if ($sub_record) {
        mm_update_subscription_status($sub_record['id'], 'CANCELLED', $reason);
    }

    if (function_exists('mm_pp_revoke_course_access_if_no_active_sub')) {
        mm_pp_revoke_course_access_if_no_active_sub($user_id, $subscription_id);
    }

    wp_send_json_success(['message' => 'Subscription cancelled']);
}

// ======================
// 6️⃣ Subscription Helper Functions
// ======================

/**
 * Log a subscription to the database table
 */
function mm_log_subscription($user_id, $course_id, $variation_index, $variation_label, $paypal_subscription_id, $paypal_plan_id, $billing_type, $price, $paypal_payer_id = '', $paypal_payer_email = '')
{
    global $wpdb;
    $table = $wpdb->prefix . 'course_subscriptions';

    $wpdb->insert($table, [
        'user_id'                  => (int)$user_id,
        'course_id'                => (int)$course_id,
        'variation_index'          => (int)$variation_index,
        'variation_label'          => sanitize_text_field($variation_label),
        'paypal_subscription_id'   => sanitize_text_field($paypal_subscription_id),
        'paypal_plan_id'           => sanitize_text_field($paypal_plan_id),
        'billing_type'             => sanitize_text_field($billing_type),
        'price'                    => (float)$price,
        'status'                   => 'ACTIVE',
        'started_at'               => current_time('mysql'),
        'paypal_payer_id'          => sanitize_text_field($paypal_payer_id),
        'paypal_payer_email'       => sanitize_email($paypal_payer_email),
    ], [
        '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s'
    ]);

    return $wpdb->insert_id;
}

/**
 * Get all active subscriptions for a user
 */
function mm_get_user_subscriptions($user_id)
{
    global $wpdb;
    $table = $wpdb->prefix . 'course_subscriptions';

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND status = 'ACTIVE' ORDER BY started_at DESC",
            $user_id
        ),
        ARRAY_A
    );
}

/**
 * Get all subscriptions for a course
 */
function mm_get_course_subscriptions($course_id)
{
    global $wpdb;
    $table = $wpdb->prefix . 'course_subscriptions';

    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE course_id = %d ORDER BY started_at DESC",
            $course_id
        ),
        ARRAY_A
    );
}

/**
 * Update subscription status
 */
function mm_update_subscription_status($subscription_id, $status, $reason = '')
{
    global $wpdb;
    $table = $wpdb->prefix . 'course_subscriptions';

    $data = [
        'status'     => sanitize_text_field($status),
        'updated_at' => current_time('mysql'),
    ];

    if ($status === 'CANCELLED') {
        $data['cancelled_at'] = current_time('mysql');
        $data['cancellation_reason'] = sanitize_text_field($reason);
    }

    return $wpdb->update($table, $data, ['id' => (int)$subscription_id], null, ['%d']);
}

/**
 * Get subscription by PayPal subscription ID
 */
function mm_get_subscription_by_paypal_id($paypal_subscription_id)
{
    global $wpdb;
    $table = $wpdb->prefix . 'course_subscriptions';

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE paypal_subscription_id = %s",
            $paypal_subscription_id
        ),
        ARRAY_A
    );
}

/**
 * Render Subscriptions Dashboard
 */
function render_subscriptions_dashboard()
{
    global $wpdb;
    $table = $wpdb->prefix . 'course_subscriptions';

    // Get filter parameters
    $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
    $course_filter = isset($_GET['course']) ? intval($_GET['course']) : 0;
    $user_filter = isset($_GET['user']) ? intval($_GET['user']) : 0;

    // Build query
    $query = "SELECT cs.*, u.user_login, u.user_email, p.post_title 
              FROM $table cs
              LEFT JOIN {$wpdb->users} u ON cs.user_id = u.ID
              LEFT JOIN {$wpdb->posts} p ON cs.course_id = p.ID
              WHERE 1=1";
    $params = [];

    if ($status_filter) {
        $query .= " AND cs.status = %s";
        $params[] = $status_filter;
    }
    if ($course_filter) {
        $query .= " AND cs.course_id = %d";
        $params[] = $course_filter;
    }
    if ($user_filter) {
        $query .= " AND cs.user_id = %d";
        $params[] = $user_filter;
    }

    $query .= " ORDER BY cs.started_at DESC LIMIT 100";

    if ($params) {
        $subscriptions = $wpdb->get_results($wpdb->prepare($query, $params));
    } else {
        $subscriptions = $wpdb->get_results($query);
    }

    // Statistics
    $stats = $wpdb->get_results(
        "SELECT 
            status, 
            COUNT(*) as count,
            SUM(CAST(price AS DECIMAL(10,2))) as revenue
        FROM $table
        GROUP BY status"
    );

    // Get courses for filter
    $courses = get_posts(['post_type' => 'course', 'numberposts' => -1, 'fields' => 'ids']);

    ?>
    <div class="wrap">
        <h1>Course Subscriptions</h1>
        <hr>

        <!-- Statistics -->
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
            <?php
            $total_active = 0;
            $total_revenue = 0;
            foreach ($stats as $stat) {
                $count = $stat->count ?? 0;
                $revenue = $stat->revenue ?? 0;
                $status = $stat->status ?? 'Unknown';

                if ($status === 'ACTIVE') {
                    $total_active = $count;
                    $total_revenue = $revenue;
                }

                $color = $status === 'ACTIVE' ? '#46b450' : ($status === 'CANCELLED' ? '#dc3232' : '#faa533');
                echo "<div style='background: #f1f1f1; padding: 20px; border-left: 4px solid $color; border-radius: 4px;'>";
                echo "<div style='font-size: 14px; color: #666;'>$status</div>";
                echo "<div style='font-size: 24px; font-weight: bold;'>$count</div>";
                echo "<div style='font-size: 12px; color: #999;'>Revenue: $" . number_format($revenue, 2) . "</div>";
                echo "</div>";
            }
            ?>
        </div>

        <!-- Filters -->
        <div style="background: #fff; padding: 15px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 20px;">
            <form method="get" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; align-items: end;">
                <input type="hidden" name="page" value="mm-subscriptions">

                <div>
                    <label><strong>Status</strong></label>
                    <select name="status">
                        <option value="">All</option>
                        <option value="ACTIVE" <?= selected($status_filter, 'ACTIVE') ?>>Active</option>
                        <option value="SUSPENDED" <?= selected($status_filter, 'SUSPENDED') ?>>Suspended</option>
                        <option value="CANCELLED" <?= selected($status_filter, 'CANCELLED') ?>>Cancelled</option>
                        <option value="EXPIRED" <?= selected($status_filter, 'EXPIRED') ?>>Expired</option>
                    </select>
                </div>

                <div>
                    <label><strong>Course</strong></label>
                    <select name="course">
                        <option value="0">All Courses</option>
                        <?php foreach ($courses as $course_id) {
                            echo '<option value="' . $course_id . '" ' . selected($course_filter, $course_id) . '>' . get_the_title($course_id) . '</option>';
                        } ?>
                    </select>
                </div>

                <div>
                    <label><strong>User ID</strong></label>
                    <input type="number" name="user" placeholder="User ID" value="<?= esc_attr($user_filter) ?>">
                </div>

                <button type="submit" class="button button-primary">Filter</button>
            </form>
        </div>

        <!-- Table -->
        <table class="wp-list-table fixed widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Course</th>
                    <th>Variation</th>
                    <th>Billing</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Started</th>
                    <th>Next Billing</th>
                    <th>PayPal ID</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subscriptions)): ?>
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 20px;">No subscriptions found</td>
                    </tr>
                <?php else:
                    foreach ($subscriptions as $sub):
                        $status_color = $sub->status === 'ACTIVE' ? '#46b450' : ($sub->status === 'CANCELLED' ? '#dc3232' : '#faa533');
                        ?>
                        <tr>
                            <td><?= $sub->id ?></td>
                            <td>
                                <a href="<?= admin_url('user-edit.php?user_id=' . $sub->user_id) ?>" target="_blank">
                                    <?= esc_html($sub->user_login) ?>
                                </a>
                                <br>
                                <small style="color: #999;"><?= esc_html($sub->user_email) ?></small>
                            </td>
                            <td>
                                <a href="<?= admin_url('post.php?post=' . $sub->course_id . '&action=edit') ?>" target="_blank">
                                    <?= esc_html($sub->post_title) ?>
                                </a>
                            </td>
                            <td><?= esc_html($sub->variation_label) ?></td>
                            <td><strong><?= ucfirst($sub->billing_type) ?></strong></td>
                            <td>$<?= number_format($sub->price, 2) ?></td>
                            <td><span style="background: $status_color; color: #fff; padding: 4px 8px; border-radius: 3px; display: inline-block;"><?= $sub->status ?></span></td>
                            <td><?= wp_date('M d, Y', strtotime($sub->started_at)) ?></td>
                            <td><?= $sub->next_billing_date ? wp_date('M d, Y', strtotime($sub->next_billing_date)) : '—' ?></td>
                            <td><code><?= substr($sub->paypal_subscription_id, 0, 15) ?>...</code></td>
                            <td style="min-width: 150px;">
                                <?php if ($sub->status === 'ACTIVE'): ?>
                                    <a href="#" onclick="alert('Open PayPal: <?= esc_attr($sub->paypal_subscription_id) ?>');" class="button button-small">View PayPal</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach;
                endif; ?>
            </tbody>
        </table>

        <hr>
        <p style="color: #666; font-size: 12px;">
            <strong>Total Active Subscriptions:</strong> <?= $total_active ?> | <strong>Total Monthly Revenue:</strong> $<?= number_format($total_revenue, 2) ?>
        </p>
    </div>
    <?php
}

?>