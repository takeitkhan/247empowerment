<?php

/**
 * Template Name: Video Library Page
 */

get_header_based_on_login();
?>
<?php
// Fix for Elementor: Add the primary page loop to render the_content
if ( have_posts() ) :
    while ( have_posts() ) : the_post();
        // Elementor requires this function to inject its page data without throwing an error
        the_content();
    endwhile;
endif;
?>
<div class="mb-5 container1030 pt20">
    <!-- PAGE TITLE -->
    <div class="mb-4 text-center">
        <h3 class="fs48">Watch. Learn. Empower.</h3>
        <p>Curated videos to uplift and inspire our community.</p>
    </div>

    <!-- MAIN FEATURED PLAYLIST -->
    <div class="video-responsive-fixed-height mb-5">
        <?php
        $top_featured_query = new WP_Query([
            'post_type'      => 'video',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'   => '_video_top_featured',
                    'value' => '1',
                ]
            ],
        ]);



        if ($top_featured_query->have_posts()) :
            $top_featured_query->the_post();

            $playlist_url = get_post_meta(get_the_ID(), '_playlist_url', true);
            $embed_url    = mm_extract_youtube_embed($playlist_url);
        ?>
            <iframe width="100%" height="100%"
                src="<?php echo esc_url($embed_url); ?>"
                frameborder="0"
                allowfullscreen>
            </iframe>

            <p class="mt-2 text-center">Top Featured</p>

        <?php
        endif;
        wp_reset_postdata();
        ?>
    </div>

    <!-- ALL VIDEOS SECTION -->
    <div class="mt-5">
        <h4 class="fs24 fw-bold">All Videos</h4>
        <div class="mt-4 row g-4">
            <?php
            $all_videos = new WP_Query([
                'post_type'      => 'video',
                'posts_per_page' => -1,
                'orderby'        => 'date',
                'order'          => 'DESC'
            ]);

            while ($all_videos->have_posts()) : $all_videos->the_post();
                $youtube_playlist_url = get_post_meta(get_the_ID(), '_playlist_url', true);
                $embed_url            = mm_extract_youtube_embed($youtube_playlist_url);
                $thumb                = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                $total_videos         = get_post_meta(get_the_ID(), '_playlist_total_videos', true);
            ?>
                <div class="col-md-6 col-sm-6">
                    <div class="yt-playlist">
                        <a href="<?php echo esc_url($youtube_playlist_url); ?>" target="_blank">
                            <div class="thumb-wrapper">
                                <div class="t-wrapper1"></div>
                                <div class="t-wrapper2"></div>

                                <?php if ($thumb) : ?>
                                    <img src="<?php echo esc_url($thumb); ?>" width="100%" height="100%">
                                <?php else : ?>
                                    <iframe width="100%" height="100%" src="<?php echo esc_url($embed_url); ?>" frameborder="0" allowfullscreen></iframe>
                                <?php endif; ?>

                                <?php if ($total_videos) : ?>
                                    <span class="video-count"><?php echo esc_html($total_videos); ?> videos</span>
                                <?php endif; ?>
                            </div>
                            <h5 class="mt-2"><?php the_title(); ?></h5>
                        </a>
                    </div>
                </div>
            <?php endwhile;
            wp_reset_postdata(); ?>
        </div>
    </div>


</div>

<?php get_footer_based_on_login(); ?>