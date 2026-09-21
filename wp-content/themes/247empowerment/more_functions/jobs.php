<?php
function create_jobs_cpt()
{
    $labels = array(
        'name' => 'Jobs',
        'singular_name' => 'Job',
        'add_new' => 'Add New Job',
        'add_new_item' => 'Add New Job',
        'edit_item' => 'Edit Job',
        'new_item' => 'New Job',
        'view_item' => 'View Job',
        'search_items' => 'Search Jobs',
        'not_found' => 'No jobs found',
        'not_found_in_trash' => 'No jobs found in trash'
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'menu_icon' => 'dashicons-businessperson',
        'supports' => array('title', 'editor', 'thumbnail'),
        'rewrite' => array('slug' => 'jobs'),
        'has_archive' => true,
    );

    register_post_type('job', $args);
}
add_action('init', 'create_jobs_cpt');
