<?php
/**
 * Escalation Handler - Handles chat escalations (offline and live agent)
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_Escalation_Handler {

	/**
	 * Handle escalation - Option A: Offline Question Submission
	 */
	public static function escalate_offline( $conversation_id, $question ) {
		global $wpdb;

		$session = MM_AI_Chat_Session::get_by_conversation_id( $conversation_id );
		if ( ! $session ) {
			return array( 'success' => false, 'error' => 'Session not found' );
		}

		// Create offline question
		$question_id = 'offq_' . wp_generate_uuid4();
		$wpdb->insert(
			$wpdb->prefix . 'ai_offline_questions',
			array(
				'question_id' => $question_id,
				'session_id'  => $session->id,
				'user_id'     => $session->user_id,
				'question'    => sanitize_textarea_field( $question ),
				'status'      => 'pending',
				'priority'    => 'medium',
				'created_at'  => current_time( 'mysql' ),
			)
		);

		// Update session
		MM_AI_Chat_Database::update_session_status( $session->id, 'closed', 'offline_submission' );

		// Send notification to admins
		do_action( 'mm_ai_chat_offline_question_submitted', array(
			'question_id' => $question_id,
			'session_id'  => $session->id,
			'user_id'     => $session->user_id,
			'question'    => $question,
		) );

		return array(
			'success'          => true,
			'question_id'      => $question_id,
			'message'          => 'Your question has been recorded. Our team will respond within 24 hours.',
		);
	}

	/**
	 * Handle escalation - Option B: Live Agent Request
	 */
	public static function escalate_to_agent( $conversation_id, $reason = null ) {
		$session = MM_AI_Chat_Session::get_by_conversation_id( $conversation_id );
		if ( ! $session ) {
			return array( 'success' => false, 'error' => 'Session not found' );
		}

		// Update session status to waiting for agent
		MM_AI_Chat_Session::escalate_to_agent( $conversation_id );

		// Get available agents
		$available_agents = self::get_available_agents();

		if ( empty( $available_agents ) ) {
			return array(
				'success'                    => true,
				'status'                     => 'waiting_for_agent',
				'message'                    => 'No agents available. You will be connected when one becomes available.',
				'estimated_wait_time_seconds' => null,
				'agents_available'           => false,
			);
		}

		// Auto-assign to agent with lowest workload
		$agent = self::get_agent_with_lowest_workload( $available_agents );

		if ( $agent ) {
			MM_AI_Chat_Session::assign_agent( $session->id, $agent->ID );

			return array(
				'success'                    => true,
				'status'                     => 'agent_assigned',
				'message'                    => 'An agent is now ready to help you.',
				'agent_id'                   => $agent->ID,
				'agent_name'                 => $agent->display_name,
				'estimated_wait_time_seconds' => 0,
				'agents_available'           => true,
			);
		}

		return array(
			'success'                    => true,
			'status'                     => 'waiting_for_agent',
			'message'                    => 'An agent will be with you shortly.',
			'estimated_wait_time_seconds' => 30,
			'agents_available'           => false,
		);
	}

	/**
	 * Get available agents
	 */
	private static function get_available_agents() {
		$args = array(
			'role'     => 'mm_ai_chat_agent',
			'orderby'  => 'user_login',
			'order'    => 'ASC',
		);
		return get_users( $args );
	}

	/**
	 * Get agent with lowest workload
	 */
	private static function get_agent_with_lowest_workload( $agents ) {
		global $wpdb;
		$assignments_table = $wpdb->prefix . 'ai_agent_assignments';

		$agent_workload = array();

		foreach ( $agents as $agent ) {
			$active_count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$assignments_table} WHERE agent_id = %d AND is_active = 1",
					$agent->ID
				)
			);

			$agent_workload[ $agent->ID ] = array(
				'user'      => $agent,
				'workload'  => intval( $active_count ),
			);
		}

		// Sort by workload
		usort(
			$agent_workload,
			function( $a, $b ) {
				return $a['workload'] - $b['workload'];
			}
		);

		return isset( $agent_workload[0] ) ? $agent_workload[0]['user'] : null;
	}

	/**
	 * Answer offline question
	 */
	public static function answer_offline_question( $question_id, $answer, $answered_by = null ) {
		global $wpdb;

		if ( ! $answered_by ) {
			$answered_by = get_current_user_id();
		}

		$wpdb->update(
			$wpdb->prefix . 'ai_offline_questions',
			array(
				'answer'      => wp_kses_post( $answer ),
				'answered_by' => $answered_by,
				'status'      => 'answered',
				'answered_at' => current_time( 'mysql' ),
			),
			array( 'question_id' => $question_id )
		);

		// Get question details
		$question = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}ai_offline_questions WHERE question_id = %s",
				$question_id
			)
		);

		// Send notification to user
		do_action( 'mm_ai_chat_offline_question_answered', array(
			'question_id'  => $question_id,
			'user_id'      => $question->user_id,
			'answer'       => $answer,
			'answered_by'  => $answered_by,
		) );

		return true;
	}

	/**
	 * Get pending offline questions
	 */
	public static function get_pending_offline_questions( $limit = 20, $offset = 0 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ai_offline_questions';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE status = 'pending' ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$limit,
				$offset
			)
		);
	}

	/**
	 * Count pending offline questions
	 */
	public static function count_pending_offline_questions() {
		global $wpdb;
		$table = $wpdb->prefix . 'ai_offline_questions';
		return $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'pending'" );
	}
}
