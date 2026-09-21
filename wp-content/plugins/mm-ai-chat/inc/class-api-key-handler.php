<?php
/**
 * API Key Handler - Secure storage and retrieval of OpenAI credentials
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_API_Key_Handler {

	/**
	 * Get encryption key from wp-config or generate one
	 */
	private static function get_encryption_key() {
		if ( defined( 'MM_AI_CHAT_ENCRYPTION_KEY' ) ) {
			return MM_AI_CHAT_ENCRYPTION_KEY;
		}

		// Use WordPress AUTH_KEY as fallback
		if ( defined( 'AUTH_KEY' ) ) {
			return AUTH_KEY;
		}

		// Generate a hash of the site URL as last resort
		return hash( 'sha256', get_site_url() );
	}

	/**
	 * Encrypt and save API key
	 */
	public static function save_api_key( $api_key ) {
		if ( empty( $api_key ) ) {
			return false;
		}

		// Validate API key format
		if ( ! self::validate_api_key_format( $api_key ) ) {
			return false;
		}

		$encrypted = self::encrypt( $api_key );
		update_option( 'mm_ai_chat_openai_api_key', $encrypted );
		return true;
	}

	/**
	 * Encrypt sensitive data
	 */
	private static function encrypt( $data ) {
		$key      = self::get_encryption_key();
		$iv       = openssl_random_pseudo_bytes( openssl_cipher_iv_length( 'aes-256-cbc' ) );
		$encrypted = openssl_encrypt( $data, 'aes-256-cbc', hash( 'sha256', $key ), 0, $iv );
		return base64_encode( $iv . $encrypted );
	}

	/**
	 * Decrypt sensitive data
	 */
	private static function decrypt( $data ) {
		if ( empty( $data ) ) {
			return '';
		}

		$key = self::get_encryption_key();
		$data = base64_decode( $data );
		$iv_length = openssl_cipher_iv_length( 'aes-256-cbc' );
		$iv = substr( $data, 0, $iv_length );
		$encrypted = substr( $data, $iv_length );
		return openssl_decrypt( $encrypted, 'aes-256-cbc', hash( 'sha256', $key ), 0, $iv );
	}

	/**
	 * Get API key
	 */
	public static function get_api_key() {
		$encrypted = get_option( 'mm_ai_chat_openai_api_key' );
		if ( ! $encrypted ) {
			return '';
		}
		return self::decrypt( $encrypted );
	}

	/**
	 * Check if API key is configured
	 */
	public static function is_api_key_configured() {
		return ! empty( self::get_api_key() );
	}

	/**
	 * Validate API key format (basic check)
	 */
	private static function validate_api_key_format( $api_key ) {
		// OpenAI API keys start with 'sk-'
		return strpos( $api_key, 'sk-' ) === 0;
	}

	/**
	 * Sanitize and encrypt API key for WordPress settings
	 * This is called by register_setting sanitize_callback
	 */
	public static function sanitize_api_key( $api_key ) {
		// If empty, return empty
		if ( empty( $api_key ) ) {
			return '';
		}

		// Trim whitespace
		$api_key = trim( $api_key );

		// Validate format
		if ( ! self::validate_api_key_format( $api_key ) ) {
			// Invalid format - return existing key
			return self::get_api_key();
		}

		// Encrypt and save
		$encrypted = self::encrypt( $api_key );
		return $encrypted;
	}

	/**
	 * Get selected model
	 */
	public static function get_model() {
		$model = get_option( 'mm_ai_chat_openai_model', 'gpt-4o' );
		return sanitize_text_field( $model );
	}

	/**
	 * Get temperature setting
	 */
	public static function get_temperature() {
		$temp = floatval( get_option( 'mm_ai_chat_openai_temperature', '0.7' ) );
		return max( 0, min( 2, $temp ) ); // Clamp between 0 and 2
	}

	/**
	 * Get system prompt
	 */
	public static function get_system_prompt() {
		$prompt = get_option(
			'mm_ai_chat_kb_system_prompt',
			'You are a helpful AI customer support assistant. Provide accurate responses based on provided knowledge base.'
		);
		return sanitize_textarea_field( $prompt );
	}

	/**
	 * Delete API key (on plugin deactivation or reset)
	 */
	public static function delete_api_key() {
		delete_option( 'mm_ai_chat_openai_api_key' );
	}

	/**
	 * Test API key by making a simple API call
	 */
	public static function test_api_key( $api_key = null ) {
		if ( ! $api_key ) {
			$api_key = self::get_api_key();
		}

		if ( empty( $api_key ) ) {
			return array( 'success' => false, 'message' => 'No API key provided' );
		}

		$response = wp_remote_post(
			'https://api.openai.com/v1/models',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'message' => $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code === 200 ) {
			return array( 'success' => true, 'message' => 'API key is valid' );
		} else {
			$body = wp_remote_retrieve_body( $response );
			$error_data = json_decode( $body, true );
			$message = isset( $error_data['error']['message'] ) ? $error_data['error']['message'] : 'API key validation failed';
			return array( 'success' => false, 'message' => $message );
		}
	}
}
