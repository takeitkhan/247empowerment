<?php

/**
 * Template Name: Login Router
 */
if (is_user_logged_in()) {
    include get_template_directory() . '/template-custom/auth/home.php';
} else {
    // For logged out users, show the page content (Gutenberg blocks, etc.)
    get_header_based_on_login();
    ?>
    <main>
        <?php
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

            <div class="container">
                <div class="xcustom-box-shadow mb-3 pt-3 xcustom-border-radius xbg-white">
                    <div class="wp-block-image wp-block-paragraph xwp-block-quote px-2 py-4">
                        <?php the_content(); ?>
                    </div>
                </div>
            </div>
        <?php
        endwhile;
        ?>
    </main>
    <?php
    get_footer_based_on_login();
}
