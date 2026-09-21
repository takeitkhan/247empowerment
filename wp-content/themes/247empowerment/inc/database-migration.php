<?php
/**
 * Database Migration & Schema Setup
 * Phase 1, 2, 3 - Post Meta Fields
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize database schema on plugin/theme activation
 */
function phase1_phase2_phase3_init_database_schema() {
    global $wpdb;
    
    // Check if migration has already been done
    $migration_version = get_option('posting_features_migration_version');
    $current_version = '1.0.0';
    
    if ($migration_version === $current_version) {
        return; // Already migrated
    }

    // Create tables and add necessary post meta support
    create_scheduled_posts_table();
    create_social_shares_table();
    create_user_notifications_table();
    
    // Update migration version
    update_option('posting_features_migration_version', $current_version);
}

/**
 * Create Scheduled Posts Table
 * Stores scheduled post information and status
 */
function create_scheduled_posts_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'scheduled_posts';
    $charset_collate = $wpdb->get_charset_collate();

    // Check if table already exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
        return;
    }

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        scheduled_timestamp BIGINT(20) NOT NULL COMMENT 'Unix timestamp for scheduled publish',
        status VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, published, failed, cancelled',
        error_message LONGTEXT NULL COMMENT 'Error message if status is failed',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        published_at DATETIME NULL COMMENT 'When post was actually published',
        PRIMARY KEY (id),
        KEY post_id (post_id),
        KEY user_id (user_id),
        KEY scheduled_timestamp (scheduled_timestamp),
        KEY status (status),
        UNIQUE KEY unique_post (post_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Create Social Shares Table
 * Tracks posts shared to Facebook and LinkedIn
 */
function create_social_shares_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'social_shares';
    $charset_collate = $wpdb->get_charset_collate();

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
        return;
    }

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        platform VARCHAR(50) NOT NULL COMMENT 'facebook, linkedin',
        account_id VARCHAR(255) NOT NULL COMMENT 'Platform-specific account ID',
        social_post_id VARCHAR(255) NULL COMMENT 'ID of post on social platform',
        status VARCHAR(20) NOT NULL DEFAULT 'success' COMMENT 'success, failed, pending',
        error_message LONGTEXT NULL COMMENT 'Error details if failed',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY post_id (post_id),
        KEY user_id (user_id),
        KEY platform (platform),
        KEY status (status),
        KEY created_at (created_at),
        UNIQUE KEY unique_post_platform_account (post_id, platform, account_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Create User Notifications Table
 * Stores notifications for users (post published, etc.)
 */
function create_user_notifications_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'user_notifications';
    $charset_collate = $wpdb->get_charset_collate();

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
        return;
    }

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        type VARCHAR(50) NOT NULL COMMENT 'post_published, post_scheduled, post_failed, etc.',
        title VARCHAR(255) NOT NULL,
        message LONGTEXT NOT NULL,
        related_post_id BIGINT(20) UNSIGNED NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        read_at DATETIME NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY type (type),
        KEY is_read (is_read),
        KEY created_at (created_at),
        KEY related_post_id (related_post_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Register custom post meta
 * These are used for storing additional post information
 */
function register_custom_post_meta() {
    // Phase 1 & 2 meta fields
    register_post_meta('post', '_scheduled_publish_time', array(
        'type'           => 'string',
        'description'    => 'Unix timestamp for scheduled post publication',
        'single'         => true,
        'show_in_rest'   => true,
        'auth_callback'  => function() { return current_user_can('edit_posts'); }
    ));

    register_post_meta('post', '_post_status_type', array(
        'type'           => 'string',
        'description'    => 'Post status type: draft, scheduled, published, failed',
        'single'         => true,
        'show_in_rest'   => true,
        'auth_callback'  => function() { return current_user_can('edit_posts'); }
    ));

    register_post_meta('post', '_post_preview_enabled', array(
        'type'           => 'boolean',
        'description'    => 'Whether post preview was shown before publishing',
        'single'         => true,
        'show_in_rest'   => true
    ));

    // Phase 3 meta fields - Social Media
    register_post_meta('post', '_social_platforms_posted', array(
        'type'           => 'string',
        'description'    => 'JSON array of platforms post was shared to',
        'single'         => true,
        'show_in_rest'   => true,
        'auth_callback'  => function() { return current_user_can('edit_posts'); }
    ));

    register_post_meta('post', '_social_share_status', array(
        'type'           => 'string',
        'description'    => 'JSON object tracking share status per platform',
        'single'         => true,
        'show_in_rest'   => true,
        'auth_callback'  => function() { return current_user_can('edit_posts'); }
    ));

    register_post_meta('post', '_original_post_status', array(
        'type'           => 'string',
        'description'    => 'Original post status before scheduling',
        'single'         => true,
        'show_in_rest'   => true
    ));
}

/**
 * Register custom user meta
 */
function register_custom_user_meta() {
    // Phase 3 - Social Media Integration
    register_meta('user', 'social_accounts', array(
        'type'           => 'string',
        'description'    => 'JSON object storing encrypted social media account tokens',
        'single'         => true,
        'show_in_rest'   => false,
        'auth_callback'  => function() { return is_user_logged_in(); }
    ));

    register_meta('user', 'facebook_connected', array(
        'type'           => 'boolean',
        'description'    => 'Whether user has connected Facebook account',
        'single'         => true,
        'show_in_rest'   => true
    ));

    register_meta('user', 'linkedin_connected', array(
        'type'           => 'boolean',
        'description'    => 'Whether user has connected LinkedIn account',
        'single'         => true,
        'show_in_rest'   => true
    ));

    register_meta('user', 'posting_notifications_enabled', array(
        'type'           => 'boolean',
        'description'    => 'Whether user wants notifications for post publishing',
        'single'         => true,
        'show_in_rest'   => true,
        'default'        => true
    ));
}

/**
 * Helper Functions
 */

/**
 * Get scheduled post info
 */
function get_scheduled_post_info($post_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'scheduled_posts';
    
    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d", $post_id)
    );
}

/**
 * Create scheduled post record
 */
function create_scheduled_post($post_id, $user_id, $timestamp) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'scheduled_posts';
    
    return $wpdb->insert(
        $table_name,
        array(
            'post_id'              => $post_id,
            'user_id'              => $user_id,
            'scheduled_timestamp'  => $timestamp,
            'status'               => 'pending'
        ),
        array('%d', '%d', '%d', '%s')
    );
}

/**
 * Update scheduled post status
 */
function update_scheduled_post_status($post_id, $status, $error_message = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'scheduled_posts';
    
    $update_data = array(
        'status' => $status
    );
    
    if ($error_message) {
        $update_data['error_message'] = $error_message;
    }
    
    if ($status === 'published') {
        $update_data['published_at'] = current_time('mysql');
    }
    
    return $wpdb->update(
        $table_name,
        $update_data,
        array('post_id' => $post_id),
        array('%s', '%s'),
        array('%d')
    );
}

/**
 * Get pending scheduled posts ready to publish
 */
function get_pending_scheduled_posts($limit = 10) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'scheduled_posts';
    $current_timestamp = current_time('timestamp');
    
    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE status = 'pending' AND scheduled_timestamp <= %d 
             ORDER BY scheduled_timestamp ASC 
             LIMIT %d",
            $current_timestamp,
            $limit
        )
    );
}

/**
 * Create notification
 */
function create_user_notification($user_id, $type, $title, $message, $post_id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_notifications';
    
    return $wpdb->insert(
        $table_name,
        array(
            'user_id'         => $user_id,
            'type'            => $type,
            'title'           => $title,
            'message'         => $message,
            'related_post_id' => $post_id
        ),
        array('%d', '%s', '%s', '%s', '%d')
    );
}

/**
 * Get user unread notifications
 */
function get_user_unread_notifications($user_id, $limit = 10) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_notifications';
    
    return $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name 
             WHERE user_id = %d AND is_read = 0 
             ORDER BY created_at DESC 
             LIMIT %d",
            $user_id,
            $limit
        )
    );
}

/**
 * Log social media share
 */
function log_social_share($post_id, $user_id, $platform, $account_id, $status = 'success', $error_message = null, $social_post_id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'social_shares';
    
    return $wpdb->insert(
        $table_name,
        array(
            'post_id'       => $post_id,
            'user_id'       => $user_id,
            'platform'      => $platform,
            'account_id'    => $account_id,
            'social_post_id' => $social_post_id,
            'status'        => $status,
            'error_message' => $error_message
        ),
        array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
    );
}

/**
 * Get social shares for post
 */
function get_post_social_shares($post_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'social_shares';
    
    return $wpdb->get_results(
        $wpdb->prepare("SELECT * FROM $table_name WHERE post_id = %d", $post_id)
    );
}

// Hook into WordPress initialization
add_action('init', 'phase1_phase2_phase3_init_database_schema', 10);
add_action('init', 'register_custom_post_meta', 11);
add_action('init', 'register_custom_user_meta', 12);

?>
