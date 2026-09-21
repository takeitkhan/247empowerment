<?php
/* -------------------------------------------------
 * Resolve user & profile
 * ------------------------------------------------- */

$user_slug = get_query_var('user_profile');
$user      = get_user_by('slug', $user_slug);

if (!$user) {
    wp_die('User not found');
}

$profile = (new UserProfileData($user_slug))->getProfile();

/* -------------------------------------------------
 * Basic profile fields
 * ------------------------------------------------- */
$profile_img = esc_url(
    get_user_meta($user->ID, 'profile_photo', true)
        ?: get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg'
);

$full_name = esc_html(trim(
    ($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')
));

$designation = esc_html($profile['designation'] ?? 'No designation provided.');
$digital_card_about = esc_html($profile['digital_card_about'] ?? 'No bio provided.');
$place_display_name = esc_html($profile['place_display_name'] ?? 'Location not available.');
$show_full_address = esc_html($profile['show_full_address'] ?? 0);

$keywords = $profile['keywords']['list'] ?? [];
$hashtags = $profile['hashtags']['list'] ?? [];

/* -------------------------------------------------
 * Industry
 * ------------------------------------------------- */
if (!empty($profile['user_category_names'])) {
    $industry = is_array($profile['user_category_names'])
        ? implode(', ', array_map('esc_html', $profile['user_category_names']))
        : esc_html($profile['user_category_names']);
} else {
    $industry = 'Not specified';
}

/* -------------------------------------------------
 * SEO NORMALIZATION (✅ correct placement)
 * ------------------------------------------------- */

// Page URL
$page_url = home_url('/' . sanitize_title($profile['display_name'] ?? $user_slug));

// Title
$page_title = $full_name ?: get_bloginfo('name');

// Description priority
$base_description = trim(
    $profile['digital_card_about']
        ?? $profile['about_me_short']
        ?? ''
);

if (!$base_description) {
    $base_description = 'View my professional profile.';
}

// Trim for SEO (≈160 chars)
$page_description = wp_trim_words($base_description, 28, '');

// Build keyword list
$keyword_list = [];

// Keywords
if (is_array($keywords)) {
    $keyword_list = array_merge($keyword_list, $keywords);
}

// Hashtags → plain keywords
if (is_array($hashtags)) {
    foreach ($hashtags as $tag) {
        $tag = ltrim($tag, '#');
        if ($tag) {
            $keyword_list[] = $tag;
        }
    }
}

// Add industry & designation
if ($industry && $industry !== 'Not specified') {
    $keyword_list[] = $industry;
}
if ($designation && $designation !== 'No designation provided.') {
    $keyword_list[] = $designation;
}

// Cleanup
$keyword_list  = array_unique(array_filter(array_map('sanitize_text_field', $keyword_list)));
$meta_keywords = implode(', ', $keyword_list);

use Endroid\QrCode\Builder\Builder;

require_once get_template_directory() . '/vendor/autoload.php';

$shareable_link = $profile['shareable_link'] ?? '';
$qrDataUri = null;

if ($shareable_link) {
    $result = Builder::create()
        ->data($shareable_link)
        ->size(300)
        ->margin(10)
        ->build();

    $qrDataUri = $result->getDataUri();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Guest Profile - <?php bloginfo('name'); ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php
    // Assuming $profile is your user data array
    $display_name = sanitize_title($profile['display_name'] ?? 'guest'); // fallback to 'guest'
    $site_url = home_url(); // e.g., http://pet.test

    // Construct values for meta tags
    $page_url = "{$site_url}/{$display_name}";
    $page_description = $profile['digital_card_about'] ?: 'Welcome to my profile.';
    $page_title = $profile['first_name'] . ' ' . $profile['last_name'];
    $page_image = $profile['profile_photo'] ?: get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg';
    ?>

    <!-- Canonical -->
    <link rel="canonical" href="<?php echo esc_url($page_url); ?>" />

    <!-- SEO Meta -->
    <meta name="description" content="<?php echo esc_attr($page_description); ?>" />
    <?php if (!empty($meta_keywords)) : ?>
        <meta name="keywords" content="<?php echo esc_attr($meta_keywords); ?>" />
    <?php endif; ?>

    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo esc_attr($page_title); ?>" />
    <meta property="og:description" content="<?php echo esc_attr($page_description); ?>" />
    <meta property="og:image" content="<?php echo esc_url($page_image); ?>" />
    <meta property="og:type" content="profile" />
    <meta property="og:url" content="<?php echo esc_url($page_url); ?>" />

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?php echo esc_attr($page_title); ?>" />
    <meta name="twitter:description" content="<?php echo esc_attr($page_description); ?>" />
    <meta name="twitter:image" content="<?php echo esc_url($page_image); ?>" />
    <?php wp_head(); ?>
</head>

<body>
    <div style="display: flex; align-items: center; justify-content: center; min-height: 100vh; width: 100%; padding: 20px; box-sizing: border-box;">
        <div style="width: 650px; max-width: 100%;">

            <div class="shadow border-0 rounded-4 overflow-hidden card">

                    <!-- QR Section -->
                    <?php if (!empty($qrDataUri)) : ?>
                        <div class="py-4 text-center">
                            <a href="<?= esc_attr($qrDataUri); ?>" download="profile-qr.png">
                                <img
                                    src="<?= esc_attr($qrDataUri); ?>"
                                    alt="Profile QR Code"
                                    class="img-fluid"
                                    style="max-width:180px;">
                            </a>
                            <!-- <div class="mt-2 text-muted fs14">Scan to view profile</div> -->
                        </div>
                    <?php endif; ?>


                    <!-- Brand Bar -->
                    <div
                        class="position-relative px-4 py-5 text-white text-center fw-semibold fs-2"
                        style="background: linear-gradient(90deg, rgba(5,72,156,1) 0%, rgba(102,38,203,1) 44%, rgba(208,0,255,1) 100%); min-height: 180px; display: flex; align-items: center; justify-content: center;">
                        <!-- Centered text -->
                        <span>24/7 Empowerment</span>

                        <!-- Right-aligned logo -->
                        <img
                            src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/nd/white_logo_247.svg'); ?>"
                            alt="24/7 Empowerment"
                            class="top-50 position-absolute me-4 translate-middle-y end-0"
                            style="height: 120px;">
                    </div>



                    <!-- Profile -->
                    <div class="px-4 card-body" style="padding-top: 3rem; padding-bottom: 2rem;">
                        <div class="text-center mb-3">
                            <img src="<?php echo $profile_img; ?>"
                                class="shadow border border-3 border-white rounded-circle"
                                width="120" height="120"
                                style="margin-top:-80px; object-fit:cover; display: block; margin-left: auto; margin-right: auto;"
                                alt="Profile Photo" />
                        </div>

                        <h5 class="mt-3 mb-1 fw-bold text-center">
                            <?php echo $full_name; ?>
                        </h5>

                        <?php if ($designation): ?>
                            <p class="mb-2 text-primary designation text-center">
                                <?php echo esc_html($designation); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($digital_card_about) && $digital_card_about !== 'No short bio available.'): ?>
                            <p class="mb-3 text-muted text-center">
                                <?php echo $digital_card_about; ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($keywords)) : ?>
                            <div class="d-flex flex-wrap gap-2 mb-3 text-muted keyword-list justify-content-center">
                                <?php foreach ($keywords as $keyword) : ?>
                                    <span class="keyword-item">
                                        <?= esc_html($keyword); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <!-- Tags -->
                        <?php if (!empty($hashtags)) : ?>
                            <div class="d-flex flex-wrap gap-2 mb-3 text-muted justify-content-center">
                                <?php foreach ($hashtags as $hashtag) : ?>
                                    <span class="bg-light border border-light text-black badge">
                                        <?= esc_html($hashtag); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>


                        <!-- Location -->
                        <?php if (!empty($show_full_address) && $show_full_address === '1') : ?>
                            <?php if (!empty($place_display_name) && $place_display_name !== 'Unknown location'): ?>
                                <p class="mb-4 text-muted small text-center">
                                    <i class="bi bi-geo-alt"></i>
                                    <?php echo $place_display_name; ?>
                                </p>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Actions -->
                        <?php
                        $user_slug = get_query_var('user_profile');
                        $ref = urlencode($user_slug);

                        $signup_url = home_url("/signup?ref={$ref}");
                        $signin_url = home_url("/signin?ref={$ref}");
                        ?>

                        <div class="d-flex align-items-center justify-content-center gap-3 mt-4">                            
                            <a href="<?php echo esc_url($signin_url); ?>" class="btn btn-outline-primary">Sign In</a>
                            <a href="<?php echo esc_url($signup_url); ?>" class="btn btn-primary">Sign Up</a>
                        </div>
                        <?php wp_footer(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .card {
            width: 650px !important;
            max-width: 100%;
            margin: 0 auto;
        }

        .px-4.card-body {
            z-index: 999;
            text-align: center;
        }

        .keyword-item {
            background-color: #f0f0f0;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
        }

        .btn {
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: #0d47a1;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background-color: #0a3880;
        }

        .btn-outline-primary {
            border: 1px solid #0d47a1;
            color: #0d47a1;
            background-color: transparent;
        }

        .btn-outline-primary:hover {
            background-color: #0d47a1;
            color: white;
        }

        @media (max-width: 576px) {
            body {
                padding: 10px;
            }

            .card {
                width: 100%;
            }

            .position-relative img {
                height: 80px !important;
                margin-right: 0 !important;
            }

            .btn {
                padding: 6px 16px;
                font-size: 14px;
            }
        }
    </style>
</body>

</html>
