<?php
/**
 * Settings → Social Poster (credentials) and Settings → Social Poster Logs.
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Social_Poster_Settings
{
    const OPTION = 'mm_social_poster_settings';
    const PAGE = 'mm-social-poster';
    const LOGS_PAGE = 'mm-social-poster-logs';

    public static function init()
    {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_init', array(__CLASS__, 'register'));
        add_action('admin_post_mm_social_verify_credentials', array(__CLASS__, 'handle_verify_credentials'));
        add_action('admin_post_mm_social_test_api_version', array(__CLASS__, 'handle_test_api_version'));
    }

    public static function get($key, $default = '')
    {
        $opts = get_option(self::OPTION, array());
        return isset($opts[$key]) ? $opts[$key] : $default;
    }

    public static function menu()
    {
        add_menu_page('Social Poster', 'Social Poster', 'manage_options', self::PAGE, array(__CLASS__, 'render_settings'), 'dashicons-share', 26);
        add_submenu_page(self::PAGE, 'Social Poster Settings', 'Settings', 'manage_options', self::PAGE, array(__CLASS__, 'render_settings'));
        add_submenu_page(self::PAGE, 'Social Poster Logs', 'Logs', 'manage_options', self::LOGS_PAGE, array(__CLASS__, 'render_logs'));
        add_submenu_page(self::PAGE, 'Social Poster Debug', 'Debug', 'manage_options', self::PAGE . '-debug', array(__CLASS__, 'render_debug'));
    }

    /**
     * Asks LinkedIn for a client_credentials token purely to validate the ID/secret pair.
     * invalid_client => wrong secret; any other answer => credentials are accepted.
     */
    public static function handle_verify_credentials()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        check_admin_referer('mm_social_verify_credentials');

        $response = wp_remote_post(MM_Social_Poster_LinkedIn::TOKEN_URL, array(
            'timeout' => 20,
            'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
            'body'    => array(
                'grant_type'    => 'client_credentials',
                'client_id'     => MM_Social_Poster_LinkedIn::client_id(),
                'client_secret' => MM_Social_Poster_LinkedIn::client_secret(),
            ),
        ));

        if (is_wp_error($response)) {
            $result = array('status' => 'failed', 'message' => 'Request to LinkedIn failed: ' . $response->get_error_message());
        } else {
            $body = json_decode(wp_remote_retrieve_body($response), true) ?: array();
            $error = $body['error'] ?? '';
            if (!empty($body['access_token']) || $error === 'access_denied' || $error === 'unauthorized_client') {
                $result = array('status' => 'ok', 'message' => 'Client ID and Client Secret are accepted by LinkedIn.');
            } elseif ($error === 'invalid_client') {
                $result = array('status' => 'failed', 'message' => 'LinkedIn rejected the Client ID / Client Secret pair (invalid_client). Re-copy the Primary Client Secret from the LinkedIn app.');
            } else {
                $result = array('status' => 'failed', 'message' => 'Unexpected LinkedIn response: ' . wp_json_encode($body));
            }
        }

        set_transient('mm_social_verify_result_' . get_current_user_id(), $result, 60);
        wp_safe_redirect(admin_url('admin.php?page=' . self::PAGE));
        exit;
    }

    /**
     * Tests if the configured API version is active on LinkedIn.
     */
    public static function handle_test_api_version()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        check_admin_referer('mm_social_test_api_version');

        if (!MM_Social_Poster_LinkedIn::is_configured()) {
            $result = array('status' => 'failed', 'message' => 'LinkedIn credentials are not configured. Please configure Client ID and Client Secret first.');
        } else {
            // Make a simple API call with the configured version to test if it's active
            $version = self::get('linkedin_api_version', '202406');
            $test_url = 'https://api.linkedin.com/rest/me?oauth2_access_token=' . urlencode('test_token');
            
            $response = wp_remote_get($test_url, array(
                'timeout' => 15,
                'headers' => array(
                    'Authorization'             => 'Bearer invalid_test_token',
                    'LinkedIn-Version'          => $version,
                    'X-Restli-Protocol-Version' => '2.0.0',
                ),
            ));

            if (is_wp_error($response)) {
                $result = array('status' => 'failed', 'message' => 'Network error: ' . $response->get_error_message());
            } else {
                $body = json_decode(wp_remote_retrieve_body($response), true) ?: array();
                $http_code = (int) wp_remote_retrieve_response_code($response);
                
                if ($http_code === 401 || $http_code === 403) {
                    // 401/403 is expected (invalid token), but means version is valid
                    $result = array('status' => 'ok', 'message' => 'API version ' . esc_html($version) . ' is active on LinkedIn. ✓');
                } elseif ($http_code === 426 && !empty($body['code']) && $body['code'] === 'NONEXISTENT_VERSION') {
                    $result = array('status' => 'failed', 'message' => 'API version ' . esc_html($version) . ' is NOT active. Error: ' . esc_html($body['message'] ?? 'Unknown'));
                } elseif ($http_code === 400 && !empty($body['code']) && $body['code'] === 'INVALID_VERSION') {
                    $result = array('status' => 'failed', 'message' => 'API version format is invalid. Must be YYYYMM or YYYYMM.RR');
                } else {
                    $result = array('status' => 'unknown', 'message' => 'Unexpected response (HTTP ' . $http_code . '). Body: ' . wp_json_encode($body));
                }
            }
        }

        set_transient('mm_social_test_version_result_' . get_current_user_id(), $result, 60);
        wp_safe_redirect(admin_url('admin.php?page=' . self::PAGE));
        exit;
    }

    public static function register()
    {
        register_setting('mm_social_poster', self::OPTION, array(
            'type'              => 'array',
            'sanitize_callback' => array(__CLASS__, 'sanitize'),
            'default'           => array(),
        ));
    }

    public static function sanitize($input)
    {
        $current = get_option(self::OPTION, array());
        $out = array(
            'linkedin_client_id'      => sanitize_text_field($input['linkedin_client_id'] ?? ''),
            'linkedin_client_secret'  => $current['linkedin_client_secret'] ?? '',
            'linkedin_api_version'    => sanitize_text_field($input['linkedin_api_version'] ?? self::get('linkedin_api_version', '202406')),
        );

        // Blank secret field keeps the stored one; otherwise store encrypted.
        // WP may run this sanitizer twice on first save, so never re-encrypt an already-encrypted value.
        $secret = trim((string) ($input['linkedin_client_secret'] ?? ''));
        if ($secret !== '') {
            $out['linkedin_client_secret'] = MM_Social_Poster_Crypto::is_encrypted($secret)
                ? $secret
                : MM_Social_Poster_Crypto::encrypt($secret);
        }
        if (!empty($input['linkedin_clear_secret'])) {
            $out['linkedin_client_secret'] = '';
        }

        return $out;
    }

    public static function render_settings()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $has_secret = MM_Social_Poster_Settings::get('linkedin_client_secret') !== '';
        ?>
        <div class="wrap">
            <h1>Social Poster</h1>
            <p>Portal users connect their own LinkedIn accounts; these are the shared LinkedIn app credentials they authorize against.</p>

            <form method="post" action="options.php">
                <?php settings_fields('mm_social_poster'); ?>
                <h2 class="title"><span class="dashicons dashicons-linkedin"></span> LinkedIn</h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="mm_sp_linkedin_client_id">Client ID</label></th>
                        <td>
                            <input type="text" class="regular-text code" id="mm_sp_linkedin_client_id"
                                   name="<?php echo esc_attr(self::OPTION); ?>[linkedin_client_id]"
                                   value="<?php echo esc_attr(self::get('linkedin_client_id')); ?>" autocomplete="off">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mm_sp_linkedin_client_secret">Client Secret</label></th>
                        <td>
                            <input type="password" class="regular-text code" id="mm_sp_linkedin_client_secret"
                                   name="<?php echo esc_attr(self::OPTION); ?>[linkedin_client_secret]"
                                   value="" autocomplete="new-password"
                                   placeholder="<?php echo $has_secret ? '•••••••• (saved — leave blank to keep)' : ''; ?>">
                            <?php if ($has_secret) : ?>
                                <?php
                                $stored_secret = MM_Social_Poster_LinkedIn::client_secret();
                                $fingerprint = $stored_secret === ''
                                    ? 'unreadable (re-enter the secret)'
                                    : sprintf('%d chars, starts with "%s", ends with "%s"', mb_strlen($stored_secret), mb_substr($stored_secret, 0, 4), mb_substr($stored_secret, -3));
                                ?>
                                <p class="description">Stored secret: <?php echo esc_html($fingerprint); ?> — compare with the LinkedIn Developer app.</p>
                                <p><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[linkedin_clear_secret]" value="1"> Clear stored secret</label></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="mm_sp_linkedin_api_version">API Version</label></th>
                        <td>
                            <input type="text" class="regular-text code" id="mm_sp_linkedin_api_version"
                                   name="<?php echo esc_attr(self::OPTION); ?>[linkedin_api_version]"
                                   value="<?php echo esc_attr(self::get('linkedin_api_version', '202406')); ?>"
                                   placeholder="YYYYMM or YYYYMM.RR">
                            <p class="description">Format: <code>YYYYMM</code> or <code>YYYYMM.RR</code> (e.g., <code>202406</code> or <code>202406.01</code>)<br>Check <a href="https://learn.microsoft.com/en-us/linkedin/shared/api-guide/concepts/api-versioning" target="_blank">LinkedIn API Versioning docs</a> for currently active versions.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Authorized redirect URL</th>
                        <td>
                            <input type="text" class="large-text code" readonly onclick="this.select()"
                                   value="<?php echo esc_attr(MM_Social_Poster_LinkedIn::redirect_uri()); ?>">
                            <p class="description">Add this URL to the LinkedIn app's <em>Authorized redirect URLs</em>. Required products: <em>Sign In with LinkedIn using OpenID Connect</em> and <em>Share on LinkedIn</em>.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Status</th>
                        <td>
                            <?php if (MM_Social_Poster_LinkedIn::is_configured()) : ?>
                                <span style="color:#00a32a;">&#9679;</span> Configured — users can connect LinkedIn.
                            <?php else : ?>
                                <span style="color:#d63638;">&#9679;</span> Not configured — enter both Client ID and Client Secret.
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <?php if (MM_Social_Poster_LinkedIn::is_configured()) : ?>
                <?php
                $verify = get_transient('mm_social_verify_result_' . get_current_user_id());
                if ($verify) {
                    delete_transient('mm_social_verify_result_' . get_current_user_id());
                    printf(
                        '<div class="inline notice notice-%s"><p>%s</p></div>',
                        $verify['status'] === 'ok' ? 'success' : 'error',
                        esc_html($verify['message'])
                    );
                }
                $test = get_transient('mm_social_test_version_result_' . get_current_user_id());
                if ($test) {
                    delete_transient('mm_social_test_version_result_' . get_current_user_id());
                    printf(
                        '<div class="inline notice notice-%s"><p><strong>API Version Test:</strong> %s</p></div>',
                        $test['status'] === 'ok' ? 'success' : ($test['status'] === 'unknown' ? 'warning' : 'error'),
                        esc_html($test['message'])
                    );
                }
                ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="mm_social_verify_credentials">
                    <?php wp_nonce_field('mm_social_verify_credentials'); ?>
                    <?php submit_button('Verify credentials with LinkedIn', 'secondary', 'submit', false); ?>
                    <p class="description">Checks the saved Client ID / Secret against LinkedIn without going through a user login.</p>
                </form>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:15px;">
                    <input type="hidden" name="action" value="mm_social_test_api_version">
                    <?php wp_nonce_field('mm_social_test_api_version'); ?>
                    <?php submit_button('Test API Version', 'secondary', 'submit', false); ?>
                    <p class="description">Tests if the configured API version is currently active on LinkedIn.</p>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function render_debug()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        global $wpdb;
        $logs_table = MM_Social_Poster_DB::logs_table();

        // Last 10 share attempts (including failed ones with error details)
        $recent_shares = $wpdb->get_results("SELECT * FROM {$logs_table} WHERE event='share' ORDER BY id DESC LIMIT 20");
        // Last 10 OAuth attempts
        $recent_oauth = $wpdb->get_results("SELECT * FROM {$logs_table} WHERE event LIKE 'oauth%' ORDER BY id DESC LIMIT 10");
        // Config status
        $is_configured = MM_Social_Poster_LinkedIn::is_configured();
        $client_id = MM_Social_Poster_LinkedIn::client_id();
        $api_version = MM_Social_Poster_LinkedIn::api_version();

        $user_label = static function ($user_id) {
            $user = $user_id ? get_user_by('ID', $user_id) : null;
            return $user ? $user->user_email : '(unknown)';
        };
        ?>
        <div class="wrap">
            <h1>Social Poster Debug</h1>

            <h2>Configuration</h2>
            <table class="form-table">
                <tr>
                    <th>LinkedIn configured</th>
                    <td><?php echo $is_configured ? '<span style="color:#00a32a;">✓ Yes</span>' : '<span style="color:#d63638;">✗ No</span>'; ?></td>
                </tr>
                <tr>
                    <th>Client ID</th>
                    <td><code><?php echo esc_html($client_id ?: '(none)'); ?></code></td>
                </tr>
                <tr>
                    <th>LinkedIn API Version</th>
                    <td>
                        <code style="color:#d63638; font-weight:600; font-size:1.1em;"><?php echo esc_html($api_version); ?></code>
                        <p style="margin:5px 0 0 0; color:#666; font-size:0.9em;">
                            Go to <strong>Social Poster → Settings</strong> tab and click <strong>Test API Version</strong> to verify this version is active with LinkedIn.
                        </p>
                    </td>
                </tr>
                    </td>
                </tr>
                <tr>
                    <th>Plugin active</th>
                    <td><?php echo defined('MM_SOCIAL_POSTER_LOADED') ? '<span style="color:#00a32a;">✓ Yes</span>' : '<span style="color:#d63638;">✗ No</span>'; ?></td>
                </tr>
                <tr>
                    <th>Plugin version</th>
                    <td><code><?php echo esc_html(defined('MM_SOCIAL_POSTER_VERSION') ? MM_SOCIAL_POSTER_VERSION : 'N/A'); ?></code></td>
                </tr>
            </table>

            <h2>Recent Share Attempts</h2>
            <p style="color:#666; font-size:0.9em;">Showing last 20 attempts (including failed ones with full error details):</p>
            <?php if ($recent_shares) : ?>
                <table class="widefat" style="margin-top:10px;">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Post</th>
                            <th>Status</th>
                            <th>Error Message / Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_shares as $row) : ?>
                            <tr style="background-color:<?php echo $row->status === 'failed' ? '#fff8f8' : '#f9fafb'; ?>;">
                                <td><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $row->created_at)); ?></td>
                                <td><?php echo esc_html($user_label($row->user_id)); ?></td>
                                <td><?php echo esc_html('#' . $row->post_id); ?></td>
                                <td><span style="color:<?php echo $row->status === 'success' ? '#00a32a' : '#d63638'; ?>;font-weight:600;"><?php echo esc_html(ucfirst($row->status)); ?></span></td>
                                <td><code style="color:<?php echo $row->status === 'failed' ? '#d63638' : '#000'; ?>; font-size:0.85em; word-break:break-word;"><?php echo esc_html($row->error_message ?: '(success - no errors)'); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No share attempts yet.</p>
            <?php endif; ?>

            <h2>Diagnostic: Most Recent Failed Attempt</h2>
            <?php
            $failed_share = $wpdb->get_row("SELECT * FROM {$logs_table} WHERE event='share' AND status='failed' ORDER BY id DESC LIMIT 1");
            if ($failed_share) :
                // Try to parse error message for LinkedIn API response details
                $error_text = $failed_share->error_message;
                $is_version_error = (strpos($error_text, 'version') !== false || strpos($error_text, 'requested') !== false);
            ?>
                <div style="background:#fff8f8; border-left:4px solid #d63638; padding:12px; margin:10px 0;">
                    <p><strong>Post ID:</strong> #<?php echo esc_html($failed_share->post_id); ?> | <strong>User:</strong> <?php echo esc_html($user_label($failed_share->user_id)); ?> | <strong>Time:</strong> <?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $failed_share->created_at)); ?></p>
                    <p><strong>Error:</strong></p>
                    <pre style="background:#f5f5f5; padding:10px; border-radius:3px; overflow-x:auto; font-size:0.85em;"><?php echo esc_html($error_text); ?></pre>
                    <?php if ($is_version_error) : ?>
                        <p style="color:#d63638;"><strong>⚠️ Looks like a LinkedIn API version issue!</strong> The error mentions "version" or "requested".</p>
                        <p>Current API version: <code><?php echo esc_html($api_version); ?></code></p>
                        <p>Check <a href="https://docs.microsoft.com/en-us/linkedin/shared/api-guide/concepts/api-versioning" target="_blank">LinkedIn API Versioning Docs</a> for currently active versions.</p>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <p style="color:#666;">No failed share attempts yet. ✓</p>
            <?php endif; ?>

            <h2>Recent OAuth Events</h2>
            <?php if ($recent_oauth) : ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Event</th>
                            <th>User</th>
                            <th>Status</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_oauth as $row) : ?>
                            <tr>
                                <td><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $row->created_at)); ?></td>
                                <td><?php echo esc_html(str_replace('oauth_', '', $row->event)); ?></td>
                                <td><?php echo esc_html($user_label($row->user_id)); ?></td>
                                <td><span style="color:<?php echo $row->status === 'success' ? '#00a32a' : '#d63638'; ?>;font-weight:600;"><?php echo esc_html(ucfirst($row->status)); ?></span></td>
                                <td><?php echo esc_html($row->error_message ?: '(success)'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No OAuth events yet.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function render_logs()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        require_once MM_SOCIAL_POSTER_DIR . 'includes/admin/class-mm-social-poster-logs-table.php';

        $table = new MM_Social_Poster_Logs_Table();
        $table->prepare_items();
        ?>
        <div class="wrap">
            <h1>Social Poster Logs</h1>
            <form method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr(self::LOGS_PAGE); ?>">
                <?php
                $table->search_box('Search user', 'mm-social-user');
                $table->display();
                ?>
            </form>
        </div>
        <?php
    }
}
