<?php
/**
 * Admin Dashboard - Chat Management for Agents
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_Dashboard {

	/**
	 * Initialize dashboard
	 */
	public static function init() {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue assets
	 */
	public static function enqueue_assets( $hook_suffix ) {
		if ( strpos( $hook_suffix, 'mm-ai-chat' ) === false ) {
			return;
		}
		wp_enqueue_style( 'mm-ai-chat-admin', MM_AI_CHAT_PLUGIN_URL . 'admin/css/admin-styles.css', array(), MM_AI_CHAT_VERSION );
		wp_enqueue_script( 'mm-ai-chat-dashboard', MM_AI_CHAT_PLUGIN_URL . 'admin/js/dashboard.js', array( 'jquery' ), MM_AI_CHAT_VERSION, true );
		wp_localize_script( 'mm-ai-chat-dashboard', 'mmAiChat', array(
			'restUrl' => rest_url( 'mm-ai-chat/v1' ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		) );
	}

	/**
	 * Render dashboard
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_ai_chat' ) ) {
			wp_die( 'Unauthorized' );
		}

		// Ensure classes are loaded
		require_once MM_AI_CHAT_PLUGIN_DIR . 'inc/class-chat-session.php';

		$stats = MM_AI_Chat_Session::get_stats();
		?>

		<div class="wrap">
			<h1>AI Chat Dashboard</h1>

			<!-- Stats -->
			<div class="dashboard-stats">
				<div class="stat-box">
					<div class="stat-number"><?php echo intval( $stats['active'] ); ?></div>
					<div class="stat-label">Active Chats</div>
				</div>
				<div class="stat-box">
					<div class="stat-number"><?php echo intval( $stats['waiting_for_agent'] ); ?></div>
					<div class="stat-label">Waiting for Agent</div>
				</div>
				<div class="stat-box">
					<div class="stat-number"><?php echo intval( $stats['agent_assigned'] ); ?></div>
					<div class="stat-label">Agent Assigned</div>
				</div>
				<div class="stat-box">
					<div class="stat-number"><?php echo intval( $stats['closed_today'] ); ?></div>
					<div class="stat-label">Closed Today</div>
				</div>
			</div>

			<!-- Tabs -->
			<h2 class="nav-tab-wrapper">
				<a href="#pending" class="nav-tab nav-tab-active">Pending Chats</a>
				<a href="#active" class="nav-tab">Active Chats</a>
				<a href="#offline" class="nav-tab">Offline Questions</a>
			</h2>

			<!-- Pending Chats Tab -->
			<div id="pending" class="tab-content">
				<h3>Chats Waiting for Agent</h3>
				<table class="wp-list-table fixed widefat striped" id="pending-chats-table">
					<thead>
						<tr>
							<th>User</th>
							<th>Waiting Since</th>
							<th>Last Message</th>
							<th>Page</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody id="pending-chats-body">
						<tr><td colspan="5">Loading...</td></tr>
					</tbody>
				</table>
			</div>

			<!-- Active Chats Tab -->
			<div id="active" class="tab-content" style="display: none;">
				<h3>Active Agent Chats</h3>
				<table class="wp-list-table fixed widefat striped" id="active-chats-table">
					<thead>
						<tr>
							<th>User</th>
							<th>Agent</th>
							<th>Started</th>
							<th>Messages</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody id="active-chats-body">
						<tr><td colspan="5">Loading...</td></tr>
					</tbody>
				</table>
			</div>

			<!-- Offline Questions Tab -->
			<div id="offline" class="tab-content" style="display: none;">
				<h3>Pending Offline Questions</h3>
				<table class="wp-list-table fixed widefat striped" id="offline-questions-table">
					<thead>
						<tr>
							<th>User</th>
							<th>Question</th>
							<th>Submitted</th>
							<th>Priority</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody id="offline-questions-body">
						<tr><td colspan="5">Loading...</td></tr>
					</tbody>
				</table>
			</div>
		</div>

		<style>
			.dashboard-stats {
				display: flex;
				gap: 20px;
				margin: 20px 0;
			}
			.stat-box {
				background: white;
				border: 1px solid #ddd;
				border-radius: 4px;
				padding: 20px;
				flex: 1;
				text-align: center;
			}
			.stat-number {
				font-size: 32px;
				font-weight: bold;
				color: #0073aa;
			}
			.stat-label {
				color: #666;
				margin-top: 5px;
			}
			.tab-content {
				padding: 20px 0;
			}
		</style>
		<?php
	}
}
