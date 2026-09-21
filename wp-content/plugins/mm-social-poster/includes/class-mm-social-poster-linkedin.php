<?php
/**
 * LinkedIn OAuth 2.0 + Posts API client (personal profile, w_member_social).
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Social_Poster_LinkedIn
{
    const PLATFORM = 'linkedin';
    const AUTH_URL = 'https://www.linkedin.com/oauth/v2/authorization';
    const TOKEN_URL = 'https://www.linkedin.com/oauth/v2/accessToken';
    const REVOKE_URL = 'https://www.linkedin.com/oauth/v2/revoke';
    const API_BASE = 'https://api.linkedin.com';
    const API_VERSION_DEFAULT = '202406';
    const SCOPES = 'openid profile w_member_social';
    const MAX_TEXT_LENGTH = 3000;

    // LinkedIn serviceErrorCodes that mean the token is dead.
    const AUTH_ERROR_CODES = array(65600, 65601, 65604);

    /**
     * Gets the current API version from settings, with fallback to default.
     */
    public static function api_version()
    {
        if (class_exists('MM_Social_Poster_Settings')) {
            $version = MM_Social_Poster_Settings::get('linkedin_api_version', '');
            if ($version !== '') {
                return $version;
            }
        }
        return self::API_VERSION_DEFAULT;
    }

    public static function client_id()
    {
        return (string) MM_Social_Poster_Settings::get('linkedin_client_id');
    }

    public static function client_secret()
    {
        return MM_Social_Poster_Crypto::decrypt(MM_Social_Poster_Settings::get('linkedin_client_secret'));
    }

    public static function is_configured()
    {
        return self::client_id() !== '' && self::client_secret() !== '';
    }

    public static function redirect_uri()
    {
        return add_query_arg('mm_social_oauth', self::PLATFORM, home_url('/'));
    }

    public static function authorization_url($state)
    {
        return self::AUTH_URL . '?' . http_build_query(array(
            'response_type' => 'code',
            'client_id'     => self::client_id(),
            'redirect_uri'  => self::redirect_uri(),
            'state'         => $state,
            'scope'         => self::SCOPES,
        ), '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * @return array|WP_Error  ['access_token','expires_in','scope']
     */
    public static function exchange_code($code)
    {
        $response = wp_remote_post(self::TOKEN_URL, array(
            'timeout' => 30,
            'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
            'body'    => array(
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => self::redirect_uri(),
                'client_id'     => self::client_id(),
                'client_secret' => self::client_secret(),
            ),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['access_token'])) {
            $msg = $body['error_description'] ?? ($body['error'] ?? 'No access token returned by LinkedIn.');
            return new WP_Error('linkedin_token', $msg, $body);
        }

        return $body;
    }

    /**
     * @return array|WP_Error  OpenID userinfo: sub, name, picture, ...
     */
    public static function userinfo($token)
    {
        return self::request('GET', self::API_BASE . '/v2/userinfo', $token);
    }

    public static function revoke($token)
    {
        $response = wp_remote_post(self::REVOKE_URL, array(
            'timeout' => 15,
            'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
            'body'    => array(
                'client_id'     => self::client_id(),
                'client_secret' => self::client_secret(),
                'token'         => $token,
            ),
        ));
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) < 300;
    }

    public static function person_urn($platform_user_id)
    {
        return 'urn:li:person:' . $platform_user_id;
    }

    /**
     * Upload a local image file and return its urn:li:image:* id.
     *
     * @return string|WP_Error
     */
    public static function upload_image($token, $person_urn, $file_path)
    {
        if (!is_readable($file_path)) {
            return new WP_Error('linkedin_image', 'Image file is not readable.');
        }

        $init = self::request(
            'POST',
            self::API_BASE . '/rest/images?action=initializeUpload',
            $token,
            array('initializeUploadRequest' => array('owner' => $person_urn))
        );
        if (is_wp_error($init)) {
            return $init;
        }

        $upload_url = $init['value']['uploadUrl'] ?? '';
        $image_urn = $init['value']['image'] ?? '';
        if (!$upload_url || !$image_urn) {
            return new WP_Error('linkedin_image', 'LinkedIn did not return an upload URL.', $init);
        }

        $put = wp_remote_request($upload_url, array(
            'method'  => 'PUT',
            'timeout' => 60,
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/octet-stream',
            ),
            'body'    => file_get_contents($file_path),
        ));
        if (is_wp_error($put)) {
            return $put;
        }
        $code = (int) wp_remote_retrieve_response_code($put);
        if ($code < 200 || $code >= 300) {
            return new WP_Error('linkedin_image', 'Image upload failed (HTTP ' . $code . ').', wp_remote_retrieve_body($put));
        }

        // Image processing is async; give it a moment to become AVAILABLE.
        for ($i = 0; $i < 3; $i++) {
            $status = self::request('GET', self::API_BASE . '/rest/images/' . rawurlencode($image_urn), $token);
            if (!is_wp_error($status) && ($status['status'] ?? '') === 'AVAILABLE') {
                break;
            }
            sleep(1);
        }

        return $image_urn;
    }

    /**
     * Create a post on the member's profile.
     *
     * @param array $args  commentary (escaped), visibility, image_urn|null, article (source,title,description)|null, media_title
     * @return array|WP_Error  ['id' => urn, 'url' => permalink]
     */
    public static function create_post($token, $person_urn, array $args)
    {
        $body = array(
            'author'                    => $person_urn,
            'commentary'                => $args['commentary'],
            'visibility'                => $args['visibility'] ?? 'PUBLIC',
            'distribution'              => array(
                'feedDistribution'               => 'MAIN_FEED',
                'targetEntities'                 => array(),
                'thirdPartyDistributionChannels' => array(),
            ),
            'lifecycleState'            => 'PUBLISHED',
            'isReshareDisabledByAuthor' => false,
        );

        if (!empty($args['image_urn'])) {
            $body['content'] = array('media' => array(
                'id'    => $args['image_urn'],
                'title' => mb_substr((string) ($args['media_title'] ?? ''), 0, 400),
            ));
        } elseif (!empty($args['article']['source'])) {
            $article = array(
                'source' => $args['article']['source'],
                'title'  => mb_substr((string) ($args['article']['title'] ?? ''), 0, 400),
            );
            if (!empty($args['article']['description'])) {
                $article['description'] = mb_substr((string) $args['article']['description'], 0, 4086);
            }
            $body['content'] = array('article' => $article);
        }

        $result = self::request('POST', self::API_BASE . '/rest/posts', $token, $body, true);
        if (is_wp_error($result)) {
            return $result;
        }

        $urn = $result['headers']['x-restli-id'] ?? '';
        if (!$urn) {
            return new WP_Error('linkedin_post', 'LinkedIn accepted the post but returned no ID.', $result);
        }

        return array(
            'id'  => $urn,
            'url' => 'https://www.linkedin.com/feed/update/' . $urn . '/',
        );
    }

    /**
     * Escape LinkedIn "little text" reserved characters in commentary.
     */
    public static function escape_commentary($text)
    {
        return preg_replace('/([\\\\|{}@\[\]()<>#*_~])/u', '\\\\$1', $text);
    }

    public static function is_auth_error($error)
    {
        if (!is_wp_error($error)) {
            return false;
        }
        $data = $error->get_error_data();
        $http = is_array($data) ? (int) ($data['http_code'] ?? 0) : 0;
        $service = is_array($data) ? (int) ($data['body']['serviceErrorCode'] ?? 0) : 0;
        return $http === 401 || in_array($service, self::AUTH_ERROR_CODES, true);
    }

    /**
     * Versioned REST request. Returns decoded body, or when $with_headers is true
     * ['body' => ..., 'headers' => [...]].
     *
     * @return array|WP_Error
     */
    private static function request($method, $url, $token, $body = null, $with_headers = false)
    {
        $args = array(
            'method'  => $method,
            'timeout' => 30,
            'headers' => array(
                'Authorization'             => 'Bearer ' . $token,
                'Content-Type'              => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0',
                'LinkedIn-Version'          => self::api_version(),
            ),
        );
        if ($body !== null) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($url, $args);
        if (is_wp_error($response)) {
            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $raw = wp_remote_retrieve_body($response);
        $decoded = $raw !== '' ? json_decode($raw, true) : array();
        if (!is_array($decoded)) {
            $decoded = array('raw' => $raw);
        }

        if ($code < 200 || $code >= 300) {
            $message = $decoded['message'] ?? ($decoded['error_description'] ?? ('LinkedIn API error (HTTP ' . $code . ')'));
            return new WP_Error('linkedin_api', $message, array(
                'http_code' => $code,
                'body'      => $decoded,
                'url'       => $url,
            ));
        }

        if ($with_headers) {
            $headers = wp_remote_retrieve_headers($response);
            $headers = is_object($headers) && method_exists($headers, 'getAll') ? $headers->getAll() : (array) $headers;
            return array('body' => $decoded, 'headers' => array_change_key_case($headers, CASE_LOWER));
        }

        return $decoded;
    }
}
