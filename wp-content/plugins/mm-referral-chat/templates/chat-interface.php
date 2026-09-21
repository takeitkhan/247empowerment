<?php
/**
 * Chat Interface Template
 * This file is included in wp_footer for logged-in users
 */

if (!is_user_logged_in()) {
    return;
}
?>

<!-- Chat Interface will be rendered by JavaScript -->
<!-- The mm-referral-chat.js script will create all necessary DOM elements -->
<!-- This file is minimal as most functionality is JavaScript-driven -->
