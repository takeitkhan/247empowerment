<?php
/**
 * Settings Page Handler
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_Settings_Page {

	/**
	 * Initialize settings page
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
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
	 * Register settings
	 */
	public static function register_settings() {
		// Require API Key Handler for sanitization
		require_once MM_AI_CHAT_PLUGIN_DIR . 'inc/class-api-key-handler.php';

		// Register API key with sanitization callback
		register_setting( 'mm_ai_chat_settings', 'mm_ai_chat_openai_api_key', array(
			'sanitize_callback' => array( 'MM_AI_Chat_API_Key_Handler', 'sanitize_api_key' ),
			'show_in_rest'      => false,
		) );

		register_setting( 'mm_ai_chat_settings', 'mm_ai_chat_openai_model' );
		register_setting( 'mm_ai_chat_settings', 'mm_ai_chat_openai_temperature' );
		register_setting( 'mm_ai_chat_settings', 'mm_ai_chat_enabled' );
		register_setting( 'mm_ai_chat_settings', 'mm_ai_chat_widget_position' );
		register_setting( 'mm_ai_chat_settings', 'mm_ai_chat_escalation_enabled' );
		register_setting( 'mm_ai_chat_settings', 'mm_ai_chat_offline_questions_enabled' );
		register_setting( 'mm_ai_chat_settings', 'mm_ai_chat_kb_system_prompt' );
	}

	/**
	 * Render settings page
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_ai_chat' ) ) {
			wp_die( 'Unauthorized' );
		}

		// Ensure classes are loaded
		require_once MM_AI_CHAT_PLUGIN_DIR . 'inc/class-api-key-handler.php';

		?>
		<div class="wrap">
			<h1>AI Chat Plugin Settings</h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'mm_ai_chat_settings' ); ?>

				<table class="form-table">
					<!-- Enable/Disable Plugin -->
					<tr>
						<th scope="row"><label for="mm_ai_chat_enabled">Enable Plugin</label></th>
						<td>
							<input type="checkbox" id="mm_ai_chat_enabled" name="mm_ai_chat_enabled" value="1" <?php checked( get_option( 'mm_ai_chat_enabled' ), 1 ); ?> />
							<p class="description">Check to enable the AI Chat widget</p>
						</td>
					</tr>

					<!-- OpenAI API Key -->
					<tr>
						<th scope="row"><label for="mm_ai_chat_openai_api_key">OpenAI API Key</label></th>
						<td>
							<input type="password" id="mm_ai_chat_openai_api_key" name="mm_ai_chat_openai_api_key" class="regular-text" value="<?php echo esc_attr( MM_AI_Chat_API_Key_Handler::get_api_key() ); ?>" />
							<p class="description">Your OpenAI API key (kept secure)</p>
							
							<?php 
								$saved_key = MM_AI_Chat_API_Key_Handler::get_api_key();
								if ( ! empty( $saved_key ) ) {
									echo '<div style="margin: 10px 0; padding: 8px 12px; background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724;">';
									echo '✅ <strong>API Key Saved</strong> - Successfully stored in database';
									echo '</div>';
								} else {
									echo '<div style="margin: 10px 0; padding: 8px 12px; background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; color: #856404;">';
									echo '⚠️ <strong>No API Key</strong> - Please paste your key and save settings';
									echo '</div>';
								}
							?>
							
							<button type="button" class="button" onclick="testApiKey()">Test Connection</button>
							<span id="api-key-test-result"></span>
						</td>
					</tr>

					<!-- Model Selection -->
					<tr>
						<th scope="row"><label for="mm_ai_chat_openai_model">Model</label></th>
						<td>
							<select id="mm_ai_chat_openai_model" name="mm_ai_chat_openai_model">
								<option value="gpt-5.5" <?php selected( MM_AI_Chat_API_Key_Handler::get_model(), 'gpt-5.5' ); ?>>GPT-5.5 (Latest)</option>
								<option value="gpt-5" <?php selected( MM_AI_Chat_API_Key_Handler::get_model(), 'gpt-5' ); ?>>GPT-5</option>
								<option value="gpt-4o" <?php selected( MM_AI_Chat_API_Key_Handler::get_model(), 'gpt-4o' ); ?>>GPT-4o</option>
								<option value="gpt-4-turbo" <?php selected( MM_AI_Chat_API_Key_Handler::get_model(), 'gpt-4-turbo' ); ?>>GPT-4 Turbo</option>
								<option value="gpt-3.5-turbo" <?php selected( MM_AI_Chat_API_Key_Handler::get_model(), 'gpt-3.5-turbo' ); ?>>GPT-3.5 Turbo</option>
							</select>
							<p class="description">
								<strong>Recommended:</strong> GPT-5.5 for latest features
								<br />
								<strong>Cost-effective:</strong> GPT-4o for best value
								<br />
								See <a href="https://platform.openai.com/docs/models" target="_blank">OpenAI Models</a> for details
							</p>
						</td>
					</tr>

					<!-- Temperature -->
					<tr>
						<th scope="row"><label for="mm_ai_chat_openai_temperature">Temperature</label></th>
						<td>
							<input type="number" id="mm_ai_chat_openai_temperature" name="mm_ai_chat_openai_temperature" min="0" max="2" step="0.1" value="<?php echo esc_attr( MM_AI_Chat_API_Key_Handler::get_temperature() ); ?>" />
							<p class="description">Lower values (0-1) make output more focused and deterministic. Higher values (1-2) make output more creative.</p>
						</td>
					</tr>

					<!-- Widget Position -->
					<tr>
						<th scope="row"><label for="mm_ai_chat_widget_position">Widget Position</label></th>
						<td>
							<select id="mm_ai_chat_widget_position" name="mm_ai_chat_widget_position">
								<option value="bottom-right" <?php selected( get_option( 'mm_ai_chat_widget_position' ), 'bottom-right' ); ?>>Bottom Right</option>
								<option value="bottom-left" <?php selected( get_option( 'mm_ai_chat_widget_position' ), 'bottom-left' ); ?>>Bottom Left</option>
								<option value="top-right" <?php selected( get_option( 'mm_ai_chat_widget_position' ), 'top-right' ); ?>>Top Right</option>
								<option value="top-left" <?php selected( get_option( 'mm_ai_chat_widget_position' ), 'top-left' ); ?>>Top Left</option>
							</select>
						</td>
					</tr>

					<!-- Escalation Options -->
					<tr>
						<th scope="row">Escalation Options</th>
						<td>
							<label>
								<input type="checkbox" name="mm_ai_chat_escalation_enabled" value="1" <?php checked( get_option( 'mm_ai_chat_escalation_enabled' ), 1 ); ?> />
								Allow users to escalate to live agents
							</label>
							<br />
							<label>
								<input type="checkbox" name="mm_ai_chat_offline_questions_enabled" value="1" <?php checked( get_option( 'mm_ai_chat_offline_questions_enabled' ), 1 ); ?> />
								Allow offline question submission
							</label>
						</td>
					</tr>

					<!-- System Prompt -->
					<tr>
						<th scope="row"><label for="mm_ai_chat_kb_system_prompt">System Prompt</label></th>
						<td>
							<textarea id="mm_ai_chat_kb_system_prompt" name="mm_ai_chat_kb_system_prompt" class="large-text" rows="5"><?php echo esc_textarea( MM_AI_Chat_API_Key_Handler::get_system_prompt() ); ?></textarea>
							<p class="description">Custom instructions for the AI assistant</p>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>

		<script>
			function testApiKey() {
				const apiKey = document.getElementById('mm_ai_chat_openai_api_key').value;
				const resultEl = document.getElementById('api-key-test-result');
				
				if ( ! apiKey || apiKey.trim() === '' ) {
					resultEl.innerHTML = '<span style="color: red; margin-left: 10px;">✗ Please enter an API key first</span>';
					return;
				}

				resultEl.innerHTML = '<span style="color: blue; margin-left: 10px;">Testing...</span>';

				const restUrl = '<?php echo esc_url( rest_url( 'mm-ai-chat/v1/admin/test-api-key' ) ); ?>';
				const nonce = '<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>';

				fetch(restUrl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': nonce
					},
					body: JSON.stringify({ api_key: apiKey })
				})
				.then(response => {
					// Always try to parse JSON, even on error
					return response.json().then(data => ({
						status: response.status,
						data: data
					}));
				})
				.then(({ status, data }) => {
					console.log('Response:', { status, data });
					
					if (data && data.success) {
						resultEl.innerHTML = '<span style="color: green; margin-left: 10px;">✓ ' + (data.message || 'Valid API key') + '</span>';
					} else if (data && data.error) {
						resultEl.innerHTML = '<span style="color: red; margin-left: 10px;">✗ ' + data.error + '</span>';
					} else {
						resultEl.innerHTML = '<span style="color: red; margin-left: 10px;">✗ Unexpected response (HTTP ' + status + ')</span>';
					}
				})
				.catch(error => {
					console.error('Fetch error:', error);
					resultEl.innerHTML = '<span style="color: red; margin-left: 10px;">✗ Request failed: ' + error.message + '</span>';
				});
			}
		</script>
		<?php
	}
}
