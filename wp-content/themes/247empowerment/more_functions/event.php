<?php

/**
 * ===============================
 * EVENT POST TYPE + TAXONOMY
 * ===============================
 */
function register_event_post_type()
{
    $labels = [
        'name' => 'Events',
        'singular_name' => 'Event',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Event',
        'edit_item' => 'Edit Event',
        'new_item' => 'New Event',
        'view_item' => 'View Event',
        'search_items' => 'Search Events',
        'not_found' => 'No events found',
        'not_found_in_trash' => 'No events found in Trash',
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => ['title', 'editor', 'author', 'thumbnail'],
        'show_in_rest' => true, // enables block editor + API
        'taxonomies' => ['event_category'], // <-- attach taxonomy here
    ];

    register_post_type('event', $args);
}
add_action('init', 'register_event_post_type', 1);

function add_event_caps()
{
    $roles = ['administrator']; // you can add other roles too
    foreach ($roles as $role_name) {
        $role = get_role($role_name);
        $role->add_cap('edit_event');
        $role->add_cap('read_event');
        $role->add_cap('delete_event');
        $role->add_cap('edit_events');
        $role->add_cap('edit_others_events');
        $role->add_cap('publish_events');
        $role->add_cap('read_private_events');
        $role->add_cap('delete_events');
        $role->add_cap('delete_private_events');
        $role->add_cap('delete_published_events');
        $role->add_cap('delete_others_events');
        $role->add_cap('edit_private_events');
        $role->add_cap('edit_published_events');
    }
}
add_action('admin_init', 'add_event_caps');


function register_event_category_taxonomy()
{
    register_taxonomy('event_category', 'event', [
        'labels' => [
            'name'          => 'Event Categories',
            'singular_name' => 'Event Category',
        ],
        'public'       => true,
        'hierarchical' => true,
        'show_admin_column' => true,
        'rewrite'      => ['slug' => 'event-category'],
        'show_in_rest' => true,
    ]);
}
add_action('init', 'register_event_category_taxonomy', 0);

function add_custom_event_rewrite_rule()
{
    // Single event page: /u/username/event/event-slug/
    add_rewrite_rule(
        '^u/([^/]+)/event/([^/]+)/?$',
        'index.php?event_user=$matches[1]&event_slug=$matches[2]',
        'top'
    );


    // User's event list: /u/username/events/
    add_rewrite_rule('^u/([^/]+)/events/?$', 'index.php?event_user=$matches[1]', 'top');
}
add_action('init', 'add_custom_event_rewrite_rule');

function add_event_caps_to_subscribers()
{
    $role = get_role('subscriber');
    if ($role) {
        $role->add_cap('read');
        $role->add_cap('read_event');
        $role->add_cap('edit_event');
        $role->add_cap('edit_events');
        $role->add_cap('edit_published_events');
        $role->add_cap('publish_events');
        $role->add_cap('delete_event');
        $role->add_cap('delete_published_events');
    }
}
add_action('init', 'add_event_caps_to_subscribers');

function add_event_query_vars($vars)
{
    $vars[] = 'event_user';
    $vars[] = 'event_slug';
    return $vars;
}
add_filter('query_vars', 'add_event_query_vars');

function load_event_template($template)
{
    $event_user = get_query_var('event_user'); // user identifier
    $event_slug = get_query_var('event_slug');

    if ($event_user && $event_slug) {
        // Single event page template
        return get_template_directory() . '/template-custom/auth/single-event-template.php';
    } elseif ($event_user) {
        // User-specific event list page
        return get_template_directory() . '/template-custom/auth/event-template.php';
    }

    return $template;
}
add_filter('template_include', 'load_event_template');

/**
 * ===============================
 * ADMIN ACTIONS
 * ===============================
 */
add_filter('post_row_actions', function ($actions, $post) {
    if ($post->post_type === 'event') {
        $current_user = wp_get_current_user();
            $store_user = $current_user->user_nicename ?: $current_user->user_login ?: 'joseph'; // fallback if no user logged in

        $custom_url = home_url("/u/{$store_user}/event/{$post->post_name}/");

        if (isset($actions['view'])) {
            $actions['view'] = '<a href="' . esc_url($custom_url) . '" target="_blank" rel="noopener noreferrer">View</a>';
        }
    }
    return $actions;
}, 10, 2);

function handle_submit_event_form()
{

    // ✅ Security check
    if (
        !isset($_POST['event_submission_nonce']) ||
        !wp_verify_nonce($_POST['event_submission_nonce'], 'event_submission')
    ) {
        wp_die('Security check failed.');
    }

    // ✅ Ensure user is logged in
    if (!is_user_logged_in()) {
        wp_die('You must be logged in to submit an event.');
    }

    // ✅ Prepare data
    $user_id = get_current_user_id();
    $event_title = sanitize_text_field($_POST['event_title']);    
    $event_date = sanitize_text_field($_POST['event_date']);
    $event_time = sanitize_text_field($_POST['event_time']);
    $event_duration = sanitize_text_field($_POST['event_duration']);
    $event_desc = sanitize_textarea_field($_POST['event_description']);
    $event_link = esc_url_raw($_POST['event_link']);
    $registrationType = sanitize_text_field($_POST['registrationType']);
    $event_price = sanitize_text_field($_POST['event_price']);

    // ✅ Insert event post
    $post_id = wp_insert_post([
        'post_title'   => $event_title,
        'post_content' => $event_desc,
        'post_status'  => 'publish',
        'post_type'    => 'event',
        'post_author'  => $user_id,
    ]);

    if (is_wp_error($post_id)) {
        wp_die('Error creating event.');
    }

    if (!empty($_POST['event_category'])) {
        $term_slug = sanitize_text_field($_POST['event_category']);

        // ✅ Get the term by slug
        $term = get_term_by('slug', $term_slug, 'event_category');

        if ($term && !is_wp_error($term)) {
            wp_set_post_terms($post_id, [$term->term_id], 'event_category', false);
        } else {
            error_log('Event category term not found for slug: ' . $term_slug);
        }
    }

    error_log('POST data: ' . print_r($_POST, true));

    // ✅ Save meta    
    update_post_meta($post_id, 'event_date', $event_date);
    update_post_meta($post_id, 'event_time', $event_time);
    update_post_meta($post_id, 'event_duration', $event_duration);
    update_post_meta($post_id, 'event_link', $event_link);
    update_post_meta($post_id, 'registration_type', $registrationType);
    update_post_meta($post_id, 'event_price', $event_price);

    // LiveKit meeting scheduling
    $is_livekit = isset($_POST['livekit_meeting']) ? boolval($_POST['livekit_meeting']) : false;
    if ($is_livekit) {
        $mm_is_video = isset($_POST['mm_is_video']) ? intval($_POST['mm_is_video']) : 1;
        $mm_max = isset($_POST['mm_max_participants']) ? intval($_POST['mm_max_participants']) : 40;
        $mm_room = isset($_POST['mm_room_name']) ? sanitize_text_field($_POST['mm_room_name']) : '';

        update_post_meta($post_id, '_mm_livekit_scheduled', '1');
        update_post_meta($post_id, '_mm_livekit_is_video', $mm_is_video ? '1' : '0');
        update_post_meta($post_id, '_mm_livekit_max_participants', $mm_max);
        if (!empty($mm_room)) update_post_meta($post_id, '_mm_livekit_room_requested', $mm_room);
        // store host id so the host can start early
        update_post_meta($post_id, '_mm_livekit_host_id', $user_id);

        // schedule activation at the selected date/time
        $when = strtotime($event_date . ' ' . $event_time);
        if ($when && $when > time()) {
            wp_schedule_single_event( $when, 'mm_livekit_activate_meeting', array( $post_id ) );
            update_post_meta($post_id, '_mm_livekit_scheduled_for', $when);
        }
    }

    // ✅ Handle cover image
    if (!empty($_FILES['eventCover']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_handle_upload('eventCover', $post_id);
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
        }
    }

    // ✅ Redirect safely
    $redirect_url = add_query_arg('event_submitted', 'true', wp_get_referer() ?: home_url());
    wp_safe_redirect($redirect_url);
    exit;
}
add_action('admin_post_submit_event_form', 'handle_submit_event_form');
add_action('admin_post_nopriv_submit_event_form', 'handle_submit_event_form');
