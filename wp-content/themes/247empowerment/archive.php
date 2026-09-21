<?php get_header_based_on_login(); ?>

<h1>Archive</h1>
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
        <h2><?php the_title(); ?></h2>
        <?php the_excerpt(); ?>
<?php endwhile;
endif; ?>

<?php get_footer_based_on_login(); ?>