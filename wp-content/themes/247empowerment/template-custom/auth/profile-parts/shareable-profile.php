<?php
/**
 * Shareable Profile Template
 * 
 * Displays a public-facing profile card for non-logged-in users.
 * Includes QR code generation, SEO meta tags, and responsive design.
 * 
 * @package 247empowerment
 * @subpackage Template
 */

use Endroid\QrCode\Builder\Builder;

require_once get_template_directory() . '/vendor/autoload.php';

// ===== DEBUG: PAGE LOADING =====
//echo '<!-- DEBUG: shareable-profile.php loaded at ' . date('Y-m-d H:i:s') . ' -->';
//error_log('DEBUG shareable-profile.php: Template started at ' . date('Y-m-d H:i:s'));
// ==============================

/**
 * ========================================
 * INITIALIZATION & DATA VALIDATION
 * ========================================
 */

// Get user from URL slug
$user_slug = get_query_var('user_profile');
//error_log('DEBUG shareable-profile: user_slug = ' . $user_slug);

$user = mm_resolve_public_user($user_slug);
//error_log('DEBUG shareable-profile: user resolved = ' . ($user ? $user->user_login : 'FALSE'));

if (!$user) {
    //error_log('DEBUG shareable-profile: User resolution failed!');
    mm_handle_error('User not found', 'User Not Found', 404);
}

// Load user profile data
$profile = (new UserProfileData($user_slug))->getProfile();
//error_log('DEBUG shareable-profile: profile = ' . json_encode(array_keys($profile ?? [])));

if (empty($profile)) {
    //error_log('DEBUG shareable-profile: Profile data empty or null!');
    mm_handle_error('Profile data unavailable', 'Profile Not Available', 404);
}

/**
 * ========================================
 * PROFILE DATA EXTRACTION
 * ========================================
 */
// echo '<pre>';
// print_r($profile);
// echo '</pre>';

$profile_data = [
    'user_id'           => $user->ID,
    'full_name'         => trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')),
    'image'             => get_user_meta($user->ID, 'profile_photo', true) ?: get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg',
    'designation'       => $profile['designation'] ?? '',
    'bio'               => $profile['digital_card_about'] ?? '',
    'location'          => $profile['place_display_name'] ?? '',
    'show_location'     => !empty($profile['show_full_address']) && $profile['show_full_address'] === '1',
    'phone'             => $profile['phone'] ?? '',
    'keywords'          => (array) ($profile['keywords']['list'] ?? []),
    'hashtags'          => (array) ($profile['hashtags']['list'] ?? []),
    'industry'          => '',
    'shareable_link'    => $profile['shareable_link'] ?? '',
];

// Extract and normalize industry
if (!empty($profile['user_category_names'])) {
    $profile_data['industry'] = is_array($profile['user_category_names'])
        ? implode(', ', array_map('esc_html', $profile['user_category_names']))
        : esc_html($profile['user_category_names']);
}

// Escape all text fields
$profile_data['full_name']    = esc_html($profile_data['full_name']);
$profile_data['designation']  = esc_html($profile_data['designation']);
$profile_data['bio']          = esc_html($profile_data['bio']);
$profile_data['location']     = esc_html($profile_data['location']);
$profile_data['phone']        = esc_html($profile_data['phone']);
$profile_data['image']        = esc_url($profile_data['image']);

/**
 * ========================================
 * SEO META TAGS SETUP
 * ========================================
 */

$seo_data = [
    'url'          => home_url('/u/' . ($user->user_nicename ?: $user->user_login) . '/'),
    'title'        => $profile_data['full_name'] ?: get_bloginfo('name'),
    'description'  => '',
    'keywords'     => '',
    'image'        => $profile_data['image'],
];

// Build SEO description
$description_base = trim($profile_data['bio'] ?? $profile['about_me_short'] ?? '');
$seo_data['description'] = $description_base ?: 'View my professional profile.';
$seo_data['description'] = wp_trim_words($seo_data['description'], 28, '');

// Build SEO keywords
$keyword_collection = [];

// Add keywords
$keyword_collection = array_merge($keyword_collection, $profile_data['keywords']);

// Add hashtags (without #)
foreach ($profile_data['hashtags'] as $hashtag) {
    $clean_tag = ltrim($hashtag, '#');
    if ($clean_tag) {
        $keyword_collection[] = $clean_tag;
    }
}

// Add industry and designation
if ($profile_data['industry']) {
    $keyword_collection[] = $profile_data['industry'];
}
if ($profile_data['designation']) {
    $keyword_collection[] = $profile_data['designation'];
}

// Sanitize and deduplicate
$keyword_collection = array_unique(
    array_filter(
        array_map('sanitize_text_field', $keyword_collection)
    )
);
$seo_data['keywords'] = implode(', ', $keyword_collection);

/**
 * ========================================
 * QR CODE GENERATION
 * ========================================
 */

$qr_code = null;

if (!empty($profile_data['shareable_link'])) {
    try {
        // Build QR data with phone number
        $qr_data = $profile_data['shareable_link'];
        if (!empty($profile_data['phone'])) {
            $qr_data .= (strpos($qr_data, '?') ? '&' : '?') . 'phone=' . urlencode($profile_data['phone']);
        }
        
        $qr_result = Builder::create()
            ->data($qr_data)
            ->size(300)
            ->margin(10)
            ->build();
        
        $qr_code = $qr_result->getDataUri();
    } catch (Exception $e) {
        // QR generation failed - continue without it
        error_log('QR Code generation failed: ' . $e->getMessage());
    }
}

/**
 * ========================================
 * CTA BUTTON URLS
 * ========================================
 */

$ref_param = urlencode($user_slug);
$cta_urls = [
    'signin'  => home_url("/signin?ref={$ref_param}"),
    'signup'  => home_url("/signup?ref={$ref_param}"),
];


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title><?php echo esc_html($seo_data['title']); ?> | <?php bloginfo('name'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <link rel="canonical" href="<?php echo esc_url($seo_data['url']); ?>" />
    <meta name="description" content="<?php echo esc_attr($seo_data['description']); ?>" />
    <meta name="robots" content="index, follow">
    
    <?php if (!empty($seo_data['keywords'])): ?>
        <meta name="keywords" content="<?php echo esc_attr($seo_data['keywords']); ?>" />
    <?php endif; ?>

    <!-- Open Graph Meta Tags -->
    <meta property="og:site_name" content="<?php bloginfo('name'); ?>" />
    <meta property="og:type" content="profile" />
    <meta property="og:url" content="<?php echo esc_url($seo_data['url']); ?>" />
    <meta property="og:title" content="<?php echo esc_attr($seo_data['title']); ?>" />
    <meta property="og:description" content="<?php echo esc_attr($seo_data['description']); ?>" />
    <meta property="og:image" content="<?php echo esc_url($seo_data['image']); ?>" />

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?php echo esc_attr($seo_data['title']); ?>" />
    <meta name="twitter:description" content="<?php echo esc_attr($seo_data['description']); ?>" />
    <meta name="twitter:image" content="<?php echo esc_url($seo_data['image']); ?>" />

    <?php wp_head(); ?>

    <!-- Shareable Profile Styles -->
    <link rel="stylesheet" href="<?php echo esc_url(get_template_directory_uri() . '/template-custom/auth/profile-parts/assets/shareable-profile.css'); ?>">
</head>


<body class="shareable-profile-page">
    <div class="profile-container">
        <div class="profile-card">

            <!-- QR Code Section -->
            <?php if (!empty($qr_code)): ?>
                <section class="profile-qr-section" aria-label="Profile QR Code">
                    <a href="<?php echo esc_url($qr_code); ?>" download="profile-qr.png" class="qr-link">
                        <img src="<?php echo esc_attr($qr_code); ?>" 
                             alt="<?php echo esc_attr($seo_data['title']); ?> - Profile QR Code" 
                             class="qr-image"
                             loading="lazy">
                    </a>
                </section>
            <?php endif; ?>

            <!-- Brand Header -->
            <header class="profile-header">
                <h1 class="brand-name">24/7 Empowerment</h1>
                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/nd/white_logo_247.svg'); ?>" 
                     alt="24/7 Empowerment Logo" 
                     class="brand-logo"
                     loading="lazy">
            </header>

            <!-- Profile Information -->
            <main class="profile-main">

                <!-- Profile Image -->
                <figure class="profile-image-container">
                    <img src="<?php echo esc_attr($profile_data['image']); ?>" 
                         alt="<?php echo esc_attr($profile_data['full_name']); ?>" 
                         class="profile-image"
                         loading="lazy">
                </figure>

                <!-- Name -->
                <h2 class="profile-name">
                    <?php echo esc_html($profile_data['full_name']); ?>
                </h2>

                <!-- Designation -->
                <?php if ($profile_data['designation']): ?>
                    <p class="profile-designation">
                        <?php echo esc_html($profile_data['designation']); ?>
                    </p>
                <?php endif; ?>

                <!-- Bio -->
                <?php if ($profile_data['bio']): ?>
                    <p class="profile-bio">
                        <?php echo esc_html($profile_data['bio']); ?>
                    </p>
                <?php endif; ?>

                <!-- Keywords List -->
                <?php if (!empty($profile_data['keywords'])): ?>
                    <div class="profile-keywords" role="list" aria-label="Keywords">
                        <?php foreach ($profile_data['keywords'] as $keyword): ?>
                            <span class="keyword-badge" role="listitem">
                                <?php echo esc_html($keyword); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Hashtags List -->
                <?php if (!empty($profile_data['hashtags'])): ?>
                    <div class="profile-hashtags" role="list" aria-label="Hashtags">
                        <?php foreach ($profile_data['hashtags'] as $hashtag): ?>
                            <span class="hashtag-badge" role="listitem">
                                <?php echo esc_html($hashtag); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Location -->
                <?php if ($profile_data['show_location'] && $profile_data['location'] && $profile_data['location'] !== 'Unknown location'): ?>
                    <p class="profile-location">
                        <span class="location-icon" aria-hidden="true">📍</span>
                        <?php echo esc_html($profile_data['location']); ?>
                    </p>
                <?php endif; ?>

                <!-- Phone Number -->
                <?php if (!empty($profile_data['phone'])): ?>
                    <p class="profile-phone">
                        <span class="phone-icon" aria-hidden="true">📞</span>
                        <a href="tel:<?php echo esc_attr($profile_data['phone']); ?>">
                            <?php echo esc_html($profile_data['phone']); ?>
                        </a>
                    </p>
                <?php endif; ?>

                <!-- Call-to-Action Buttons -->
                <div class="profile-cta">
                    <a href="<?php echo esc_url($cta_urls['signin']); ?>" class="btn btn-secondary">
                        Sign In
                    </a>
                    <a href="<?php echo esc_url($cta_urls['signup']); ?>" class="btn btn-primary">
                        Sign Up
                    </a>
                </div>

            </main>

        </div>
    </div>

    <?php wp_footer(); ?>
</body>
</html>

<?php
// echo '<pre>';
// print_r($profile_data);
// echo '</pre>';