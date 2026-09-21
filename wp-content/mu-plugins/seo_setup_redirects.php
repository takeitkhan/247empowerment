<?php
/**
 * Plugin Name: SEO Setup Permanent Redirects
 * Description: Handles permanent 301 redirections from old slugs to new slugs.
 * Author: SEO Setup Plugin
 * Version: 1.0
 */

if (!is_admin()) {
    add_action('template_redirect', function() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'seo_setup_redirects';

        $current_uri = strtok($_SERVER['REQUEST_URI'], '?');
        $home_url    = home_url();
        $home_path   = parse_url($home_url, PHP_URL_PATH);

        if (!empty($home_path) && $home_path !== '/') {
            if (strpos($current_uri, $home_path) === 0) {
                $current_uri = substr($current_uri, strlen($home_path));
            }
        }
        $current_uri = trim($current_uri, '/');
        $normalized_current_slug = sanitize_title($current_uri);

        $redirects = $wpdb->get_results("SELECT old_url, new_url FROM $table_name", ARRAY_A);
        if ($redirects) {
            foreach ($redirects as $redirect) {
                $old_path = parse_url($redirect['old_url'], PHP_URL_PATH);
                if (!empty($home_path) && $home_path !== '/') {
                    if (strpos($old_path, $home_path) === 0) {
                        $old_path = substr($old_path, strlen($home_path));
                    }
                }
                $old_path = trim($old_path, '/');
                $normalized_old_slug = sanitize_title($old_path);

                if ($normalized_old_slug === $normalized_current_slug) {
                    wp_redirect($redirect['new_url'], 301);
                    exit;
                }
            }
        }
    });
}