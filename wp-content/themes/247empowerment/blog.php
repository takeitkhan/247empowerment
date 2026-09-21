<?php
/**
 * Template Name: Blog Page
 * 
 * The template for displaying the Faq of the Life Cube theme.
 * 
 * @package Life Cube
 * @author Your Name
 * @version 1.0
 */
if (is_user_logged_in()) {
    get_header('portal');
} else {
    get_header('main');
}
?>
 <!-- owl slider script -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.3.3/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/owl-carousel/1.3.3/owl.theme.default.min.css">
    <!-- owl crousal -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.2.1/assets/owl.carousel.min.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.2.1/assets/owl.theme.default.min.css">
<?php
    if ( have_posts() ) :
        while ( have_posts() ) :
            the_post();
            the_content();?>
                 <section id="blog-listing" class="pb-0 blog-listing card-sec-padding white-bg">
    <div class="blog-list-wrapper container">
        <div class="flex articles-card-row">
            <?php
            $args = array(
                'post_type' => 'post',
                'category_name' => 'blog',
                'posts_per_page' => -1, // Load all posts
            );
            $query = new WP_Query($args);
            if ($query->have_posts()) :
                while ($query->have_posts()) : $query->the_post(); ?>
                    <div class="article-list-box">
                        <div class="articles-card">
                            <?php if (has_post_thumbnail()) : ?>
                                <img src="<?php the_post_thumbnail_url(); ?>" alt="<?php the_title(); ?>">
                            <?php endif; ?>
                            <div class="artist-content">
                                <div class="flex date-box">
                                <p><a href="<?php the_permalink(); ?>"><img decoding="async" src="/wp-content/uploads/2024/08/featured-col-small-1.png" alt="blog"></a></p>
                                    <p class="date"><?php echo get_the_date(); ?></p>
                                </div>
                                <a href="<?php the_permalink(); ?>">
                                    <h3><?php the_title(); ?></h3>
                                </a>
                                <?php if (get_field('description')) : ?>
                                    <p><?php the_field('description'); ?></p>
                                <?php else : ?>
                                    <p><?php _e('No description available.', 'textdomain'); ?></p>
                                <?php endif; ?>
                                <a href="<?php the_permalink(); ?>" class="mt-20 btn-blue">
                                    <svg width="40px" height="10px" viewBox="0 0 40 10">
                                        <path d="M1,5 L36,5"></path>
                                        <polyline points="33 1 37 5 33 9"></polyline>
                                    </svg>
                                    <span>Read More</span>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile;
                wp_reset_postdata();
            else : ?>
                <p><?php _e('No posts found in the blog category.', 'textdomain'); ?></p>
            <?php endif; ?>
        </div>
        <!-- Pagination Controls -->
        <div class="flex pagination-controls">
            <button class="prev-page" disabled=""><span>← </span> Previous</button>
            <div class="page-numbers"></div>
            <button class="next-page" disabled="">Next<span>→ </span></button>
        </div>
    </div>
</section>   

        <?php endwhile;
    else :
        echo '<p>No content found</p>';
    endif;
    ?>
<script>
        // pagination
        $(document).ready(function () {
          const itemsPerPage = 5;
          const totalItems = $('.article-list-box').length;
          const totalPages = Math.ceil(totalItems / itemsPerPage);
          let currentPage = 1;
    
          function showPage(page) {
            const start = (page - 1) * itemsPerPage;
            const end = start + itemsPerPage;
    
            $('.article-list-box').hide().slice(start, end).show();
    
            // Update active page button
            $('.page-numbers button').removeClass('active');
            $(`.page-numbers button[data-page="${page}"]`).addClass('active');
    
            // Disable/enable buttons
            $('.prev-page').prop('disabled', page === 1);
            $('.next-page').prop('disabled', page === totalPages);
          }
    
          function createPageNumbers() {
            for (let i = 1; i <= totalPages; i++) {
              $('.page-numbers').append(`<button data-page="${i}">${i}</button>`);
            }
          }
    
          // Create page numbers and set up click event
          createPageNumbers();
          $('.page-numbers').on('click', 'button', function () {
            currentPage = $(this).data('page');
            showPage(currentPage);
          });
    
          $('.prev-page').click(function () {
            if (currentPage > 1) {
              currentPage--;
              showPage(currentPage);
            }
          });
    
          $('.next-page').click(function () {
            if (currentPage < totalPages) {
              currentPage++;
              showPage(currentPage);
            }
          });
    
          // Initial call to show the first page
          showPage(currentPage);
    
        });
      </script>

<?php
if (is_user_logged_in()) {
    get_footer('portal'); // loads footer-custom.php
} else {
    get_footer('main'); // loads footer-main.php
}
?>