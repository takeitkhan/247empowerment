<?php
/**
 * Publishes a portal post to the author's connected social accounts and logs the result.
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Social_Poster_Publisher
{
    /**
     * @param int      $post_id
     * @param int      $user_id   Owner of the social connection (defaults to post author).
     * @param string[] $platforms
     * @return array  platform => ['status' => 'success', 'id', 'url'] | ['status' => 'failed', 'code', 'error']
     */
    public static function publish($post_id, $user_id = null, array $platforms = array('linkedin'))
    {
        $post = get_post($post_id);
        if (!$post) {
            return array();
        }
        $user_id = $user_id ? (int) $user_id : (int) $post->post_author;

        $results = array();
        foreach (array_unique($platforms) as $platform) {
            if ($platform === MM_Social_Poster_LinkedIn::PLATFORM) {
                $results[$platform] = self::publish_linkedin($post, $user_id);
            } else {
                $results[$platform] = self::failed('unsupported', 'Unsupported platform: ' . $platform);
            }
        }

        return $results;
    }

    private static function publish_linkedin(WP_Post $post, $user_id)
    {
        $platform = MM_Social_Poster_LinkedIn::PLATFORM;

        if (!MM_Social_Poster_LinkedIn::is_configured()) {
            return self::failed('not_configured', 'LinkedIn posting is not configured on this portal.');
        }

        $connection = MM_Social_Poster_DB::get_connection($user_id, $platform);
        if (!$connection) {
            return self::failed('not_connected', 'Your LinkedIn account is not connected.');
        }
        if ($connection->status !== 'active') {
            return self::failed('expired', 'Your LinkedIn connection has expired. Please reconnect.');
        }
        if ($connection->token_expires && strtotime($connection->token_expires) <= time()) {
            self::mark_expired($user_id, $platform);
            return self::failed('expired', 'Your LinkedIn connection has expired. Please reconnect.');
        }

        $token = MM_Social_Poster_Crypto::decrypt($connection->access_token);
        if ($token === '') {
            self::mark_expired($user_id, $platform);
            return self::failed('expired', 'Stored LinkedIn token could not be read. Please reconnect.');
        }

        $person_urn = MM_Social_Poster_LinkedIn::person_urn($connection->platform_user_id);
        $share_url = self::share_url($post, $user_id);
        $title = self::post_title($post);
        $image_urn = null;

        // Image post when a featured image exists (URL appended to text); otherwise an article/link share.
        $thumb_id = get_post_thumbnail_id($post);
        if ($thumb_id) {
            $file = get_attached_file($thumb_id);
            if ($file) {
                $uploaded = MM_Social_Poster_LinkedIn::upload_image($token, $person_urn, $file);
                if (is_wp_error($uploaded)) {
                    return self::handle_error($post, $user_id, $platform, $uploaded);
                }
                $image_urn = $uploaded;
            }
        }

        $commentary = self::prepare_text($post->post_content, $image_urn ? $share_url : '');

        $args = array(
            'commentary'  => $commentary,
            'visibility'  => 'PUBLIC',
            'media_title' => $title,
        );
        if ($image_urn) {
            $args['image_urn'] = $image_urn;
        } else {
            $args['article'] = array(
                'source'      => $share_url,
                'title'       => $title,
                'description' => wp_trim_words(wp_strip_all_tags($post->post_content), 40, '…'),
            );
        }

        $result = MM_Social_Poster_LinkedIn::create_post($token, $person_urn, $args);
        if (is_wp_error($result)) {
            return self::handle_error($post, $user_id, $platform, $result);
        }

        MM_Social_Poster_DB::add_log(array(
            'post_id'        => $post->ID,
            'user_id'        => $user_id,
            'platform'       => $platform,
            'status'         => 'success',
            'remote_post_id' => $result['id'],
            'remote_url'     => $result['url'],
        ));

        do_action('mm_social_poster_shared', $post->ID, $user_id, $platform, $result);

        return array('status' => 'success', 'id' => $result['id'], 'url' => $result['url']);
    }

    private static function handle_error(WP_Post $post, $user_id, $platform, WP_Error $error)
    {
        $code = 'api_error';
        $message = $error->get_error_message();
        $error_data = $error->get_error_data();

        if (MM_Social_Poster_LinkedIn::is_auth_error($error)) {
            $code = 'expired';
            $message = 'Your LinkedIn connection has expired or was revoked. Please reconnect.';
            self::mark_expired($user_id, $platform);
        }

        // Build detailed error message for logging
        $detailed_message = $message;
        if (is_array($error_data)) {
            if (!empty($error_data['body'])) {
                // Append the full API response body for debugging
                $body_details = wp_json_encode($error_data['body']);
                $detailed_message .= ' | API Response: ' . $body_details;
            }
            if (!empty($error_data['http_code'])) {
                $detailed_message .= ' | HTTP ' . $error_data['http_code'];
            }
        }

        MM_Social_Poster_DB::add_log(array(
            'post_id'       => $post->ID,
            'user_id'       => $user_id,
            'platform'      => $platform,
            'status'        => 'failed',
            'error_message' => $detailed_message,
            'response'      => $error_data,
        ));

        return self::failed($code, $message);
    }

    private static function mark_expired($user_id, $platform)
    {
        MM_Social_Poster_DB::set_connection_status($user_id, $platform, 'expired');

        if (class_exists('NotificationManager')) {
            NotificationManager::getInstance()->add(
                $user_id,
                'linkedin_reconnect_required',
                'Your LinkedIn connection has expired. Reconnect it to keep sharing posts to LinkedIn.',
                array(
                    'action_url'   => home_url('/connect-social-media-accounts/'),
                    'action_label' => 'Reconnect LinkedIn',
                    'metadata'     => array('platform' => $platform),
                )
            );
        }

        do_action('mm_social_poster_token_expired', $user_id, $platform);
    }

    private static function failed($code, $message)
    {
        return array('status' => 'failed', 'code' => $code, 'error' => $message);
    }

    /**
     * URL shared on social: the author's portal profile/feed page.
     */
    public static function share_url(WP_Post $post, $user_id)
    {
        $user = get_userdata($user_id);
        $url = '';

        if ($user && class_exists('UserProfileData')) {
            $url = (new UserProfileData($user))->getProfileUrl();
        }
        if (!$url && $user) {
            $url = home_url('/u/' . ($user->user_nicename ?: $user->user_login));
        }
        if (!$url) {
            $url = home_url('/');
        }

        return apply_filters('mm_social_poster_share_url', $url, $post, $user_id);
    }

    /**
     * Plain text, entity-decoded, optional URL appended, truncated to the LinkedIn limit, then escaped.
     */
    public static function prepare_text($content, $append_url = '')
    {
        $text = wp_strip_all_tags((string) $content);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/\r\n|\r/", "\n", $text);
        $text = preg_replace("/\n{3,}/", "\n\n", $text);
        $text = trim($text);

        $suffix = $append_url ? "\n\n" . $append_url : '';
        $limit = MM_Social_Poster_LinkedIn::MAX_TEXT_LENGTH;

        $build = function ($body) use ($suffix) {
            return MM_Social_Poster_LinkedIn::escape_commentary($body) . $suffix;
        };

        $base = $text;
        $result = $build($base);
        while (mb_strlen($result) > $limit && mb_strlen($base) > 0) {
            $over = mb_strlen($result) - $limit + 1;
            $base = rtrim(mb_substr($base, 0, max(0, mb_strlen($base) - $over)));
            $result = $build($base . '…');
        }

        return $result;
    }

    private static function post_title(WP_Post $post)
    {
        $title = trim(wp_strip_all_tags(get_the_title($post)));
        return $title !== '' ? $title : get_bloginfo('name');
    }
}
