<?php

/**
 * Template Name: Logged In Marketplace
 */
get_header_based_on_login();

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

$current_user = wp_get_current_user();

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


<div class="pb-5 container profile-page pt20">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">

            <?php get_template_part('template-custom/auth/feed-parts/profile-card', null, ['profile' => $profile]); ?>
            <?php
            /* $current_user = wp_get_current_user();
            $username = get_query_var('store_user') ?: $current_user->user_nicename;

            $terms = get_terms([
                'taxonomy' => 'course_category',
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
            ]);
            
            <div class="bg-white custom-card navbar-link">
                <ul class="d-flex flex-column gap-2 nav">
                    <!-- Browse All -->
                    <li class="d-flex align-items-center gap-2 nav-item">
                        <img src="<?= get_template_directory_uri(); ?>/assets/img/nd/empower.png" alt="empower" class="icon-img">
                        <a href="<?php echo esc_url(home_url("/$username/store")); ?>" class="p-0 text">Browse All</a>
                    </li>

                    <!-- Dynamic Category List -->
                    <?php foreach ($terms as $term): ?>
                        <li class="d-flex align-items-center gap-2 nav-item">
                            <?php
                            $icon_id = get_term_meta($term->term_id, 'term_icon', true) ??  get_template_directory_uri() . '/assets/img/marketplace/default.png';
                            if ($icon_id) {
                                echo '<img class="icon-img" src="' . esc_url(wp_get_attachment_url($icon_id)) . '" alt="' . esc_attr($term->name) . '">';
                            }
                            ?>
                            <a href="<?php echo esc_url(home_url("/$username/store?category={$term->slug}")); ?>" class="p-0 text">
                                <?php echo esc_html($term->name); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    
                </ul>
            </div>
            */
            ?>
            <?php get_template_part('template-custom/auth/profile-parts/navlink', null, ['profile' => $profile]); ?>
        </div>

        <!-- Main Feed -->
        <div class="mb-4 col-lg-9">
            <div class="custom-card post-search">
                <div class="gap-3 mb-2 post-row">
                    <div class="d-flex pb-3 u-title">
                        <h5 class="portal-title">Popular programs</h5>
                    </div>
                    <p>Browse and buy any courses from your favorite instructors</p>
                </div>
            </div>

            <div class="d-flex flex-column">
                <?php
                $store_user = get_query_var('store_user');
                $category_slug = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';

                $args = [
                    'post_type'      => 'course',
                    'posts_per_page' => -1,
                ];

                if (!empty($category_slug)) {
                    $args['tax_query'] = [
                        [
                            'taxonomy' => 'course_category',
                            'field'    => 'slug',
                            'terms'    => $category_slug,
                        ],
                    ];
                }

                $query = new WP_Query($args);

                if ($query->have_posts()) :
                    while ($query->have_posts()) :
                        $query->the_post();

                        $price = get_field('price');
                        $short_details = get_field('short_details');
                        $instructor = get_field('instructor');
                        $duration = get_field('duration');
                        $rating = get_field('rating') ?: '5.0'; // Optional rating field (default 5.0)
                        $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: get_template_directory_uri() . '/assets/img/default-course.jpg';

                        $store_user = get_query_var('store_user') ?: 'store';
                        $course_slug = get_post_field('post_name', get_the_ID());
                        $custom_permalink = home_url("/u/{$store_user}/store/{$course_slug}");
                        $shareable_link = home_url("/u/{$store_user}/store/{$course_slug}/?shareable=1");
                ?>

                        <div class="bg-white p-3 custom-card">
                            <div class="d-flex flex-column flex-md-row align-items-start gap-3">

                                <!-- Image Section -->
                                <div class="flex-shrink-0 img240">
                                    <a href="<?= esc_url($custom_permalink); ?>">
                                        <img class="w-100 h-100 object-fit-cover" src="<?= esc_url($thumbnail_url); ?>" alt="<?= esc_attr(get_the_title()); ?>">
                                    </a>
                                </div>

                                <!-- Content Section -->
                                <div class="d-flex flex-grow-1 flex-column justify-content-between content-col">
                                    <div>
                                        <span class="d-block mb-1 fs14 emp-color">
                                            <?php
                                            $terms = get_the_terms(get_the_ID(), 'course_category');
                                            if (!empty($terms) && !is_wp_error($terms)) {
                                                $term_names = wp_list_pluck($terms, 'name');
                                                echo esc_html(implode(', ', $term_names));
                                            }
                                            ?>
                                        </span>

                                        <!-- Title and Price -->
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <a href="<?= esc_url($custom_permalink); ?>" class="text-reset text-decoration-none fs18 fw-semibold">
                                                <?= esc_html(get_the_title()); ?>
                                            </a>
                                            <span class="gradient-fs24"><?= $price ? '$' . esc_html($price) : ''; ?></span>
                                        </div>

                                        <!-- Rating -->
                                        <p class="d-flex align-items-center mb-1">
                                            <img src="<?= get_template_directory_uri(); ?>/assets/img/nd/star.png" alt="Rating">
                                            <span class="ms-2 fw-medium"><?= esc_html($rating); ?></span>
                                        </p>

                                        <!-- Short Description -->
                                        <p class="mb-2">
                                            <?php
                                            $words = explode(' ', wp_strip_all_tags($short_details));
                                            $short_text = implode(' ', array_slice($words, 0, 6));
                                            echo esc_html($short_text) . (count($words) > 6 ? '...' : '');
                                            ?>
                                        </p>
                                    </div>

                                    <!-- Instructor and Duration -->
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <?php if (!empty($instructor)): ?>
                                            <p class="mb-0"><span class="fw-medium">Instructor:</span> <?= esc_html($instructor); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($duration)): ?>
                                            <p class="mb-0"><span class="fw-medium">Duration:</span> <?= esc_html($duration); ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Buttons -->
                                    <div class="d-flex justify-content-end gap-3">
                                        <button
                                            type="button"
                                            class="d-flex align-items-center gap-2 bg-transparent border-0 text-primary-color"
                                            onclick="copySharableLink('<?= esc_url($shareable_link); ?>')">
                                            <img class="copy-img" src="<?= get_template_directory_uri(); ?>/assets/img/nd/copy-link.png" alt=""> Copy link
                                        </button>
                                        <a href="<?= esc_url($custom_permalink); ?>" class="text-white text-decoration-none custom-btn-size background-primary">More</a>
                                    </div>
                                </div>

                            </div>
                        </div>

                    <?php endwhile;
                    wp_reset_postdata();
                else : ?>
                    <p>No courses found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Events -->
        <?php
        /**
        <div class="col-lg-3">
            <div class="bg-white upcoming-events custom-card">
                <div class="d-flex pb-4 u-title">
                    <h5 class="portal-title">Popular programs</h5>
                </div>

                <div class="d-flex flex-column gap-4">
                    <?php
                    $args = [
                        'post_type'      => 'course',
                        'posts_per_page' => 3,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                    ];

                    $query = new WP_Query($args);

                    if ($query->have_posts()) :
                        while ($query->have_posts()) :
                            $query->the_post();

                            $price = get_field('price');
                            $thumbnail_url = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: get_template_directory_uri() . '/assets/img/default-course.jpg';

                            $terms = get_the_terms(get_the_ID(), 'course_category');
                            $category_name = '';
                            if (!empty($terms) && !is_wp_error($terms)) {
                                $category_name = implode(', ', wp_list_pluck($terms, 'name'));
                            }

                            $store_user = get_query_var('store_user') ?: 'store';
                            $course_slug = get_post_field('post_name', get_the_ID());
                            $custom_permalink = home_url("/u/{$store_user}/store/{$course_slug}");
                    ?>

                            <div class="d-flex xalign-items-center gap-3 pb-3 event">
                                <div class="img44-box">
                                    <a href="<?= esc_url($custom_permalink); ?>">
                                        <img class="w-100 h-100 object-fit-cover"
                                            src="<?= esc_url($thumbnail_url); ?>"
                                            alt="<?= esc_attr(get_the_title()); ?>">
                                    </a>
                                </div>

                                <div class="d-flex flex-column">
                                    <?php if ($category_name): ?>
                                        <span class="fs12 emp-color"><?= esc_html($category_name); ?></span>
                                    <?php endif; ?>

                                    <a href="<?= esc_url($custom_permalink); ?>" class="text-reset text-decoration-none lh-1">
                                        <span class="fs16"><?= esc_html(get_the_title()); ?></span>
                                    </a>

                                    <?php if ($price): ?>
                                        <span class="p-price fs14">Price: $<?= esc_html($price); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                        <?php endwhile;
                        wp_reset_postdata();
                    else : ?>
                        <p>No courses found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        **/ ?>

    </div>
</div>

<script>
    function copySharableLink(link) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(link)
                .then(() => {
                    Toastify({
                        text: "Link copied to clipboard!",
                        duration: 3000,
                        gravity: "bottom", // top or bottom
                        position: "left", // left, center, right
                        backgroundColor: "#4CAF50",
                    }).showToast();
                })
                .catch(err => {
                    Toastify({
                        text: "Copy failed: " + err,
                        duration: 3000,
                        gravity: "bottom",
                        position: "left",
                        backgroundColor: "#f44336",
                    }).showToast();
                });
        } else {
            // Fallback
            const textarea = document.createElement("textarea");
            textarea.value = link;
            textarea.style.position = "fixed"; // Avoid scrolling to bottom
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            try {
                document.execCommand('copy');
                Toastify({
                    text: "Link copied to clipboard!",
                    duration: 3000,
                    gravity: "bottom",
                    position: "left",
                    backgroundColor: "#4CAF50",
                }).showToast();
            } catch (err) {
                Toastify({
                    text: "Copy failed (fallback): " + err,
                    duration: 3000,
                    gravity: "bottom",
                    position: "left",
                    backgroundColor: "#f44336",
                }).showToast();
            }
            document.body.removeChild(textarea);
        }
    }
</script>

<?php get_footer_based_on_login(); ?>