<?php
/**
 * Frontend: connect/disconnect flow, OAuth callback, [mm_social_connect] shortcode, share badge.
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Social_Poster_Frontend
{
    const FALLBACK_RETURN = '/connect-social-media-accounts/';

    public static function init()
    {
        add_action('init', array(__CLASS__, 'maybe_handle_oauth_callback'), 20);
        add_action('admin_post_mm_social_connect', array(__CLASS__, 'handle_connect'));
        add_action('admin_post_mm_social_disconnect', array(__CLASS__, 'handle_disconnect'));
        add_shortcode('mm_social_connect', array(__CLASS__, 'shortcode'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'));
    }

    public static function enqueue()
    {
        if (!is_user_logged_in()) {
            return;
        }
        $file = MM_SOCIAL_POSTER_DIR . 'assets/css/mm-social-poster.css';
        wp_enqueue_style('mm-social-poster', MM_SOCIAL_POSTER_URL . 'assets/css/mm-social-poster.css', array(), file_exists($file) ? filemtime($file) : MM_SOCIAL_POSTER_VERSION);
    }

    // ------------------------------------------------------------------
    // URLs
    // ------------------------------------------------------------------

    public static function connect_url($platform = 'linkedin')
    {
        return wp_nonce_url(
            add_query_arg(array('action' => 'mm_social_connect', 'platform' => $platform), admin_url('admin-post.php')),
            'mm_social_connect_' . $platform
        );
    }

    public static function disconnect_url($platform = 'linkedin')
    {
        return wp_nonce_url(
            add_query_arg(array('action' => 'mm_social_disconnect', 'platform' => $platform), admin_url('admin-post.php')),
            'mm_social_disconnect_' . $platform
        );
    }

    const STATE_TTL = 15 * MINUTE_IN_SECONDS;

    // User meta instead of transients: object caches on some hosts drop transients between requests.
    private static function state_meta_key($platform)
    {
        return '_mm_social_oauth_' . $platform;
    }

    private static function save_pending($user_id, $platform, array $data)
    {
        $data['created'] = time();
        update_user_meta($user_id, self::state_meta_key($platform), $data);
    }

    private static function get_pending($user_id, $platform)
    {
        wp_cache_delete($user_id, 'user_meta');
        $pending = get_user_meta($user_id, self::state_meta_key($platform), true);
        if (!is_array($pending) || empty($pending['state'])) {
            return null;
        }
        if ((time() - (int) ($pending['created'] ?? 0)) > self::STATE_TTL) {
            return null;
        }
        return $pending;
    }

    private static function clear_pending($user_id, $platform)
    {
        delete_user_meta($user_id, self::state_meta_key($platform));
    }

    private static function clean_return_url($url)
    {
        $url = $url ? remove_query_arg(array('mm_social', 'mm_social_error'), $url) : '';
        return $url ? $url : home_url(self::FALLBACK_RETURN);
    }

    private static function redirect_with($url, $args)
    {
        wp_safe_redirect(add_query_arg($args, $url));
        exit;
    }

    // ------------------------------------------------------------------
    // Connect
    // ------------------------------------------------------------------

    public static function handle_connect()
    {
        if (!is_user_logged_in()) {
            auth_redirect();
        }

        $platform = sanitize_key($_GET['platform'] ?? '');
        if ($platform !== MM_Social_Poster_LinkedIn::PLATFORM) {
            wp_die('Unsupported platform.');
        }
        check_admin_referer('mm_social_connect_' . $platform);

        $return_to = self::clean_return_url(wp_get_referer());

        if (!MM_Social_Poster_LinkedIn::is_configured()) {
            self::redirect_with($return_to, array('mm_social_error' => 'not_configured'));
        }

        $user_id = get_current_user_id();
        $state = wp_generate_password(40, false);
        self::save_pending($user_id, $platform, array(
            'state'     => $state,
            'return_to' => $return_to,
        ));

        wp_redirect(MM_Social_Poster_LinkedIn::authorization_url($state));
        exit;
    }

    // ------------------------------------------------------------------
    // OAuth callback: home_url('/?mm_social_oauth=linkedin')
    // ------------------------------------------------------------------

    public static function maybe_handle_oauth_callback()
    {
        if (empty($_GET['mm_social_oauth'])) {
            return;
        }
        $platform = sanitize_key(wp_unslash($_GET['mm_social_oauth']));
        if ($platform !== MM_Social_Poster_LinkedIn::PLATFORM) {
            return;
        }

        if (!is_user_logged_in()) {
            auth_redirect();
        }

        $user_id = get_current_user_id();
        $pending = self::get_pending($user_id, $platform);
        $return_to = self::clean_return_url($pending ? ($pending['return_to'] ?? '') : '');

        $state = sanitize_text_field(wp_unslash($_GET['state'] ?? ''));
        if (!$pending || !hash_equals($pending['state'], $state)) {
            self::clear_pending($user_id, $platform);

            // Re-opened / reloaded callback URL after a successful connect: nothing to do.
            $existing = MM_Social_Poster_DB::get_connection($user_id, $platform);
            if (!$pending && $existing && $existing->status === 'active') {
                self::redirect_with($return_to, array('mm_social' => 'already_connected'));
            }

            self::log_oauth($user_id, $platform, 'oauth_state', 'failed', 'State mismatch or expired connect attempt.', array(
                'received_state' => $state,
                'has_pending'    => (bool) $pending,
            ));
            self::redirect_with($return_to, array('mm_social_error' => 'state'));
        }
        self::clear_pending($user_id, $platform);

        if (!empty($_GET['error'])) {
            $err = sanitize_key(wp_unslash($_GET['error']));
            $desc = sanitize_text_field(wp_unslash($_GET['error_description'] ?? ''));
            self::log_oauth($user_id, $platform, 'oauth_authorize', 'failed', $err . ($desc ? ': ' . $desc : ''), array(
                'error'             => $err,
                'error_description' => $desc,
            ));
            self::redirect_with($return_to, array('mm_social_error' => $err === 'user_cancelled_authorize' || $err === 'user_cancelled_login' ? 'cancelled' : 'denied'));
        }

        $code = sanitize_text_field(wp_unslash($_GET['code'] ?? ''));
        if ($code === '') {
            self::log_oauth($user_id, $platform, 'oauth_authorize', 'failed', 'LinkedIn returned no authorization code.', $_GET);
            self::redirect_with($return_to, array('mm_social_error' => 'no_code'));
        }

        $token = MM_Social_Poster_LinkedIn::exchange_code($code);
        if (is_wp_error($token)) {
            self::log_oauth($user_id, $platform, 'oauth_token', 'failed', $token->get_error_message(), array(
                'response'     => $token->get_error_data(),
                'redirect_uri' => MM_Social_Poster_LinkedIn::redirect_uri(),
                'client_id'    => MM_Social_Poster_LinkedIn::client_id(),
            ));
            self::redirect_with($return_to, array('mm_social_error' => 'token'));
        }

        $me = MM_Social_Poster_LinkedIn::userinfo($token['access_token']);
        if (is_wp_error($me) || empty($me['sub'])) {
            self::log_oauth($user_id, $platform, 'oauth_userinfo', 'failed', is_wp_error($me) ? $me->get_error_message() : 'Userinfo response has no "sub".', is_wp_error($me) ? $me->get_error_data() : $me);
            self::redirect_with($return_to, array('mm_social_error' => 'profile'));
        }

        $expires_in = (int) ($token['expires_in'] ?? 60 * DAY_IN_SECONDS);
        MM_Social_Poster_DB::save_connection($user_id, $platform, array(
            'platform_user_id'   => sanitize_text_field($me['sub']),
            'platform_user_name' => sanitize_text_field($me['name'] ?? ''),
            'platform_avatar'    => esc_url_raw($me['picture'] ?? ''),
            'access_token'       => MM_Social_Poster_Crypto::encrypt($token['access_token']),
            'token_expires'      => gmdate('Y-m-d H:i:s', time() + $expires_in),
            'scopes'             => sanitize_text_field($token['scope'] ?? MM_Social_Poster_LinkedIn::SCOPES),
            'status'             => 'active',
        ));

        // Existing theme hook (fires the "LinkedIn Connected" notification).
        do_action('mm_linkedin_connected', $user_id, sanitize_text_field($me['sub']));
        do_action('mm_social_poster_connected', $user_id, $platform);

        self::log_oauth($user_id, $platform, 'oauth_connect', 'success', 'Connected as ' . sanitize_text_field($me['name'] ?? $me['sub']), array(
            'sub'        => $me['sub'],
            'scope'      => $token['scope'] ?? null,
            'expires_in' => $expires_in,
        ));

        self::redirect_with($return_to, array('mm_social' => 'connected'));
    }

    private static function log_oauth($user_id, $platform, $event, $status, $message, $response = null)
    {
        MM_Social_Poster_DB::add_log(array(
            'event'         => $event,
            'user_id'       => (int) $user_id,
            'platform'      => $platform,
            'status'        => $status,
            'error_message' => $message,
            'response'      => $response,
        ));
    }

    // ------------------------------------------------------------------
    // Disconnect
    // ------------------------------------------------------------------

    public static function handle_disconnect()
    {
        if (!is_user_logged_in()) {
            auth_redirect();
        }

        $platform = sanitize_key($_GET['platform'] ?? '');
        if ($platform !== MM_Social_Poster_LinkedIn::PLATFORM) {
            wp_die('Unsupported platform.');
        }
        check_admin_referer('mm_social_disconnect_' . $platform);

        $user_id = get_current_user_id();
        $return_to = self::clean_return_url(wp_get_referer());

        $connection = MM_Social_Poster_DB::get_connection($user_id, $platform);
        if ($connection) {
            $token = MM_Social_Poster_Crypto::decrypt($connection->access_token);
            if ($token !== '' && MM_Social_Poster_LinkedIn::is_configured()) {
                MM_Social_Poster_LinkedIn::revoke($token);
            }
            MM_Social_Poster_DB::delete_connection($user_id, $platform);
            do_action('mm_social_poster_disconnected', $user_id, $platform);
            self::log_oauth($user_id, $platform, 'oauth_disconnect', 'success', 'Disconnected LinkedIn.');
        }

        self::redirect_with($return_to, array('mm_social' => 'disconnected'));
    }

    // ------------------------------------------------------------------
    // Shortcode [mm_social_connect]
    // ------------------------------------------------------------------

    public static function shortcode()
    {
        if (!is_user_logged_in()) {
            return '<p class="text-muted">Please sign in to manage your social connections.</p>';
        }

        $platform = MM_Social_Poster_LinkedIn::PLATFORM;
        $user_id = get_current_user_id();
        $connection = MM_Social_Poster_DB::get_connection($user_id, $platform);
        $configured = MM_Social_Poster_LinkedIn::is_configured();

        ob_start();
        echo self::notice_html();
        ?>
        <div class="d-flex align-items-center justify-content-between gap-3 p-3 border rounded mm-social-card">
            <div class="d-flex align-items-center gap-3">
                <span class="mm-social-card__icon mm-social-card__icon--linkedin"><i class="bi bi-linkedin"></i></span>
                <div>
                    <div class="fw-bold">LinkedIn</div>
                    <?php if ($connection && $connection->status === 'active') : ?>
                        <small class="text-muted">
                            Connected as <?php echo esc_html($connection->platform_user_name ?: 'LinkedIn member'); ?>
                            <?php if ($connection->token_expires) : ?>
                                · expires <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($connection->token_expires))); ?>
                            <?php endif; ?>
                        </small>
                    <?php elseif ($connection) : ?>
                        <small class="text-danger">Connection expired — please reconnect to keep sharing.</small>
                    <?php elseif (!$configured) : ?>
                        <small class="text-muted">LinkedIn sharing is not available yet.</small>
                    <?php else : ?>
                        <small class="text-muted">Share your public posts to your LinkedIn profile.</small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <?php if ($connection && $connection->status === 'active') : ?>
                    <a class="btn-outline-danger btn btn-sm" href="<?php echo esc_url(self::disconnect_url($platform)); ?>">Disconnect</a>
                <?php elseif ($connection) : ?>
                    <a class="btn btn-sm btn-primary" href="<?php echo esc_url(self::connect_url($platform)); ?>">Reconnect</a>
                    <a class="btn-outline-secondary btn btn-sm" href="<?php echo esc_url(self::disconnect_url($platform)); ?>">Remove</a>
                <?php elseif ($configured) : ?>
                    <a class="btn btn-sm btn-primary" href="<?php echo esc_url(self::connect_url($platform)); ?>"><i class="me-1 bi bi-linkedin"></i>Connect</a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function notice_html()
    {
        $ok = sanitize_key($_GET['mm_social'] ?? '');
        $err = sanitize_key($_GET['mm_social_error'] ?? '');

        if ($ok === 'connected') {
            return '<div class="alert alert-success">LinkedIn connected successfully.</div>';
        }
        if ($ok === 'already_connected') {
            return '<div class="alert alert-info">That LinkedIn link was already used — your account is connected.</div>';
        }
        if ($ok === 'disconnected') {
            return '<div class="alert alert-info">LinkedIn disconnected.</div>';
        }
        if ($err === '') {
            return '';
        }

        $messages = array(
            'not_configured' => 'LinkedIn sharing has not been configured by the portal owner yet.',
            'state'          => 'Security check failed. Please try connecting again.',
            'cancelled'      => 'LinkedIn connection was cancelled.',
            'denied'         => 'LinkedIn did not authorize the connection.',
            'no_code'        => 'LinkedIn did not return an authorization code.',
            'token'          => 'Could not obtain an access token from LinkedIn.',
            'profile'        => 'Could not read your LinkedIn profile.',
        );
        return '<div class="alert alert-danger">' . esc_html($messages[$err] ?? 'LinkedIn connection failed.') . '</div>';
    }

    // ------------------------------------------------------------------
    // Badge
    // ------------------------------------------------------------------

    public static function badge($post_id)
    {
        $share = MM_Social_Poster_DB::get_latest_success($post_id, MM_Social_Poster_LinkedIn::PLATFORM);
        if (!$share || empty($share->remote_url)) {
            return '';
        }

        $when = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($share->created_at));
        return sprintf(
            '<a class="btn btn-sm btn-light mm-social-badge mm-social-badge--linkedin" href="%s" target="_blank" rel="noopener" title="%s"><i class="bi bi-linkedin"></i><span class="visually-hidden">Shared on LinkedIn</span></a>',
            esc_url($share->remote_url),
            esc_attr('Shared on LinkedIn · ' . $when)
        );
    }
}
