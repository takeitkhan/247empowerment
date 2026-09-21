<?php
/**
 * Frontend Enqueue - Scripts and Styles
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_Enqueue {

	/**
	 * Initialize enqueue
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue styles
	 */
	public static function enqueue_styles() {
		if ( ! get_option( 'mm_ai_chat_enabled' ) || ! is_user_logged_in() ) {
			return;
		}

		wp_enqueue_style(
			'mm-ai-chat-widget',
			MM_AI_CHAT_PLUGIN_URL . 'public/assets/css/chat-widget.css',
			array(),
			MM_AI_CHAT_VERSION
		);
	}

	/**
	 * Enqueue scripts
	 */
	public static function enqueue_scripts() {
		if ( ! get_option( 'mm_ai_chat_enabled' ) || ! is_user_logged_in() ) {
			return;
		}

		wp_enqueue_script(
			'mm-ai-chat-widget',
			MM_AI_CHAT_PLUGIN_URL . 'public/assets/js/chat-widget.js',
			array( 'jquery' ),
			MM_AI_CHAT_VERSION,
			true
		);

		// Localize script with rest API data
		$user = wp_get_current_user();
		wp_localize_script( 'mm-ai-chat-widget', 'mmAiChatData', array(
			'restUrl'           => rest_url( 'mm-ai-chat/v1' ),
			'nonce'             => wp_create_nonce( 'wp_rest' ),
			'chatNonce'         => wp_create_nonce( 'mm_ai_chat_nonce' ),
			'userId'            => get_current_user_id(),
			'userEmail'         => $user->user_email ?? '',
			'userName'          => $user->display_name ?? '',
			'escalationEnabled' => (string) get_option( 'mm_ai_chat_escalation_enabled', '1' ) !== '0',
			'offlineEnabled'    => (string) get_option( 'mm_ai_chat_offline_questions_enabled', '1' ) !== '0',
		) );
	}
}
