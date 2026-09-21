<?php

// ============================================
// Load Helper Functions (FIRST - needed by other modules)
// ============================================
require_once get_template_directory() . '/inc/helpers.php';

// ============================================
// Load Notification System Classes
// ============================================
require_once get_template_directory() . '/inc/NotificationTypes.php';
require_once get_template_directory() . '/inc/NotificationManager.php';

// ============================================
// Load Payout System Classes
// ============================================
require_once get_template_directory() . '/inc/PayoutSystem.php';
require_once get_template_directory() . '/inc/PayPalAPI.php';
require_once get_template_directory() . '/inc/PayoutNotifications.php';
require_once get_template_directory() . '/inc/payout-balance.php';

// ============================================
// Load Phase 1, 2, 3 Enhanced Posting Features
// ============================================
require_once get_template_directory() . '/inc/database-migration.php';
require_once get_template_directory() . '/inc/status-indicators.php';

// Load MM LiveKit Integration
require_once get_template_directory() . '/mm-livekit/mm-livekit.php';

// Activation hook - for theme activation
register_activation_hook(__FILE__, ['PayoutSystem', 'activate']);

// Create Notification table on activation
register_activation_hook(__FILE__, ['NotificationManager', 'createTable']);

// Ensure tables exist on every page load (fallback)
add_action('init', function() {
    static $tables_checked = false;
    if (!$tables_checked) {
        PayoutSystem::activate();
        NotificationManager::createTable();
        $tables_checked = true;
    }
}, 1); // Run early

// Initialize Payout System
if (is_admin() || is_user_logged_in()) {
    new PayoutSystem();
    new PayPalAPI();
    new PayoutNotifications();
}

add_action('init', function () {
    if (!session_id()) {
        session_start();
    }
    
    // Disable WordPress emoji processing - use SVG icons instead
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
});

// function mm_theme_setup()
// {
//     add_theme_support('title-tag');
//     add_theme_support('post-thumbnails');
// }
// add_action('after_setup_theme', 'mm_theme_setup');

function mm_theme_setup()
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    
    // Gutenberg/Block Editor Support
    add_theme_support('wp-block-styles');
    add_theme_support('responsive-embeds');
    add_theme_support('align-wide');
    add_theme_support('editor-styles');
    
    // Register Navigation Menus
    register_nav_menus([
        'primary' => __('Primary Menu', '247empowerment'),
        'authentication' => __('Authentication Menu (Sign In/Sign Up)', '247empowerment'),
        'secondary' => __('Secondary Menu (Footer)', '247empowerment'),
    ]);
}
add_action('after_setup_theme', 'mm_theme_setup');

// emoji-picker-element স্ক্রিপ্ট ট্যাগে type="module" যুক্ত করার ফিল্টার
function mm_make_emoji_picker_a_module($tag, $handle, $src) {
    if ('emoji-picker-element' === $handle) {
        return '<script type="module" src="' . esc_url($src) . '" id="emoji-picker-element-js"></script>';
    }
    return $tag;
}
add_filter('script_loader_tag', 'mm_make_emoji_picker_a_module', 10, 3);  

function mm_enqueue_assets()
{
    // CSS - Reliable CDNs
    wp_enqueue_style('glightbox-css', 'https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css', [], '3.2.0');
    wp_enqueue_style('aos-css', 'https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css', [], '2.3.4');
    
    // Local CSS files
    wp_enqueue_style('output', get_template_directory_uri() . '/assets/css/output.css', [], filemtime(get_template_directory() . '/assets/css/output.css'));
    wp_enqueue_style('mm-style', get_stylesheet_uri(), [], filemtime(get_stylesheet_directory() . '/style.css'));
    
    // নতুন আলাদা করা সিএসএস ফাইলটি এখানে লোড করুন
    wp_enqueue_style(
        'mm-comments-reactions', 
        get_template_directory_uri() . '/assets/css/comments-reactions.css', 
        ['mm-style'], // এটি 'mm-style' এর পরে লোড হবে
        filemtime(get_template_directory() . '/assets/css/comments-reactions.css')
    );

    // Phase 1 Modal CSS
    wp_enqueue_style(
        'phase1-modal-css',
        get_template_directory_uri() . '/template-custom/auth/profile-parts/modal-design.css',
        [],
        filemtime(get_template_directory() . '/template-custom/auth/profile-parts/modal-design.css')
    );

    // JS - CDNs
    wp_enqueue_script('purecounter-js', 'https://cdn.jsdelivr.net/npm/@srexi/purecounterjs@1.5.0/dist/purecounter_vanilla.js', [], '1.5.0', true);
    wp_enqueue_script('aos-js', 'https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js', [], '2.3.4', true);
    wp_enqueue_script('glightbox-js', 'https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js', [], '3.2.0', true);
    
    // Bootstrap & Custom JS
    //wp_enqueue_script('bootstrap-js', get_template_directory_uri() . '/assets/js/bootstrap.bundle.min.js', ['jquery'], '5.3.3', true);
    // Bootstrap 5.3.8 CDN JS
    wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js', ['jquery'], '5.3.8', true);

    wp_enqueue_script(
        'menu-js',
        get_template_directory_uri() . '/assets/js/menu.js',
        [],
        filemtime(get_template_directory() . '/assets/js/menu.js'),
        true
    );

    wp_enqueue_script(
        'mm-main-js',
        get_template_directory_uri() . '/assets/js/main.js',
        ['jquery', 'purecounter-js', 'aos-js', 'glightbox-js', 'menu-js'],
        filemtime(get_template_directory() . '/assets/js/main.js'),
        true
    );

    wp_localize_script('mm-main-js', 'themeData', [
        'dir' => get_template_directory_uri(),
    ]);

    // JS Libraries & Phase 1 Handler
    wp_enqueue_script('emoji-picker-element', 'https://cdn.jsdelivr.net/npm/emoji-picker-element@1/index.js', [], '1.0.0', true);

    wp_enqueue_script(
        'phase1-modal-handler-js',
        get_template_directory_uri() . '/template-custom/auth/profile-parts/modal-handler.js',
        ['jquery', 'emoji-picker-element'],
        filemtime(get_template_directory() . '/template-custom/auth/profile-parts/modal-handler.js'),
        true
    );

    wp_localize_script('phase1-modal-handler-js', 'ajaxurl', admin_url('admin-ajax.php'));
}
add_action('wp_enqueue_scripts', 'mm_enqueue_assets');

/**
if (class_exists('Kirki')) {
    Kirki::add_config('my_config', array(
        'capability'  => 'edit_theme_options',
        'option_type' => 'theme_mod',
    ));

    Kirki::add_section('hero_text_section', array(
        'title'    => esc_html__('Hero Text Section', 'textdomain'),
        'priority' => 160,
    ));

    Kirki::add_field('my_config', [
        'type'        => 'editor',
        'settings'    => 'hero_text_content',
        'label'       => esc_html__('Hero Text Content', 'textdomain'),
        'section'     => 'hero_text_section',
        'default'     => '<h2>Hello <strong>world!</strong></h2>',
        'choices'     => [
            'rows'           => 10,
            'toolbar'        => 'full',
            'media_buttons'  => false,
        ],
    ]);
}
 */
// Utility functions moved to helpers.php


function mm_customize_register($wp_customize)
{
    $wp_customize->add_section('hero_section', [
        'title' => __('Hero Section', 'Mathmozo'),
        'priority' => 30,
    ]);

    // Small Logo
    $wp_customize->add_setting('small_logo', [
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ]);

    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'small_logo', [
        'label' => __('Small Logo', 'Mathmozo'),
        'section' => 'title_tagline', // Or use your desired section
        'settings' => 'small_logo',
    ]));

    // Medium Logo
    $wp_customize->add_setting('medium_logo', [
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ]);

    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'medium_logo', [
        'label' => __('Medium Logo', 'Mathmozo'),
        'section' => 'title_tagline', // Or use your desired section
        'settings' => 'medium_logo',
    ]));

    // Large Logo
    $wp_customize->add_setting('large_logo', [
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ]);

    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'large_logo', [
        'label' => __('Large Logo', 'Mathmozo'),
        'section' => 'title_tagline', // Or use your desired section
        'settings' => 'large_logo',
    ]));

    $wp_customize->add_setting('hero_image', [
        'default' => '',
        'sanitize_callback' => 'esc_url_raw',
    ]);

    $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'hero_image', [
        'label' => __('Hero Image', 'Mathmozo'),
        'section' => 'hero_section',
        'settings' => 'hero_image',
    ]));

    // Section
    $wp_customize->add_section('hero_text_section', array(
        'title' => __('Hero Section Text', 'mm'),
        'priority' => 30,
    ));

    // Section for Custom Subline (you can reuse existing section or create new one)
    $wp_customize->add_section('mm_custom_subline_section', [
        'title'    => __('Custom Subline', 'mm'),
        'priority' => 31, // after your header section
    ]);

    // New Subline Setting
    $wp_customize->add_setting('mm_custom_subline', [
        'default'           => 'Your subline goes here',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    // Control for Subline
    $wp_customize->add_control('mm_custom_subline', [
        'label'   => __('Subline Text', 'mm'),
        'section' => 'mm_custom_subline_section',
        'type'    => 'text',
    ]);

    // Textarea for custom HTML (like <span>)
    // $wp_customize->add_setting('hero_text_content', array(
    //     'default' => 'Welcome to <span style="color:#ff0;">My Website</span>',
    //     'sanitize_callback' => 'wp_kses_post', // Allows safe HTML like <span>
    // ));

    // $wp_customize->add_control('hero_text_content', array(
    //     'label' => __('Hero Text Content (HTML allowed)', 'mm'),
    //     'section' => 'hero_text_section',
    //     'type' => 'textarea',
    // ));


    // Hero Extra Classes (line-height, text-shadow, width, etc.)
    $wp_customize->add_setting('hero_extra_classes', array(
        'default' => 'lh-base text-shadow-lg w-75', // Example default
        'sanitize_callback' => 'sanitize_text_field',
    ));

    $wp_customize->add_control('hero_extra_classes', array(
        'label' => __('Hero Extra Classes (Line-height, Text-shadow, Width etc.)', 'mm'),
        'section' => 'hero_text_section',
        'type' => 'text',
    ));

    // Section
    $wp_customize->add_section('mm_custom_header_text', array(
        'title' => __('Custom Header Text', 'mm'),
        'priority' => 30,
    ));

    // Line 1
    $wp_customize->add_setting('mm_header_text_line1', array(
        'default' => 'Personal',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('mm_header_text_line1', array(
        'label' => __('Header Line 1', 'mm'),
        'section' => 'mm_custom_header_text',
        'type' => 'text',
    ));

    // Line 2
    $wp_customize->add_setting('mm_header_text_line2', array(
        'default' => 'Empowerment',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('mm_header_text_line2', array(
        'label' => __('Header Line 2', 'mm'),
        'section' => 'mm_custom_header_text',
        'type' => 'text',
    ));

    // Line 3
    $wp_customize->add_setting('mm_header_text_line3', array(
        'default' => 'Teams, Inc.',
        'sanitize_callback' => 'sanitize_text_field',
    ));
    $wp_customize->add_control('mm_header_text_line3', array(
        'label' => __('Header Line 3', 'mm'),
        'section' => 'mm_custom_header_text',
        'type' => 'text',
    ));
}
add_action('customize_register', 'mm_customize_register');

function custom_theme_customizer($wp_customize)
{
    // Add Section for Social Media Links
    $wp_customize->add_section('social_media_section', array(
        'title'       => __('Social Media Links', 'mm'),
        'description' => __('Manage your social media links and upload icons.', 'mm'),
        'priority'    => 30,
    ));

    // Add Settings for Social Media URLs
    $wp_customize->add_setting('facebook_url', array(
        'default'   => '',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control('facebook_url', array(
        'label'   => __('Facebook URL', 'mm'),
        'section' => 'social_media_section',
        'type'    => 'url',
    ));

    $wp_customize->add_setting('twitter_url', array(
        'default'   => '',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control('twitter_url', array(
        'label'   => __('Twitter (X) URL', 'mm'),
        'section' => 'social_media_section',
        'type'    => 'url',
    ));

    $wp_customize->add_setting('instagram_url', array(
        'default'   => '',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control('instagram_url', array(
        'label'   => __('Instagram URL', 'mm'),
        'section' => 'social_media_section',
        'type'    => 'url',
    ));

    $wp_customize->add_setting('meetup_url', array(
        'default'   => '',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control('meetup_url', array(
        'label'   => __('Meetup URL', 'mm'),
        'section' => 'social_media_section',
        'type'    => 'url',
    ));

    $wp_customize->add_setting('linkedin_url', array(
        'default'   => '',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control('linkedin_url', array(
        'label'   => __('LinkedIn URL', 'mm'),
        'section' => 'social_media_section',
        'type'    => 'url',
    ));

    $wp_customize->add_setting('youtube_url', array(
        'default'   => '',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control('youtube_url', array(
        'label'   => __('YouTube URL', 'mm'),
        'section' => 'social_media_section',
        'type'    => 'url',
    ));

    // Add Settings for Social Media Icons (SVG file upload)
    $wp_customize->add_setting('facebook_icon', array(
        'default'   => '',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'facebook_icon', array(
        'label'   => __('Upload Facebook Icon (SVG)', 'mm'),
        'section' => 'social_media_section',
        'mime_type' => 'image/svg+xml',
    )));

    $wp_customize->add_setting('twitter_icon', array(
        'default'   => '',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'twitter_icon', array(
        'label'   => __('Upload Twitter (X) Icon (SVG)', 'mm'),
        'section' => 'social_media_section',
        'mime_type' => 'image/svg+xml',
    )));

    $wp_customize->add_setting('instagram_icon', array(
        'default'   => '',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'instagram_icon', array(
        'label'   => __('Upload Instagram Icon (SVG)', 'mm'),
        'section' => 'social_media_section',
        'mime_type' => 'image/svg+xml',
    )));

    $wp_customize->add_setting('meetup_icon', array(
        'default'   => '',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'meetup_icon', array(
        'label'   => __('Upload Meetup Icon (SVG)', 'mm'),
        'section' => 'social_media_section',
        'mime_type' => 'image/svg+xml',
    )));

    $wp_customize->add_setting('linkedin_icon', array(
        'default'   => '',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'linkedin_icon', array(
        'label'   => __('Upload LinkedIn Icon (SVG)', 'mm'),
        'section' => 'social_media_section',
        'mime_type' => 'image/svg+xml',
    )));

    $wp_customize->add_setting('youtube_icon', array(
        'default'   => '',
        'transport' => 'refresh',
    ));
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'youtube_icon', array(
        'label'   => __('Upload YouTube Icon (SVG)', 'mm'),
        'section' => 'social_media_section',
        'mime_type' => 'image/svg+xml',
    )));
}
add_action('customize_register', 'custom_theme_customizer');


function mm_mime_types($mimes)
{
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
add_filter('upload_mimes', 'mm_mime_types');



add_filter('theme_page_templates', function ($templates) {
    $template_dir = get_template_directory() . '/template-custom/';
    $template_files = glob($template_dir . '**/*.php');

    foreach ($template_files as $file) {
        $headers = get_file_data($file, [
            'Template Name' => 'Template Name',
            'Template Post Type' => 'Template Post Type',
        ]);

        if (!empty($headers['Template Name'])) {
            $relative_path = str_replace(get_template_directory() . '/', '', $file);
            $templates[$relative_path] = $headers['Template Name'];
        }
    }


    return $templates;
});

// Register rewrite rules
add_action('init', function () {
    add_rewrite_rule('^report/?$', 'index.php?custom_page=report&$query_string', 'top');
    add_rewrite_rule('^suggestion/?$', 'index.php?custom_page=suggestion&$query_string', 'top');
    // add_rewrite_rule('^facebook-oauth-callback/?$', 'index.php?custom_page=facebook-oauth-callback&$query_string', 'top');
    // add_rewrite_rule('^linkedin-oauth-callback/?$', 'index.php?custom_page=linkedin-oauth-callback&$query_string', 'top');
});

// Register query var
add_filter('query_vars', function ($vars) {
    $vars[] = 'custom_page';
    $vars[] = 'user_profile';
    return $vars;
});

// Ensure rewrite rules are registered
add_action('init', function () {
    add_rewrite_rule(
        '^u/([^/]+)/?$',
        'index.php?user_profile=$matches[1]',
        'top'
    );
    
    // Flush rules if needed (check transient to avoid excessive flushing)
    if (!get_transient('mm_rewrite_rules_flushed')) {
        flush_rewrite_rules(false);
        set_transient('mm_rewrite_rules_flushed', 1, HOUR_IN_SECONDS);
    }
}, 11);

// Debug parse_request to see if URL is being matched
add_action('parse_request', function ($wp) {
}, 1);

/**
 * Canonicalize legacy user routes to /u/{user_nicename}.
 *
 * Legacy paths supported:
 * - /username
 * - /username/referrals
 * - /username/meetings
 * - /username/store
 * - /username/store/course
 * - /username/store/course/shareable
 * - /username/events
 * - /username/event/event-slug
 */
add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax()) {
        return;
    }

    $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    if ($path === '') {
        return;
    }

    $segments = explode('/', $path);
    $first = $segments[0] ?? '';

    // Skip known non-user roots early.
    if (in_array($first, ['wp-admin', 'wp-login.php', 'wp-json', 'feed', 'category', 'tag'], true)) {
        return;
    }

    $is_u_route = ($first === 'u');

    $identifier = $is_u_route ? ($segments[1] ?? '') : $first;
    if ($identifier === '') {
        return;
    }

    $user = mm_resolve_public_user($identifier);
    if (!$user) {
        return;
    }

    $canonical_slug = $user->user_nicename ?: $user->user_login;

    $allowed_legacy = [
        1 => true,
        2 => true,
        3 => true,
        4 => true,
    ];

    $tail = $is_u_route ? array_slice($segments, 2) : array_slice($segments, 1);
    $tail_count = count($tail);

    if (!isset($allowed_legacy[$tail_count])) {
        return;
    }

    $valid = false;
    if ($tail_count === 0) {
        $valid = true;
    } elseif ($tail_count === 1) {
        $valid = in_array($tail[0], ['referrals', 'meetings', 'store', 'events'], true);
    } elseif ($tail_count === 2) {
        $valid = in_array($tail[0], ['store', 'event'], true) && $tail[1] !== '';
    } elseif ($tail_count === 3) {
        $valid = ($tail[0] === 'store' && $tail[2] === 'shareable' && $tail[1] !== '');
    }

    if (!$valid) {
        return;
    }

    $canonical_path = 'u/' . $canonical_slug;
    if (!empty($tail)) {
        $canonical_path .= '/' . implode('/', $tail);
    }
    $canonical_url = home_url('/' . $canonical_path . '/');

    if (!empty($_SERVER['QUERY_STRING'])) {
        $canonical_url .= '?' . $_SERVER['QUERY_STRING'];
    }

    $current_url = home_url('/' . $path . '/');
    if (!empty($_SERVER['QUERY_STRING'])) {
        $current_url .= '?' . $_SERVER['QUERY_STRING'];
    }

    if (untrailingslashit($canonical_url) !== untrailingslashit($current_url)) {
        wp_redirect($canonical_url, 301);
        exit;
    }
}, 1);

add_filter('template_include', function ($template) {
    // DEBUG: Check if this filter is being called
    error_log('=== TEMPLATE_INCLUDE CALLED ===');
    error_log('Current URL: ' . $_SERVER['REQUEST_URI']);
    error_log('Template file: ' . $template);
    error_log('Query vars: ' . json_encode($_GET));
    error_log('Is 404: ' . (is_404() ? 'YES' : 'NO'));
    error_log('Is singular: ' . (is_singular() ? 'YES' : 'NO'));
    error_log('All query vars: ' . json_encode($wp_query->query_vars ?? []));

    // Handle custom_page
    $custom_page = get_query_var('custom_page');
    if ($custom_page === 'facebook-oauth-callback') {
        require_once get_template_directory() . '/more_functions/facebook-auth.php';
        handle_facebook_oauth_callback();
        exit;
    }
    if ($custom_page === 'linkedin-oauth-callback') {
        // Bridge to new empowerment-social-poster plugin
        error_log('[BRIDGE] LinkedIn callback intercepted, redirecting to new plugin handler');
        $code = sanitize_text_field(wp_unslash($_GET['code'] ?? ''));
        $state = sanitize_text_field(wp_unslash($_GET['state'] ?? ''));
        error_log('[BRIDGE] Code: ' . substr($code, 0, 20) . '..., State: ' . $state);
        
        if ($code && $state) {
            $redirect_url = add_query_arg(
                array(
                    'action' => 'esp_oauth_callback',
                    'platform' => 'linkedin',
                    'code' => $code,
                    'state' => $state,
                ),
                admin_url('admin-post.php')
            );
            error_log('[BRIDGE] Redirecting to: ' . $redirect_url);
            wp_safe_redirect($redirect_url);
            exit;
        }
        error_log('[BRIDGE] ❌ Missing code or state');
        wp_die('Invalid OAuth callback: missing code or state');
    }
    if ($custom_page === 'report') {
        return get_theme_file_path('template-custom/auth/report.php');
    }
    if ($custom_page === 'suggestion') {
        return get_theme_file_path('template-custom/auth/suggestion.php');
    }

    // Handle user profile
    $user_slug = get_query_var('user_profile');
    
    if (!$user_slug) {
        $username_qv = get_query_var('username');
        if ($username_qv) {
            $resolved_user = mm_resolve_public_user($username_qv);
            if ($resolved_user) {
                $user_slug = $resolved_user->user_nicename ?: $resolved_user->user_login;
                set_query_var('user_profile', $user_slug);
            }
        }
    }
    if (!$user_slug) {
        error_log('DEBUG template_include: No user_slug, returning template');
        return $template;
    }

    // Let real pages/posts win
    if (get_page_by_path($user_slug, OBJECT, ['page', 'post'])) {
        return $template;
    }

    // Resolve user
    $user = mm_resolve_public_user($user_slug);

    if ($user) {
        return get_theme_file_path('template-custom/auth/user-profile.php');
    }

    return $template;

    return $template;
});

// add_filter('template_include', function ($template) {
//     // Handle custom_page
//     $custom_page = get_query_var('custom_page');
//     if ($custom_page === 'report') {
//         return get_template_directory() . '/template-custom/auth/report.php';
//     }
//     if ($custom_page === 'suggestion') {
//         return get_template_directory() . '/template-custom/auth/suggestion.php';
//     }

//     // Handle user_profile
//     $user_slug = get_query_var('user_profile');

//     if (!$user_slug) return $template;

//     // Skip if slug belongs to an existing post or page (FAST)
//     if (get_page_by_path($user_slug, OBJECT, ['page', 'post'])) {
//         return $template;
//     }

//     if (get_posts(['name' => $user_slug, 'post_type' => 'any'])) return $template;

//     // Try to load user profile
//     $user_slug = get_query_var('user_profile');
//     if (!$user_slug) {
//         return $template;
//     }

//     // Let real content win (fast)
//     if (!is_404()) {
//         return $template;
//     }

//     // Try user lookup
//     $user = get_user_by('slug', $user_slug);
//     if (!$user) {
//         $user = get_user_by('login', $user_slug);
//     }

//     if ($user) {
//         return get_theme_file_path('template-custom/auth/user-profile.php');
//     }

//     return $template;
// });



add_filter('show_admin_bar', '__return_false');

add_action('wp_ajax_chatgpt_ajax_handler', 'chatgpt_ajax_handler');
function chatgpt_ajax_handler()
{
    $body = json_decode(file_get_contents('php://input'), true);
    $message = sanitize_text_field($body['message']);

    $apiKey = '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'; // Replace with your actual key

    $postData = [
        "model" => "openai/gpt-3.5-turbo", // Important: include the provider prefix (as shown in your Postman result)
        "messages" => [
            ["role" => "user", "content" => $message]
        ]
    ];

    $response = wp_remote_post("https://openrouter.ai/api/v1/chat/completions", [
        "headers" => [
            "Authorization" => "Bearer $apiKey",
            "Content-Type" => "application/json",
            "HTTP-Referer" => home_url(), // OpenRouter requires Referer
            "X-Title" => "My WP Chatbot", // Optional
        ],
        "body" => json_encode($postData),
    ]);

    if (is_wp_error($response)) {
        wp_send_json(['reply' => '⚠️ Could not reach OpenRouter.']);
    }

    $response_body = wp_remote_retrieve_body($response);
    error_log("OpenRouter RESPONSE: $response_body");

    $json = json_decode($response_body, true);
    $reply = $json['choices'][0]['message']['content'] ?? null;

    if ($reply) {
        wp_send_json(['reply' => trim($reply)]);
    } else {
        wp_send_json(['reply' => '⚠️ AI response was empty or malformed.']);
    }
}

// functions.php
// OG tags for normal posts/pages
function add_open_graph_tags()
{
    if (is_singular()) {
        global $post;

        $title = get_the_title($post->ID);
        $description = get_the_excerpt($post->ID);
        $url = get_permalink($post->ID);

        $image = get_the_post_thumbnail_url($post->ID, 'full');
        if (!$image) {
            $image = get_template_directory_uri() . '/assets/img/default-og.jpg';
        }

        echo '
            <meta property="og:title" content="' . esc_attr($title) . '" />
            <meta property="og:description" content="' . esc_attr($description) . '" />
            <meta property="og:type" content="article" />
            <meta property="og:url" content="' . esc_url($url) . '" />
            <meta property="og:image" content="' . esc_url($image) . '" />
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" content="' . esc_attr($title) . '" />
            <meta name="twitter:description" content="' . esc_attr($description) . '" />
            <meta name="twitter:image" content="' . esc_url($image) . '" />
        ';
    }
}

// OG tags for course pages
function output_course_og_tags()
{
    $course_slug = get_query_var('course_slug');
    $store_user = get_query_var('store_user');
    $is_shareable = get_query_var('shareable');

    if ($course_slug && $store_user) {
        $course = get_page_by_path($course_slug, OBJECT, 'course');
        if (!$course) {
            return;
        }

        $title = get_the_title($course);
        $description = get_field('short_details', $course->ID) ?: wp_trim_words($course->post_content, 30);
        $image = get_the_post_thumbnail_url($course->ID, 'large') ?: get_template_directory_uri() . '/img/banner.jpg';

        // Use shareable URL if on shareable page
        $url = $is_shareable
            ? home_url("/u/{$store_user}/store/{$course_slug}/shareable/")
            : home_url("/u/{$store_user}/store/{$course_slug}/");

        echo '
            <meta property="og:title" content="' . esc_attr($title) . '" />
            <meta property="og:description" content="' . esc_attr($description) . '" />
            <meta property="og:image" content="' . esc_url($image) . '" />
            <meta property="og:url" content="' . esc_url($url) . '" />
            <meta property="og:type" content="website" />
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" content="' . esc_attr($title) . '" />
            <meta name="twitter:description" content="' . esc_attr($description) . '" />
            <meta name="twitter:image" content="' . esc_url($image) . '" />
        ';
    }
}
add_action('wp_head', 'output_course_og_tags');


// The main function deciding which OG tags to output
function output_appropriate_og_tags()
{
    $course_slug = get_query_var('course_slug');
    $store_user = get_query_var('store_user');
    $shareable = get_query_var('shareable');

    // If this is the minimal shareable template (URL ends with /shareable or has shareable=1)
    if ($shareable && $course_slug && $store_user) {
        output_course_og_tags();
    } elseif ($course_slug && $store_user) {
        output_course_og_tags();
    } else {
        add_open_graph_tags();
    }
}
add_action('wp_head', 'output_appropriate_og_tags');


// Register the 'shareable' query var
function custom_query_vars($vars)
{
    $vars[] = 'shareable';
    return $vars;
}
add_filter('query_vars', 'custom_query_vars');


// Add rewrite rule so /{store_user}/store/{course_slug}/shareable/ maps to query vars
function custom_store_shareable_rewrite_rule()
{
    add_rewrite_rule(
        '^u/([^/]+)/store/([^/]+)/shareable/?$', // URL pattern: u/{store_user}/store/{course_slug}/shareable
        'index.php?store_user=$matches[1]&course_slug=$matches[2]&shareable=1', // query vars
        'top'
    );
}
add_action('init', 'custom_store_shareable_rewrite_rule');

/**
 * Summary of give_referral_commission
 * @param mixed $buyer_id
 * @param mixed $course_price
 * @return bool
 */
function give_referral_commission($buyer_id, $course_price, $course_id): bool
{
    $referrer_username = get_user_meta($buyer_id, 'referrer', true);

    if (!$referrer_username || $referrer_username == $buyer_id) {
        return false;
    }

    $referrer_user = get_user_by('login', $referrer_username);
    if (!$referrer_user) {
        return false;
    }

    $referrer_id = $referrer_user->ID;

    // 💸 Commission logic
    $commission_percent = 10;
    $commission_amount = round(($course_price * $commission_percent) / 100, 2);

    if ($commission_amount <= 0) {
        return false;
    }

    // 🪙 Update referrer's wallet
    $current_balance = (float) get_user_meta($referrer_id, 'referral_commission', true);
    update_user_meta($referrer_id, 'referral_commission', $current_balance + $commission_amount);

    // 📝 Add user log
    $logs = get_user_meta($referrer_id, 'referral_logs', true);
    $logs = is_array($logs) ? $logs : [];
    $logs[] = [
        'referred_user_id' => $buyer_id,
        'amount' => $commission_amount,
        'earned_for' => $course_id ? get_the_title($course_id) : 'Unknown Course',
        'earned_for_id' => $course_id,
        'date' => current_time('mysql')
    ];
    update_user_meta($referrer_id, 'referral_logs', $logs);

    // 🔁 Global log for admin
    $global_logs = get_option('referral_commission_global_log', []);
    $global_logs[] = [
        'referrer_id' => $referrer_id,
        'buyer_id' => $buyer_id,
        'amount' => $commission_amount,
        'course_price' => $course_price,
        'date' => current_time('mysql')
    ];
    update_option('referral_commission_global_log', $global_logs);
    return true;
}

add_action('admin_init', function () {
    register_setting('general', 'default_referrer_username', [
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => 'admin',
    ]);

    add_settings_field(
        'default_referrer_username',
        'Default Referrer Username',
        function () {
            $value = get_option('default_referrer_username', '');
            echo '<input type="text" name="default_referrer_username" value="' . esc_attr($value) . '" class="regular-text">';
        },
        'general'
    );
});

// Notifications Enqueue
function enqueue_notifications_assets()
{
    if (!is_user_logged_in()) {
        return;
    }

    wp_enqueue_script(
        'notifications',
        get_template_directory_uri() . '/assets/js/notifications.js',
        ['jquery'],
        filemtime(get_template_directory() . '/assets/js/notifications.js'),
        true
    );

    wp_localize_script('notifications', 'notificationsData', [
        'ajaxurl'    => admin_url('admin-ajax.php'),
        'nonce'      => wp_create_nonce('notifications_nonce'),
        'circleIcon' => esc_url(get_template_directory_uri() . '/assets/img/nd/circle-notification.png'),
        'userImg'    => esc_url(get_template_directory_uri() . '/assets/img/loggedin_images/banner.jpg'),
        'activeIcon' => esc_url(get_template_directory_uri() . '/assets/img/nd/active_icon.png'),
        'sound'      => esc_url(get_template_directory_uri() . '/assets/sounds/coin.mp3'),
    ]);
}
add_action('wp_enqueue_scripts', 'enqueue_notifications_assets');

// Enqueue SimpleMDE for Modify-Profile About Me editor
add_action('wp_enqueue_scripts', function () {
    if (!is_user_logged_in()) return;
    // Only enqueue on modify-profile page (adjust slug as needed)
    if (!is_page('modify-profile')) return;

    // SimpleMDE CSS & JS
    wp_enqueue_style('simplemde-css', 'https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.css');
    wp_enqueue_script('simplemde-js', 'https://cdn.jsdelivr.net/simplemde/latest/simplemde.min.js', [], null, true);

    // Inline JS to initialize SimpleMDE on About Me textarea
    $init_js = "
    document.addEventListener('DOMContentLoaded', function() {
        var textarea = document.querySelector('textarea[name=\'about_me\']');
        if (textarea && typeof SimpleMDE !== 'undefined') {
            // Prevent duplicate editors if other scripts try to init
            if (!textarea.dataset.mdeInitialized) {
                new SimpleMDE({ element: textarea });
                textarea.dataset.mdeInitialized = '1';
            }
        }
    });
    ";
    wp_add_inline_script('simplemde-js', $init_js);
});


add_action('wp_ajax_mark_all_notifications_read', 'mark_all_notifications_read');
function mark_all_notifications_read()
{
    check_ajax_referer('notifications_nonce', 'security');

    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
    }

    $user_id = get_current_user_id();
    $notifications = Notifications::getInstance();
    $notifications->markAllAsRead($user_id);

    wp_send_json_success();
}

// Mark single as read
add_action('wp_ajax_mark_notification_read', function () {
    check_ajax_referer('notifications_nonce', 'security');
    $user_id  = get_current_user_id();
    $notif_id = sanitize_text_field($_POST['notification_id'] ?? '');

    $result = Notifications::getInstance()->markAsRead($user_id, $notif_id);

    wp_send_json_success($result);
});

// Clear all notifications
add_action('wp_ajax_clear_all_notifications', function () {
    check_ajax_referer('notifications_nonce', 'security');
    $user_id = get_current_user_id();

    $result = Notifications::getInstance()->clearNotifications($user_id);

    wp_send_json_success($result);
});

// Notifications Enqueue end

add_action('wp_footer', function () {
    if (function_exists('wp_enqueue_block_template_skip_link')) {
        wp_enqueue_block_template_skip_link();
    }
}, 1);


/**
 * Sort users by most recently registered (DESC) in admin
 */
add_action('pre_get_users', function ($query) {

    if (!is_admin()) {
        return;
    }

    global $pagenow;

    if ($pagenow === 'users.php' && empty($_GET['orderby'])) {
        $query->set('orderby', 'registered');
        $query->set('order', 'DESC');
    }
});
/**
 * Add Phone column in users list
 */
add_filter('manage_users_columns', function ($columns) {
    $columns['phone'] = 'Phone';
    return $columns;
});

/**
 * Show Phone value in users list
 */
add_filter('manage_users_custom_column', function ($value, $column_name, $user_id) {

    if ($column_name === 'phone') {
        $phone = get_user_meta($user_id, 'phone', true);
        return $phone ? esc_html($phone) : '—';
    }

    return $value;

}, 10, 3);


require_once get_template_directory() . '/inc/UserProfileData.php';
require_once get_template_directory() . '/inc/Notifications.php';
require_once get_template_directory() . '/inc/UserConnectionManager.php';
require_once get_template_directory() . '/more_functions/walker-menu.php';
require_once get_template_directory() . '/more_functions/walker-menu-v2.php';
require_once get_template_directory() . '/more_functions/authentication.php';

// ============================================
// Withdrawal Shortcode
// ============================================
add_shortcode('withdrawal_form', function() {
    if (!is_user_logged_in()) {
        return '<p style="text-align: center; padding: 20px; background: #f5f5f5; border-radius: 5px;">Please <a href="' . wp_login_url() . '">log in</a> to request a withdrawal.</p>';
    }
    ob_start();
    include get_template_directory() . '/template-custom/frontend/withdrawal-form.php';
    return ob_get_clean();
});

require_once get_template_directory() . '/more_functions/profile.php';
require_once get_template_directory() . '/more_functions/store.php';
require_once get_template_directory() . '/more_functions/paypalsettings.php';
require_once get_template_directory() . '/more_functions/paypal-api.php';
require_once get_template_directory() . '/more_functions/store-variations.php';
require_once get_template_directory() . '/more_functions/paypal-diagnostic-dashboard.php';
require_once get_template_directory() . '/more_functions/event.php';
require_once get_template_directory() . '/more_functions/blog.php';
require_once get_template_directory() . '/more_functions/video.php';
require_once get_template_directory() . '/more_functions/jobs.php';
require_once get_template_directory() . '/more_functions/agreement.php';
require_once get_template_directory() . '/more_functions/issues.php';
require_once get_template_directory() . '/more_functions/paypal-webhook.php';
require_once get_template_directory() . '/more_functions/course-sales-admin.php';

// Notifications System
require_once get_template_directory() . '/more_functions/notifications-dashboard.php';
require_once get_template_directory() . '/more_functions/notifications-ajax-handlers.php';
require_once get_template_directory() . '/more_functions/notifications-test-helper.php';
require_once get_template_directory() . '/more_functions/notifications-triggers.php';

// Social Media Integration (API credentials now managed by empowerment-social-poster plugin)
require_once get_template_directory() . '/more_functions/facebook-auth.php';

/**
 * Register custom template locations for page templates
 */
function mm_register_page_templates( $templates ) {
    $custom_templates = array(
        'template-custom/auth/collaboration-hub.php' => 'Collaboration Hub',
    );
    return array_merge( $templates, $custom_templates );
}
add_filter( 'theme_page_templates', 'mm_register_page_templates' );

/**
 * Load custom page templates from non-standard locations
 */
function mm_load_custom_page_template( $template ) {
    $post = get_post();
    if ( ! $post ) {
        return $template;
    }
    
    $page_template = get_post_meta( $post->ID, '_wp_page_template', true );
    $custom_template_path = get_template_directory() . '/' . $page_template;
    
    if ( $page_template && file_exists( $custom_template_path ) && strpos( $page_template, 'template-custom/auth/' ) !== false ) {
        return $custom_template_path;
    }
    
    return $template;
}
add_filter( 'page_template', 'mm_load_custom_page_template' );
require_once get_template_directory() . '/more_functions/social-auth-handler.php';
require_once get_template_directory() . '/more_functions/facebook-poster.php';
// LinkedIn: legacy theme OAuth/posting removed - now handled by empowerment-social-poster plugin
require_once get_template_directory() . '/more_functions/hire-experts-complete-wizard.php';

// Collaboration & LiveKit Integration
// Collaboration Suite (LiveKit-based) - DISABLED
// Disabled per request to remove LiveKit integration while preserving files.
// To re-enable, remove the `false &&` guard.
if (false) {
    require_once get_template_directory() . '/more_functions/collaboration.php';
}

// Debug Logs Viewer (for OAuth debugging)
require_once get_template_directory() . '/more_functions/debug-logs-viewer.php';
require_once get_template_directory() . '/more_functions/debug-paypal-plans.php';
// require_once get_template_directory() . '/more_functions/paypal-diagnostic.php'; // Use paypal-diagnostic-dashboard.php instead
require_once get_template_directory() . '/more_functions/check-db-diagnostic.php';

// Code from Adeel's team for Gamification (badges, points, etc.)
require_once get_template_directory() . '/more_functions/gamifications-functions.php';

// Ensure any previously scheduled LiveKit cron events are cleared (safety cleanup)
add_action('init', function() {
    if (function_exists('wp_clear_scheduled_hook')) {
        wp_clear_scheduled_hook('livekit_collect_metrics');
        wp_clear_scheduled_hook('livekit_cleanup_logs');
    }
}, 5);

/**
 * ============================================
 * CUSTOM ERROR PAGE HANDLER
 * ============================================
 * Override WordPress default error page styling
 * Prevents id="error-page" from breaking theme design
 */
add_filter('wp_die_handler', function() {
    return 'mm_custom_die_handler';
});

function mm_custom_die_handler($message, $title = '', $args = []) {
    $defaults = array(
        'response' => 500,
        'back_link' => false,
        'text_direction' => '',
        'charset' => 'utf-8',
    );

    $args = wp_parse_args($args, $defaults);
    $status_code = intval($args['response']);
    $title = !empty($title) ? $title : 'Error';
    
    // Set HTTP status
    status_header($status_code);
    nocache_headers();
    
    // Start output - WITHOUT id="error-page"
    if (!did_action('admin_head')) {
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php echo esc_attr($args['charset']); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($title); ?></title>
            <style>
                * { margin: 0; padding: 0; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", sans-serif;
                    line-height: 1.6;
                    color: #333;
                }
                .error-wrapper {
                    max-width: 600px;
                    margin: 60px auto;
                    padding: 30px;
                    background: #fff;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                h1 {
                    color: #d32f2f;
                    margin-bottom: 20px;
                    font-size: 28px;
                }
                p {
                    color: #666;
                    margin-bottom: 20px;
                    line-height: 1.8;
                }
                a {
                    color: #0073aa;
                    text-decoration: none;
                }
                a:hover {
                    text-decoration: underline;
                }
            </style>
        </head>
        <body>
            <div class="error-wrapper">
                <h1><?php echo esc_html($title); ?></h1>
                <div><?php echo wp_kses_post($message); ?></div>
                <?php
                if (!empty($args['back_link'])) {
                    ?><p><a href="<?php echo esc_url(wp_get_referer() ?: home_url()); ?>">&larr; Go Back</a></p><?php
                }
                ?>
            </div>
        </body>
        </html>
        <?php
    } else {
        // Admin area - simple display
        echo '<div style="max-width: 600px; margin: 20px auto; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 8px;">';
        echo '<h1 style="color: #d32f2f; margin-bottom: 20px;">' . esc_html($title) . '</h1>';
        echo '<div>' . wp_kses_post($message) . '</div>';
        echo '</div>';
    }
    
    die();
}



add_filter( 'template_include', 'custom_login_router_front_page', 99 );

function custom_login_router_front_page( $template ) {
    // Check if this is the site's front/home page
    if ( is_front_page() ) {
        global $wp_query;
        
        if ( WP_DEBUG ) {
            error_log( 'DEBUG router - is_front_page: true' );
            error_log( 'DEBUG router - is_user_logged_in: ' . (is_user_logged_in() ? 'true' : 'false') );
        }
        
        // If user is logged in, load the custom dashboard/home template
        if ( is_user_logged_in() ) {
            $logged_in_template = get_template_directory() . '/template-custom/auth/home.php';
            
            if ( file_exists( $logged_in_template ) ) {
                return $logged_in_template;
            }
        } else {
            // User is logged out: load "Brand New Welcome" page
            $welcome_page = get_page_by_path( 'brand-new-welcome', OBJECT, 'page' );
            
            if ( WP_DEBUG ) {
                error_log( 'DEBUG router - welcome_page found: ' . ($welcome_page ? $welcome_page->ID : 'NOT FOUND') );
            }
            
            if ( $welcome_page ) {
                // Set this page as the main query post
                $wp_query->set( 'page_id', $welcome_page->ID );
                $wp_query->is_page = true;
                $wp_query->is_singular = true;
                $wp_query->is_home = false;
                $wp_query->posts = array( $welcome_page );
                $wp_query->post_count = 1;
                $wp_query->post = $welcome_page;
                
                if ( WP_DEBUG ) {
                    error_log( 'DEBUG router - post_count set to: ' . $wp_query->post_count );
                }
                
                // Always use page.php for Brand New Welcome (ignore custom template assignment)
                return get_template_directory() . '/page.php';
            }
            
            // Fallback: load page selected in WordPress Reading settings
            if ( 'page' === get_option( 'show_on_front' ) ) {
                $page_id = get_option( 'page_on_front' );
                if ( $page_id ) {
                    // Get the page and set it up in the query
                    $front_page = get_post( $page_id );
                    if ( $front_page ) {
                        // Set this page as the main query post
                        $wp_query->set( 'page_id', $front_page->ID );
                        $wp_query->is_page = true;
                        $wp_query->is_singular = true;
                        $wp_query->is_home = false;
                        $wp_query->posts = array( $front_page );
                        $wp_query->post_count = 1;
                        $wp_query->post = $front_page;
                        
                        if ( WP_DEBUG ) {
                            error_log( 'DEBUG router - fallback page_on_front ID: ' . $page_id );
                        }
                        
                        return get_page_template();
                    }
                }
            }
        }
    }
    
    // Default: return template that WordPress determined (blog posts or other pages)
    return $template;
}


// End of theme functions.php
