<?php
/**
 * Chat Session Handler
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_Session {

	/**
	 * Initialize a new chat session
	 */
	public static function initiate( $user_id, $page_context = array() ) {
		$conversation_id = 'conv_' . wp_generate_uuid4();
		$metadata        = array(
			'page_url'    => isset( $page_context['page_url'] ) ? esc_url( $page_context['page_url'] ) : '',
			'page_title'  => isset( $page_context['page_title'] ) ? sanitize_text_field( $page_context['page_title'] ) : '',
			'referrer'    => isset( $page_context['referrer'] ) ? esc_url( $page_context['referrer'] ) : '',
			'initiated_at' => current_time( 'mysql' ),
		);

		$session_id = MM_AI_Chat_Database::create_session( $user_id, $conversation_id, $metadata );

		return array(
			'session_id'      => $session_id,
			'conversation_id' => $conversation_id,
			'mode'            => 'ai',
			'status'          => 'active',
		);
	}

	/**
	 * Get session by conversation ID
	 */
	public static function get_by_conversation_id( $conversation_id ) {
		$session = MM_AI_Chat_Database::get_session_by_conversation_id( $conversation_id );
		if ( ! $session ) {
			return null;
		}
		return $session;
	}

	/**
	 * Check if user has access to session
	 */
	public static function user_has_access( $conversation_id, $user_id ) {
		$session = self::get_by_conversation_id( $conversation_id );
		if ( ! $session ) {
			return false;
		}

		// User created session or is assigned agent
		return (int) $session->user_id === (int) $user_id || (int) $session->agent_id === (int) $user_id;
	}

	/**
	 * Escalate to waiting for agent
	 */
	public static function escalate_to_agent( $conversation_id ) {
		$session = self::get_by_conversation_id( $conversation_id );
		if ( ! $session ) {
			return false;
		}

		MM_AI_Chat_Database::update_session_status( $session->id, 'waiting_for_agent', 'agent' );

		// Trigger notification to agents
		do_action( 'mm_ai_chat_user_requested_agent', $session );

		return true;
	}

	/**
	 * Assign agent to session
	 */
	public static function assign_agent( $session_id, $agent_id ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'ai_chat_sessions',
			array(
				'agent_id'   => $agent_id,
				'status'     => 'agent_assigned',
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $session_id )
		);

		// Record in agent assignments
		$wpdb->insert(
			$wpdb->prefix . 'ai_agent_assignments',
			array(
				'agent_id'   => $agent_id,
				'session_id' => $session_id,
				'accepted_at' => current_time( 'mysql' ),
			)
		);

		return true;
	}

	/**
	 * Close session
	 */
	public static function close( $conversation_id, $reason = 'user_closed' ) {
		$session = self::get_by_conversation_id( $conversation_id );
		if ( ! $session ) {
			return false;
		}

		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . 'ai_chat_sessions',
			array(
				'status'     => 'closed',
				'closed_at'  => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $session->id )
		);

		// Update agent assignment if exists
		if ( $session->agent_id ) {
			$wpdb->update(
				$wpdb->prefix . 'ai_agent_assignments',
				array(
					'closed_at'         => current_time( 'mysql' ),
					'is_active'         => 0,
					'duration_seconds'  => strtotime( current_time( 'mysql' ) ) - strtotime( $session->initiated_at ),
				),
				array(
					'session_id' => $session->id,
					'is_active'  => 1,
				)
			);
		}

		// Trigger action
		do_action( 'mm_ai_chat_session_closed', $session, $reason );

		return true;
	}

	/**
	 * Get session statistics
	 */
	public static function get_stats() {
		global $wpdb;
		$sessions_table = $wpdb->prefix . 'ai_chat_sessions';

		return array(
			'active'                => $wpdb->get_var( "SELECT COUNT(*) FROM {$sessions_table} WHERE status = 'active'" ),
			'waiting_for_agent'     => $wpdb->get_var( "SELECT COUNT(*) FROM {$sessions_table} WHERE status = 'waiting_for_agent'" ),
			'agent_assigned'        => $wpdb->get_var( "SELECT COUNT(*) FROM {$sessions_table} WHERE status = 'agent_assigned'" ),
			'closed_today'          => $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . $sessions_table . " WHERE status = 'closed' AND DATE(closed_at) = %s", gmdate( 'Y-m-d' ) ) ),
		);
	}
}
