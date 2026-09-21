<?php
/**
 * Knowledge Base Handler - RAG (Retrieval Augmented Generation) System
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_Knowledge_Base {

	/**
	 * Retrieve relevant KB items for a user message
	 */
	public static function retrieve_context( $user_message, $limit = 5 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ai_knowledge_base';

		// Extract keywords from user message
		$keywords = self::extract_keywords( $user_message );

		if ( empty( $keywords ) ) {
			// Fallback: Get highest priority active items
			return $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE is_active = 1 ORDER BY priority ASC LIMIT %d",
					$limit
				)
			);
		}

		// Build SQL query with keyword matching
		$keyword_conditions = array();
		foreach ( $keywords as $keyword ) {
			$escaped_keyword    = '%' . $wpdb->esc_like( $keyword ) . '%';
			$keyword_conditions[] = $wpdb->prepare(
				"(question LIKE %s OR answer LIKE %s OR keywords LIKE %s)",
				$escaped_keyword,
				$escaped_keyword,
				$escaped_keyword
			);
		}

		$where_clause = 'WHERE is_active = 1 AND (' . implode( ' OR ', $keyword_conditions ) . ')';

		$query = $wpdb->prepare(
			"SELECT *, 
				CASE 
					WHEN question LIKE %s THEN 2
					WHEN keywords LIKE %s THEN 1
					ELSE 0
				END as relevance_score
			FROM {$table} 
			{$where_clause}
			ORDER BY relevance_score DESC, priority ASC
			LIMIT %d",
			'%' . $wpdb->esc_like( $keywords[0] ) . '%',
			'%' . $wpdb->esc_like( $keywords[0] ) . '%',
			$limit
		);

		return $wpdb->get_results( $query );
	}

	/**
	 * Extract keywords from user message
	 */
	private static function extract_keywords( $message ) {
		// Remove common words
		$stopwords = array( 'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'is', 'are', 'do', 'does', 'can', 'could', 'would', 'will', 'should', 'what', 'when', 'where', 'how', 'why' );

		// Convert to lowercase and split
		$words = str_word_count( strtolower( $message ), 1 );

		// Filter stopwords and get unique keywords
		$keywords = array_filter(
			$words,
			function( $word ) use ( $stopwords ) {
				return ! in_array( $word, $stopwords, true ) && strlen( $word ) > 2;
			}
		);

		return array_slice( array_unique( $keywords ), 0, 5 ); // Return top 5 keywords
	}

	/**
	 * Build system prompt with KB context
	 */
	public static function build_system_prompt_with_context( $kb_items ) {
		$base_prompt = MM_AI_Chat_API_Key_Handler::get_system_prompt();

		if ( empty( $kb_items ) ) {
			return $base_prompt;
		}

		$kb_context = "\n\nKNOWLEDGE BASE:\n---\n";

		foreach ( $kb_items as $item ) {
			$kb_context .= "Q: " . $item->question . "\n";
			$kb_context .= "A: " . $item->answer . "\n\n";
		}

		$kb_context .= "---\n";

		return $base_prompt . $kb_context;
	}

	/**
	 * Create a KB item
	 */
	public static function create_item( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ai_knowledge_base';

		$kb_item = array(
			'kb_id'       => 'kb_' . wp_generate_uuid4(),
			'category'    => sanitize_text_field( $data['category'] ),
			'question'    => sanitize_textarea_field( $data['question'] ),
			'answer'      => wp_kses_post( $data['answer'] ),
			'keywords'    => sanitize_text_field( $data['keywords'] ?? '' ),
			'priority'    => intval( $data['priority'] ?? 100 ),
			'is_active'   => isset( $data['is_active'] ) ? intval( $data['is_active'] ) : 1,
			'created_by'  => get_current_user_id(),
			'created_at'  => current_time( 'mysql' ),
		);

		$wpdb->insert( $table, $kb_item );
		return $wpdb->insert_id;
	}

	/**
	 * Update a KB item
	 */
	public static function update_item( $kb_id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ai_knowledge_base';

		$update_data = array(
			'category'   => sanitize_text_field( $data['category'] ),
			'question'   => sanitize_textarea_field( $data['question'] ),
			'answer'     => wp_kses_post( $data['answer'] ),
			'keywords'   => sanitize_text_field( $data['keywords'] ?? '' ),
			'priority'   => intval( $data['priority'] ?? 100 ),
			'is_active'  => isset( $data['is_active'] ) ? intval( $data['is_active'] ) : 1,
			'updated_by' => get_current_user_id(),
			'updated_at' => current_time( 'mysql' ),
		);

		$wpdb->update( $table, $update_data, array( 'kb_id' => $kb_id ) );
	}

	/**
	 * Delete a KB item
	 */
	public static function delete_item( $kb_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ai_knowledge_base';
		$wpdb->delete( $table, array( 'kb_id' => $kb_id ) );
	}

	/**
	 * Get all KB items with filtering
	 */
	public static function get_items( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ai_knowledge_base';

		$defaults = array(
			'category' => null,
			'search'   => null,
			'limit'    => 20,
			'offset'   => 0,
			'active'   => true,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = 'WHERE 1=1';

		if ( $args['active'] ) {
			$where .= ' AND is_active = 1';
		}

		if ( $args['category'] ) {
			$where .= $wpdb->prepare( ' AND category = %s', $args['category'] );
		}

		if ( $args['search'] ) {
			$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where .= $wpdb->prepare( ' AND (question LIKE %s OR answer LIKE %s)', $search, $search );
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} {$where} ORDER BY priority ASC LIMIT %d OFFSET %d",
				$args['limit'],
				$args['offset']
			)
		);
	}

	/**
	 * Get KB categories
	 */
	public static function get_categories() {
		global $wpdb;
		$table = $wpdb->prefix . 'ai_knowledge_base';
		return $wpdb->get_col( "SELECT DISTINCT category FROM {$table} WHERE is_active = 1 ORDER BY category ASC" );
	}

	/**
	 * Count KB items
	 */
	public static function count_items( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'ai_knowledge_base';

		$where = 'WHERE is_active = 1';

		if ( isset( $args['category'] ) && $args['category'] ) {
			$where .= $wpdb->prepare( ' AND category = %s', $args['category'] );
		}

		if ( isset( $args['search'] ) && $args['search'] ) {
			$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where .= $wpdb->prepare( ' AND (question LIKE %s OR answer LIKE %s)', $search, $search );
		}

		return $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" );
	}
}
