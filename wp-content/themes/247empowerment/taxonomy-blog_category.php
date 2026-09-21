<?php
if (is_user_logged_in()) {
    get_header('portal');
} else {
    get_header('main');
}

$term = get_queried_object(); // Current category term
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;

$query = new WP_Query(array(
    'post_type' => 'blog',
    'tax_query' => array(
        array(
            'taxonomy' => 'blog_category',
            'field'    => 'slug',
            'terms'    => $term->slug,
        ),
    ),
    'posts_per_page' => 6,
    'paged' => $paged,
));
?>

<div class="p-4 container">
    <div class="blog">
        <h2 class="mb-4 text-center title"><?php echo esc_html($term->name); ?></h2>
        <p class="mb-5 text-center"><?php echo esc_html($term->description); ?></p>

        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-12 col-md-12 col-lg-3">
                <?php get_template_part('template-parts/blog/blog', 'sidebar'); ?>
            </div>

            <!-- Blog Posts -->
            <div class="col-12 col-md-12 col-lg-9">
                <div class="row g-4">
                    <?php
                    if ($query->have_posts()) :
                        while ($query->have_posts()) : $query->the_post();
                            $short_details = get_post_meta(get_the_ID(), '_short_details', true);
                            $author_name = get_the_author();
                            $date = get_the_date('F j, Y');
                            $tags = get_the_terms(get_the_ID(), 'blog_tag');
                    ?>
                            <div class="col-12 col-md-6 col-lg-6">
                                <div class="">
                                    <?php if (has_post_thumbnail()) : ?>
                                        <img class="mb-3 img-fluid" src="<?php the_post_thumbnail_url('large'); ?>" alt="<?php the_title_attribute(); ?>">
                                    <?php else : ?>
                                        <img class="mb-3 img-fluid" src="<?php echo get_template_directory_uri(); ?>/images/no-image.jpg" alt="no image">
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-between gap-3 mb-2 blog-author">
                                        <p class="mb-0"><?php echo esc_html($author_name); ?></p>
                                        <p class="mb-0 date"><?php echo esc_html($date); ?></p>
                                    </div>

                                    <h5 class="mb-2 blog-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h5>

                                    <?php if ($short_details) : ?>
                                        <p class="mb-2"><?php echo esc_html($short_details); ?></p>
                                    <?php endif; ?>

                                    <?php if (!empty($tags) && !is_wp_error($tags)) : ?>
                                        <div class="d-flex flex-wrap gap-2 blog-tags">
                                            <?php foreach ($tags as $tag) : ?>
                                                <span class='blog-tag'><?php echo esc_html($tag->name); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                    <?php
                        endwhile;
                        wp_reset_postdata();
                    else :
                        echo '<p>No posts found in this category.</p>';
                    endif;
                    ?>
                </div>

                <div class="mt-4">
                    <?php
                    the_posts_pagination(array(
                        'mid_size' => 2,
                        'prev_text' => __('« Prev'),
                        'next_text' => __('Next »'),
                    ));
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if (is_user_logged_in()) {
    get_footer('portal');
} else {
    get_footer('main');
}
?>