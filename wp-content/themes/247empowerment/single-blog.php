<?php get_header_based_on_login(); ?>

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
                        if (have_posts()) :
                            while (have_posts()) : the_post();
                                $short_details = get_post_meta(get_the_ID(), '_short_details', true);
                                $author = get_the_author();
                                $date = get_the_date('F j, Y');
                        ?>
                                <article class="mb-5">
                                    <!-- Featured Image -->
                                    <?php if (has_post_thumbnail()) : ?>
                                        <img src="<?php the_post_thumbnail_url('large'); ?>" class="mb-3 rounded img-fluid" alt="<?php the_title_attribute(); ?>">
                                    <?php endif; ?>

                                    <!-- Title -->
                                    <h1 class="mb-2"><?php the_title(); ?></h1>

                                    <!-- Author + Date -->
                                    <div class="d-flex justify-content-between mb-3 text-muted">
                                        <span><?php echo esc_html($author); ?></span>
                                        <span><?php echo esc_html($date); ?></span>
                                    </div>

                                    <!-- Short Details -->
                                    <?php if ($short_details) : ?>
                                        <p class="text-muted"><?php echo esc_html($short_details); ?></p>
                                    <?php endif; ?>

                                    <!-- Content -->
                                    <div class="blog-content">
                                        <?php the_content(); ?>
                                    </div>

                                    <!-- Tags -->
                                    <?php
                                    $tags = get_the_terms(get_the_ID(), 'blog_tag');
                                    if (!empty($tags) && !is_wp_error($tags)) :
                                    ?>
                                        <div class="mt-4">
                                            <?php foreach ($tags as $tag) : ?>
                                                <span class="bg-light me-1 border text-dark badge"><?php echo esc_html($tag->name); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </article>

                        <?php
                            endwhile;
                        else :
                            echo '<p>No blog post found.</p>';
                        endif;
                        ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php get_footer_based_on_login(); ?>