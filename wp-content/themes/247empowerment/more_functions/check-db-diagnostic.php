<?php
/**
 * Database Check - WordPress Admin Page
 * Accessible from: WordPress Dashboard → Dashboard → "DB Check"
 */

add_action('admin_menu', 'mm_add_db_check_page');

function mm_add_db_check_page() {
    add_dashboard_page(
        'DB Check - Variations',
        'DB Check',
        'manage_options',
        'mm-db-check',
        'mm_render_db_check_page'
    );
}

function mm_render_db_check_page() {
    global $wpdb;

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            .mm-check-container { max-width: 1200px; margin: 20px; font-family: monospace; }
            .mm-check-table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; }
            .mm-check-table th, .mm-check-table td { border: 1px solid #ddd; padding: 12px; text-align: left; }
            .mm-check-table th { background: #0073aa; color: white; }
            .mm-status-empty { color: #d32f2f; font-weight: bold; }
            .mm-status-ok { color: #388e3c; font-weight: bold; }
            .mm-status-warn { color: #f57c00; font-weight: bold; }
            .mm-detail-box { margin: 15px 0; padding: 15px; border: 2px solid #0073aa; border-radius: 5px; background: #f5f5f5; }
            pre { background: #1e1e1e; color: #0f0; padding: 15px; overflow-x: auto; border-radius: 3px; }
            hr { border: none; border-top: 2px solid #0073aa; margin: 40px 0; }
        </style>
    </head>
    <body>

    <div class="mm-check-container">
        <h1>🔍 Database Check - Course Variations</h1>

        <?php

        // Get all courses with their meta
        $query = "
            SELECT p.ID, p.post_title, pm.meta_value
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id AND pm.meta_key = '_course_variations')
            WHERE p.post_type = 'course'
            AND p.post_status = 'publish'
            ORDER BY p.post_title
        ";

        $results = $wpdb->get_results($query);

        if (empty($results)) {
            echo '<p class="mm-status-empty">❌ کوئی courses نہیں ملے</p>';
        } else {
            echo '<table class="mm-check-table">';
            echo '<tr><th>Course ID</th><th>Course Title</th><th>Variations</th><th>Status</th></tr>';

            foreach ($results as $row) {
                $meta_value = $row->meta_value;
                $status = '❌ EMPTY';
                $status_class = 'mm-status-empty';

                if (!empty($meta_value)) {
                    $decoded = json_decode($meta_value, true);
                    if (is_array($decoded)) {
                        $has_subs = false;
                        $has_plans = false;

                        foreach ($decoded as $var) {
                            $billing = $var['billing'] ?? 'onetime';
                            if ($billing !== 'onetime') {
                                $has_subs = true;
                                if (!empty($var['plan_id'])) {
                                    $has_plans = true;
                                }
                            }
                        }

                        if ($has_subs && $has_plans) {
                            $status = '✅ HAS PLANS';
                            $status_class = 'mm-status-ok';
                        } elseif ($has_subs && !$has_plans) {
                            $status = '⚠️ HAS SUBS NO PLANS';
                            $status_class = 'mm-status-warn';
                        } else {
                            $status = '✅ ONE-TIME ONLY';
                            $status_class = 'mm-status-ok';
                        }
                    }
                }

                echo '<tr>';
                echo '<td>' . $row->ID . '</td>';
                echo '<td><strong>' . esc_html($row->post_title) . '</strong></td>';
                echo '<td><small>' . (empty($meta_value) ? 'NULL' : (strlen($meta_value) > 50 ? substr($meta_value, 0, 50) . '...' : $meta_value)) . '</small></td>';
                echo '<td class="' . $status_class . '">' . $status . '</td>';
                echo '</tr>';
            }

            echo '</table>';
        }

        echo '<hr>';

        // Show detailed analysis
        echo '<h2>📋 تفصیلی تجزیہ</h2>';

        foreach ($results as $row) {
            $meta_value = $row->meta_value;
            if (empty($meta_value)) continue;

            $decoded = json_decode($meta_value, true);
            if (!is_array($decoded)) continue;

            echo '<div class="mm-detail-box">';
            echo '<h3>📚 ' . esc_html($row->post_title) . ' (ID: ' . $row->ID . ')</h3>';

            foreach ($decoded as $idx => $var) {
                $billing = $var['billing'] ?? 'onetime';
                $plan_id = $var['plan_id'] ?? '';
                $label = $var['label'] ?? 'N/A';
                $price = $var['price'] ?? 'N/A';

                echo '<div style="margin: 10px 0; padding: 10px; border-left: 4px solid #0073aa; background: white;">';
                echo '<strong>Variation [' . $idx . ']: ' . esc_html($label) . '</strong><br>';
                echo 'Price: ' . esc_html($price) . ' | Billing: ' . esc_html($billing) . '<br>';

                if ($billing === 'onetime') {
                    echo 'Plan ID: <span class="mm-status-ok">N/A (یکمشتی خریداری)</span>';
                } else {
                    if (empty($plan_id)) {
                        echo 'Plan ID: <span class="mm-status-empty">❌ EMPTY - یہ مسئلہ ہے!</span>';
                    } else {
                        echo 'Plan ID: <span class="mm-status-ok">✅ ' . esc_html($plan_id) . '</span>';

                        // Try to validate with PayPal
                        if (function_exists('mm_pp_request')) {
                            echo '<br>PayPal Validation: ';
                            $res = mm_pp_request('GET', '/v1/billing/plans/' . rawurlencode($plan_id));
                            if (is_wp_error($res)) {
                                echo '<span class="mm-status-empty">❌ نہیں ملا - ' . $res->get_error_message() . '</span>';
                            } else {
                                echo '<span class="mm-status-ok">✅ موجود - Status: ' . ($res['status'] ?? 'معلوم نہیں') . '</span>';
                            }
                        }
                    }
                }

                echo '</div>';
            }

            echo '</div>';
        }

        ?>

        <hr>

        <h2>✏️ حل: Plan ID کو manually add کریں</h2>

        <div class="mm-detail-box">
            <p><strong>اگر Plan ID خالی ہے تو:</strong></p>
            <ol>
                <li>WordPress Admin میں جائیں → <strong>Courses</strong></li>
                <li>اپنا course edit کریں</li>
                <li><strong>"Product / Course Variations"</strong> section تلاش کریں</li>
                <li>Subscription variation (monthly/yearly) کو تلاش کریں</li>
                <li><strong>"PayPal Plan ID"</strong> field میں اپنا plan ID paste کریں جو PayPal sandbox سے ہے</li>
                <li><strong>"Save Post"</strong> کریں</li>
                <li>اس page کو refresh کریں تو status تبدیل ہو جائے گا</li>
            </ol>
            <p><strong>مثال Plan ID:</strong> <code>I-ABC1D2E3F4G5H6I7</code></p>
        </div>

    </div>

    </body>
    </html>

    <?php
}
