<?php
/**
 * PayPal Diagnostic Dashboard
 * Admin page to debug PayPal API integration issues
 */

if (!defined('ABSPATH')) exit;

// Add admin menu
add_action('admin_menu', function () {
    add_submenu_page(
        'options-general.php',
        'PayPal Diagnostics',
        'PayPal Diagnostics',
        'manage_options',
        'paypal-diagnostics',
        'mm_paypal_diagnostic_page'
    );
});

function mm_paypal_diagnostic_page()
{
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    // Handle cache clear
    if (isset($_POST['action']) && $_POST['action'] === 'clear_cache' && check_admin_referer('paypal_diagnostic')) {
        delete_transient('mm_pp_access_token');
        echo '<div class="notice notice-success"><p>✓ PayPal token cache cleared</p></div>';
    }

    // Handle token request
    if (isset($_POST['action']) && $_POST['action'] === 'request_token' && check_admin_referer('paypal_diagnostic')) {
        delete_transient('mm_pp_access_token');
        if (function_exists('mm_pp_clear_debug')) {
            mm_pp_clear_debug();
        }
        $token = mm_pp_get_access_token();
        if (is_wp_error($token)) {
            echo '<div class="notice notice-error"><p>Error: ' . $token->get_error_message() . '</p>';
            if ($data = $token->get_error_data()) {
                echo '<pre>' . wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre>';
            }
            echo '</div>';
        } else {
            echo '<div class="notice notice-success"><p>✓ Token obtained successfully</p>';
            echo '<p>Token length: ' . strlen($token) . ' chars</p>';
            echo '<p>Token preview: ' . substr($token, 0, 30) . '...' . substr($token, -30) . '</p>';
            echo '</div>';
        }
        if (function_exists('mm_pp_get_debug')) {
            $debug = mm_pp_get_debug();
            if (!empty($debug)) {
                echo '<div class="notice notice-info"><p><strong>Debug Log:</strong></p>';
                echo '<pre>' . wp_json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</pre></div>';
            }
        }
    }

    // Get credentials
    $cred = get_paypal_api_credentials();
    $has_client_id = !empty($cred['client_id']);
    $has_secret = !empty($cred['secret']);
    $cached_token = get_transient('mm_pp_access_token');
    $env = get_option('paypal_environment', 'sandbox');

    ?>
    <div class="wrap">
        <h1>🔍 PayPal Diagnostics</h1>
        
        <div style="background: #fff; padding: 20px; border-radius: 5px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,.1);">
            <h2>Configuration Status</h2>
            
            <table class="widefat">
                <tbody>
                    <tr>
                        <td><strong>Environment</strong></td>
                        <td>
                            <span style="font-size: 18px; font-weight: bold; color: <?= $env === 'live' ? '#e74c3c' : '#27ae60' ?>;">
                                <?= $env === 'live' ? '🔴 LIVE' : '🟡 SANDBOX' ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>API URL</strong></td>
                        <td><code><?= esc_html($cred['api_url']) ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>Client ID</strong></td>
                        <td>
                            <?php if ($has_client_id): ?>
                                <span style="color: #27ae60;">✓ Configured</span>
                                <br><code><?= substr($cred['client_id'], 0, 20) ?>...<?= substr($cred['client_id'], -20) ?></code>
                                <br><small>Length: <?= strlen($cred['client_id']) ?> chars</small>
                            <?php else: ?>
                                <span style="color: #e74c3c;">✗ Missing</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Secret</strong></td>
                        <td>
                            <?php if ($has_secret): ?>
                                <span style="color: #27ae60;">✓ Configured</span>
                                <br><code><?= substr($cred['secret'], 0, 20) ?>...<?= substr($cred['secret'], -20) ?></code>
                                <br><small>Length: <?= strlen($cred['secret']) ?> chars</small>
                            <?php else: ?>
                                <span style="color: #e74c3c;">✗ Missing</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Cached Token</strong></td>
                        <td>
                            <?php if ($cached_token): ?>
                                <span style="color: #27ae60;">✓ Cached</span>
                                <br><small>Length: <?= strlen($cached_token) ?> chars</small>
                                <br><code><?= substr($cached_token, 0, 20) ?>...<?= substr($cached_token, -20) ?></code>
                            <?php else: ?>
                                <span style="color: #999;">Not cached (will request new)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 5px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,.1);">
            <h2>Token Testing</h2>
            
            <form method="post" style="display: flex; gap: 10px; flex-wrap: wrap;">
                <?php wp_nonce_field('paypal_diagnostic') ?>
                
                <button type="submit" name="action" value="request_token" class="button button-primary">
                    🔄 Request New Token
                </button>
                
                <button type="submit" name="action" value="clear_cache" class="button button-secondary">
                    🗑️ Clear Cache
                </button>
                
                <?php if ($cached_token): ?>
                    <button type="button" class="button" onclick="copyToClipboard('cached_token')">
                        📋 Copy Cached Token
                    </button>
                    <span id="copy_status" style="display: none; color: #27ae60; font-weight: bold;">Copied!</span>
                <?php endif; ?>
            </form>
            
            <?php if ($cached_token): ?>
                <textarea id="cached_token" readonly style="width: 100%; height: 80px; margin-top: 10px; font-family: monospace; font-size: 12px;"><?= esc_textarea($cached_token) ?></textarea>
            <?php endif; ?>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 5px; margin: 20px 0; box-shadow: 0 1px 3px rgba(0,0,0,.1);">
            <h2>Latest Debug Output</h2>
            
            <?php 
            $variations_debug = get_transient('mm_latest_paypal_debug');
            if (!empty($variations_debug)):
            ?>
                <pre style="background: #f5f5f5; padding: 15px; border-radius: 3px; border: 1px solid #ddd; overflow-x: auto;">
<?= esc_html(wp_json_encode($variations_debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?>
                </pre>
            <?php else: ?>
                <p style="color: #999;">No debug data yet. Save a course with variations to generate debug output.</p>
            <?php endif; ?>
        </div>

        <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; border-left: 4px solid #3498db; margin: 20px 0;">
            <h3>💡 How to Debug</h3>
            <ol>
                <li>Click <strong>Request New Token</strong> to test token generation</li>
                <li>If it fails, check credentials above (Client ID + Secret)</li>
                <li>If token works, go to course edit page and save to test plan creation</li>
                <li>Debug output will show here after save</li>
            </ol>
        </div>
    </div>

    <script>
    function copyToClipboard(elementId) {
        const element = document.getElementById(elementId);
        element.select();
        document.execCommand('copy');
        const status = document.getElementById('copy_status');
        status.style.display = 'inline';
        setTimeout(() => {
            status.style.display = 'none';
        }, 2000);
    }
    </script>
    <?php
}

// Store debug info for diagnostic page
add_action('save_post_course', function ($post_id) {
    if (function_exists('mm_pp_get_debug')) {
        $debug = mm_pp_get_debug();
        if (!empty($debug)) {
            set_transient('mm_latest_paypal_debug', $debug, DAY_IN_SECONDS);
        }
    }
}, 999); // Run at end after plan creation
