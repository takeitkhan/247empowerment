<?php
/**
 * Template Name: ChatBot Info Block
 *
 * Pages assigned to this template are displayed underneath the AI Chat Assistant
 * box on the ChatGPT Bot page. The page itself is not meant to be viewed directly;
 * its content is pulled into the chatbot page via `the_content` filter.
 */

// If someone visits this page directly, redirect them to the chatbot page.
if (!is_admin()) {
    $chatbot_page = get_page_by_path('chatgpt-bot');
    if ($chatbot_page) {
        wp_safe_redirect(get_permalink($chatbot_page->ID));
        exit;
    }
}
