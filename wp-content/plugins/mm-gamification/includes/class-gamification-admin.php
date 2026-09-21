<?php
if (! defined('ABSPATH')) exit;

class MM_Gamification_Admin
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_admin_menus']);
    }

    public function register_admin_menus()
    {
        add_menu_page(
            'Gamification',
            'Gamification',
            'manage_options',
            'mm-gamification',
            [$this, 'actions_list_page'],
            'dashicons-awards',
            30
        );

        add_submenu_page(
            'mm-gamification',
            'Add New Action',
            'Add New Action',
            'manage_options',
            'mm-gamification-add',
            [$this, 'add_action_page']
        );
    }

    public function actions_list_page()
    {
        if (isset($_GET['delete']) && check_admin_referer('mm_delete_action_nonce')) {
            global $wpdb;
            $table = $wpdb->prefix . 'gamification_actions';
            $wpdb->delete($table, ['id' => intval($_GET['delete'])]);

            echo '<div class="notice notice-success is-dismissible"><p>Action deleted.</p></div>';
        }

        require_once plugin_dir_path(__FILE__) . 'class-gamification-actions-table.php';

        $table = new Gamification_Actions_Table();
        $table->prepare_items();
?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Gamification Actions</h1>
            <a href="<?php echo admin_url('admin.php?page=mm-gamification-add'); ?>" class="page-title-action">Add New</a>
            <hr class="wp-header-end">
            <form method="post">
                <?php $table->display(); ?>
            </form>
        </div>
    <?php
    }



    public function add_action_page()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gamification_actions';
        $available_actions = mm_get_available_actions();

        $edit_id = isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit' ? intval($_GET['id']) : 0;

        if ($edit_id) {
            $action_to_edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $edit_id));
        }


        if (isset($_POST['mm_add_action'])) {
            check_admin_referer('mm_add_action_nonce');

            if (!empty($_POST['action_key_custom'])) {
                $action_key = sanitize_text_field($_POST['action_key_custom']);
            } elseif (!empty($_POST['action_key_dropdown'])) {
                $action_key = sanitize_text_field($_POST['action_key_dropdown']);
            } else {
                $action_key = '';
            }

            if (empty($action_key)) {
                echo '<div class="notice notice-error is-dismissible"><p>Please select or enter an action.</p></div>';
            } else {
                $custom_message = sanitize_textarea_field($_POST['custom_message']);
                $points         = intval($_POST['points']);
                $notification_message = sanitize_textarea_field($_POST['notification_message']);

                if ($edit_id) {
                    $wpdb->update($table, [
                        'action_key' => $action_key,
                        'custom_message' => $custom_message,
                        'notification_message' => $notification_message,
                        'points' => $points
                    ], ['id' => $edit_id]);

                    echo '<div class="notice notice-success is-dismissible"><p>Action updated successfully!</p></div>';
                } else {
                    $wpdb->insert($table, [
                        'action_key' => $action_key,
                        'custom_message' => $custom_message,
                        'notification_message' => $notification_message,
                        'points' => $points
                    ]);

                    // Build debug info
                    $debug = [
                        'Inserted Data' => [
                            'action_key' => $action_key,
                            'custom_message' => $custom_message,
                            'notification_message' => $notification_message,
                            'points' => $points
                        ],
                        'Last Query'   => $wpdb->last_query,
                        'Last Error'   => $wpdb->last_error,
                        'Insert ID'    => $wpdb->insert_id,
                        'Rows Affected' => $wpdb->rows_affected,
                    ];

                    // Dump everything with wp_die()
                    // wp_die(
                    //     '<h2>Gamification Debug Dump</h2><pre>' .
                    //         print_r($debug, true) .
                    //         '</pre>'
                    // );


                    echo '<div class="notice notice-success is-dismissible"><p>Action added successfully!</p></div>';
                }
            }
        }

    ?>
        <div class="wrap">
            <h1>Add New Action</h1>
            <form method="post">
                <?php wp_nonce_field('mm_add_action_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="action_key_dropdown">Select Predefined Action</label></th>
                        <td>
                            <select name="action_key_dropdown" id="action_key_dropdown">
                                <option value="">-- Select an Action --</option>
                                <?php
                                $current_action_key = $action_to_edit->action_key ?? '';
                                foreach ($available_actions as $key => $label) : ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($current_action_key, $key); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <br>
                            <small>Or enter a custom action below:</small>
                            <br>
                            <?php
                            $custom_key_value = '';
                            if ($edit_id && !array_key_exists($current_action_key, $available_actions)) {
                                $custom_key_value = $current_action_key;
                            }
                            ?>
                            <input type="text" name="action_key_custom" placeholder="Custom action key" class="regular-text" value="<?= esc_attr($custom_key_value) ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="custom_message">Custom Message</label></th>
                        <td><textarea name="custom_message" rows="4" class="large-text" required><?= esc_textarea(isset($action_to_edit->custom_message) ? stripslashes($action_to_edit->custom_message) : '') ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="notification_message">Notification Message</label></th>
                        <td>
                            <input type="text" name="notification_message" placeholder="Optional notification message" class="regular-text" value="<?= esc_attr(isset($action_to_edit->notification_message) ? stripslashes($action_to_edit->notification_message) : '') ?>">
                            <p class="description">This will be shown in user notifications when points are awarded.</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="points">Points</label></th>
                        <td><input type="number" name="points" required class="small-text" value="<?= esc_attr($action_to_edit->points ?? '') ?>"></td>
                    </tr>
                </table>
                <?php submit_button('Save Action', 'primary', 'mm_add_action'); ?>
            </form>
        </div>
<?php
    }
}
