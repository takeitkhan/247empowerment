<?php

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Gamification_Actions_Table extends WP_List_Table
{
    private $actions_data;

    public function __construct()
    {
        parent::__construct([
            'singular' => 'gamification_action',
            'plural'   => 'gamification_actions',
            'ajax'     => false
        ]);
    }

    public function get_columns()
    {
        return [
            'cb'                   => '<input type="checkbox" />',
            'id'                   => 'ID',
            'action_key'           => 'Action Key',
            'custom_message'       => 'Message',
            'notification_message' => 'Notification Message',
            'points'               => 'Points',
            'created_at'           => 'Created'
        ];
    }

    public function prepare_items()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'gamification_actions';

        $this->actions_data = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC", ARRAY_A);

        $columns  = $this->get_columns();
        $hidden   = [];
        $sortable = [];

        $this->_column_headers = [$columns, $hidden, $sortable];
        $this->items = $this->actions_data;
    }

    public function column_default($item, $column_name)
    {
        return isset($item[$column_name]) ? esc_html($item[$column_name]) : '';
    }

    public function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="id[]" value="%s" />', $item['id']);
    }

    public function column_action_key($item)
    {
        $edit_url = admin_url('admin.php?page=mm-gamification-add&action=edit&id=' . $item['id']);
        $delete_url = wp_nonce_url(
            admin_url('admin.php?page=mm-gamification&delete=' . $item['id']),
            'mm_delete_action_nonce'
        );

        return sprintf(
            '%s <div class="row-actions">
                <span class="edit"><a href="%s">Edit</a></span> |
                <span class="trash"><a href="%s" onclick="return confirm(\'Delete?\')">Delete</a></span>
            </div>',
            esc_html($item['action_key']),
            $edit_url,
            $delete_url
        );
    }
}
