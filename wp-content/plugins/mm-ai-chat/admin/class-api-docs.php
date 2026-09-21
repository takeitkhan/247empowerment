<?php
/**
 * API Documentation Page
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_API_Docs {

	/**
	 * Initialize API docs
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
	}

	/**
	 * Render API documentation page
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_ai_chat' ) ) {
			wp_die( 'Unauthorized' );
		}
		?>

		<div class="wrap">
			<h1><?php esc_html_e( 'API Documentation', 'mm-ai-chat' ); ?></h1>

			<p class="description" style="margin: 20px 0;">
				<?php esc_html_e( 'Complete REST API documentation for MM AI Chat plugin. Base URL:', 'mm-ai-chat' ); ?>
				<code style="background: #f1f1f1; padding: 5px 10px; border-radius: 3px;">
					<?php echo esc_html( rest_url( 'mm-ai-chat/v1' ) ); ?>
				</code>
			</p>

			<!-- Table of Contents -->
			<div class="postbox" style="margin: 30px 0;">
				<h2 class="hndle"><span><?php esc_html_e( '📑 Table of Contents', 'mm-ai-chat' ); ?></span></h2>
				<div class="inside">
					<ul style="columns: 2; gap: 30px;">
						<li><a href="#chat-endpoints"><?php esc_html_e( 'Chat Endpoints', 'mm-ai-chat' ); ?></a></li>
						<li><a href="#admin-endpoints"><?php esc_html_e( 'Admin Endpoints', 'mm-ai-chat' ); ?></a></li>
						<li><a href="#kb-endpoints"><?php esc_html_e( 'Knowledge Base Endpoints', 'mm-ai-chat' ); ?></a></li>
						<li><a href="#settings-endpoints"><?php esc_html_e( 'Settings Endpoints', 'mm-ai-chat' ); ?></a></li>
						<li><a href="#authentication"><?php esc_html_e( 'Authentication', 'mm-ai-chat' ); ?></a></li>
						<li><a href="#error-codes"><?php esc_html_e( 'Error Codes', 'mm-ai-chat' ); ?></a></li>
					</ul>
				</div>
			</div>

			<!-- Chat Endpoints Section -->
			<div id="chat-endpoints">
				<?php self::render_endpoint_group( 'Chat Endpoints', 'Public user-facing chat endpoints', array(
					array(
						'title'       => 'Initiate Chat Session',
						'method'      => 'POST',
						'url'         => '/chat/initiate',
						'description' => 'Start a new chat session',
						'auth'        => 'Nonce required',
						'params'      => array(),
						'request'     => array(),
						'response'    => array(
							'success' => true,
							'data'    => array(
								'conversation_id' => 'conv_abc123xyz',
								'session_id'      => 1,
								'status'          => 'active',
								'created_at'      => '2026-06-17 10:30:00'
							)
						)
					),
					array(
						'title'       => 'Send Message',
						'method'      => 'POST',
						'url'         => '/chat/message',
						'description' => 'Send a user message and get AI response',
						'auth'        => 'Nonce required',
						'params'      => array(
							'conversation_id' => 'string (required) - Session conversation ID',
							'message'         => 'string (required) - User message',
						),
						'request'     => array(
							'conversation_id' => 'conv_abc123xyz',
							'message'         => 'How do I reset my password?'
						),
						'response'    => array(
							'success' => true,
							'data'    => array(
								'message_id'      => 'msg_12345',
								'conversation_id' => 'conv_abc123xyz',
								'user_message'    => 'How do I reset my password?',
								'ai_response'     => 'To reset your password, visit the login page and click "Forgot Password"...',
								'tokens_used'     => 150,
								'created_at'      => '2026-06-17 10:31:00'
							)
						)
					),
					array(
						'title'       => 'Get Chat History',
						'method'      => 'GET',
						'url'         => '/chat/messages/{conversation_id}',
						'description' => 'Retrieve all messages in a conversation',
						'auth'        => 'Session access check',
						'params'      => array(
							'conversation_id' => 'string (required) - Session conversation ID in URL path',
						),
						'request'     => array(),
						'response'    => array(
							'success' => true,
							'data'    => array(
								'conversation_id' => 'conv_abc123xyz',
								'messages'        => array(
									array(
										'id'        => 'msg_1',
										'type'      => 'user',
										'content'   => 'What are your hours?',
										'timestamp' => '2026-06-17 10:00:00'
									),
									array(
										'id'        => 'msg_2',
										'type'      => 'ai',
										'content'   => 'We are open Monday-Friday 9AM-5PM...',
										'timestamp' => '2026-06-17 10:00:15'
									)
								)
							)
						)
					),
					array(
						'title'       => 'Escalate to Offline',
						'method'      => 'POST',
						'url'         => '/chat/escalate/offline',
						'description' => 'Submit a question for offline response',
						'auth'        => 'Nonce required',
						'params'      => array(
							'conversation_id' => 'string (required) - Session conversation ID',
							'question'        => 'string (required) - Question for offline response',
							'email'           => 'string (required) - User email',
						),
						'request'     => array(
							'conversation_id' => 'conv_abc123xyz',
							'question'        => 'Can I schedule a demo?',
							'email'           => 'user@example.com'
						),
						'response'    => array(
							'success' => true,
							'message' => 'Your question has been submitted. We will respond within 24 hours.'
						)
					),
					array(
						'title'       => 'Escalate to Live Agent',
						'method'      => 'POST',
						'url'         => '/chat/escalate/agent',
						'description' => 'Request connection to a live agent',
						'auth'        => 'Nonce required',
						'params'      => array(
							'conversation_id' => 'string (required) - Session conversation ID',
							'reason'          => 'string (optional) - Reason for escalation',
						),
						'request'     => array(
							'conversation_id' => 'conv_abc123xyz',
							'reason'          => 'Need to speak with sales team'
						),
						'response'    => array(
							'success'  => true,
							'data'     => array(
								'status'          => 'agent_assigned',
								'agent_name'      => 'John Doe',
								'queue_position'  => 0,
								'estimated_wait'  => '2 minutes'
							),
							'message'  => 'An agent will be with you shortly.'
						)
					),
					array(
						'title'       => 'Close Chat Session',
						'method'      => 'POST',
						'url'         => '/chat/close',
						'description' => 'Close an active chat session',
						'auth'        => 'Nonce required',
						'params'      => array(
							'conversation_id' => 'string (required) - Session conversation ID',
						),
						'request'     => array(
							'conversation_id' => 'conv_abc123xyz'
						),
						'response'    => array(
							'success' => true,
							'message' => 'Chat session closed.'
						)
					),
					array(
						'title'       => 'Send Agent Reply',
						'method'      => 'POST',
						'url'         => '/chat/agent-reply',
						'description' => 'Send message from live agent to user',
						'auth'        => 'Agent capability required',
						'params'      => array(
							'conversation_id' => 'string (required) - Session conversation ID',
							'message'         => 'string (required) - Agent message',
						),
						'request'     => array(
							'conversation_id' => 'conv_abc123xyz',
							'message'         => 'Thank you for your inquiry. I can help you with that...'
						),
						'response'    => array(
							'success' => true,
							'message' => 'Reply sent to user.'
						)
					),
				) ); ?>
			</div>

			<!-- Admin Endpoints Section -->
			<div id="admin-endpoints">
				<?php self::render_endpoint_group( 'Admin Endpoints', 'Agent and admin-only endpoints (requires manage_ai_chat capability)', array(
					array(
						'title'       => 'Get Active Sessions',
						'method'      => 'GET',
						'url'         => '/admin/sessions',
						'description' => 'List all active chat sessions',
						'auth'        => 'Admin capability required',
						'params'      => array(
							'status' => 'string (optional) - Filter by status: pending, active, closed',
							'limit' => 'int (optional, default: 20) - Number of results',
						),
						'request'     => array(),
						'response'    => array(
							'success' => true,
							'data'    => array(
								'total' => 5,
								'sessions' => array(
									array(
										'session_id'      => 1,
										'conversation_id' => 'conv_abc123',
										'status'          => 'active',
										'user_name'       => 'John Doe',
										'last_message'    => '2 minutes ago',
										'agent_id'        => null,
										'created_at'      => '2026-06-17 10:00:00'
									)
								)
							)
						)
					),
					array(
						'title'       => 'Accept Session',
						'method'      => 'POST',
						'url'         => '/admin/sessions/{session_id}/accept',
						'description' => 'Agent accepts a pending chat session',
						'auth'        => 'Agent capability required',
						'params'      => array(
							'session_id' => 'int (required) - Session ID in URL path',
						),
						'request'     => array(),
						'response'    => array(
							'success' => true,
							'data'    => array(
								'session_id'      => 1,
								'status'          => 'active',
								'agent_assigned'  => 'Current User',
								'timestamp'       => '2026-06-17 10:05:00'
							)
						)
					),
					array(
						'title'       => 'Get Offline Questions',
						'method'      => 'GET',
						'url'         => '/admin/offline-questions',
						'description' => 'Retrieve all offline questions submitted by users',
						'auth'        => 'Admin capability required',
						'params'      => array(
							'status' => 'string (optional) - pending or answered',
							'limit' => 'int (optional, default: 20) - Number of results',
						),
						'request'     => array(),
						'response'    => array(
							'success' => true,
							'data'    => array(
								'total' => 3,
								'questions' => array(
									array(
										'question_id' => 'q_xyz789',
										'question'    => 'Can I schedule a demo?',
										'email'       => 'user@example.com',
										'status'      => 'pending',
										'submitted_at' => '2026-06-17 09:00:00'
									)
								)
							)
						)
					),
					array(
						'title'       => 'Answer Offline Question',
						'method'      => 'POST',
						'url'         => '/admin/offline-questions/{question_id}/answer',
						'description' => 'Provide answer to offline question',
						'auth'        => 'Admin capability required',
						'params'      => array(
							'question_id' => 'string (required) - Question ID in URL path',
							'answer'      => 'string (required) - Response to the question',
						),
						'request'     => array(
							'answer' => 'Yes, we can schedule a demo! Please contact our sales team...'
						),
						'response'    => array(
							'success' => true,
							'message' => 'Answer saved. Email notification sent to user.'
						)
					),
					array(
						'title'       => 'Test API Key',
						'method'      => 'POST',
						'url'         => '/admin/test-api-key',
						'description' => 'Verify OpenAI API key configuration',
						'auth'        => 'Admin capability required',
						'params'      => array(),
						'request'     => array(),
						'response'    => array(
							'success' => true,
							'data'    => array(
								'valid' => true,
								'model' => 'gpt-4o',
								'message' => 'API key is valid and working'
							)
						)
					),
				) ); ?>
			</div>

			<!-- Knowledge Base Endpoints Section -->
			<div id="kb-endpoints">
				<?php self::render_endpoint_group( 'Knowledge Base Endpoints', 'Management endpoints for KB items (requires manage_ai_chat capability)', array(
					array(
						'title'       => 'List KB Items',
						'method'      => 'GET',
						'url'         => '/admin/knowledge-base',
						'description' => 'Retrieve all knowledge base items with optional filtering',
						'auth'        => 'Admin capability required',
						'params'      => array(
							'search'   => 'string (optional) - Search in questions and answers',
							'category' => 'string (optional) - Filter by category',
							'limit'    => 'int (optional, default: 50) - Number of results',
						),
						'request'     => array(),
						'response'    => array(
							'success' => true,
							'data'    => array(
								'total' => 5,
								'items' => array(
									array(
										'kb_id'     => 'kb_001',
										'question'  => 'How do I reset my password?',
										'answer'    => 'Visit the login page and click Forgot Password...',
										'category'  => 'Account',
										'keywords'  => 'password, reset, login',
										'priority'  => 10,
										'is_active' => true,
										'created_at' => '2026-06-17 08:00:00'
									)
								)
							)
						)
					),
					array(
						'title'       => 'Create KB Item',
						'method'      => 'POST',
						'url'         => '/admin/knowledge-base',
						'description' => 'Add a new knowledge base item',
						'auth'        => 'Admin capability required',
						'params'      => array(
							'question'  => 'string (required) - Question/Title',
							'answer'    => 'string (required) - Answer content',
							'category'  => 'string (required) - Category name',
							'keywords'  => 'string (optional) - Comma-separated keywords',
							'priority'  => 'int (optional, default: 50) - Priority ranking',
							'is_active' => 'bool (optional, default: 1) - Active status',
						),
						'request'     => array(
							'question'  => 'What payment methods do you accept?',
							'answer'    => 'We accept all major credit cards, PayPal, and bank transfers.',
							'category'  => 'Billing',
							'keywords'  => 'payment, credit card, invoice, billing',
							'priority'  => 5,
							'is_active' => 1
						),
						'response'    => array(
							'success' => true,
							'data'    => array(
								'kb_id'     => 'kb_002',
								'question'  => 'What payment methods do you accept?',
								'created_at' => '2026-06-17 10:30:00'
							)
						)
					),
					array(
						'title'       => 'Update KB Item',
						'method'      => 'PUT',
						'url'         => '/admin/knowledge-base/{kb_id}',
						'description' => 'Update an existing knowledge base item',
						'auth'        => 'Admin capability required',
						'params'      => array(
							'kb_id'    => 'string (required) - KB Item ID in URL path',
							'question' => 'string (optional) - Updated question',
							'answer'   => 'string (optional) - Updated answer',
							'category' => 'string (optional) - Updated category',
							'priority' => 'int (optional) - Updated priority',
							'is_active' => 'bool (optional) - Active status',
						),
						'request'     => array(
							'answer' => 'Updated answer content here...',
							'priority' => 15
						),
						'response'    => array(
							'success' => true,
							'message' => 'KB item updated successfully'
						)
					),
					array(
						'title'       => 'Delete KB Item',
						'method'      => 'DELETE',
						'url'         => '/admin/knowledge-base/{kb_id}',
						'description' => 'Remove a knowledge base item',
						'auth'        => 'Admin capability required',
						'params'      => array(
							'kb_id' => 'string (required) - KB Item ID in URL path',
						),
						'request'     => array(),
						'response'    => array(
							'success' => true,
							'message' => 'KB item deleted successfully'
						)
					),
					array(
						'title'       => 'Get Categories',
						'method'      => 'GET',
						'url'         => '/admin/knowledge-base/categories',
						'description' => 'Retrieve all available KB categories',
						'auth'        => 'Admin capability required',
						'params'      => array(),
						'request'     => array(),
						'response'    => array(
							'success' => true,
							'data'    => array(
								'categories' => array(
									'Account',
									'Billing',
									'Technical Support',
									'Shipping',
									'Returns'
								)
							)
						)
					),
				) ); ?>
			</div>

			<!-- Settings Endpoints Section -->
			<div id="settings-endpoints">
				<?php self::render_endpoint_group( 'Settings Endpoints', 'Plugin configuration endpoints (requires manage_ai_chat capability)', array(
					array(
						'title'       => 'Get Settings',
						'method'      => 'GET',
						'url'         => '/admin/settings',
						'description' => 'Retrieve current plugin settings',
						'auth'        => 'Admin capability required',
						'params'      => array(),
						'request'     => array(),
						'response'    => array(
							'success' => true,
							'data'    => array(
								'api_key_configured' => true,
								'model'              => 'gpt-5.5',
								'temperature'       => 0.7,
								'widget_position'   => 'bottom-right',
								'enable_escalation' => true,
								'offline_enabled'   => true
							)
						)
					),
					array(
						'title'       => 'Update Settings',
						'method'      => 'PUT',
						'url'         => '/admin/settings',
						'description' => 'Update plugin configuration',
						'auth'        => 'Admin capability required',
						'params'      => array(
							'api_key'             => 'string (optional) - OpenAI API key',
							'model'               => 'string (optional) - Model: gpt-5.5, gpt-5, gpt-4o, gpt-4-turbo, gpt-3.5-turbo',
							'temperature'        => 'float (optional) - Temperature 0-2',
							'widget_position'    => 'string (optional) - bottom-right, bottom-left, top-right, top-left',
							'enable_escalation'  => 'bool (optional) - Enable escalation feature',
							'offline_enabled'    => 'bool (optional) - Enable offline questions',
						),
						'request'     => array(
							'model'       => 'gpt-5.5',
							'temperature' => 0.5,
							'widget_position' => 'bottom-left'
						),
						'response'    => array(
							'success' => true,
							'message' => 'Settings updated successfully'
						)
					),
				) ); ?>
			</div>

			<!-- Authentication Section -->
			<div id="authentication">
				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( '🔐 Authentication', 'mm-ai-chat' ); ?></span></h2>
					<div class="inside">
						<h3><?php esc_html_e( 'Public Endpoints (Nonce Required)', 'mm-ai-chat' ); ?></h3>
						<p><?php esc_html_e( 'Public chat endpoints require WordPress nonce verification:', 'mm-ai-chat' ); ?></p>
						<pre><code>headers: {
  'X-WP-Nonce': '&lt;nonce-value&gt;'
}</code></pre>

						<h3><?php esc_html_e( 'Admin Endpoints (Capability Required)', 'mm-ai-chat' ); ?></h3>
						<p><?php esc_html_e( 'Admin and agent endpoints require:', 'mm-ai-chat' ); ?></p>
						<ul>
							<li><?php esc_html_e( 'User to be logged in', 'mm-ai-chat' ); ?></li>
							<li><?php esc_html_e( 'User to have "manage_ai_chat" capability', 'mm-ai-chat' ); ?></li>
							<li><?php esc_html_e( 'Valid WordPress nonce', 'mm-ai-chat' ); ?></li>
						</ul>

						<h3><?php esc_html_e( 'Example Request with cURL:', 'mm-ai-chat' ); ?></h3>
						<pre><code>curl -X POST <?php echo esc_html( rest_url( 'mm-ai-chat/v1/chat/initiate' ) ); ?> \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: &lt;nonce-value&gt;" \
  -d '{}'</code></pre>
					</div>
				</div>
			</div>

			<!-- Error Codes Section -->
			<div id="error-codes">
				<div class="postbox">
					<h2 class="hndle"><span><?php esc_html_e( '⚠️ Error Codes & Responses', 'mm-ai-chat' ); ?></span></h2>
					<div class="inside">
						<table class="wp-list-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Code', 'mm-ai-chat' ); ?></th>
									<th><?php esc_html_e( 'Status', 'mm-ai-chat' ); ?></th>
									<th><?php esc_html_e( 'Description', 'mm-ai-chat' ); ?></th>
									<th><?php esc_html_e( 'Example Response', 'mm-ai-chat' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><code>200</code></td>
									<td><?php esc_html_e( 'OK', 'mm-ai-chat' ); ?></td>
									<td><?php esc_html_e( 'Request successful', 'mm-ai-chat' ); ?></td>
									<td><code>{ "success": true, "data": {...} }</code></td>
								</tr>
								<tr>
									<td><code>400</code></td>
									<td><?php esc_html_e( 'Bad Request', 'mm-ai-chat' ); ?></td>
									<td><?php esc_html_e( 'Invalid parameters or missing required fields', 'mm-ai-chat' ); ?></td>
									<td><code>{ "success": false, "message": "Missing conversation_id" }</code></td>
								</tr>
								<tr>
									<td><code>401</code></td>
									<td><?php esc_html_e( 'Unauthorized', 'mm-ai-chat' ); ?></td>
									<td><?php esc_html_e( 'Invalid or missing nonce/authentication', 'mm-ai-chat' ); ?></td>
									<td><code>{ "success": false, "message": "Invalid nonce" }</code></td>
								</tr>
								<tr>
									<td><code>403</code></td>
									<td><?php esc_html_e( 'Forbidden', 'mm-ai-chat' ); ?></td>
									<td><?php esc_html_e( 'User lacks required capabilities', 'mm-ai-chat' ); ?></td>
									<td><code>{ "success": false, "message": "Insufficient permissions" }</code></td>
								</tr>
								<tr>
									<td><code>404</code></td>
									<td><?php esc_html_e( 'Not Found', 'mm-ai-chat' ); ?></td>
									<td><?php esc_html_e( 'Resource not found', 'mm-ai-chat' ); ?></td>
									<td><code>{ "success": false, "message": "Session not found" }</code></td>
								</tr>
								<tr>
									<td><code>500</code></td>
									<td><?php esc_html_e( 'Server Error', 'mm-ai-chat' ); ?></td>
									<td><?php esc_html_e( 'Internal server error', 'mm-ai-chat' ); ?></td>
									<td><code>{ "success": false, "message": "Internal server error" }</code></td>
								</tr>
								<tr>
									<td><code>503</code></td>
									<td><?php esc_html_e( 'Service Unavailable', 'mm-ai-chat' ); ?></td>
									<td><?php esc_html_e( 'API service temporarily unavailable', 'mm-ai-chat' ); ?></td>
									<td><code>{ "success": false, "message": "API service unavailable" }</code></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>

		<?php
	}

	/**
	 * Render endpoint group
	 *
	 * @param string $group_title Group title
	 * @param string $description Group description
	 * @param array  $endpoints Array of endpoint configurations
	 */
	private static function render_endpoint_group( $group_title, $description, $endpoints ) {
		?>
		<div class="postbox" style="margin: 30px 0;">
			<h2 class="hndle"><span><?php echo esc_html( $group_title ); ?></span></h2>
			<div class="inside">
				<p class="description" style="margin-bottom: 20px;"><?php echo esc_html( $description ); ?></p>

				<?php foreach ( $endpoints as $endpoint ) : ?>
					<div style="background: white; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px; overflow: hidden;">
						<!-- Endpoint Header -->
						<div style="background: #f8f9fa; padding: 15px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
							<h3 style="margin: 0;">
								<?php echo esc_html( $endpoint['title'] ); ?>
								<span style="background: <?php echo esc_attr( self::get_method_color( $endpoint['method'] ) ); ?>; color: white; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; margin-left: 10px;">
									<?php echo esc_html( $endpoint['method'] ); ?>
								</span>
							</h3>
						</div>

						<!-- Endpoint Details -->
						<div style="padding: 15px;">
							<p style="margin: 5px 0;"><strong><?php esc_html_e( 'URL:', 'mm-ai-chat' ); ?></strong> <code style="background: #f1f1f1; padding: 5px 10px; border-radius: 3px;"><?php echo esc_html( $endpoint['url'] ); ?></code></p>
							<p style="margin: 5px 0;"><strong><?php esc_html_e( 'Description:', 'mm-ai-chat' ); ?></strong> <?php echo esc_html( $endpoint['description'] ); ?></p>
							<p style="margin: 5px 0;"><strong><?php esc_html_e( 'Authentication:', 'mm-ai-chat' ); ?></strong> <span style="background: #e7f3ff; padding: 3px 8px; border-radius: 3px; font-size: 12px;"><?php echo esc_html( $endpoint['auth'] ); ?></span></p>

							<!-- Parameters -->
							<?php if ( ! empty( $endpoint['params'] ) ) : ?>
								<div style="margin-top: 15px;">
									<strong><?php esc_html_e( 'Parameters:', 'mm-ai-chat' ); ?></strong>
									<table style="width: 100%; margin-top: 8px; border-collapse: collapse;">
										<?php foreach ( $endpoint['params'] as $param_name => $param_desc ) : ?>
											<tr>
												<td style="padding: 6px 0; border-bottom: 1px solid #eee;"><code><?php echo esc_html( $param_name ); ?></code></td>
												<td style="padding: 6px 10px; border-bottom: 1px solid #eee;"><?php echo esc_html( $param_desc ); ?></td>
											</tr>
										<?php endforeach; ?>
									</table>
								</div>
							<?php endif; ?>

							<!-- Request/Response Examples -->
							<div style="margin-top: 15px;">
								<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
									<!-- Request Example -->
									<?php if ( ! empty( $endpoint['request'] ) || 'GET' !== $endpoint['method'] ) : ?>
										<div>
											<strong><?php esc_html_e( 'Example Request:', 'mm-ai-chat' ); ?></strong>
											<pre style="padding: 12px; border-radius: 4px; overflow-x: auto; font-size: 12px;"><code><?php echo esc_html( wp_json_encode( $endpoint['request'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></code></pre>
										</div>
									<?php endif; ?>

									<!-- Response Example -->
									<div>
										<strong><?php esc_html_e( 'Example Response:', 'mm-ai-chat' ); ?></strong>
										<pre style="padding: 12px; border-radius: 4px; overflow-x: auto; font-size: 12px;"><code><?php echo esc_html( wp_json_encode( $endpoint['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></code></pre>
									</div>
								</div>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get color for HTTP method
	 *
	 * @param string $method HTTP method
	 * @return string Color code
	 */
	private static function get_method_color( $method ) {
		$colors = array(
			'GET'    => '#007cba',
			'POST'   => '#228821',
			'PUT'    => '#ff8700',
			'DELETE' => '#dc3545',
		);
		return isset( $colors[ $method ] ) ? $colors[ $method ] : '#333';
	}
}
