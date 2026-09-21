<?php get_header_based_on_login(); ?>

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
                <div class="bg-white shadow-sm mb-4 p-4 rounded">
                    <h5 class="mb-3 fw-semibold">Search Articles</h5>
                    <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                        <div class="input-group">
                            <input type="search" name="s" class="form-control" placeholder="Search for the article...">
                            <input type="hidden" name="post_type" value="blog">
                            <button type="submit" class="px-4 btn btn-primary">Search</button>
                        </div>
                    </form>
                </div>

                <!-- Categories -->
                <div class="bg-white shadow-sm p-4 rounded">
                    <h5 class="mb-3 fw-semibold">Categories</h5>
                    <ul class="m-0 list-unstyled">
                        <?php
                        $categories = get_terms([
                            'taxonomy' => 'blog_category',
                            'hide_empty' => true,
                        ]);

                        if (!empty($categories) && !is_wp_error($categories)) :
                            foreach ($categories as $cat) :
                        ?>
                                <li class="mb-2">
                                    <a href="<?php echo esc_url(get_term_link($cat)); ?>" class="d-flex align-items-center text-dark text-decoration-none">
                                        <img src="<?php echo get_template_directory_uri(); ?>/images/empower.png" alt="" class="me-2" style="width:18px;height:18px;">
                                        <?php echo esc_html($cat->name); ?>
                                    </a>
                                </li>
                        <?php
                            endforeach;
                        else :
                            echo '<li class="text-muted">No categories found.</li>';
                        endif;
                        ?>
                    </ul>
                </div>
            </aside>

            <!-- Blog Posts -->
            <div class="col-lg-8">
                <?php if (is_search()) : ?>
                    <h4 class="mb-4 text-center">Search Results for "<?php echo esc_html(get_search_query()); ?>"</h4>
                <?php endif; ?>

                <div class="row g-4">
                    <?php
                    if (have_posts()) :
                        while (have_posts()) : the_post();
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
                                            <img src="<?php echo get_template_directory_uri(); ?>/images/no-image.jpg" class="card-img-top rounded-top" alt="No image">
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
                                        if (!empty($tags) && !is_wp_error($tags)) :
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

                        echo '<div class="mt-4">';
                        the_posts_pagination([
                            'mid_size' => 2,
                            'prev_text' => __('« Prev'),
                            'next_text' => __('Next »'),
                        ]);
                        echo '</div>';

                    else :
                        echo '<p class="mt-5 text-muted text-center">No blog posts found.</p>';
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php get_footer_based_on_login(); ?>