<?php
/**
 * Admin Menu Handler
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_Admin_Menu {

	/**
	 * Initialize admin menu
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
	}

	/**
	 * Register admin menu
	 */
	public static function register_menu() {
		// Main menu
		add_menu_page(
			'AI Chat',
			'AI Chat',
			'manage_ai_chat',
			'mm-ai-chat',
			array( __CLASS__, 'dashboard_page' ),
			'dashicons-format-chat',
			26
		);

		// Dashboard submenu
		add_submenu_page(
			'mm-ai-chat',
			'Dashboard',
			'Dashboard',
			'manage_ai_chat',
			'mm-ai-chat',
			array( __CLASS__, 'dashboard_page' )
		);

		// Settings submenu
		add_submenu_page(
			'mm-ai-chat',
			'Settings',
			'Settings',
			'manage_ai_chat',
			'mm-ai-chat-settings',
			array( __CLASS__, 'settings_page' )
		);

		// Knowledge Base submenu
		add_submenu_page(
			'mm-ai-chat',
			'Knowledge Base',
			'Knowledge Base',
			'manage_ai_chat',
			'mm-ai-chat-kb',
			array( __CLASS__, 'kb_page' )
		);

		// Documentation submenu
		add_submenu_page(
			'mm-ai-chat',
			'API Documentation',
			'API Documentation',
			'manage_ai_chat',
			'mm-ai-chat-docs',
			array( __CLASS__, 'docs_page' )
		);
	}

	/**
	 * Dashboard page callback
	 */
	public static function dashboard_page() {
		require_once MM_AI_CHAT_PLUGIN_DIR . 'admin/class-dashboard.php';
		MM_AI_Chat_Dashboard::render();
	}

	/**
	 * Settings page callback
	 */
	public static function settings_page() {
		require_once MM_AI_CHAT_PLUGIN_DIR . 'admin/class-settings-page.php';
		MM_AI_Chat_Settings_Page::render();
	}

	/**
	 * KB page callback
	 */
	public static function kb_page() {
		require_once MM_AI_CHAT_PLUGIN_DIR . 'admin/class-kb-manager.php';
		MM_AI_Chat_KB_Manager::render();
	}

	/**
	 * Documentation page callback
	 */
	public static function docs_page() {
		require_once MM_AI_CHAT_PLUGIN_DIR . 'admin/class-api-docs.php';
		MM_AI_Chat_API_Docs::render();
	}
}
