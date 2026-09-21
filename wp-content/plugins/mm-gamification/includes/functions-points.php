<?php
function mm_get_action_by_key($action_key)
{
    global $wpdb;
    $table = $wpdb->prefix . 'gamification_actions';
    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE action_key = %s", $action_key));
}
