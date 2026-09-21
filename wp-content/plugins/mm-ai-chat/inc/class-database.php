<?php
/**
 * Database Handler
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_Database {

	/**
	 * Check if tables need creation and create them
	 */
	public static function maybe_create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Check if main table exists
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}ai_chat_sessions'" );
		if ( ! $table_exists ) {
			self::create_tables();
		}
	}

	/**
	 * Create all database tables
	 */
	public static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Create chat sessions table
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ai_chat_sessions (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			conversation_id VARCHAR(255) NOT NULL UNIQUE,
			user_id BIGINT UNSIGNED NOT NULL,
			agent_id BIGINT UNSIGNED NULL,
			status ENUM('active', 'waiting_for_agent', 'agent_assigned', 'closed', 'archived') NOT NULL DEFAULT 'active',
			mode ENUM('ai', 'agent', 'offline_submission') NOT NULL DEFAULT 'ai',
			initiated_at DATETIME NOT NULL,
			last_message_at DATETIME NULL,
			closed_at DATETIME NULL,
			offline_question_id BIGINT UNSIGNED NULL,
			metadata JSON NULL,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			KEY idx_user_id (user_id),
			KEY idx_agent_id (agent_id),
			KEY idx_status (status),
			KEY idx_mode (mode),
			KEY idx_initiated_at (initiated_at),
			KEY idx_conversation_id (conversation_id)
		) {$charset_collate};";

		// Create messages table
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ai_chat_messages (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			session_id BIGINT UNSIGNED NOT NULL,
			message_id VARCHAR(255) NOT NULL UNIQUE,
			sender_type ENUM('user', 'ai', 'agent') NOT NULL,
			sender_id BIGINT UNSIGNED NULL,
			content LONGTEXT NOT NULL,
			openai_response_metadata JSON NULL,
			message_tokens INT UNSIGNED NULL,
			is_edited BOOLEAN DEFAULT FALSE,
			edited_at DATETIME NULL,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			KEY idx_session_id (session_id),
			KEY idx_sender_type (sender_type),
			KEY idx_created_at (created_at),
			FOREIGN KEY (session_id) REFERENCES {$wpdb->prefix}ai_chat_sessions(id) ON DELETE CASCADE
		) {$charset_collate};";

		// Create knowledge base table
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ai_knowledge_base (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			kb_id VARCHAR(255) NOT NULL UNIQUE,
			category VARCHAR(255) NOT NULL,
			question TEXT NOT NULL,
			answer LONGTEXT NOT NULL,
			keywords VARCHAR(500) NULL,
			embedding_vector MEDIUMBLOB NULL,
			priority INT DEFAULT 100,
			is_active BOOLEAN DEFAULT TRUE,
			created_by BIGINT UNSIGNED NOT NULL,
			updated_by BIGINT UNSIGNED NULL,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			KEY idx_category (category),
			KEY idx_is_active (is_active),
			KEY idx_priority (priority),
			KEY idx_keywords (keywords(50))
		) {$charset_collate};";

		// Create offline questions table
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ai_offline_questions (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			question_id VARCHAR(255) NOT NULL UNIQUE,
			session_id BIGINT UNSIGNED NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			question TEXT NOT NULL,
			answer LONGTEXT NULL,
			answered_by BIGINT UNSIGNED NULL,
			status ENUM('pending', 'answered', 'resolved') NOT NULL DEFAULT 'pending',
			assigned_to BIGINT UNSIGNED NULL,
			priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			answered_at DATETIME NULL,
			KEY idx_status (status),
			KEY idx_user_id (user_id),
			KEY idx_assigned_to (assigned_to),
			KEY idx_session_id (session_id),
			FOREIGN KEY (session_id) REFERENCES {$wpdb->prefix}ai_chat_sessions(id) ON DELETE SET NULL
		) {$charset_collate};";

		// Create agent assignments table
		$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}ai_agent_assignments (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			agent_id BIGINT UNSIGNED NOT NULL,
			session_id BIGINT UNSIGNED NOT NULL,
			assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			accepted_at DATETIME NULL,
			closed_at DATETIME NULL,
			duration_seconds INT UNSIGNED NULL,
			is_active BOOLEAN DEFAULT TRUE,
			KEY idx_agent_id (agent_id),
			KEY idx_session_id (session_id),
			KEY idx_is_active (is_active),
			FOREIGN KEY (session_id) REFERENCES {$wpdb->prefix}ai_chat_sessions(id) ON DELETE CASCADE
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}
	}

	/**
	 * Create a chat session
	 */
	public static function create_session( $user_id, $conversation_id, $metadata = null ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ai_chat_sessions';

		$data = array(
			'conversation_id' => $conversation_id,
			'user_id'         => $user_id,
			'initiated_at'    => current_time( 'mysql' ),
			'metadata'        => wp_json_encode( $metadata ),
		);

		$wpdb->insert( $table, $data );
		return $wpdb->insert_id;
	}

	/**
	 * Get session by conversation ID
	 */
	public static function get_session_by_conversation_id( $conversation_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ai_chat_sessions';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE conversation_id = %s", $conversation_id ) );
	}

	/**
	 * Save a message
	 */
	public static function save_message( $session_id, $sender_type, $content, $sender_id = null, $metadata = null ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ai_chat_messages';

		$data = array(
			'session_id'   => $session_id,
			'message_id'   => 'msg_' . wp_generate_uuid4(),
			'sender_type'  => $sender_type,
			'sender_id'    => $sender_id,
			'content'      => $content,
			'created_at'   => current_time( 'mysql' ),
		);

		if ( $metadata ) {
			$data['openai_response_metadata'] = wp_json_encode( $metadata );
		}

		$wpdb->insert( $table, $data );
		return $wpdb->insert_id;
	}

	/**
	 * Get messages for a session
	 */
	public static function get_session_messages( $session_id, $limit = 50, $offset = 0 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ai_chat_messages';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE session_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$session_id,
				$limit,
				$offset
			)
		);
	}

	/**
	 * Update session status
	 */
	public static function update_session_status( $session_id, $status, $mode = null ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ai_chat_sessions';

		$data = array(
			'status'       => $status,
			'updated_at'   => current_time( 'mysql' ),
		);

		if ( $mode ) {
			$data['mode'] = $mode;
		}

		$wpdb->update( $table, $data, array( 'id' => $session_id ) );
	}

	/**
	 * Get active sessions
	 */
	public static function get_active_sessions( $status = null, $limit = 20, $offset = 0 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ai_chat_sessions';

		$where = "WHERE status IN ('active', 'waiting_for_agent', 'agent_assigned')";
		if ( $status ) {
			$where = $wpdb->prepare( "WHERE status = %s", $status );
		}

		return $wpdb->get_results(
			"SELECT * FROM {$table} {$where} ORDER BY initiated_at DESC LIMIT {$limit} OFFSET {$offset}"
		);
	}
}
