<?php
/**
 * Plugin Name: MM Social Poster
 * Description: Lets every portal user connect their own LinkedIn account and cross-post portal posts to it. The portal owner configures the LinkedIn app credentials under Settings → Social Poster.
 * Version: 1.0.0
 * Author: 247 Empowerment
 * Text Domain: mm-social-poster
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('MM_SOCIAL_POSTER_LOADED', true);
define('MM_SOCIAL_POSTER_VERSION', '1.0.0');
define('MM_SOCIAL_POSTER_FILE', __FILE__);
define('MM_SOCIAL_POSTER_DIR', plugin_dir_path(__FILE__));
define('MM_SOCIAL_POSTER_URL', plugin_dir_url(__FILE__));

require_once MM_SOCIAL_POSTER_DIR . 'includes/class-mm-social-poster-crypto.php';
require_once MM_SOCIAL_POSTER_DIR . 'includes/class-mm-social-poster-db.php';
require_once MM_SOCIAL_POSTER_DIR . 'includes/class-mm-social-poster-linkedin.php';
require_once MM_SOCIAL_POSTER_DIR . 'includes/class-mm-social-poster-publisher.php';
require_once MM_SOCIAL_POSTER_DIR . 'includes/class-mm-social-poster-frontend.php';
require_once MM_SOCIAL_POSTER_DIR . 'includes/admin/class-mm-social-poster-settings.php';
require_once MM_SOCIAL_POSTER_DIR . 'includes/functions.php';

register_activation_hook(__FILE__, array('MM_Social_Poster_DB', 'install'));

add_action('plugins_loaded', function () {
    MM_Social_Poster_DB::maybe_upgrade();
    MM_Social_Poster_Frontend::init();

    if (is_admin()) {
        MM_Social_Poster_Settings::init();
    }
});
