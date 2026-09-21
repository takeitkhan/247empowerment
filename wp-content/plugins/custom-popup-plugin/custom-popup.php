<?php
/*
Plugin Name: Custom Scaling Popup
Description: Displays a scaling popup for guest users with typing animation.
Version: 2.1
Author: Hassan
*/

if (!defined('ABSPATH')) {
    exit;
}

function is_auth_page() {
    if ( ! function_exists( 'is_page' ) ) return false;
    
    // Also hide on user profile pages (/joshuajoseph/, /adeel/ etc.)
    if ( get_query_var('user_profile') ) return true;
    
    return is_page( array( 'signin', 'signup', 'guide' ) );
}

/*
|--------------------------------------------------------------------------
| FRONTEND SCRIPTS & STYLES
|--------------------------------------------------------------------------
*/
function custom_popup_enqueue_scripts() {
    
    if ( is_auth_page() ) {
        return;
    }

    wp_enqueue_style(
    'custom-popup-style',
    plugin_dir_url(__FILE__) . 'assets/style.css',
    array(),
    filemtime(plugin_dir_path(__FILE__) . 'assets/style.css')
);

    wp_enqueue_script(
        'typed-js',
        'https://cdn.jsdelivr.net/npm/typed.js@2.0.12',
        array(),
        null,
        true
    );

    wp_enqueue_script(
    'custom-popup-script',
    plugin_dir_url(__FILE__) . 'assets/script.js',
    array('typed-js'),
    filemtime(plugin_dir_path(__FILE__) . 'assets/script.js'),
    true
);

    wp_localize_script('custom-popup-script', 'popupData', array(
        'imageUrl' => plugin_dir_url(__FILE__) . 'assets/images/popup-bg.png'
    ));
}
add_action('wp_enqueue_scripts', 'custom_popup_enqueue_scripts');


/*
|--------------------------------------------------------------------------
| POPUP HTML
|--------------------------------------------------------------------------
*/
function custom_popup_html() {


    if ( is_user_logged_in() || is_auth_page() ) {
        return;
    }

   // '?v=1' add karne se browser ko lagega ye nayi file hai
$bg_url = plugin_dir_url(__FILE__) . 'assets/images/popup-bg.png?v=' . time();
    ?>
    <div id="custom-popup">
        <div class="popup-content" style="background-image: url('<?php echo esc_url($bg_url); ?>');">
            <h2 id="typing-heading"></h2>

            <ul class="popup-list">
                <li data-text="$600 Referrals"></li>
                <li data-text="Lifetime Passive Income"></li>
                <li data-text="50% Commission"></li>
                <li data-text="Points and Credits"></li>
            </ul>

            <div class="popup-button">
                <a href="https://personalempowermentteams.me/signup/" class="signup" id="popup-btn">
                   Sign Up
                </a>
            </div>

            <span class="popup-close">&times;</span>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'custom_popup_html');


/*
|--------------------------------------------------------------------------
| ADMIN MENU & DOCS (Unchanged)
|--------------------------------------------------------------------------
*/
add_action('admin_menu', 'custom_popup_admin_menu');
function custom_popup_admin_menu() {
    add_menu_page('Custom Scaling Popup', 'Custom Popup', 'manage_options', 'custom-popup-docs', 'custom_popup_docs_page', 'dashicons-format-chat', 25);
}

function custom_popup_docs_page() {
    ?>
   
    <div class="wrap">
        <h1>Custom Scaling Popup</h1>

        <p>
            <strong>Purpose</strong><br>
            This plugin displays a scaling popup with typing animation
            only to users who are not logged in.
        </p>

        <hr>

        <h2>Features</h2>
        <ul>
            <li>Popup shown only to guest users</li>
            <li>Smooth scale animation</li>
            <li>Typing text effect</li>
            <li>Login and Signup buttons</li>
            <li>Close option available</li>
        </ul>

        <hr>

        <h2>How to Use</h2>
        <ol>
            <li>Install and activate the plugin</li>
            <li>No shortcode required</li>
            <li>Popup appears automatically on frontend</li>
        </ol>

        <p style="margin-top:20px;">
            <em>Developed by Hassan</em>
        </p>
    </div>

    <?php
}