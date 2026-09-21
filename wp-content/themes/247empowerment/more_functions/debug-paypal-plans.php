<?php
/**
 * PayPal Plans Debug Page
 * URL: /wp-admin/?page=mm-paypal-debug
 */

if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    if (!current_user_can('manage_options')) return;
    
    add_submenu_page(
        'tools.php',
        'PayPal Plans Debug',
        'PayPal Plans Debug',
        'manage_options',
        'mm-paypal-debug',
        'mm_paypal_debug_page'
    );
});

function mm_paypal_debug_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('Access denied');
    }
    
    global $wpdb;
    
    // Get PayPal credentials
    $creds = function_exists('get_paypal_api_credentials') ? get_paypal_api_credentials() : null;
    $env = get_option('paypal_environment', 'sandbox');
    
    ?>
    <div class="wrap">
        <h1>🔍 PayPal Plans Debug</h1>
        
        <!-- Credentials Check -->
        <div class="card" style="margin-top: 20px;">
            <h2>1️⃣ PayPal Credentials Status</h2>
            <table class="widefat">
                <tr>
                    <td><strong>Environment:</strong></td>
                    <td><?= esc_html($env) ?></td>
                </tr>
                <tr>
                    <td><strong>Client ID (from DB):</strong></td>
                    <td>
                        <code><?= esc_html($creds['client_id'] ?? 'MISSING') ?></code>
                        <span style="color: <?= (empty($creds['client_id']) ? '#dc3232' : '#28a745') ?>">
                            <?= (empty($creds['client_id']) ? '❌ Missing' : '✅ Found') ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>Secret (from DB):</strong></td>
                    <td>
                        <code><?= (empty($creds['secret']) ? 'MISSING' : '***' . substr($creds['secret'], -10)) ?></code>
                        <span style="color: <?= (empty($creds['secret']) ? '#dc3232' : '#28a745') ?>">
                            <?= (empty($creds['secret']) ? '❌ Missing' : '✅ Found (last 10 chars shown)') ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <td><strong>API URL:</strong></td>
                    <td><code><?= esc_html($creds['api_url']) ?></code></td>
                </tr>
            </table>
            
            <h3 style="margin-top: 20px;">Raw Option Values (with length check):</h3>
            <table class="widefat">
                <tr>
                    <td><strong>paypal_environment:</strong></td>
                    <td><code><?= esc_html(get_option('paypal_environment')) ?></code></td>
                </tr>
                <tr>
                    <td><strong>paypal_sandbox_client_id:</strong></td>
                    <td>
                        <code><?= esc_html(get_option('paypal_sandbox_client_id')) ?></code>
                        <small style="color: #999;">(Length: <?= strlen(get_option('paypal_sandbox_client_id')) ?> chars)</small>
                    </td>
                </tr>
                <tr>
                    <td><strong>paypal_sandbox_secret:</strong></td>
                    <td>
                        <code><?= '***' . substr(get_option('paypal_sandbox_secret'), -10) ?></code>
                        <small style="color: #999;">(Length: <?= strlen(get_option('paypal_sandbox_secret')) ?> chars)</small>
                    </td>
                </tr>
                <tr>
                    <td><strong>paypal_live_client_id:</strong></td>
                    <td>
                        <code><?= esc_html(get_option('paypal_live_client_id')) ?></code>
                        <small style="color: #999;">(Length: <?= strlen(get_option('paypal_live_client_id')) ?> chars)</small>
                    </td>
                </tr>
                <tr>
                    <td><strong>paypal_live_secret:</strong></td>
                    <td>
                        <code><?= '***' . substr(get_option('paypal_live_secret'), -10) ?></code>
                        <small style="color: #999;">(Length: <?= strlen(get_option('paypal_live_secret')) ?> chars)</small>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Force Create Plans Button -->
        <div class="card" style="margin-top: 20px;">
            <h2>⚡ Force Create Missing Plans</h2>
            <p>This will attempt to create PayPal plans for all variations that are missing them.</p>
            
            <?php
            if (isset($_POST['force_create_plans']) && wp_verify_nonce($_POST['_wpnonce'], 'force_create')) {
                $result = mm_force_create_all_plans();
                echo '<div style="background: ' . ($result['success'] ? '#d4edda' : '#f8d7da') . '; padding: 15px; border-radius: 3px; margin-bottom: 15px; border: 1px solid ' . ($result['success'] ? '#c3e6cb' : '#f5c6cb') . ';">';
                echo '<strong>' . ($result['success'] ? '✅ Operation complete!' : '⚠️ Some plans failed') . '</strong>';
                echo '<ul style="margin: 10px 0 0 0;">';
                foreach ($result['messages'] as $msg) {
                    echo '<li>' . htmlspecialchars($msg) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            ?>
            
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('force_create'); ?>
                <button type="submit" name="force_create_plans" class="button button-primary" style="padding: 10px 20px; font-size: 14px;">
                    ⚡ Create All Missing Plans Now
                </button>
            </form>
        </div>
        
        <!-- All Courses with Variations -->
        <div class="card" style="margin-top: 20px;">
            <h2>2️⃣ Courses with Monthly/Yearly Variations</h2>
            
            <?php
            $courses = get_posts([
                'post_type' => 'course',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ]);
            
            if (empty($courses)) {
                echo '<p>No courses found.</p>';
            } else {
                foreach ($courses as $course) {
                    $variations = function_exists('mm_get_course_variations') 
                        ? mm_get_course_variations($course->ID) 
                        : [];
                    
                    if (empty($variations)) continue;
                    
                    // Check for subscription variations
                    $has_subs = false;
                    foreach ($variations as $v) {
                        if (in_array($v['billing'] ?? 'onetime', ['monthly', 'yearly', 'weekly'])) {
                            $has_subs = true;
                            break;
                        }
                    }
                    
                    if (!$has_subs) continue;
                    
                    echo '<div style="margin-bottom: 30px; padding: 15px; background: #f5f5f5; border-left: 4px solid #2271b1;">';
                    echo '<h3 style="margin-top: 0;">' . esc_html($course->post_title) . ' (ID: ' . $course->ID . ')</h3>';
                    echo '<table class="widefat">';
                    echo '<thead><tr><th>Label</th><th>Billing</th><th>Price</th><th>Plan ID</th><th>Status</th></tr></thead>';
                    echo '<tbody>';
                    
                    foreach ($variations as $i => $v) {
                        if (!in_array($v['billing'] ?? 'onetime', ['monthly', 'yearly', 'weekly'])) continue;
                        
                        $has_plan = !empty($v['plan_id']);
                        $status = $has_plan ? '✅ OK' : '❌ MISSING';
                        $status_color = $has_plan ? '#28a745' : '#dc3232';
                        
                        echo '<tr>';
                        echo '<td>' . esc_html($v['label']) . '</td>';
                        echo '<td>' . esc_html($v['billing']) . '</td>';
                        echo '<td>$' . esc_html($v['price']) . '</td>';
                        echo '<td><code>' . ($has_plan ? esc_html($v['plan_id']) : 'EMPTY') . '</code></td>';
                        echo '<td style="color: ' . $status_color . '; font-weight: bold;">' . $status . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody>';
                    echo '</table>';
                    echo '</div>';
                }
            }
            ?>
        </div>
        
        <!-- Errors from Last Save -->
        <div class="card" style="margin-top: 20px;">
            <h2>3️⃣ Recent PayPal Errors (Last 10 Courses)</h2>
            
            <?php
            $has_errors = false;
            foreach ($courses as $course) {
                $errors = get_transient('mm_var_errors_' . $course->ID);
                if (!$errors) continue;
                
                $has_errors = true;
                echo '<div style="margin-bottom: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #dc3232;">';
                echo '<h4 style="margin-top: 0;">' . esc_html($course->post_title) . '</h4>';
                echo '<ul style="margin: 0;">';
                foreach ($errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            
            if (!$has_errors) {
                echo '<p style="color: #28a745;"><strong>✅ No errors found!</strong></p>';
            }
            ?>
        </div>
        
        <!-- Raw Database Query -->
        <div class="card" style="margin-top: 20px;">
            <h2>4️⃣ Raw Database Data</h2>
            <p>Here's the exact JSON stored in post meta for each course:</p>
            
            <?php
            foreach ($courses as $course) {
                $raw = get_post_meta($course->ID, '_course_variations', true);
                if (!$raw) continue;
                
                $data = json_decode($raw, true);
                
                echo '<div style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">';
                echo '<h4 style="margin-top: 0;">' . esc_html($course->post_title) . ' (Post ID: ' . $course->ID . ')</h4>';
                echo '<pre style="background: white; padding: 10px; border-radius: 3px; overflow-x: auto;">';
                echo htmlspecialchars(wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                echo '</pre>';
                echo '</div>';
            }
            ?>
        </div>
        
        <!-- Test Connection -->
        <div class="card" style="margin-top: 20px;">
            <h2>5️⃣ Test PayPal Connection</h2>
            
            <?php
            // Clear cached token to force fresh request
            if (isset($_POST['test_paypal']) && wp_verify_nonce($_POST['_wpnonce'], 'test_paypal')) {
                delete_transient('mm_pp_access_token');
                echo '<div style="background: #fff3cd; padding: 10px; border-radius: 3px; margin-bottom: 15px;">
                    <strong>✓ Cache cleared - making fresh request...</strong>
                </div>';
            }
            ?>
            
            <form method="post" style="margin-bottom: 15px;">
                <?php wp_nonce_field('test_paypal'); ?>
                <button type="submit" name="test_paypal" class="button button-primary">🔄 Test Connection (Clear Cache)</button>
            </form>
            
            <?php
            if (!$creds || empty($creds['client_id']) || empty($creds['secret'])) {
                echo '<p style="color: #dc3232;"><strong>❌ PayPal credentials not configured!</strong></p>';
            } else {
                // Try to get access token
                if (function_exists('mm_pp_get_access_token')) {
                    $token = mm_pp_get_access_token();
                    if (is_wp_error($token)) {
                        echo '<p style="color: #dc3232;"><strong>❌ Cannot get access token:</strong></p>';
                        echo '<pre style="background: #fff3cd; padding: 10px; border-radius: 3px;">';
                        echo htmlspecialchars($token->get_error_message());
                        if ($token->get_error_data()) {
                            echo "\n\n";
                            echo htmlspecialchars(wp_json_encode($token->get_error_data(), JSON_PRETTY_PRINT));
                        }
                        echo '</pre>';
                    } else {
                        echo '<p style="color: #28a745;"><strong>✅ PayPal connection successful!</strong></p>';
                        echo '<p><code>Token: ' . substr($token, 0, 30) . '...</code></p>';
                    }
                } else {
                    echo '<p style="color: #dc3232;"><strong>❌ PayPal API functions not loaded!</strong></p>';
                }
            }
            ?>
        </div>
        
        <!-- Debug Logs -->
        <div class="card" style="margin-top: 20px;">
            <h2>6️⃣ Recent Debug Logs (Last 50 lines from debug.log)</h2>
            
            <?php
            $debug_log = WP_CONTENT_DIR . '/debug.log';
            if (file_exists($debug_log)) {
                $lines = file($debug_log);
                $last_lines = array_slice($lines, -50);
                
                // Filter for PayPal lines
                $paypal_lines = array_filter($last_lines, function($line) {
                    return strpos($line, '[PayPal]') !== false || strpos($line, 'paypal') !== false;
                });
                
                if (!empty($paypal_lines)) {
                    echo '<pre style="background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; max-height: 400px; font-size: 11px;">';
                    foreach (array_slice($paypal_lines, -30) as $line) {
                        echo htmlspecialchars($line);
                    }
                    echo '</pre>';
                } else {
                    echo '<p style="color: #999;">No PayPal debug logs found. Check if WP_DEBUG is enabled in wp-config.php</p>';
                }
            } else {
                echo '<p style="color: #dc3232;">⚠️ debug.log not found at: ' . $debug_log . '</p>';
                echo '<p>Enable it in wp-config.php:</p>';
                echo '<pre style="background: #f5f5f5; padding: 10px; border-radius: 3px;">define(\'WP_DEBUG\', true);
define(\'WP_DEBUG_LOG\', true);
define(\'WP_DEBUG_DISPLAY\', false);</pre>';
            }
            ?>
        </div>
    </div>
    
    <style>
        .widefat { margin: 15px 0; }
        .widefat th { background: #f5f5f5; padding: 10px; font-weight: 600; }
        .widefat td { padding: 10px; border-bottom: 1px solid #ddd; }
        pre { font-size: 12px; }
    </style>
    <?php
}

/**
 * Force create all missing PayPal plans
 */
function mm_force_create_all_plans()
{
    $result = ['success' => true, 'messages' => []];
    
    $courses = get_posts([
        'post_type' => 'course',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);
    
    foreach ($courses as $course) {
        $variations = function_exists('mm_get_course_variations') 
            ? mm_get_course_variations($course->ID) 
            : [];
        
        if (empty($variations)) continue;
        
        $clean = [];
        $errors = [];
        
        foreach ($variations as $i => $v) {
            // Only process subscription variations without plan IDs
            if (!in_array($v['billing'] ?? 'onetime', ['weekly', 'monthly', 'yearly']) || !empty($v['plan_id'])) {
                $clean[] = $v; // Keep as is
                continue;
            }
            
            $interval_map = ['weekly' => 'WEEK', 'monthly' => 'MONTH', 'yearly' => 'YEAR'];
            $interval = $interval_map[$v['billing']] ?? 'MONTH';
            
            // Try to create product first
            $product_id = mm_pp_get_or_create_product($course->ID);
            $plan = null;
            
            if (!is_wp_error($product_id)) {
                // Try with product
                $plan = mm_pp_create_plan($product_id, $v['label'], $v['price'], $interval);
            }
            
            // If product failed OR plan creation failed, try simpler version
            if (is_wp_error($plan)) {
                $result['messages'][] = '⚠️ ' . $course->post_title . ' > ' . $v['label'] . ': Retrying without product...';
                $plan = mm_pp_create_plan_simple($v['label'], $v['price'], $interval);
            }
            
            // Check final result
            if (is_wp_error($plan)) {
                $error_msg = $plan->get_error_message();
                $error_data = $plan->get_error_data();
                if (is_array($error_data) && isset($error_data['details'])) {
                    $error_msg .= ' [' . $error_data['details'][0]['issue'] . ']';
                }
                $errors[] = $course->post_title . ' > ' . $v['label'] . ': ' . $error_msg;
                $clean[] = $v; // Keep without plan ID
                $result['success'] = false;
            } else {
                $v['plan_id'] = $plan;
                $clean[] = $v;
                $result['messages'][] = '✅ ' . $course->post_title . ' > ' . $v['label'] . ': Plan ' . $plan;
            }
        }
        
        // Save the updated variations
        if (!empty($clean)) {
            update_post_meta($course->ID, '_course_variations', wp_json_encode($clean));
        }
        
        // Log any errors
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $result['messages'][] = '❌ ' . $error;
            }
        }
    }
    
    if (empty($result['messages'])) {
        $result['messages'][] = 'No missing plans found to create.';
    }
    
    return $result;
}
