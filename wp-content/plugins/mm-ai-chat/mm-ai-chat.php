<?php
/**
 * Plugin Name: MM AI Chat
 * Plugin URI: https://personalempowermentteams.me
 * Description: Intelligent AI Chat Widget with Human Takeover using OpenAI & WordPress REST API
 * Version: 1.0.5
 * Author: Samrat Khan
 * Author URI: https://personalempowermentteams.me
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mm-ai-chat
 * Domain Path: /languages
 *
 * @package MM_AI_Chat
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'MM_AI_CHAT_VERSION', '1.0.7' );
define( 'MM_AI_CHAT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MM_AI_CHAT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MM_AI_CHAT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Load plugin text domain
 */
function mm_ai_chat_load_textdomain() {
	load_plugin_textdomain( 'mm-ai-chat', false, dirname( MM_AI_CHAT_PLUGIN_BASENAME ) . '/languages' );
}
add_action( 'plugins_loaded', 'mm_ai_chat_load_textdomain' );

/**
 * Autoload plugin classes
 */
function mm_ai_chat_autoload( $class ) {
	if ( strpos( $class, 'MM_AI_Chat_' ) !== 0 ) {
		return;
	}

	$overrides = array(
		'MM_AI_Chat_Session' => 'inc/class-chat-session.php',
	);

	if ( isset( $overrides[ $class ] ) ) {
		$file_path = MM_AI_CHAT_PLUGIN_DIR . $overrides[ $class ];
		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		}
		return;
	}

	$suffix     = substr( $class, strlen( 'MM_AI_Chat_' ) );
	$class_file = 'class-' . str_replace( '_', '-', strtolower( $suffix ) ) . '.php';
	$dirs       = array( 'inc', 'admin', 'api', 'public' );

	foreach ( $dirs as $dir ) {
		$file_path = MM_AI_CHAT_PLUGIN_DIR . $dir . '/' . $class_file;
		if ( file_exists( $file_path ) ) {
			require_once $file_path;
			return;
		}
	}
}

spl_autoload_register( 'mm_ai_chat_autoload' );

/**
 * Initialize plugin on activation
 */
function mm_ai_chat_activate() {
	require_once MM_AI_CHAT_PLUGIN_DIR . 'inc/class-activation.php';
	MM_AI_Chat_Activation::activate();
}
register_activation_hook( __FILE__, 'mm_ai_chat_activate' );

/**
 * Clean up on deactivation
 */
function mm_ai_chat_deactivate() {
	require_once MM_AI_CHAT_PLUGIN_DIR . 'inc/class-deactivation.php';
	MM_AI_Chat_Deactivation::deactivate();
}
register_deactivation_hook( __FILE__, 'mm_ai_chat_deactivate' );

/**
 * Initialize plugin
 */
function mm_ai_chat_init() {
	// Load database class
	require_once MM_AI_CHAT_PLUGIN_DIR . 'inc/class-database.php';
	MM_AI_Chat_Database::maybe_create_tables();

	// Initialize capability manager
	require_once MM_AI_CHAT_PLUGIN_DIR . 'inc/class-capability-manager.php';
	MM_AI_Chat_Capability_Manager::init();

	// Load admin classes
	if ( is_admin() ) {
		require_once MM_AI_CHAT_PLUGIN_DIR . 'admin/class-admin-menu.php';
		MM_AI_Chat_Admin_Menu::init();

		require_once MM_AI_CHAT_PLUGIN_DIR . 'admin/class-settings-page.php';
		MM_AI_Chat_Settings_Page::init();

		require_once MM_AI_CHAT_PLUGIN_DIR . 'admin/class-kb-manager.php';
		MM_AI_Chat_KB_Manager::init();

		require_once MM_AI_CHAT_PLUGIN_DIR . 'admin/class-dashboard.php';
		MM_AI_Chat_Dashboard::init();

		require_once MM_AI_CHAT_PLUGIN_DIR . 'admin/class-api-docs.php';
		MM_AI_Chat_API_Docs::init();
	}

	// Load public classes
	if ( ! is_admin() ) {
		require_once MM_AI_CHAT_PLUGIN_DIR . 'public/class-enqueue.php';
		MM_AI_Chat_Enqueue::init();

		require_once MM_AI_CHAT_PLUGIN_DIR . 'public/class-widget.php';
		MM_AI_Chat_Widget::init();
	}

	// Register REST API routes
	add_action( 'rest_api_init', 'mm_ai_chat_register_rest_routes' );
}
add_action( 'plugins_loaded', 'mm_ai_chat_init' );

/**
 * Register REST API routes
 */
function mm_ai_chat_register_rest_routes() {
	require_once MM_AI_CHAT_PLUGIN_DIR . 'api/class-chat-routes.php';
	require_once MM_AI_CHAT_PLUGIN_DIR . 'api/class-admin-routes.php';
	require_once MM_AI_CHAT_PLUGIN_DIR . 'api/class-kb-routes.php';
	require_once MM_AI_CHAT_PLUGIN_DIR . 'api/class-settings-routes.php';

	MM_AI_Chat_Chat_Routes::register_routes();
	MM_AI_Chat_Admin_Routes::register_routes();
	MM_AI_Chat_KB_Routes::register_routes();
	MM_AI_Chat_Settings_Routes::register_routes();
}
