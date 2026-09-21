<?php
/**
 * Capability Manager - Manages plugin-specific roles and capabilities
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_Capability_Manager {

	/**
	 * Initialize capabilities
	 */
	public static function init() {
		// Add custom capability
		add_action( 'admin_init', array( __CLASS__, 'add_capabilities' ) );
	}

	/**
	 * Add plugin capabilities to roles
	 */
	public static function add_capabilities() {
		// Get admin role
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->add_cap( 'manage_ai_chat' );
			$admin_role->add_cap( 'mm_ai_chat_agent' );
		}

		// You can also add capabilities to other roles as needed
		// Example: Allow shop managers to be agents
		$shop_manager = get_role( 'shop_manager' );
		if ( $shop_manager ) {
			$shop_manager->add_cap( 'mm_ai_chat_agent' );
		}
	}

	/**
	 * Check if user is AI chat agent
	 */
	public static function user_is_agent( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		return user_can( $user_id, 'mm_ai_chat_agent' );
	}

	/**
	 * Check if user can manage AI chat
	 */
	public static function user_can_manage( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		return user_can( $user_id, 'manage_ai_chat' ) || user_can( $user_id, 'manage_options' );
	}

	/**
	 * Remove capabilities on plugin deactivation
	 */
	public static function remove_capabilities() {
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->remove_cap( 'manage_ai_chat' );
			$admin_role->remove_cap( 'mm_ai_chat_agent' );
		}

		$shop_manager = get_role( 'shop_manager' );
		if ( $shop_manager ) {
			$shop_manager->remove_cap( 'mm_ai_chat_agent' );
		}
	}
}
