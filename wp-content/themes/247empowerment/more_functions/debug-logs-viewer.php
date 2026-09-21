<?php
/**
 * Debug Logs Viewer - For OAuth Debugging
 * Access: /wp-admin/admin.php?page=debug-logs
 */

// Add admin menu
add_action('admin_menu', 'register_debug_logs_page');
function register_debug_logs_page() {
    if (is_super_admin()) {
        add_menu_page(
            'Debug Logs',
            'Debug Logs',
            'manage_options',
            'debug-logs',
            'render_debug_logs_page',
            'dashicons-format-aside',
            99
        );
    }
}

function render_debug_logs_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    $debug_log_file = WP_CONTENT_DIR . '/debug.log';
    $log_exists = file_exists($debug_log_file);
    $log_content = '';
    $log_size = 0;

    if ($log_exists) {
        $log_size = filesize($debug_log_file);
        $max_display = 50000; // Show last 50KB

        if ($log_size > $max_display) {
            // Show last N bytes
            $log_content = file_get_contents($debug_log_file, false, null, $log_size - $max_display);
            $truncated = true;
        } else {
            $log_content = file_get_contents($debug_log_file);
            $truncated = false;
        }
    }

    // Get OAuth state values from transients for debugging
    $user_id = get_current_user_id();
    $fb_state = get_transient('facebook_oauth_state_' . $user_id);
    ?>
    <div class="wrap">
        <h1>🔍 Debug Logs Viewer</h1>
        
        <!-- System Info -->
        <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h3>System Information</h3>
            <table style="width: 100%;">
                <tr>
                    <td><strong>WordPress Version:</strong></td>
                    <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                </tr>
                <tr>
                    <td><strong>PHP Version:</strong></td>
                    <td><?php echo esc_html(phpversion()); ?></td>
                </tr>
                <tr>
                    <td><strong>Debug Mode:</strong></td>
                    <td><?php echo defined('WP_DEBUG') && WP_DEBUG ? '✅ Enabled' : '❌ Disabled'; ?></td>
                </tr>
                <tr>
                    <td><strong>Debug Log:</strong></td>
                    <td><?php echo defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? '✅ Enabled' : '❌ Disabled'; ?></td>
                </tr>
                <tr>
                    <td><strong>Current User ID:</strong></td>
                    <td><?php echo esc_html($user_id); ?></td>
                </tr>
            </table>
        </div>

        <!-- OAuth State Info -->
        <div style="background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h3>🔐 OAuth State (Current User)</h3>
            <p style="margin: 0;"><strong>Facebook State Transient:</strong></p>
            <code style="display: block; background: white; padding: 10px; margin: 10px 0; overflow-x: auto;">
                <?php echo $fb_state ? esc_html($fb_state) : '(empty - not set yet)'; ?>
            </code>
        </div>

        <!-- Log File Status -->
        <div style="background: <?php echo $log_exists ? '#d4edda' : '#f8d7da'; ?>; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h3><?php echo $log_exists ? '✅ Debug Log Found' : '❌ Debug Log Not Found'; ?></h3>
            <?php if ($log_exists): ?>
                <p><strong>File Location:</strong> <code><?php echo esc_html($debug_log_file); ?></code></p>
                <p><strong>File Size:</strong> <?php echo size_format($log_size); ?></p>
                <?php if ($truncated): ?>
                    <p style="color: #856404;"><strong>Note:</strong> Showing last 50KB of log file (truncated)</p>
                <?php endif; ?>
            <?php else: ?>
                <p>Debug log file not found. Enable it in wp-config.php:</p>
                <code style="display: block; background: white; padding: 10px; margin: 10px 0;">
define('WP_DEBUG', true);<br>
define('WP_DEBUG_LOG', true);<br>
define('WP_DEBUG_DISPLAY', false);
                </code>
            <?php endif; ?>
        </div>

        <!-- Log Content -->
        <div>
            <h3>📋 Log Content</h3>
            <div style="background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 5px; overflow-x: auto; max-height: 600px; overflow-y: auto; font-family: 'Courier New', monospace; font-size: 12px; white-space: pre-wrap; word-wrap: break-word;">
                <?php 
                if ($log_content) {
                    // Highlight OAuth-related lines
                    $lines = explode("\n", $log_content);
                    foreach ($lines as $line) {
                        if (strpos($line, 'OAuth') !== false || 
                            strpos($line, 'state') !== false ||
                            strpos($line, 'facebook') !== false ||
                            strpos($line, 'linkedin') !== false) {
                            echo '<span style="background: #4a4a00; padding: 2px 5px;">' . esc_html($line) . "</span>\n";
                        } else {
                            echo esc_html($line) . "\n";
                        }
                    }
                } else {
                    echo '(No log content available)';
                }
                ?>
            </div>
        </div>

        <!-- Actions -->
        <div style="margin: 20px 0;">
            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=debug-logs&action=clear'), 'clear_debug_log'); ?>" 
               class="button button-secondary"
               onclick="return confirm('Are you sure you want to clear the debug log?');">
                Clear Log
            </a>
            <button class="button button-primary" onclick="location.reload();">
                Refresh
            </button>
        </div>

        <!-- Instructions -->
        <div style="background: #e7f3ff; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <h3>📖 How to Debug OAuth Issue</h3>
            <ol>
                <li>Click "Connect with Facebook" in profile settings</li>
                <li>Complete the login</li>
                <li>Come back to this page and refresh</li>
                <li>Look for "OAuth" entries in the log (highlighted)</li>
                <li>Copy the entire log and share with developer</li>
            </ol>
        </div>
    </div>

    <?php
    // Handle clear action
    if (isset($_GET['action']) && $_GET['action'] === 'clear') {
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'clear_debug_log')) {
            wp_die('Security check failed');
        }
        if (file_exists($debug_log_file)) {
            file_put_contents($debug_log_file, '');
            echo '<div class="notice notice-success"><p>Debug log cleared!</p></div>';
            wp_safe_remote_get(admin_url('admin.php?page=debug-logs'));
        }
    }
}
