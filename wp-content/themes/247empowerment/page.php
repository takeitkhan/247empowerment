<?php get_header_based_on_login(); ?>

<main>
    <?php
    // DEBUG: Check if we have posts
    if ( WP_DEBUG ) {
        error_log( 'DEBUG page.php - is_front_page: ' . (is_front_page() ? 'true' : 'false') );
        error_log( 'DEBUG page.php - have_posts: ' . (have_posts() ? 'true' : 'false') );
        error_log( 'DEBUG page.php - post_count: ' . get_the_ID() );
    }
    
    while (have_posts()) :
        the_post();
    ?>
        <?php if (has_post_thumbnail()) : ?>
            <div class="position-relative page-header">
                <?php the_post_thumbnail('full', [
                    'class' => 'w-100 img-fluid',
                    'style' => 'height:auto;'
                ]); ?>

                <div class="page-header-overlay">
                    <h1 class="page-title"><?php the_title(); ?></h1>
                </div>
            </div>
        <?php else : ?>
            <div class="container">
                <h1 class="pt-4 text-center page-title"><?php the_title(); ?></h1>
            </div>
        <?php endif; ?>

        <?php
        // Check if we are on the front/home page
        $is_home = is_front_page() || is_home();

        // Conditionally assign the classes
        $container_classes = $is_home ? 'mb-3 pt-3 xcustom-border-radius xbg-white' : 'mb-3 p-3 xcustom-border-radius xbg-white';
        $content_classes   = $is_home ? 'wp-block-image wp-block-paragraph xwp-block-quote px-2 py-4' : 'wp-block-image wp-block-paragraph xwp-block-quote px-2 py-4';
        ?>

        <div class="container">
            <div class="xcustom-box-shadow <?php echo esc_attr($container_classes); ?>">
                <div class="<?php echo esc_attr($content_classes); ?>">
                    <?php the_content(); ?>
                </div>
            </div>
        </div>
    <?php
    endwhile;
    ?>
</main>

<?php get_footer_based_on_login(); ?>