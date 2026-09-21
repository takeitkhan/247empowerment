<?php

/**
 * Search results for blog post_type
 * Filename: search-blog.php
 */

get_header_based_on_login();

// Grab search term and current paged value (for paginate_links)
$search_term = get_search_query();
$paged = (get_query_var('paged')) ? absint(get_query_var('paged')) : 1;

// Build a custom query to ensure only 'blog' post type is returned
$args = [
    'post_type'      => 'blog',
    's'              => $search_term,
    'posts_per_page' => 6,
    'paged'          => $paged,
];

$search_query = new WP_Query($args);
?>

<section class="bg-light py-5">
    <div class="container">
        <div class="mb-5 text-center">
            <h2 class="mb-2 title fw-bold">Empowerment Insights</h2>
            <p class="text-muted">Subscribe to get the latest stories and insights straight to your inbox.</p>
        </div>

        <div class="row g-5">
            <!-- Sidebar -->
            <aside class="col-lg-4">
                <!-- Search -->
                <div>
                    <div class="d-flex flex-column flex-md-row align-items-center gap-3 mb-2">
                        <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="d-flex flex-column flex-md-row gap-3 w-100">
                            <input type="search" name="s" class="w-100 input" placeholder="Search for the article..." value="<?php echo esc_attr($search_term); ?>">
                            <input type="hidden" name="post_type" value="blog">
                            <div class="c-btn">
                                <button type="submit" class="w-100 custom-btn">Search</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Categories -->
                <div class="bg-white shadow-sm p-4 rounded">
                    <h5 class="mb-3 fw-semibold">Categories</h5>
                    <ul class="m-0 list-unstyled">
                        <?php
                        $categories = get_terms([
                            'taxonomy'   => 'blog_category',
                            'hide_empty' => true,
                        ]);

                        if (! empty($categories) && ! is_wp_error($categories)) :
                            foreach ($categories as $cat) : ?>
                                <li class="mb-2">
                                    <a href="<?php echo esc_url(get_term_link($cat)); ?>" class="d-flex align-items-center text-dark text-decoration-none">
                                        <img src="<?php echo esc_url(get_template_directory_uri() . '/images/empower.png'); ?>" alt="" class="me-2" style="width:18px;height:18px;">
                                        <?php echo esc_html($cat->name); ?>
                                    </a>
                                </li>
                        <?php endforeach;
                        else :
                            echo '<li class="text-muted">No categories found.</li>';
                        endif;
                        ?>
                    </ul>
                </div>
            </aside>

            <!-- Blog Posts -->
            <div class="col-lg-8">
                <h4 class="mb-4 text-center">Search Results for "<?php echo esc_html($search_term); ?>"</h4>

                <div class="row g-4">
                    <?php
                    if ($search_query->have_posts()) {
                        while ($search_query->have_posts()) : $search_query->the_post();
                            $short_details = get_post_meta(get_the_ID(), '_short_details', true);
                            $author = get_the_author();
                            $date = get_the_date('F j, Y');
                    ?>
                            <div class="col-md-6">
                                <div class="shadow-sm border-0 h-100 card">
                                    <a href="<?php the_permalink(); ?>" class="d-block">
                                        <?php if (has_post_thumbnail()) : ?>
                                            <img src="<?php the_post_thumbnail_url('large'); ?>" class="card-img-top rounded-top" alt="<?php the_title_attribute(); ?>">
                                        <?php else : ?>
                                            <img src="<?php echo esc_url(get_template_directory_uri() . '/images/no-image.jpg'); ?>" class="card-img-top rounded-top" alt="No image">
                                        <?php endif; ?>
                                    </a>

                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-2 text-muted small">
                                            <span><?php echo esc_html($author); ?></span>
                                            <span><?php echo esc_html($date); ?></span>
                                        </div>

                                        <h5 class="card-title">
                                            <a href="<?php the_permalink(); ?>" class="text-dark text-decoration-none fw-semibold">
                                                <?php the_title(); ?>
                                            </a>
                                        </h5>

                                        <?php if ($short_details) : ?>
                                            <p class="mb-3 text-muted card-text small"><?php echo esc_html($short_details); ?></p>
                                        <?php endif; ?>

                                        <?php
                                        $tags = get_the_terms(get_the_ID(), 'blog_tag');
                                        if (! empty($tags) && ! is_wp_error($tags)) :
                                        ?>
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php foreach ($tags as $tag) : ?>
                                                    <span class="bg-light border text-dark badge"><?php echo esc_html($tag->name); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                    <?php
                        endwhile;

                        // Pagination (use paginate_links to link to the search URL with s + post_type)
                        $big = 999999999; // need an unlikely integer
                        $pagination = paginate_links([
                            'base'      => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                            'format'    => '?paged=%#%',
                            'current'   => max(1, $paged),
                            'total'     => $search_query->max_num_pages,
                            'type'      => 'array',
                            'add_args'  => [
                                's'         => $search_term,
                                'post_type' => 'blog',
                            ],
                            'prev_text' => __('« Prev'),
                            'next_text' => __('Next »'),
                        ]);

                        if (is_array($pagination)) {
                            echo '<nav class="mt-4"><ul class="justify-content-center pagination">';
                            foreach ($pagination as $page_link) {
                                // wrap each link in <li class="page-item"> and keep classes from WP
                                $page_link = str_replace('page-numbers', 'page-link', $page_link);
                                echo '<li class="page-item">' . $page_link . '</li>';
                            }
                            echo '</ul></nav>';
                        }
                    } else {
                        echo '<p class="mt-5 text-muted text-center">No blog posts found for that search.</p>';
                    }

                    // Reset postdata
                    wp_reset_postdata();
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php get_footer_based_on_login(); ?>