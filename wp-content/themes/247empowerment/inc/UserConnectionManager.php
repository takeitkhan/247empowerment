<?php
class UserConnectionManager
{
    public static function follow($follower_id, $following_id)
    {
        if ($follower_id == $following_id) return false;

        $following = get_user_meta($follower_id, 'following_users', true) ?: [];
        $followers = get_user_meta($following_id, 'follower_users', true) ?: [];

        if (!in_array($following_id, $following)) {
            $following[] = $following_id;
            update_user_meta($follower_id, 'following_users', $following);
        }

        if (!in_array($follower_id, $followers)) {
            $followers[] = $follower_id;
            update_user_meta($following_id, 'follower_users', $followers);
        }

        return true;
    }

    public static function unfollow($follower_id, $following_id)
    {
        $following = get_user_meta($follower_id, 'following_users', true) ?: [];
        $followers = get_user_meta($following_id, 'follower_users', true) ?: [];

        $following = array_diff($following, [$following_id]);
        $followers = array_diff($followers, [$follower_id]);

        update_user_meta($follower_id, 'following_users', $following);
        update_user_meta($following_id, 'follower_users', $followers);

        return true;
    }

    public static function isFollowing($follower_id, $following_id)
    {
        $following = get_user_meta($follower_id, 'following_users', true) ?: [];
        return in_array($following_id, $following);
    }

    public static function getFollowers($user_id)
    {
        return get_user_meta($user_id, 'follower_users', true) ?: [];
    }

    public static function getFollowing($user_id)
    {
        return get_user_meta($user_id, 'following_users', true) ?: [];
    }

    public static function getMutualConnections($user_id)
    {
        $followers = self::getFollowers($user_id);
        $following = self::getFollowing($user_id);
        return array_intersect($followers, $following);
    }
}
