<?php
/**
 * Notifications Admin Menu & Dashboard
 * Shows all notifications with filtering, searching, and pagination
 */

require_once get_template_directory() . '/inc/NotificationManager.php';
require_once get_template_directory() . '/inc/NotificationTypes.php';

// Register menu
add_action('admin_menu', function() {
    add_menu_page(
        'Notifications',
        'Notifications',
        'manage_options',
        'mm-notifications-dashboard',
        'mm_render_notifications_dashboard',
        'dashicons-bell',
        22 // Position
    );
});

// Render notifications dashboard
function mm_render_notifications_dashboard() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    $manager = NotificationManager::getInstance();
    
    // Get current page and filters from URL
    $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
    $category_filter = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
    $read_filter = isset($_GET['read']) ? sanitize_text_field($_GET['read']) : '';
    $search_filter = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

    // Get all users for user filter
    $users = get_users(['role' => 'subscriber', 'number' => 0]);
    $user_filter = isset($_GET['user']) ? intval($_GET['user']) : 0;

    // Build query args
    $query_args = [
        'page' => $current_page,
        'per_page' => 20,
        'type' => $type_filter,
        'category' => $category_filter,
        'read' => $read_filter,
    ];

    // If no specific user selected, show all notifications
    if ($user_filter > 0) {
        $results = $manager->get($user_filter, $query_args);
    } else {
        // Get all notifications from database
        global $wpdb;
        $table_name = $wpdb->prefix . 'notifications';
        
        $where = "WHERE 1=1";
        if (!empty($type_filter)) {
            $where .= $wpdb->prepare(" AND type = %s", $type_filter);
        }
        if (!empty($category_filter)) {
            $where .= $wpdb->prepare(" AND category = %s", $category_filter);
        }
        if ($read_filter === 'unread') {
            $where .= " AND read_at IS NULL";
        } elseif ($read_filter === 'read') {
            $where .= " AND read_at IS NOT NULL";
        }
        
        $orderby = "ORDER BY created_at DESC";
        $limit = ($current_page - 1) * 20 . ", 20";

        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where");
        $notifications = $wpdb->get_results("SELECT * FROM $table_name $where $orderby LIMIT $limit");

        $results = [
            'notifications' => $notifications,
            'total' => $total,
            'page' => $current_page,
            'per_page' => 20,
            'total_pages' => ceil($total / 20),
        ];
    }

    $notifications = $results['notifications'];
    $total = $results['total'];
    $total_pages = $results['total_pages'];

    // Get stats
    $all_unread = 0;
    $all_total = 0;
    foreach ($users as $user) {
        $all_unread += $manager->getUnreadCount($user->ID);
    }
    global $wpdb;
    $all_total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}notifications");

    ?>
    <div class="wrap">
        <h1 style="margin-bottom: 30px;">📬 Notifications Dashboard</h1>

        <!-- Stats -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="font-size: 14px; color: #666;">Total Notifications</div>
                <div style="font-size: 32px; font-weight: bold; color: #2271b1;"><?php echo number_format($all_total); ?></div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="font-size: 14px; color: #666;">Unread Notifications</div>
                <div style="font-size: 32px; font-weight: bold; color: #d63638;"><?php echo number_format($all_unread); ?></div>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <div style="font-size: 14px; color: #666;">Total Users</div>
                <div style="font-size: 32px; font-weight: bold; color: #7c3aed;"><?php echo count($users); ?></div>
            </div>
        </div>

        <!-- Filters & Search -->
        <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
            <form method="GET" action="">
                <input type="hidden" name="page" value="mm-notifications-dashboard">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                    <!-- User Filter -->
                    <div>
                        <label for="user-filter" style="display: block; margin-bottom: 5px; font-weight: 500;">User:</label>
                        <select id="user-filter" name="user" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user) : ?>
                                <option value="<?php echo $user->ID; ?>" <?php selected($user_filter, $user->ID); ?>>
                                    <?php echo $user->display_name . ' (' . $user->user_email . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div>
                        <label for="category-filter" style="display: block; margin-bottom: 5px; font-weight: 500;">Category:</label>
                        <select id="category-filter" name="category" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Categories</option>
                            <?php foreach (NotificationTypes::getCategories() as $cat_key => $cat_name) : ?>
                                <option value="<?php echo $cat_key; ?>" <?php selected($category_filter, $cat_key); ?>>
                                    <?php echo $cat_name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <label for="type-filter" style="display: block; margin-bottom: 5px; font-weight: 500;">Type:</label>
                        <select id="type-filter" name="type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All Types</option>
                            <?php foreach (NotificationTypes::getAll() as $type_key => $type_data) : ?>
                                <option value="<?php echo $type_key; ?>" <?php selected($type_filter, $type_key); ?>>
                                    <?php echo $type_data['icon'] . ' ' . $type_data['label']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Read Status Filter -->
                    <div>
                        <label for="read-filter" style="display: block; margin-bottom: 5px; font-weight: 500;">Status:</label>
                        <select id="read-filter" name="read" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="">All</option>
                            <option value="unread" <?php selected($read_filter, 'unread'); ?>>Unread</option>
                            <option value="read" <?php selected($read_filter, 'read'); ?>>Read</option>
                        </select>
                    </div>
                </div>

                <!-- Search -->
                <div style="display: grid; grid-template-columns: 1fr auto; gap: 10px;">
                    <input type="text" name="search" placeholder="Search notifications..." value="<?php echo $search_filter; ?>" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <button type="submit" class="button button-primary" style="cursor: pointer;">🔍 Search</button>
                </div>
            </form>
        </div>

        <!-- Notifications Table -->
        <div style="background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                    <tr>
                        <th style="padding: 12px; text-align: left; font-weight: 600;">Icon</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600;">Type</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600;">Message</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600;">User</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600;">Date</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($notifications)) : ?>
                        <?php foreach ($notifications as $notif) : 
                            $user = get_user_by('id', $notif->user_id);
                            $is_read = !is_null($notif->read_at);
                            $bg_color = $is_read ? '#ffffff' : '#fff3cd';
                        ?>
                            <tr style="border-bottom: 1px solid #eee; background: <?php echo $bg_color; ?>;">
                                <td style="padding: 12px; font-size: 20px;"><?php echo $notif->icon; ?></td>
                                <td style="padding: 12px;">
                                    <div style="font-weight: 600; font-size: 13px;"><?php echo $notif->title; ?></div>
                                    <div style="font-size: 12px; color: #666;"><?php echo ucfirst($notif->category); ?></div>
                                </td>
                                <td style="padding: 12px; font-size: 13px;">
                                    <?php echo wp_kses_post(wp_trim_words($notif->message, 15)); ?>
                                </td>
                                <td style="padding: 12px; font-size: 13px;">
                                    <?php if ($user) : ?>
                                        <div><?php echo esc_html($user->display_name); ?></div>
                                        <div style="color: #666; font-size: 11px;"><?php echo esc_html($user->user_email); ?></div>
                                    <?php else : ?>
                                        <span style="color: #999;">User Deleted</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; font-size: 13px; white-space: nowrap;">
                                    <?php echo mysql2date('M d, Y H:i', $notif->created_at); ?>
                                </td>
                                <td style="padding: 12px;">
                                    <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 500; background: <?php echo $is_read ? '#e8f5e9' : '#ffebee'; ?>; color: <?php echo $is_read ? '#2e7d32' : '#c62828'; ?>;">
                                        <?php echo $is_read ? '✓ Read' : '● Unread'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6" style="padding: 40px; text-align: center; color: #999;">
                                No notifications found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1) : ?>
            <div style="margin-top: 20px; text-align: center;">
                <?php 
                $current_url = remove_query_arg('paged');
                $base_url = add_query_arg('paged', '%#%', $current_url);
                
                echo paginate_links([
                    'base' => $base_url,
                    'format' => '',
                    'current' => $current_page,
                    'total' => $total_pages,
                    'prev_text' => '« Previous',
                    'next_text' => 'Next »',
                    'type' => 'list',
                ]);
                ?>
            </div>
        <?php endif; ?>

        <div style="margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 4px; font-size: 13px; color: #666;">
            Showing <strong><?php echo count($notifications); ?></strong> of <strong><?php echo number_format($total); ?></strong> notifications
        </div>
    </div>
    <?php
}
