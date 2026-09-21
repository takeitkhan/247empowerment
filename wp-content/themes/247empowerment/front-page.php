<?php
/**
 * Front Page Template
 * Shows header, homepage content, and footer
 */

get_header_based_on_login();
?>

<main id="primary" class="mt-5 site-main container">
    <?php
    if (have_posts()):
        while (have_posts()): 
            the_post();
            
            $hide_title = get_post_meta(get_the_ID(), 'hide_title', true);
            if (!$hide_title && get_the_title()) {
                echo '<h1 class="mb-4 page-title">' . esc_html(get_the_title()) . '</h1>';
            }
            
            // Render page content
            the_content();
            
        endwhile;
    else:
        echo '<p>No content found for this page.</p>';
    endif;
    ?>
</main>

<?php
get_footer_based_on_login();