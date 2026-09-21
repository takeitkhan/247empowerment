<?php
/**
 * OpenAI Client - Handles communication with OpenAI API
 *
 * @package MM_AI_Chat
 */

class MM_AI_Chat_OpenAI_Client {

	private static $base_url = 'https://api.openai.com/v1';
	private static $model = 'gpt-4o';
	private static $temperature = 0.7;

	/**
	 * Initialize client with settings
	 */
	public static function init() {
		self::$model       = MM_AI_Chat_API_Key_Handler::get_model();
		self::$temperature = MM_AI_Chat_API_Key_Handler::get_temperature();
	}

	/**
	 * Make API request to OpenAI
	 */
	public static function send_message( $system_prompt, $user_message, $conversation_history = array() ) {
		$api_key = MM_AI_Chat_API_Key_Handler::get_api_key();

		if ( empty( $api_key ) ) {
			return array(
				'success' => false,
				'error'   => 'OpenAI API key not configured',
				'code'    => 'missing_api_key',
			);
		}

		self::init();

		// Build messages array
		$messages = array();

		// Add system message
		$messages[] = array(
			'role'    => 'system',
			'content' => $system_prompt,
		);

		// Add conversation history
		foreach ( $conversation_history as $message ) {
			$messages[] = array(
				'role'    => $message['role'],
				'content' => $message['content'],
			);
		}

		// Add current user message
		$messages[] = array(
			'role'    => 'user',
			'content' => $user_message,
		);

		// Prepare request payload (newer models use max_completion_tokens)
		$payload = self::build_payload( $messages );

		// Make API call
		$response = self::request_chat_completion( $api_key, $payload );

		// Retry with max_completion_tokens if model rejects max_tokens
		if ( ! $response['success'] && self::should_retry_with_completion_tokens( $response ) ) {
			$payload = self::build_payload( $messages, true );
			$response = self::request_chat_completion( $api_key, $payload );
		}

		return $response;
	}

	/**
	 * Build chat completion payload
	 */
	private static function build_payload( $messages, $use_completion_tokens = null ) {
		if ( null === $use_completion_tokens ) {
			$use_completion_tokens = self::model_uses_completion_tokens( self::$model );
		}

		$payload = array(
			'model'    => self::$model,
			'messages' => $messages,
		);

		if ( $use_completion_tokens ) {
			$payload['max_completion_tokens'] = 1000;
		} else {
			$payload['temperature'] = self::$temperature;
			$payload['max_tokens']  = 1000;
		}

		return $payload;
	}

	/**
	 * Whether model expects max_completion_tokens
	 */
	private static function model_uses_completion_tokens( $model ) {
		$model = strtolower( (string) $model );
		return (
			strpos( $model, 'o1' ) === 0 ||
			strpos( $model, 'o3' ) === 0 ||
			strpos( $model, 'gpt-5' ) === 0
		);
	}

	/**
	 * Retry when OpenAI rejects max_tokens
	 */
	private static function should_retry_with_completion_tokens( $result ) {
		if ( empty( $result['error'] ) ) {
			return false;
		}
		return false !== stripos( $result['error'], 'max_completion_tokens' );
	}

	/**
	 * POST to OpenAI chat completions
	 */
	private static function request_chat_completion( $api_key, $payload ) {
		$response = wp_remote_post(
			self::$base_url . '/chat/completions',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $payload ),
				'timeout' => 30,
			)
		);

		// Handle errors
		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
				'code'    => 'request_error',
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		// Handle API errors
		if ( $status_code !== 200 ) {
			$error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : 'Unknown error';
			$error_code    = isset( $data['error']['code'] ) ? $data['error']['code'] : 'unknown_error';

			return array(
				'success' => false,
				'error'   => $error_message,
				'code'    => $error_code,
				'status'  => $status_code,
			);
		}

		// Extract response
		if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
			return array(
				'success' => false,
				'error'   => 'Invalid response format from OpenAI',
				'code'    => 'invalid_response',
			);
		}

		$ai_response = $data['choices'][0]['message']['content'];

		return array(
			'success'  => true,
			'response' => $ai_response,
			'metadata' => array(
				'model'             => self::$model,
				'prompt_tokens'     => isset( $data['usage']['prompt_tokens'] ) ? $data['usage']['prompt_tokens'] : 0,
				'completion_tokens' => isset( $data['usage']['completion_tokens'] ) ? $data['usage']['completion_tokens'] : 0,
				'total_tokens'      => isset( $data['usage']['total_tokens'] ) ? $data['usage']['total_tokens'] : 0,
				'finish_reason'     => $data['choices'][0]['finish_reason'],
				'openai_id'         => $data['id'],
			),
		);
	}

	/**
	 * Handle rate limits and retries
	 */
	public static function send_message_with_retry( $system_prompt, $user_message, $conversation_history = array(), $max_retries = 3 ) {
		$retry_count = 0;
		$wait_time   = 1;

		while ( $retry_count < $max_retries ) {
			$result = self::send_message( $system_prompt, $user_message, $conversation_history );

			if ( $result['success'] ) {
				return $result;
			}

			// Check if it's a rate limit error
			if ( isset( $result['code'] ) && $result['code'] === 'rate_limit_exceeded' ) {
				$retry_count++;
				if ( $retry_count < $max_retries ) {
					sleep( $wait_time );
					$wait_time *= 2; // Exponential backoff
					continue;
				}
			}

			// Return error if not rate limited or max retries reached
			return $result;
		}

		return array(
			'success' => false,
			'error'   => 'Max retries exceeded',
			'code'    => 'max_retries_exceeded',
		);
	}
}
