<?php
/**
 * PayPal Diagnostic Page
 * Access via: WordPress Admin → Dashboard → PayPal Diagnostic
 */

if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    if (!current_user_can('manage_options')) return;
    
    add_dashboard_page(
        'PayPal Diagnostic',
        'PayPal Diagnostic',
        'manage_options',
        'mm-paypal-diag',
        'mm_paypal_diagnostic_page'
    );
});

function mm_paypal_diagnostic_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('Access denied');
    }
    
    ?>
    <div class="wrap">
        <h1>🔍 PayPal Diagnostic Check</h1>
        
        <!-- Database Content -->
        <div class="card" style="margin-top: 20px;">
            <h2>1️⃣ Check All Courses & Their Variation Data</h2>
            
            <?php
            $courses = get_posts([
                'post_type' => 'course',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ]);
            
            if (empty($courses)) {
                echo '<p style="color: #dc3232;">❌ No courses found</p>';
            } else {
                foreach ($courses as $course) {
                    $raw_json = get_post_meta($course->ID, '_course_variations', true);
                    $variations = !empty($raw_json) ? json_decode($raw_json, true) : [];
                    
                    echo '<div style="margin-bottom: 20px; padding: 15px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;">';
                    echo '<h3 style="margin-top: 0;">' . esc_html($course->post_title) . ' (ID: ' . $course->ID . ')</h3>';
                    
                    // Show raw JSON
                    echo '<strong>Raw JSON in Database:</strong><br>';
                    echo '<pre style="background: white; padding: 10px; border-radius: 3px; overflow-x: auto; max-height: 200px;">';
                    if (empty($raw_json)) {
                        echo '<span style="color: #dc3232;">❌ NO VARIATIONS SAVED</span>';
                    } else {
                        echo htmlspecialchars($raw_json);
                    }
                    echo '</pre>';
                    
                    // Analyze each variation
                    if (!empty($variations) && is_array($variations)) {
                        echo '<strong>Decoded Variations:</strong><br>';
                        echo '<table style="width: 100%; border-collapse: collapse; margin-top: 10px;">';
                        echo '<tr style="background: #ddd; border: 1px solid #999;">';
                        echo '<th style="padding: 5px; border: 1px solid #999;">Label</th>';
                        echo '<th style="padding: 5px; border: 1px solid #999;">Price</th>';
                        echo '<th style="padding: 5px; border: 1px solid #999;">Billing</th>';
                        echo '<th style="padding: 5px; border: 1px solid #999;">Plan ID Status</th>';
                        echo '</tr>';
                        
                        foreach ($variations as $i => $var) {
                            $plan_id = $var['plan_id'] ?? '';
                            $billing = $var['billing'] ?? 'onetime';
                            $has_plan = !empty($plan_id);
                            $status = ($billing === 'onetime') 
                                ? '<span style="color: #0073aa;">N/A (One-time)</span>'
                                : ($has_plan 
                                    ? '<span style="color: #28a745;">✅ ' . esc_html(substr($plan_id, 0, 20)) . '...</span>'
                                    : '<span style="color: #dc3232;">❌ EMPTY</span>');
                            
                            echo '<tr style="border: 1px solid #ddd;">';
                            echo '<td style="padding: 5px; border: 1px solid #ddd;">' . esc_html($var['label'] ?? 'N/A') . '</td>';
                            echo '<td style="padding: 5px; border: 1px solid #ddd;">$' . esc_html($var['price'] ?? '0') . '</td>';
                            echo '<td style="padding: 5px; border: 1px solid #ddd;">' . esc_html($billing) . '</td>';
                            echo '<td style="padding: 5px; border: 1px solid #ddd;">' . $status . '</td>';
                            echo '</tr>';
                        }
                        echo '</table>';
                    }
                    
                    echo '</div>';
                }
            }
            ?>
        </div>
        
        <!-- Frontend Rendering Test -->
        <div class="card" style="margin-top: 20px;">
            <h2>2️⃣ Test Frontend Rendering</h2>
            <p>Go to a course page while logged in and check browser console (F12).</p>
            <p>You should see:</p>
            <pre style="background: #f5f5f5; padding: 10px; border-radius: 3px;">
📦 [single-store-template] Loaded variations for course 123: 1 variations
  [0] Label: Monthly Plan | Billing: monthly | Plan ID: I-ABC1D2E3F4G5H6I7</pre>
            <p><strong>If Plan ID shows "EMPTY":</strong> Plan was not saved to database</p>
            <p><strong>If you don't see this log:</strong> File was not updated or WP_DEBUG not enabled</p>
        </div>
        
        <!-- PayPal API Test -->
        <div class="card" style="margin-top: 20px;">
            <h2>3️⃣ Validate Plan Exists in PayPal</h2>
            
            <form method="post">
                <?php wp_nonce_field('test_plan_paypal'); ?>
                <p>
                    <label for="plan_id">Plan ID to test:</label><br>
                    <input type="text" id="plan_id" name="plan_id" placeholder="I-ABC1D2E3F4G5H6I7" style="width: 100%; max-width: 400px; padding: 5px;" />
                </p>
                <button type="submit" class="button button-primary">🔍 Check if Plan Exists in PayPal</button>
            </form>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan_id'])) {
                if (!wp_verify_nonce($_POST['_wpnonce'], 'test_plan_paypal')) {
                    echo '<p style="color: #dc3232;">❌ Nonce verification failed</p>';
                } else {
                    $plan_id = sanitize_text_field($_POST['plan_id']);
                    if (function_exists('mm_pp_request')) {
                        $result = mm_pp_request('GET', '/v1/billing/plans/' . rawurlencode($plan_id));
                        
                        echo '<div style="margin-top: 15px; padding: 15px; background: #f5f5f5; border-radius: 3px;">';
                        echo '<strong>PayPal Response:</strong><br>';
                        echo '<pre style="background: white; padding: 10px; border-radius: 3px; overflow-x: auto;">';
                        if (is_wp_error($result)) {
                            echo '<span style="color: #dc3232;">❌ ERROR:</span> ' . htmlspecialchars($result->get_error_message());
                            if ($result->get_error_data()) {
                                echo '<br><br>';
                                echo htmlspecialchars(wp_json_encode($result->get_error_data(), JSON_PRETTY_PRINT));
                            }
                        } else {
                            echo '<span style="color: #28a745;">✅ PLAN EXISTS</span><br><br>';
                            echo htmlspecialchars(wp_json_encode($result, JSON_PRETTY_PRINT));
                        }
                        echo '</pre>';
                        echo '</div>';
                    } else {
                        echo '<p style="color: #dc3232;">❌ PayPal API functions not available</p>';
                    }
                }
            }
            ?>
        </div>
        
        <style>
            .card { background: white; border: 1px solid #ccc; border-radius: 4px; padding: 20px; }
        </style>
    </div>
    <?php
}
