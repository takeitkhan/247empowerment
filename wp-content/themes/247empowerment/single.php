<?php get_header_based_on_login(); ?>

<?php while (have_posts()) : the_post(); ?>
    <h1><?php the_title(); ?></h1>
    <?php the_content(); ?>
<?php endwhile; ?>

<?php get_footer_based_on_login(); ?>