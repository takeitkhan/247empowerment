<?php

/**
 * Template Name: Reputation
 * Custom Reputation Page Template
 */
if (is_user_logged_in()) {
    get_header('portal');
} else {
    get_header('main');
}
?>

<main>
    <div class="main-container" style="padding-top: 80px;">
        <div class="bg-white custom-box-shadow mb-3 p-3 custom-border-radius">
            <h1 class="mb-4 text-base text-lg"><?php the_title(); ?></h1>
            <div>
                <?php while (have_posts()) : the_post(); ?>

                    <?php
                    // Check if the 'hide_title' custom field is set (truthy)
                    $hide_title = get_post_meta(get_the_ID(), 'hide_title', true);
                    ?>

                    <div class="col-12">
                        <?php if (!$hide_title) : ?>

                        <?php endif; ?>

                        <?php the_content(); ?>
                    </div>

                <?php endwhile; ?>
            </div>
        </div>
    </div>
</main>

<?php
if (is_user_logged_in()) {
    get_footer('portal');
} else {
    get_footer('main');
}
?>