<?php
/**
 * Plugin Deactivation Handler
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_Deactivation {

	/**
	 * Run deactivation routines
	 */
	public static function deactivate() {
		// Flush rewrite rules
		flush_rewrite_rules();
	}
}
