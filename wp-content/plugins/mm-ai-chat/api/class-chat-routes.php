<?php
/**
 * Chat Routes - User-facing chat endpoints
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_Chat_Routes {

	/**
	 * Register routes
	 */
	public static function register_routes() {
		register_rest_route( 'mm-ai-chat/v1', '/chat/initiate', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'initiate_chat' ),
			'permission_callback' => array( __CLASS__, 'check_nonce' ),
		) );

		register_rest_route( 'mm-ai-chat/v1', '/chat/message', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'send_message' ),
			'permission_callback' => array( __CLASS__, 'check_nonce' ),
		) );

		register_rest_route( 'mm-ai-chat/v1', '/chat/messages/(?P<conversation_id>[a-zA-Z0-9_-]+)', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_messages' ),
			'permission_callback' => array( __CLASS__, 'check_session_access' ),
		) );

		register_rest_route( 'mm-ai-chat/v1', '/chat/escalate/offline', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'escalate_offline' ),
			'permission_callback' => array( __CLASS__, 'check_nonce' ),
		) );

		register_rest_route( 'mm-ai-chat/v1', '/chat/escalate/agent', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'escalate_to_agent' ),
			'permission_callback' => array( __CLASS__, 'check_nonce' ),
		) );

		register_rest_route( 'mm-ai-chat/v1', '/chat/agent-reply', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'agent_reply' ),
			'permission_callback' => array( __CLASS__, 'check_agent_capability' ),
		) );

		register_rest_route( 'mm-ai-chat/v1', '/chat/close', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'close_chat' ),
			'permission_callback' => array( __CLASS__, 'check_nonce' ),
		) );
	}

	/**
	 * Check nonce / logged-in user
	 */
	public static function check_nonce( $request ) {
		$nonce = $request->get_param( 'nonce' );
		if ( $nonce && wp_verify_nonce( $nonce, 'mm_ai_chat_nonce' ) ) {
			return true;
		}
		$header = $request->get_header( 'X-WP-Nonce' );
		if ( $header && wp_verify_nonce( $header, 'mm_ai_chat_nonce' ) ) {
			return true;
		}
		if ( $header && wp_verify_nonce( $header, 'wp_rest' ) ) {
			return true;
		}
		return is_user_logged_in();
	}

	/**
	 * Check session access
	 */
	public static function check_session_access( $request ) {
		require_once dirname( __DIR__ ) . '/inc/class-database.php';
		require_once dirname( __DIR__ ) . '/inc/class-chat-session.php';

		$conversation_id = $request->get_param( 'conversation_id' );
		$session = MM_AI_Chat_Session::get_by_conversation_id( $conversation_id );
		$user_id = get_current_user_id();
		return $session && MM_AI_Chat_Session::user_has_access( $conversation_id, $user_id );
	}

	/**
	 * Check agent capability
	 */
	public static function check_agent_capability( $request ) {
		return current_user_can( 'mm_ai_chat_agent' );
	}

	/**
	 * Initiate chat
	 */
	public static function initiate_chat( $request ) {
		require_once dirname( __DIR__ ) . '/inc/class-api-key-handler.php';
		require_once dirname( __DIR__ ) . '/inc/class-chat-session.php';
		require_once dirname( __DIR__ ) . '/inc/class-knowledge-base.php';
		require_once dirname( __DIR__ ) . '/inc/class-openai-client.php';
		require_once dirname( __DIR__ ) . '/inc/class-database.php';

		MM_AI_Chat_Database::maybe_create_tables();

		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		$session = MM_AI_Chat_Session::initiate(
			get_current_user_id(),
			isset( $params['page_context'] ) ? $params['page_context'] : array()
		);

		if ( empty( $session['session_id'] ) || empty( $session['conversation_id'] ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'Could not create chat session. Database tables may be missing — deactivate and reactivate the MM AI Chat plugin.',
			), 500 );
		}

		$welcome_message = 'Welcome! How can I help you today?';
		$api_warning     = '';

		if ( MM_AI_Chat_API_Key_Handler::is_api_key_configured() ) {
			$kb_items      = MM_AI_Chat_Knowledge_Base::retrieve_context( 'greeting', 3 );
			$system_prompt = MM_AI_Chat_Knowledge_Base::build_system_prompt_with_context( $kb_items );

			MM_AI_Chat_OpenAI_Client::init();
			$ai_result = MM_AI_Chat_OpenAI_Client::send_message(
				$system_prompt,
				'Greet the user warmly and ask how you can help them today.',
				array()
			);

			if ( ! empty( $ai_result['success'] ) && ! empty( $ai_result['response'] ) ) {
				$welcome_message = $ai_result['response'];
				MM_AI_Chat_Database::save_message(
					$session['session_id'],
					'ai',
					$welcome_message,
					null,
					isset( $ai_result['metadata'] ) ? $ai_result['metadata'] : null
				);
			} else {
				$api_warning = isset( $ai_result['error'] ) ? $ai_result['error'] : 'OpenAI request failed';
				MM_AI_Chat_Database::save_message( $session['session_id'], 'ai', $welcome_message );
			}
		} else {
			$api_warning = 'OpenAI API key is not configured. Add your key in AI Chat → Settings.';
			MM_AI_Chat_Database::save_message( $session['session_id'], 'ai', $welcome_message );
		}

		return new WP_REST_Response( array(
			'success'         => true,
			'conversation_id' => $session['conversation_id'],
			'session_id'      => $session['session_id'],
			'mode'            => 'ai',
			'status'          => 'active',
			'message'         => array(
				'id'          => 'msg_' . wp_generate_uuid4(),
				'sender_type' => 'ai',
				'content'     => $welcome_message,
				'created_at'  => current_time( 'c' ),
			),
			'api_warning'     => $api_warning,
		), 200 );
	}

	/**
	 * Send message
	 */
	public static function send_message( $request ) {
		self::load_message_dependencies();

		$params = $request->get_json_params();
		$conversation_id = sanitize_text_field( $params['conversation_id'] ?? '' );
		$message = sanitize_textarea_field( $params['message'] ?? '' );

		if ( ! $conversation_id || ! $message ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'Missing required parameters',
			), 400 );
		}

		$result = MM_AI_Chat_Message_Handler::process_user_message( $conversation_id, $message );

		if ( ! $result['success'] ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => $result['error'],
				'code'    => $result['code'] ?? 'unknown',
			), 500 );
		}

		return new WP_REST_Response( array(
			'success'      => true,
			'ai_response'  => $result['ai_response'],
			'metadata'     => $result['metadata'],
			'kb_sources'   => $result['kb_sources'],
		), 200 );
	}

	/**
	 * Get messages
	 */
	public static function get_messages( $request ) {
		// Load required classes
		require_once dirname( __DIR__ ) . '/inc/class-chat-session.php';
		require_once dirname( __DIR__ ) . '/inc/class-message-handler.php';

		$conversation_id = $request->get_param( 'conversation_id' );
		$limit = intval( $request->get_param( 'limit' ) ?? 50 );
		$offset = intval( $request->get_param( 'offset' ) ?? 0 );

		$session = MM_AI_Chat_Session::get_by_conversation_id( $conversation_id );
		if ( ! $session ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'Session not found',
			), 404 );
		}

		$messages = MM_AI_Chat_Message_Handler::get_session_messages( $session->id, $limit, $offset );

		return new WP_REST_Response( array(
			'success'          => true,
			'conversation_id'  => $conversation_id,
			'messages'         => $messages,
		), 200 );
	}

	/**
	 * Escalate offline
	 */
	public static function escalate_offline( $request ) {
		self::load_escalation_dependencies();

		$params = $request->get_json_params();
		$conversation_id = sanitize_text_field( $params['conversation_id'] ?? '' );
		$question = sanitize_textarea_field( $params['question'] ?? '' );

		if ( ! $conversation_id || ! $question ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'Missing required parameters',
			), 400 );
		}

		$result = MM_AI_Chat_Escalation_Handler::escalate_offline( $conversation_id, $question );

		return new WP_REST_Response( $result, $result['success'] ? 200 : 500 );
	}

	/**
	 * Escalate to agent
	 */
	public static function escalate_to_agent( $request ) {
		self::load_escalation_dependencies();

		$params = $request->get_json_params();
		$conversation_id = sanitize_text_field( $params['conversation_id'] ?? '' );
		$reason = sanitize_textarea_field( $params['reason'] ?? '' );

		if ( ! $conversation_id ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'Missing conversation_id',
			), 400 );
		}

		$result = MM_AI_Chat_Escalation_Handler::escalate_to_agent( $conversation_id, $reason );

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Agent reply
	 */
	public static function agent_reply( $request ) {
		self::load_message_dependencies();

		$params = $request->get_json_params();
		$conversation_id = sanitize_text_field( $params['conversation_id'] ?? '' );
		$message = sanitize_textarea_field( $params['message'] ?? '' );

		if ( ! $conversation_id || ! $message ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'Missing required parameters',
			), 400 );
		}

		$agent_id = get_current_user_id();
		$result = MM_AI_Chat_Message_Handler::process_agent_message( $conversation_id, $agent_id, $message );

		return new WP_REST_Response( $result, $result['success'] ? 200 : 500 );
	}

	/**
	 * Close chat
	 */
	public static function close_chat( $request ) {
		// Load required classes
		require_once dirname( __DIR__ ) . '/inc/class-chat-session.php';

		$params = $request->get_json_params();
		$conversation_id = sanitize_text_field( $params['conversation_id'] ?? '' );
		$reason = sanitize_text_field( $params['reason'] ?? 'user_closed' );

		if ( ! $conversation_id ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'Missing conversation_id',
			), 400 );
		}

		MM_AI_Chat_Session::close( $conversation_id, $reason );

		return new WP_REST_Response( array(
			'success' => true,
			'message' => 'Chat closed successfully',
		), 200 );
	}

	/**
	 * Load classes required for message processing
	 */
	private static function load_message_dependencies() {
		require_once dirname( __DIR__ ) . '/inc/class-message-handler.php';
	}

	/**
	 * Load classes required for escalation endpoints
	 */
	private static function load_escalation_dependencies() {
		require_once dirname( __DIR__ ) . '/inc/class-database.php';
		require_once dirname( __DIR__ ) . '/inc/class-chat-session.php';
		require_once dirname( __DIR__ ) . '/inc/class-escalation-handler.php';
	}
}
