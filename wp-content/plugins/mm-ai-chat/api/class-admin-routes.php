<?php
/**
 * Admin Routes - Admin and agent endpoints
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_Admin_Routes {

	/**
	 * Register routes
	 */
	public static function register_routes() {
		// Get active sessions
		register_rest_route( 'mm-ai-chat/v1', '/admin/sessions', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_sessions' ),
			'permission_callback' => array( __CLASS__, 'check_manage_capability' ),
		) );

		// Accept session
		register_rest_route( 'mm-ai-chat/v1', '/admin/sessions/(?P<session_id>\d+)/accept', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'accept_session' ),
			'permission_callback' => array( __CLASS__, 'check_agent_capability' ),
		) );

		// Get offline questions
		register_rest_route( 'mm-ai-chat/v1', '/admin/offline-questions', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_offline_questions' ),
			'permission_callback' => array( __CLASS__, 'check_manage_capability' ),
		) );

		// Answer offline question
		register_rest_route( 'mm-ai-chat/v1', '/admin/offline-questions/(?P<question_id>[a-zA-Z0-9_-]+)/answer', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'answer_offline_question' ),
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
	 * Check agent capability
	 */
	public static function check_agent_capability( $request ) {
		return current_user_can( 'mm_ai_chat_agent' );
	}

	/**
	 * Get sessions
	 */
	public static function get_sessions( $request ) {
		// Load required classes
		require_once dirname( __DIR__ ) . '/inc/class-database.php';

		$status = sanitize_text_field( $request->get_param( 'status' ) ?? '' );
		$limit = intval( $request->get_param( 'limit' ) ?? 20 );
		$offset = intval( $request->get_param( 'offset' ) ?? 0 );

		$sessions = MM_AI_Chat_Database::get_active_sessions( $status ?: null, $limit, $offset );

		if ( ! $sessions ) {
			$sessions = array();
		}

		$formatted = array();
		foreach ( $sessions as $session ) {
			$user = get_user_by( 'ID', $session->user_id );
			$agent = $session->agent_id ? get_user_by( 'ID', $session->agent_id ) : null;

			$formatted[] = array(
				'session_id'        => $session->id,
				'conversation_id'   => $session->conversation_id,
				'user_name'         => $user ? $user->display_name : 'Guest',
				'user_email'        => $user ? $user->user_email : '',
				'status'            => $session->status,
				'mode'              => $session->mode,
				'initiated_at'      => $session->initiated_at,
				'assigned_agent_id' => $session->agent_id,
				'assigned_agent_name' => $agent ? $agent->display_name : null,
			);
		}

		return new WP_REST_Response( array(
			'success'  => true,
			'sessions' => $formatted,
		), 200 );
	}

	/**
	 * Accept session
	 */
	public static function accept_session( $request ) {
		// Load required classes
		require_once dirname( __DIR__ ) . '/inc/class-chat-session.php';

		$session_id = intval( $request->get_param( 'session_id' ) );
		$agent_id = get_current_user_id();

		MM_AI_Chat_Session::assign_agent( $session_id, $agent_id );

		return new WP_REST_Response( array(
			'success'   => true,
			'message'   => 'Session accepted',
			'agent_id'  => $agent_id,
		), 200 );
	}

	/**
	 * Get offline questions
	 */
	public static function get_offline_questions( $request ) {
		// Load required classes
		require_once dirname( __DIR__ ) . '/inc/class-escalation-handler.php';
		require_once dirname( __DIR__ ) . '/inc/class-database.php';

		$status = sanitize_text_field( $request->get_param( 'status' ) ?? 'pending' );
		$limit = intval( $request->get_param( 'limit' ) ?? 20 );
		$offset = intval( $request->get_param( 'offset' ) ?? 0 );

		$questions = MM_AI_Chat_Escalation_Handler::get_pending_offline_questions( $limit, $offset );

		if ( ! $questions ) {
			$questions = array();
		}

		$formatted = array();
		foreach ( $questions as $question ) {
			$user = get_user_by( 'ID', $question->user_id );
			$answerer = $question->answered_by ? get_user_by( 'ID', $question->answered_by ) : null;

			$formatted[] = array(
				'id'           => $question->id,
				'question_id'  => $question->question_id,
				'user_name'    => $user ? $user->display_name : 'Guest',
				'user_email'   => $user ? $user->user_email : '',
				'question'     => $question->question,
				'answer'       => $question->answer,
				'status'       => $question->status,
				'priority'     => $question->priority,
				'answered_by'  => $answerer ? $answerer->display_name : null,
				'created_at'   => $question->created_at,
				'answered_at'  => $question->answered_at,
			);
		}

		return new WP_REST_Response( array(
			'success'     => true,
			'questions'   => $formatted,
		), 200 );
	}

	/**
	 * Answer offline question
	 */
	public static function answer_offline_question( $request ) {
		// Load required classes
		require_once dirname( __DIR__ ) . '/inc/class-escalation-handler.php';

		$question_id = sanitize_text_field( $request->get_param( 'question_id' ) );
		$params = $request->get_json_params();
		$answer = sanitize_textarea_field( $params['answer'] ?? '' );

		if ( ! $answer ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'Answer is required',
			), 400 );
		}

		MM_AI_Chat_Escalation_Handler::answer_offline_question( $question_id, $answer );

		return new WP_REST_Response( array(
			'success' => true,
			'message' => 'Question answered successfully',
		), 200 );
	}

	/**
	 * Test API key
	 */
	public static function test_api_key( $request ) {
		// Load required classes
		require_once dirname( __DIR__ ) . '/inc/class-api-key-handler.php';

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
			), 200 );
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
