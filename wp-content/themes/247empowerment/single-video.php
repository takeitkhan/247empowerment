<?php get_header_based_on_login(); ?>

<div class="p-4 container">
    <div class='video-page'>
        <div>
            <h2 class='mb-4 text-center title'>Video Library</h2>
            <p class='text-center'>Watch inspiring & uplifting empowerment videos.</p>

            <div class="mt-3 mb-2 row g-4">
                <!-- Video Content -->
                <div class="col-12 col-md-12 col-lg-12">
                    <div class="row g-4">
                        <?php
                        if (have_posts()) :
                            while (have_posts()) : the_post();

                                $video_url   = get_post_meta(get_the_ID(), '_video_url', true);
                                $duration    = get_post_meta(get_the_ID(), '_video_duration', true);
                                $is_featured = get_post_meta(get_the_ID(), '_video_featured', true);

                                $playlists = get_the_terms(get_the_ID(), 'video_playlist');
                                $topics    = get_the_terms(get_the_ID(), 'video_topic');

                                $date = get_the_date('F j, Y');

                                // Convert URL to embed
                                function mm_embed_video($url) {
                                    if (strpos($url, 'youtube') !== false || strpos($url, 'youtu') !== false) {
                                        $id = preg_replace('/.*v=|.*youtu.be\//', '', $url);
                                        return 'https://www.youtube.com/embed/' . esc_attr($id);
                                    }

                                    if (strpos($url, 'vimeo') !== false) {
                                        $id = (int) substr(parse_url($url, PHP_URL_PATH), 1);
                                        return 'https://player.vimeo.com/video/' . esc_attr($id);
                                    }

                                    return $url; // fallback
                                }

                                $embed_url = $video_url ? mm_embed_video($video_url) : null;
                        ?>

                                <article class="mb-5">

                                    <!-- Video Embed -->
                                    <?php if ($embed_url): ?>
                                        <div class="mb-3 ratio ratio-16x9">
                                            <iframe src="<?php echo esc_url($embed_url); ?>" allowfullscreen></iframe>
                                        </div>
                                    <?php elseif (has_post_thumbnail()): ?>
                                        <img src="<?php the_post_thumbnail_url('large'); ?>" class="mb-3 rounded img-fluid" alt="<?php the_title_attribute(); ?>">
                                    <?php endif; ?>

                                    <!-- Title -->
                                    <h1 class="mb-2"><?php the_title(); ?></h1>

                                    <!-- Meta (duration, date, featured) -->
                                    <div class="d-flex justify-content-between mb-3 text-muted">
                                        <span><?php echo esc_html($date); ?></span>
                                        <?php if ($duration): ?>
                                            <span>⏱ <?php echo esc_html($duration); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($is_featured): ?>
                                        <span class="bg-danger mb-3 badge">Featured Video</span>
                                    <?php endif; ?>

                                    <!-- Description -->
                                    <div class="video-content">
                                        <?php the_content(); ?>
                                    </div>

                                    <!-- Playlists -->
                                    <?php if (!empty($playlists) && !is_wp_error($playlists)): ?>
                                        <h5 class="mt-4">Playlists</h5>
                                        <div class="mb-2">
                                            <?php foreach ($playlists as $list): ?>
                                                <span class="bg-light me-1 border text-dark badge">
                                                    <?php echo esc_html($list->name); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Topics -->
                                    <?php if (!empty($topics) && !is_wp_error($topics)): ?>
                                        <h5 class="mt-3">Topics</h5>
                                        <div>
                                            <?php foreach ($topics as $topic): ?>
                                                <span class="bg-light me-1 border text-dark badge">
                                                    <?php echo esc_html($topic->name); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                </article>

                        <?php
                            endwhile;
                        else :
                            echo '<p>No video found.</p>';
                        endif;
                        ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php get_footer_based_on_login(); ?>
