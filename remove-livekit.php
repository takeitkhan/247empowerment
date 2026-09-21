<?php
/**
 * One-time cleanup script to remove LiveKit DB tables and event meta.
 * Usage: php remove-livekit.php (run from inside the `wp/` directory)
 * IMPORTANT: This will DROP tables and remove post meta. Back up your DB first.
 */

if (php_sapi_name() !== 'cli') {
    echo "Run this script from command line: php remove-livekit.php\n";
    exit;
}

$wp_load = __DIR__ . '/wp-load.php';
if (!file_exists($wp_load)) {
    echo "Could not find wp-load.php. Run this script from the WordPress root (wp/).\n";
    exit(1);
}

require_once $wp_load;

global $wpdb;

echo "Backing up options and listing LiveKit-related tables...\n";

$tables = [
    $wpdb->prefix . 'livekit_rooms',
    $wpdb->prefix . 'livekit_session_logs',
    $wpdb->prefix . 'livekit_metrics',
    $wpdb->prefix . 'livekit_youtube_streams',
    $wpdb->prefix . 'livekit_rate_limits',
    $wpdb->prefix . 'livekit_config',
];

foreach ($tables as $t) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$t}'") === $t;
    echo ($exists ? "FOUND: {$t}\n" : "MISSING: {$t}\n");
}

echo "\nThis script will DROP the above tables if they exist and remove post meta key '_livekit_room'.\n";
fwrite(STDOUT, "Proceed? (yes/no): ");
$answer = trim(fgets(STDIN));
if (strtolower($answer) !== 'yes') {
    echo "Aborted. No changes made.\n";
    exit;
}

// Drop tables
foreach ($tables as $t) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$t}'") === $t;
    if ($exists) {
        $wpdb->query("DROP TABLE IF EXISTS {$t}");
        echo "Dropped {$t}\n";
    }
}

// Remove post meta and options
$removed_meta = $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_livekit_room'));
echo "Removed _livekit_room postmeta rows: " . intval($removed_meta) . "\n";

$opts = [
    'livekit_api_key', 'livekit_api_secret', 'livekit_ws_url',
    'livekit_cloud_api_key', 'livekit_cloud_api_secret', 'livekit_tables_created'
];
foreach ($opts as $o) {
    if (get_option($o) !== false) {
        delete_option($o);
        echo "Deleted option: {$o}\n";
    }
}

echo "Cleanup complete. Check your site and restore from backup if needed.\n";

exit;
