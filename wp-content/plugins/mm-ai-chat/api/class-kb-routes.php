<?php
/**
 * Knowledge Base Routes - KB management endpoints
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_KB_Routes {

	/**
	 * Register routes
	 */
	public static function register_routes() {
		// List KB items
		register_rest_route( 'mm-ai-chat/v1', '/admin/knowledge-base', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_items' ),
			'permission_callback' => array( __CLASS__, 'check_manage_capability' ),
		) );

		// Create KB item
		register_rest_route( 'mm-ai-chat/v1', '/admin/knowledge-base', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'create_item' ),
			'permission_callback' => array( __CLASS__, 'check_manage_capability' ),
		) );

		// Update KB item
		register_rest_route( 'mm-ai-chat/v1', '/admin/knowledge-base/(?P<kb_id>[a-zA-Z0-9_-]+)', array(
			'methods'             => 'PUT',
			'callback'            => array( __CLASS__, 'update_item' ),
			'permission_callback' => array( __CLASS__, 'check_manage_capability' ),
		) );

		// Delete KB item
		register_rest_route( 'mm-ai-chat/v1', '/admin/knowledge-base/(?P<kb_id>[a-zA-Z0-9_-]+)', array(
			'methods'             => 'DELETE',
			'callback'            => array( __CLASS__, 'delete_item' ),
			'permission_callback' => array( __CLASS__, 'check_manage_capability' ),
		) );

		// Get categories
		register_rest_route( 'mm-ai-chat/v1', '/admin/knowledge-base/categories', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'get_categories' ),
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
	 * Get KB items
	 */
	public static function get_items( $request ) {
		// Load required classes
		require_once dirname( __DIR__ ) . '/inc/class-knowledge-base.php';

		$search = sanitize_text_field( $request->get_param( 'search' ) ?? '' );
		$category = sanitize_text_field( $request->get_param( 'category' ) ?? '' );
		$limit = intval( $request->get_param( 'limit' ) ?? 20 );
		$offset = intval( $request->get_param( 'offset' ) ?? 0 );

		$items = MM_AI_Chat_Knowledge_Base::get_items( array(
			'search'   => $search,
			'category' => $category,
			'limit'    => $limit,
			'offset'   => $offset,
		) );

		$count = MM_AI_Chat_Knowledge_Base::count_items( array(
			'search'   => $search,
			'category' => $category,
		) );

		return new WP_REST_Response( array(
			'success' => true,
			'total'   => $count,
			'items'   => $items,
		), 200 );
	}

	/**
	 * Create KB item
	 */
	public static function create_item( $request ) {
		// Load required classes
		require_once dirname( __DIR__ ) . '/inc/class-knowledge-base.php';

		$params = $request->get_json_params();

		// Fallback: try raw body if json_params fails
		if ( ! is_array( $params ) || empty( $params ) ) {
			$body = $request->get_body();
			$params = json_decode( $body, true );
		}

		if ( ! is_array( $params ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'Invalid JSON request body',
			), 400 );
		}

		$required_fields = array( 'category', 'question', 'answer' );
		foreach ( $required_fields as $field ) {
			if ( ! isset( $params[ $field ] ) || empty( trim( $params[ $field ] ) ) ) {
				return new WP_REST_Response( array(
					'success' => false,
					'error'   => 'Missing or empty required field: ' . $field,
				), 400 );
			}
		}

		$item_id = MM_AI_Chat_Knowledge_Base::create_item( $params );

		if ( ! $item_id ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'Failed to create item',
			), 500 );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'message' => 'Item created successfully',
			'id'      => $item_id,
		), 201 );
	}

	/**
	 * Update KB item
	 */
	public static function update_item( $request ) {
		// Load required classes
		require_once dirname( __DIR__ ) . '/inc/class-knowledge-base.php';

		$kb_id = $request->get_param( 'kb_id' );
		$params = $request->get_json_params();

		// Fallback: try raw body if json_params fails
		if ( ! is_array( $params ) || empty( $params ) ) {
			$body = $request->get_body();
			$params = json_decode( $body, true );
		}

		if ( ! is_array( $params ) ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'Invalid JSON request body',
			), 400 );
		}

		$result = MM_AI_Chat_Knowledge_Base::update_item( $kb_id, $params );

		if ( ! $result ) {
			return new WP_REST_Response( array(
				'success' => false,
				'error'   => 'Failed to update item',
			), 500 );
		}

		return new WP_REST_Response( array(
			'success' => true,
			'message' => 'Item updated successfully',
		), 200 );
	}

	/**
	 * Delete KB item
	 */
	public static function delete_item( $request ) {
		// Load required classes
		require_once dirname( __DIR__ ) . '/inc/class-knowledge-base.php';

		$kb_id = sanitize_text_field( $request->get_param( 'kb_id' ) );

		MM_AI_Chat_Knowledge_Base::delete_item( $kb_id );

		return new WP_REST_Response( array(
			'success' => true,
			'message' => 'Item deleted successfully',
		), 200 );
	}

	/**
	 * Get categories
	 */
	public static function get_categories( $request ) {
		// Load required classes
		require_once dirname( __DIR__ ) . '/inc/class-knowledge-base.php';

		$categories = MM_AI_Chat_Knowledge_Base::get_categories();

		return new WP_REST_Response( array(
			'success'     => true,
			'categories'  => $categories,
		), 200 );
	}
}
