<?php
/**
 * Frontend Widget - Chat Widget Renderer
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_Widget {

	/**
	 * Initialize widget
	 */
	public static function init() {
		add_action( 'wp_footer', array( __CLASS__, 'render_widget' ) );
	}

	/**
	 * Render the chat widget
	 */
	public static function render_widget() {
		// AI chat is for logged-in members only; guests use the Joshua welcome chat in footer-main.php
		if ( ! is_user_logged_in() ) {
			return;
		}

		// Only show if plugin is enabled
		if ( ! get_option( 'mm_ai_chat_enabled' ) ) {
			return;
		}

		$position = get_option( 'mm_ai_chat_widget_position', 'bottom-right' );
		?>

		<div id="mm-ai-chat-widget-container" class="mm-ai-chat-position-<?php echo esc_attr( $position ); ?>">
			<div id="mm-ai-chat-widget" class="mm-ai-chat-widget" style="display: none;">
				<div class="mm-ai-chat-header">
					<h3>Chat with us</h3>
					<button id="mm-ai-chat-close" class="mm-ai-chat-close">&times;</button>
				</div>
				<div class="mm-ai-chat-messages" id="mm-ai-chat-messages"></div>
				<div id="mm-ai-chat-mode-bar" class="mm-ai-chat-mode-bar" style="display: none;" aria-live="polite"></div>
				<div id="mm-ai-chat-escalation" class="mm-ai-chat-escalation" style="display: none;">
					<p class="mm-ai-chat-escalation-hint"><?php esc_html_e( 'Need more help?', 'mm-ai-chat' ); ?></p>
					<div class="mm-ai-chat-escalation-actions">
						<button type="button" id="mm-ai-chat-offline-btn" class="mm-ai-chat-esc-btn mm-ai-chat-esc-offline">
							<?php esc_html_e( 'Get offline answer', 'mm-ai-chat' ); ?>
						</button>
						<button type="button" id="mm-ai-chat-agent-btn" class="mm-ai-chat-esc-btn mm-ai-chat-esc-agent">
							<?php esc_html_e( 'Talk to portal agent', 'mm-ai-chat' ); ?>
						</button>
					</div>
					<div id="mm-ai-chat-offline-form" class="mm-ai-chat-offline-form" style="display: none;">
						<textarea id="mm-ai-chat-offline-question" rows="3" placeholder="<?php esc_attr_e( 'Describe your question — our team will reply within 24 hours…', 'mm-ai-chat' ); ?>"></textarea>
						<div class="mm-ai-chat-offline-form-actions">
							<button type="button" id="mm-ai-chat-offline-submit" class="mm-ai-chat-esc-btn mm-ai-chat-esc-offline"><?php esc_html_e( 'Submit', 'mm-ai-chat' ); ?></button>
							<button type="button" id="mm-ai-chat-offline-cancel" class="mm-ai-chat-esc-btn mm-ai-chat-esc-cancel"><?php esc_html_e( 'Cancel', 'mm-ai-chat' ); ?></button>
						</div>
					</div>
				</div>
				<div class="mm-ai-chat-input-area">
					<textarea id="mm-ai-chat-input" placeholder="Type your message..." rows="2"></textarea>
					<button id="mm-ai-chat-send" class="mm-ai-chat-send-btn">Send</button>
				</div>
			</div>
			<button id="mm-ai-chat-toggle" class="mm-ai-chat-toggle-btn">💬</button>
		</div>

		<?php
	}
}
