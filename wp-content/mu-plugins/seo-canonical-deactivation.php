<?php
// This is the MU Plugin for persistent canonical tags.

add_filter('wpseo_canonical', '__return_false');

add_action('wp_head', 'seo_setup_output_mu_plugins_canonical_tag', 1);

function seo_setup_output_mu_plugins_canonical_tag() {
    if ( ! is_singular() ) {
        return;
    }
    $post_id = get_the_ID();
    if ( ! $post_id ) {
        return;
    }
    // Retrieve stored canonical URL if available
    $canonicals = get_option('seo_setup_stored_canonicals', array());
    if ( isset($canonicals[$post_id]) && ! empty($canonicals[$post_id]) ) {
        $canonical_url = $canonicals[$post_id];
    } else {
        $canonical_url = get_permalink($post_id);
    }
    // Optionally update post meta
    update_post_meta($post_id, '_seo_setup_canonical_url', $canonical_url);
    if ( ! empty($canonical_url) ) {
        echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
    }
}
