<?php
defined('ABSPATH') || exit;

/**
 * Get ALL steps dynamically
 * Replaces hardcoded mm_spg_get_steps()
 */
function mm_spg_get_steps()
{
    $query = new WP_Query([
        'post_type'      => 'spg_step',
        'posts_per_page' => -1,
        'post_status'    => 'publish',

        // 🔥 enable custom ordering
        'mm_spg_ordering' => true,

        'meta_query' => [
            [
                'key'   => '_spg_phase',
                'value' => 2,
                'type'  => 'NUMERIC',
            ],
        ],
    ]);

    $steps = [];

    while ($query->have_posts()) {
        $query->the_post();

        $steps[] = [
            'phase'  => 2,
            'title'  => get_the_title(),
            'blocks' => get_post_meta(get_the_ID(), '_spg_blocks', true) ?: [],
        ];
    }

    wp_reset_postdata();

    return $steps;
}


/**
 * Build Phase 3 steps based on interest slug
 */
function mm_spg_build_phase_3_steps($interest_slug)
{
    if (!$interest_slug) {
        return [];
    }

    $query = new WP_Query([
        'post_type'      => 'spg_step',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'mm_spg_ordering'=> true,
        'meta_query'     => [
            [
                'key'   => '_spg_phase',
                'value' => 3,
                'type'  => 'NUMERIC',
            ],
            [
                'key'   => '_spg_interest',
                'value' => $interest_slug,
            ],
        ],
    ]);

    $steps = [];

    while ($query->have_posts()) {
        $query->the_post();

        $steps[] = [
            'phase'    => 3,
            'title'    => get_the_title(),
            'interest' => $interest_slug,
            'blocks'   => get_post_meta(get_the_ID(), '_spg_blocks', true) ?: [],
        ];
    }

    wp_reset_postdata();
    return $steps;
}




function mm_spg_apply_phase_interest_ordering($clauses, $query)
{

    // Only affect SPG queries
    if (empty($query->get('mm_spg_ordering'))) {
        return $clauses;
    }

    global $wpdb;

    // Join phase meta
    $clauses['join'] .= "
        LEFT JOIN {$wpdb->postmeta} AS phase_meta
            ON ({$wpdb->posts}.ID = phase_meta.post_id
            AND phase_meta.meta_key = '_spg_phase')
    ";

    // Join interest meta
    $clauses['join'] .= "
        LEFT JOIN {$wpdb->postmeta} AS interest_meta
            ON ({$wpdb->posts}.ID = interest_meta.post_id
            AND interest_meta.meta_key = '_spg_interest')
    ";

    // Phase → Interest → menu_order
    $clauses['orderby'] = "
        CAST(phase_meta.meta_value AS UNSIGNED) ASC,
        interest_meta.meta_value ASC,
        {$wpdb->posts}.menu_order ASC
    ";

    return $clauses;
}

add_filter('posts_clauses', 'mm_spg_apply_phase_interest_ordering', 10, 2);