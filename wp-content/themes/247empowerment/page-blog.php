<?php

/**
 * Template Name: Custom Blog Page
 */
get_header_based_on_login();
?>
<?php
// Fix for Elementor: Add the primary page loop to render the_content
if ( have_posts() ) :
    while ( have_posts() ) : the_post();
        // Elementor requires this function to inject its page data without throwing an error
        the_content();
    endwhile;
endif;
?>
<div class="p-4 container">
    <div class='blog'>
        <div>
            <h2 class='mb-4 text-center title'>Empowerment Insights</h2>
            <p class='text-center'>Subscribe to get the latest stories and insights straight to your inbox.</p>

            <div class="mt-3 mb-2 row g-4">

                <!-- Sidebar -->
                <div class="col-12 col-md-12 col-lg-3">
                    <?php get_template_part('template-parts/blog/blog', 'sidebar'); ?>
                </div>

                <!-- Blog Posts List -->
                <div class="col-12 col-md-12 col-lg-9">
                    <div class="row g-4">
                        <?php
                        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
                        $query = new WP_Query([
                            'post_type' => 'blog',
                            'posts_per_page' => 6,
                            'paged' => $paged,
                        ]);

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
                            echo '<p>No blog posts found.</p>';
                        endif;
                        ?>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        <?php
                        the_posts_pagination([
                            'mid_size' => 2,
                            'prev_text' => __('« Prev'),
                            'next_text' => __('Next »'),
                        ]);
                        ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php get_footer_based_on_login(); ?>