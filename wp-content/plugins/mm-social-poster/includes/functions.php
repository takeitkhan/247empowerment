<?php
/**
 * Public helper functions for theme integration.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 'none' | 'active' | 'expired'
 */
function mm_social_poster_connection_status($user_id, $platform = 'linkedin')
{
    $connection = MM_Social_Poster_DB::get_connection($user_id, $platform);
    if (!$connection) {
        return 'none';
    }
    if ($connection->status !== 'active' || ($connection->token_expires && strtotime($connection->token_expires) <= time())) {
        return 'expired';
    }
    return 'active';
}

function mm_social_poster_is_connected($user_id, $platform = 'linkedin')
{
    return mm_social_poster_connection_status($user_id, $platform) === 'active';
}

function mm_social_poster_is_configured($platform = 'linkedin')
{
    return $platform === 'linkedin' && MM_Social_Poster_LinkedIn::is_configured();
}

function mm_social_poster_connect_url($platform = 'linkedin')
{
    return MM_Social_Poster_Frontend::connect_url($platform);
}

function mm_social_poster_disconnect_url($platform = 'linkedin')
{
    return MM_Social_Poster_Frontend::disconnect_url($platform);
}

/**
 * Share a post to the given platforms as $user_id.
 *
 * @return array platform => ['status' => 'success', 'id', 'url'] | ['status' => 'failed', 'code', 'error']
 */
function mm_social_poster_publish($post_id, $user_id = null, array $platforms = array('linkedin'))
{
    return MM_Social_Poster_Publisher::publish($post_id, $user_id, $platforms);
}

/**
 * Latest successful share log row for a post, or null.
 */
function mm_social_poster_get_share($post_id, $platform = 'linkedin')
{
    return MM_Social_Poster_DB::get_latest_success($post_id, $platform);
}

/**
 * Small "Shared on LinkedIn" icon linking to the LinkedIn post (empty string if not shared).
 */
function mm_social_poster_badge($post_id, $echo = true)
{
    $html = MM_Social_Poster_Frontend::badge($post_id);
    if ($echo) {
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- built with escaped parts
    }
    return $html;
}
