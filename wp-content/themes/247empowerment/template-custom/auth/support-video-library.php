<?php

/**
 * Template Name: Portal Video Library Page
 * Custom Video Library Page Template
 */
if (!is_user_logged_in()) {
    wp_redirect(home_url('/signin'));
    exit;
}
get_header_based_on_login();

// Get current logged-in user ID (used as a fallback if no slug is provided)
$current_user_id = get_current_user_id();

// 1. Get the user slug from the query variable
$user_slug = get_query_var('user_profile');

// 2. Determine the target user
if ($user_slug) {
    // If a slug is present, try to get the user by their slug (login or nicename)
    $user = get_user_by('slug', $user_slug);
} else {
    // If no slug, fall back to the currently logged-in user
    $user = get_user_by('ID', $current_user_id);
}

// 3. Instantiate the UserProfileData class and get the profile array
if ($user) {
    // We pass the WP_User object to the class constructor, or the ID/slug depending on the class's constructor.
    // Given your original line: $profile = (new UserProfileData($user_slug))->getProfile();
    // We'll update it to pass the $user object for better data handling, assuming the class supports it.
    // If the class REQUIRES a slug, use $user_slug or $user->user_login.

    // Option A: If UserProfileData takes a WP_User object (Recommended)
    $profile_data_instance = new UserProfileData($user);

    // Option B: If UserProfileData only takes the slug (Sticking closer to your original code)
    // Use the slug if present, otherwise use the current user's login.
    $target_identifier = $user_slug ? $user_slug : $user->user_login;
    $profile_data_instance = new UserProfileData($target_identifier);

    // Get the profile array
    $profile = $profile_data_instance->getProfile();
} else {
    // Set variables to null if no user could be determined
    $user = null;
    $profile = null;
}
?>

<div class="container profile-page pt20">
    <div class="row">
        <div class="col-lg-3">
            <?php get_template_part('template-custom/auth/feed-parts/profile-card', null, ['profile' => $profile]); ?>
            <?php get_template_part('template-custom/auth/profile-parts/navlink', null, ['profile' => $profile]); ?>
        </div>
        <div class="col-lg-9">
            <div class="mb-5">
                <!-- PAGE TITLE -->
                <div class="mb-4 text-start">
                    <h2 class="fs48 title">Watch. Learn. Empower.</h2>
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

                <?php
                /**
                <!-- FEATURED THIS WEEK -->
                <div class="mt-5">
                    <h4 class="fs24 fw-bold">Featured this week</h4>
                    <div class="mt-4 row g-4">
                        <?php
                        $featured_list = new WP_Query([
                            'post_type'      => 'video',
                            'meta_key'       => '_video_featured',
                            'meta_value'     => 1,
                            'posts_per_page' => 2,
                            'offset'         => 1
                        ]);

                        while ($featured_list->have_posts()) :
                            $featured_list->the_post();
                            $youtube_playlist_url = get_post_meta(get_the_ID(), '_playlist_url', true);
                            $embed_url = mm_extract_youtube_embed($youtube_playlist_url);
                            $thumb = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                            $total_videos = get_post_meta(get_the_ID(), '_playlist_total_videos', true);
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

                                            <span class="video-count"><?php echo esc_html($total_videos); ?> videos</span>
                                        </div>
                                        <h5 class="mt-2"><?php the_title(); ?></h5>
                                    </a>
                                </div>
                            </div>
                        <?php endwhile;
                        wp_reset_postdata(); ?>
                    </div>
                </div>

                 */
                ?>

                <!-- PLAYLISTS SECTION -->
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
        </div>
    </div>
</div>
<?php get_footer_based_on_login(); ?>