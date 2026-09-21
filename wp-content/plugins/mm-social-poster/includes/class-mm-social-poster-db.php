<?php
/**
 * Database layer: user connections + share logs.
 */

if (!defined('ABSPATH')) {
    exit;
}

class MM_Social_Poster_DB
{
    const DB_VERSION = '1.1.0';
    const OPTION_DB_VERSION = 'mm_social_poster_db_version';

    public static function connections_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'mm_social_connections';
    }

    public static function logs_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'mm_social_logs';
    }

    public static function install()
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $connections = self::connections_table();
        $logs = self::logs_table();

        dbDelta("CREATE TABLE {$connections} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            platform VARCHAR(32) NOT NULL,
            platform_user_id VARCHAR(191) NOT NULL,
            platform_user_name VARCHAR(255) NULL,
            platform_avatar TEXT NULL,
            access_token TEXT NOT NULL,
            token_expires DATETIME NULL,
            scopes VARCHAR(255) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_platform (user_id, platform),
            KEY platform (platform),
            KEY status (status)
        ) {$charset};");

        dbDelta("CREATE TABLE {$logs} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            event VARCHAR(40) NOT NULL DEFAULT 'share',
            post_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            platform VARCHAR(32) NOT NULL,
            status VARCHAR(20) NOT NULL,
            remote_post_id VARCHAR(191) NULL,
            remote_url TEXT NULL,
            error_message TEXT NULL,
            response LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY event (event),
            KEY post_id (post_id),
            KEY user_id (user_id),
            KEY platform (platform),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset};");

        update_option(self::OPTION_DB_VERSION, self::DB_VERSION);
    }

    public static function maybe_upgrade()
    {
        if (get_option(self::OPTION_DB_VERSION) !== self::DB_VERSION) {
            self::install();
        }
    }

    // ------------------------------------------------------------------
    // Connections
    // ------------------------------------------------------------------

    public static function get_connection($user_id, $platform)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . self::connections_table() . ' WHERE user_id = %d AND platform = %s',
            (int) $user_id,
            $platform
        ));
    }

    /**
     * Insert or update a connection. $data['access_token'] must already be encrypted.
     */
    public static function save_connection($user_id, $platform, array $data)
    {
        global $wpdb;
        $now = current_time('mysql');
        $row = array_merge(array(
            'platform_user_id'   => '',
            'platform_user_name' => null,
            'platform_avatar'    => null,
            'access_token'       => '',
            'token_expires'      => null,
            'scopes'             => null,
            'status'             => 'active',
        ), $data);

        $existing = self::get_connection($user_id, $platform);
        if ($existing) {
            $row['updated_at'] = $now;
            return false !== $wpdb->update(self::connections_table(), $row, array('id' => $existing->id));
        }

        $row['user_id'] = (int) $user_id;
        $row['platform'] = $platform;
        $row['created_at'] = $now;
        $row['updated_at'] = $now;
        return false !== $wpdb->insert(self::connections_table(), $row);
    }

    public static function set_connection_status($user_id, $platform, $status)
    {
        global $wpdb;
        return $wpdb->update(
            self::connections_table(),
            array('status' => $status, 'updated_at' => current_time('mysql')),
            array('user_id' => (int) $user_id, 'platform' => $platform)
        );
    }

    public static function delete_connection($user_id, $platform)
    {
        global $wpdb;
        return $wpdb->delete(self::connections_table(), array('user_id' => (int) $user_id, 'platform' => $platform));
    }

    // ------------------------------------------------------------------
    // Logs
    // ------------------------------------------------------------------

    public static function add_log(array $data)
    {
        global $wpdb;
        $row = array_merge(array(
            'event'          => 'share',
            'post_id'        => 0,
            'user_id'        => 0,
            'platform'       => '',
            'status'         => 'failed',
            'remote_post_id' => null,
            'remote_url'     => null,
            'error_message'  => null,
            'response'       => null,
            'created_at'     => current_time('mysql'),
        ), $data);

        if (is_array($row['response']) || is_object($row['response'])) {
            $row['response'] = wp_json_encode($row['response']);
        }

        $wpdb->insert(self::logs_table(), $row);
        return (int) $wpdb->insert_id;
    }

    public static function get_latest_success($post_id, $platform)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . self::logs_table() . " WHERE post_id = %d AND platform = %s AND status = 'success' ORDER BY id DESC LIMIT 1",
            (int) $post_id,
            $platform
        ));
    }

    /**
     * Build WHERE clause for log queries. Supported args: event, status, platform, user_ids (array).
     */
    private static function logs_where(array $args)
    {
        global $wpdb;
        $where = array('1=1');

        if (!empty($args['event'])) {
            $where[] = $wpdb->prepare('event = %s', $args['event']);
        }
        if (!empty($args['status'])) {
            $where[] = $wpdb->prepare('status = %s', $args['status']);
        }
        if (!empty($args['platform'])) {
            $where[] = $wpdb->prepare('platform = %s', $args['platform']);
        }
        if (isset($args['user_ids']) && is_array($args['user_ids'])) {
            $ids = array_map('intval', $args['user_ids']);
            $where[] = $ids ? 'user_id IN (' . implode(',', $ids) . ')' : '0=1';
        }

        return implode(' AND ', $where);
    }

    public static function get_logs(array $args = array())
    {
        global $wpdb;
        $per_page = max(1, (int) ($args['per_page'] ?? 20));
        $offset = max(0, (int) ($args['offset'] ?? 0));

        return $wpdb->get_results(
            'SELECT * FROM ' . self::logs_table() . ' WHERE ' . self::logs_where($args)
            . $wpdb->prepare(' ORDER BY id DESC LIMIT %d OFFSET %d', $per_page, $offset)
        );
    }

    public static function count_logs(array $args = array())
    {
        global $wpdb;
        return (int) $wpdb->get_var('SELECT COUNT(*) FROM ' . self::logs_table() . ' WHERE ' . self::logs_where($args));
    }
}
