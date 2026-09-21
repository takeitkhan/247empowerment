<?php
/**
 * Status Indicators for Posts
 * Shows scheduled, draft, published, and failed status badges
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display post status badge
 */
function display_post_status_badge($post_id) {
    $status_type = get_post_meta($post_id, '_post_status_type', true);
    $scheduled_time = get_post_meta($post_id, '_scheduled_publish_time', true);
    
    if (empty($status_type)) {
        // Default to published if no status type set
        $status_type = 'published';
    }

    $badge_html = '';

    switch ($status_type) {
        case 'scheduled':
            $badge_html = get_scheduled_badge_html($post_id, $scheduled_time);
            break;
        case 'draft':
            $badge_html = '<span class="post-status-badge draft">
                <i class="bi bi-pencil-square me-1"></i>Draft
            </span>';
            break;
        case 'failed':
            $error_msg = get_post_meta($post_id, '_post_error_message', true);
            $badge_html = '<span class="post-status-badge failed" title="' . esc_attr($error_msg) . '">
                <i class="bi bi-exclamation-triangle me-1"></i>Failed
            </span>';
            break;
        case 'published':
        default:
            $badge_html = '<span class="post-status-badge published">
                <i class="bi bi-check-circle me-1"></i>Published
            </span>';
            break;
    }

    echo wp_kses_post($badge_html);
}

/**
 * Get scheduled badge HTML with countdown
 */
function get_scheduled_badge_html($post_id, $scheduled_time) {
    if (empty($scheduled_time)) {
        return '';
    }

    $scheduled_timestamp = intval($scheduled_time);
    $current_timestamp = current_time('timestamp');
    $time_diff = $scheduled_timestamp - $current_timestamp;

    // Format the display time
    $display_time = wp_date('M d, Y @ g:i A', $scheduled_timestamp);
    
    // Calculate countdown
    $countdown = '';
    if ($time_diff > 0) {
        if ($time_diff < 3600) {
            $minutes = ceil($time_diff / 60);
            $countdown = $minutes . 'm';
        } elseif ($time_diff < 86400) {
            $hours = ceil($time_diff / 3600);
            $countdown = $hours . 'h';
        } else {
            $days = ceil($time_diff / 86400);
            $countdown = $days . 'd';
        }
    }

    $html = '<span class="post-status-badge scheduled" title="' . esc_attr($display_time) . '">
        <i class="bi bi-calendar-event me-1"></i>
        Scheduled
        ' . ($countdown ? '<span class="badge bg-secondary ms-1">' . esc_html($countdown) . '</span>' : '') . '
    </span>';

    return $html;
}

/**
 * Display scheduled time info below post
 */
function display_scheduled_time_info($post_id) {
    $status_type = get_post_meta($post_id, '_post_status_type', true);
    
    if ($status_type === 'scheduled') {
        $scheduled_time = get_post_meta($post_id, '_scheduled_publish_time', true);
        if (!empty($scheduled_time)) {
            $display_time = wp_date('F j, Y \a\t g:i A', intval($scheduled_time));
            echo '<div class="scheduled-time-info">';
            echo '<i class="bi bi-info-circle me-1"></i>';
            echo 'This post will be published on ' . esc_html($display_time);
            echo '</div>';
        }
    }
}

/**
 * Add post status column to admin posts list
 */
function add_post_status_column($columns) {
    $columns['post_status_type'] = 'Publishing Status';
    return $columns;
}

/**
 * Display post status in admin column
 */
function display_post_status_column($column, $post_id) {
    if ($column === 'post_status_type') {
        display_post_status_badge($post_id);
    }
}

/**
 * Make status column sortable in admin
 */
function make_status_column_sortable($columns) {
    $columns['post_status_type'] = 'post_status_type';
    return $columns;
}

/**
 * Handle status column sorting in admin
 */
function handle_status_column_orderby($query) {
    if (!is_admin()) {
        return;
    }

    $orderby = $query->get('orderby');
    if ($orderby === 'post_status_type') {
        $query->set('meta_key', '_post_status_type');
        $query->set('orderby', 'meta_value');
    }
}

// Hooks for admin column display
add_filter('manage_posts_columns', 'add_post_status_column');
add_action('manage_posts_custom_column', 'display_post_status_column', 10, 2);
add_filter('manage_sortable_columns', 'make_status_column_sortable');
add_action('pre_get_posts', 'handle_status_column_orderby');

?>
