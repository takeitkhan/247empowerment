<?php
/**
 * Message Handler
 *
 * @package MM_AI_Chat
 */

require_once dirname( __FILE__ ) . '/class-database.php';
require_once dirname( __FILE__ ) . '/class-chat-session.php';
require_once dirname( __FILE__ ) . '/class-api-key-handler.php';
require_once dirname( __FILE__ ) . '/class-knowledge-base.php';
require_once dirname( __FILE__ ) . '/class-openai-client.php';

class MM_AI_Chat_Message_Handler {

	/**
	 * Process user message and generate AI response
	 */
	public static function process_user_message( $conversation_id, $user_message ) {
		// Get session
		$session = MM_AI_Chat_Session::get_by_conversation_id( $conversation_id );
		if ( ! $session || 'ai' !== $session->mode ) {
			return array(
				'success' => false,
				'error'   => 'Invalid session or not in AI mode',
			);
		}

		// Check if OpenAI is configured
		if ( ! MM_AI_Chat_API_Key_Handler::is_api_key_configured() ) {
			return array(
				'success' => false,
				'error'   => 'OpenAI API not configured',
				'code'    => 'openai_not_configured',
			);
		}

		// Save user message
		$user_message_id = MM_AI_Chat_Database::save_message(
			$session->id,
			'user',
			$user_message,
			$session->user_id
		);

		// Retrieve knowledge base context
		$kb_items = MM_AI_Chat_Knowledge_Base::retrieve_context( $user_message );

		// Build system prompt with KB context
		$system_prompt = MM_AI_Chat_Knowledge_Base::build_system_prompt_with_context( $kb_items );

		// Get conversation history (last 5 messages)
		$history = self::get_conversation_history( $session->id, 5 );

		// Call OpenAI API
		MM_AI_Chat_OpenAI_Client::init();
		$ai_result = MM_AI_Chat_OpenAI_Client::send_message_with_retry( $system_prompt, $user_message, $history );

		if ( ! $ai_result['success'] ) {
			return array(
				'success' => false,
				'error'   => $ai_result['error'],
				'code'    => $ai_result['code'] ?? 'unknown',
			);
		}

		// Save AI response
		$ai_message_id = MM_AI_Chat_Database::save_message(
			$session->id,
			'ai',
			$ai_result['response'],
			null,
			$ai_result['metadata']
		);

		// Update session last message time
		MM_AI_Chat_Database::update_session_status( $session->id, 'active' );

		return array(
			'success' => true,
			'user_message_id' => $user_message_id,
			'ai_message_id'   => $ai_message_id,
			'ai_response'     => $ai_result['response'],
			'metadata'        => $ai_result['metadata'],
			'kb_sources'      => self::format_kb_sources( $kb_items ),
		);
	}

	/**
	 * Get conversation history for context
	 */
	private static function get_conversation_history( $session_id, $limit = 10 ) {
		$messages = MM_AI_Chat_Database::get_session_messages( $session_id, $limit );

		$history = array();
		foreach ( array_reverse( $messages ) as $message ) {
			if ( 'user' === $message->sender_type ) {
				$history[] = array(
					'role'    => 'user',
					'content' => $message->content,
				);
			} elseif ( 'ai' === $message->sender_type ) {
				$history[] = array(
					'role'    => 'assistant',
					'content' => $message->content,
				);
			}
		}

		return $history;
	}

	/**
	 * Format KB sources for response
	 */
	private static function format_kb_sources( $kb_items ) {
		$sources = array();
		foreach ( $kb_items as $item ) {
			$sources[] = array(
				'kb_id'    => $item->kb_id,
				'category' => $item->category,
				'question' => $item->question,
			);
		}
		return $sources;
	}

	/**
	 * Handle agent message
	 */
	public static function process_agent_message( $conversation_id, $agent_id, $message ) {
		$session = MM_AI_Chat_Session::get_by_conversation_id( $conversation_id );
		if ( ! $session ) {
			return array( 'success' => false, 'error' => 'Session not found' );
		}

		// Verify agent is assigned to this session
		if ( (int) $session->agent_id !== (int) $agent_id ) {
			return array( 'success' => false, 'error' => 'Agent not assigned to this session' );
		}

		// Save agent message
		$message_id = MM_AI_Chat_Database::save_message(
			$session->id,
			'agent',
			$message,
			$agent_id
		);

		return array(
			'success'    => true,
			'message_id' => $message_id,
		);
	}

	/**
	 * Get formatted messages for session
	 */
	public static function get_session_messages( $session_id, $limit = 50, $offset = 0 ) {
		$messages = MM_AI_Chat_Database::get_session_messages( $session_id, $limit, $offset );

		$formatted = array();
		foreach ( $messages as $message ) {
			$user_name = 'AI Assistant';
			if ( 'user' === $message->sender_type ) {
				$user = get_user_by( 'ID', $message->sender_id );
				$user_name = $user ? $user->display_name : 'User';
			} elseif ( 'agent' === $message->sender_type ) {
				$user = get_user_by( 'ID', $message->sender_id );
				$user_name = $user ? $user->display_name : 'Agent';
			}

			$formatted[] = array(
				'id'           => $message->message_id,
				'sender_type'  => $message->sender_type,
				'sender_id'    => $message->sender_id,
				'sender_name'  => $user_name,
				'content'      => $message->content,
				'created_at'   => $message->created_at,
				'metadata'     => $message->openai_response_metadata ? json_decode( $message->openai_response_metadata, true ) : null,
			);
		}

		return array_reverse( $formatted );
	}
}
