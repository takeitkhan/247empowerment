<?php
$current_user = wp_get_current_user();
$phone = get_user_meta($current_user->ID, 'phone', true);
?>
