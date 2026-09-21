<?php
/**
 * Settings Routes - Settings management endpoints
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_Settings_Routes {

	/**
	 * Register routes
	 */
	public static function register_routes() {
		// Get settings
		register_rest_route( 'mm-ai-chat/v1', '/admin/settings', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_settings' ),
			'permission_callback' => array( __CLASS__, 'check_manage_capability' ),
		) );

		// Update settings
		register_rest_route( 'mm-ai-chat/v1', '/admin/settings', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'update_settings' ),
			'permission_callback' => array( __CLASS__, 'check_manage_capability' ),
		) );

		// Test API key
		register_rest_route( 'mm-ai-chat/v1', '/admin/test-api-key', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'test_api_key' ),
			'permission_callback' => array( __CLASS__, 'check_manage_capability' ),
		) );
	}

	/**
	 * Check manage capability
	 */
	public static function check_manage_capability( $request ) {
		return current_user_can( 'manage_ai_chat' ) || current_user_can( 'manage_options' );
	}

	/**
	 * Get settings
	 */
	public static function get_settings( $request ) {
		// Load required classes
		require_once dirname( __DIR__ ) . '/inc/class-api-key-handler.php';

		return new WP_REST_Response( array(
			'success' => true,
			'settings' => array(
				'openai_api_key_configured' => MM_AI_Chat_API_Key_Handler::is_api_key_configured(),
				'openai_model'              => MM_AI_Chat_API_Key_Handler::get_model(),
				'openai_temperature'        => MM_AI_Chat_API_Key_Handler::get_temperature(),
				'plugin_enabled'            => get_option( 'mm_ai_chat_enabled', 1 ),
				'escalation_enabled'        => get_option( 'mm_ai_chat_escalation_enabled', 1 ),
				'offline_questions_enabled' => get_option( 'mm_ai_chat_offline_questions_enabled', 1 ),
				'agent_notifications_enabled' => get_option( 'mm_ai_chat_agent_notifications_enabled', 1 ),
				'widget_position'           => get_option( 'mm_ai_chat_widget_position', 'bottom-right' ),
				'kb_system_prompt'          => MM_AI_Chat_API_Key_Handler::get_system_prompt(),
				'available_models'          => array( 'gpt-5.5', 'gpt-5', 'gpt-4o', 'gpt-4-turbo', 'gpt-3.5-turbo' ),
			),
		), 200 );
	}

	/**
	 * Update settings
	 */
	public static function update_settings( $request ) {
		// Load required classes
		require_once dirname( __DIR__ ) . '/inc/class-api-key-handler.php';

		$params = $request->get_json_params();

		// Update OpenAI API key if provided
		if ( isset( $params['openai_api_key'] ) ) {
			if ( ! MM_AI_Chat_API_Key_Handler::save_api_key( $params['openai_api_key'] ) ) {
				return new WP_REST_Response( array(
					'success' => false,
					'error'   => 'Invalid API key format',
				), 400 );
			}
		}

		// Update other settings
		$settings = array(
			'mm_ai_chat_openai_model'               => 'openai_model',
			'mm_ai_chat_openai_temperature'         => 'openai_temperature',
			'mm_ai_chat_enabled'                    => 'plugin_enabled',
			'mm_ai_chat_escalation_enabled'         => 'escalation_enabled',
			'mm_ai_chat_offline_questions_enabled'  => 'offline_questions_enabled',
			'mm_ai_chat_widget_position'            => 'widget_position',
			'mm_ai_chat_kb_system_prompt'           => 'kb_system_prompt',
		);

		foreach ( $settings as $option_name => $param_name ) {
			if ( isset( $params[ $param_name ] ) ) {
				update_option( $option_name, $params[ $param_name ] );
			}
		}

		return new WP_REST_Response( array(
			'success' => true,
			'message' => 'Settings updated successfully',
		), 200 );
	}

	/**
	 * Test OpenAI API key
	 */
	public static function test_api_key( $request ) {
		// Ensure API Key Handler is loaded
		require_once dirname( __DIR__ ) . '/inc/class-api-key-handler.php';

		// Get API key from JSON body
		$params = $request->get_json_params();
		
		// Fallback: try to get from body as raw JSON if get_json_params fails
		if ( ! is_array( $params ) || empty( $params ) ) {
			$body = $request->get_body();
			$params = json_decode( $body, true );
		}

		$api_key = '';
		if ( is_array( $params ) && isset( $params['api_key'] ) ) {
			$api_key = sanitize_text_field( $params['api_key'] );
		}

		// If no API key in request, try to get saved one
		if ( empty( $api_key ) ) {
			$api_key = MM_AI_Chat_API_Key_Handler::get_api_key();
		}

		if ( empty( $api_key ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'No API key provided or saved',
			), 400 );
		}

		// Test the API key by calling OpenAI models endpoint
		$response = wp_remote_get(
			'https://api.openai.com/v1/models',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'timeout'   => 10,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'Connection failed: ' . $response->get_error_message(),
			), 200 );  // Return 200 so JavaScript handles it
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );

		if ( $status_code === 200 ) {
			return new WP_REST_Response( array(
				'success' => true,
				'message' => '✓ API key is valid and working',
			), 200 );
		} else {
			$data = json_decode( $body, true );
			$error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Invalid API key (HTTP ' . $status_code . ')';

			return new WP_REST_Response( array(
				'success' => false,
				'error'   => $error_message,
			), 200 );  // Return 200 so JavaScript can handle error properly
		}
	}
}

