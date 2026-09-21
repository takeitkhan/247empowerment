<?php
/**
 * Plugin Activation Handler
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_Activation {

	/**
	 * Run activation routines
	 */
	public static function activate() {
		// Create database tables
		require_once MM_AI_CHAT_PLUGIN_DIR . 'inc/class-database.php';
		MM_AI_Chat_Database::create_tables();

		// Add capabilities
		require_once MM_AI_CHAT_PLUGIN_DIR . 'inc/class-capability-manager.php';
		MM_AI_Chat_Capability_Manager::add_capabilities();

		// Set default options
		self::set_default_options();

		// Flush rewrite rules
		flush_rewrite_rules();
	}

	/**
	 * Set default plugin options
	 */
	private static function set_default_options() {
		$defaults = array(
			'mm_ai_chat_enabled'                        => 1,
			'mm_ai_chat_widget_position'                => 'bottom-right',
			'mm_ai_chat_openai_temperature'             => '0.7',
			'mm_ai_chat_escalation_enabled'             => 1,
			'mm_ai_chat_offline_questions_enabled'      => 1,
			'mm_ai_chat_agent_notifications_enabled'    => 1,
			'mm_ai_chat_kb_system_prompt'               => self::get_default_system_prompt(),
		);

		foreach ( $defaults as $key => $value ) {
			if ( ! get_option( $key ) ) {
				add_option( $key, $value );
			}
		}
	}

	/**
	 * Get default system prompt
	 */
	private static function get_default_system_prompt() {
		return 'You are a helpful AI customer support assistant. Provide accurate, professional responses based on the provided knowledge base. If a question is not covered, be honest about limitations and offer to escalate to a human agent.';
	}
}
