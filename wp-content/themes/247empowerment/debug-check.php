<?php
/**
 * Debug Check - Verify files are loaded correctly
 */

echo "<h2>Debug Check</h2>";
echo "<pre>";

// Check helpers.php
$helpers_path = get_template_directory() . '/inc/helpers.php';
echo "Helpers path: " . $helpers_path . "\n";
echo "File exists: " . (file_exists($helpers_path) ? "YES" : "NO") . "\n";
echo "Function exists (mm_trigger_action): " . (function_exists('mm_trigger_action') ? "YES" : "NO") . "\n\n";

// Check NotificationManager
echo "NotificationManager class exists: " . (class_exists('NotificationManager') ? "YES" : "NO") . "\n\n";

// Check if helpers functions work
if (function_exists('mm_trigger_action')) {
    echo "mm_trigger_action is callable: YES\n";
    echo "Testing mm_trigger_action:\n";
    
    // Capture any actions
    $test_fired = false;
    add_action('test_mm_trigger', function() {
        global $test_fired;
        $test_fired = true;
    });
    
    mm_trigger_action('test_mm_trigger');
    echo "Test hook fired: " . ($test_fired ? "YES" : "NO") . "\n";
} else {
    echo "mm_trigger_action is NOT callable\n";
}

echo "</pre>";
?>
