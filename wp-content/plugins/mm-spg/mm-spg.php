<?php

/**
 * Plugin Name: MM Sweet Portal Guide
 * Plugin URI:  https://mathmozo.com
 * Description: Minimal gamified onboarding guide with avatar and modal steps.
 * Version: 1.0.1
 * License: MIT
 * Author: Samrat Khan
 * Text Domain: mm-spg
 */

if (!defined('ABSPATH')) {
    exit; // No direct access
}

/**
 * Plugin constants
 */
define('MM_SPG_VERSION', '0.2.8');
define('MM_SPG_PATH', plugin_dir_path(__FILE__));
define('MM_SPG_URL', plugin_dir_url(__FILE__));

// Load Frontend UI
require_once(MM_SPG_PATH . '/inc/frontend/dynamic-steps.php');
require_once(MM_SPG_PATH . '/inc/frontend/bootstrap.php');
require_once(MM_SPG_PATH . '/inc/frontend/current-guide-state.php');
//require_once(MM_SPG_PATH . '/inc/frontend/modal.php');
require_once(MM_SPG_PATH . '/inc/frontend/shortcodes.php');

// Load API Endpoints
require_once(MM_SPG_PATH . '/inc/api/auth-endpoints.php');
require_once(MM_SPG_PATH . '/inc/api/interests-endpoints.php');
require_once(MM_SPG_PATH . '/inc/api/business-card-endpoints.php');
require_once(MM_SPG_PATH . '/inc/api/social-links-endpoints.php');
require_once(MM_SPG_PATH . '/inc/api/leaderboard-endpoints.php');

// Load Admin Documentation
require_once(MM_SPG_PATH . '/inc/admin/api-documentation.php');

// Hardcoded steps loader (deprecated)
// require_once(MM_SPG_PATH . '/inc/frontend/hardcoded-steps.php');
// Frontend (dynamic) steps loader


// Load Admin UI (backend)
require_once MM_SPG_PATH . 'inc/backend/post-types/spg-step-cpt.php';
require_once MM_SPG_PATH . 'inc/backend/meta-boxes/step-settings.php';
require_once MM_SPG_PATH . 'inc/backend/meta-boxes/step-blocks.php';
require_once MM_SPG_PATH . 'inc/backend/save/save-step-meta.php';
require_once MM_SPG_PATH . 'inc/backend/user-profile.php';

require_once __DIR__ . '/inc/backend/admin-enqueue.php';
require_once __DIR__ . '/inc/backend/spg-step-sort.php';
