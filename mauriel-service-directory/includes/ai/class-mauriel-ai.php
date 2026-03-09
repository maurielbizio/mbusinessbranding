<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_AI
 *
 * Core AI integration layer. Wraps the OpenAI Chat Completions API and
 * exposes static helpers consumed by every other AI-related class in the
 * plugin.
 *
 * @package MaurielServiceDirectory
 * @since   1.0.0
 */
class Mauriel_AI {

	// -------------------------------------------------------------------------
	// Public static API
	// -------------------------------------------------------------------------

	/**
	 * Whether the AI feature is active and usable.
	 *
	 * Requires both the "AI enabled" toggle AND a stored API key.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$enabled = (string) get_option( 'mauriel_ai_enabled', '0' );
		$key     = self::get_api_key();

		return ( '1' === $enabled ) && ( ! empty( $key ) );
	}

	/**
	 * Returns the stored OpenAI API key (unescaped — used only server-side).
	 *
	 * @return string
	 */
	public static function get_api_key() {
		return (string) get_option( 'mauriel_openai_api_key', '' );
	}

	/**
	 * Returns the model to use for completions.
	 *
	 * Defaults to gpt-4o-mini which balances quality and cost for
	 * business-description and review-response tasks.
	 *
	 * @return string
	 */
	public static function get_model() {
		$model = (string) get_option( 'mauriel_openai_model', 'gpt-4o-mini' );

		// Whitelist of supported models — fall back to default if unexpected value.
		$allowed = array(
			'gpt-4o',
			'gpt-4o-mini',
			'gpt-4-turbo',
			'gpt-4',
			'gpt-3.5-turbo',
		);

		return in_array( $model, $allowed, true ) ? $model : 'gpt-4o-mini';
	}

	/**
	 * Sends a chat-completion request to OpenAI and returns the response text.
	 *
	 * @param  string $prompt     The user-turn prompt to send.
	 * @param  int    $max_tokens Maximum tokens in the completion (default 400).
	 * @return string|WP_Error    Response content string or WP_Error on failure.
	 */
	public static function complete( $prompt, $max_tokens = 400 ) {
		if ( ! self::is_enabled() ) {
			return new WP_Error(
				'ai_disabled',
				__( 'AI is not enabled. Please enable AI features and add an OpenAI API key in the plugin settings.', 'mauriel-service-directory' )
			);
		}

		$api_key    = self::get_api_key();
		$model      = self::get_model();
		$max_tokens = absint( $max_tokens );

		if ( $max_tokens < 1 ) {
			$max_tokens = 400;
		}

		$body = wp_json_encode(
			array(
				'model'      => $model,
				'messages'   => array(
					array(
						'role'    => 'system',
						'content' => self::get_prompt_prefix(),
					),
					array(
						'role'    => 'user',
						'content' => (string) $prompt,
					),
				),
				'max_tokens'  => $max_tokens,
				'temperature' => 0.7,
			)
		);

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => $body,
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'ai_request_failed',
				sprintf(
					/* translators: %s: underlying error message */
					__( 'OpenAI request failed: %s', 'mauriel-service-directory' ),
					$response->get_error_message()
				)
			);
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );

		if ( $http_code < 200 || $http_code >= 300 ) {
			$raw_body     = wp_remote_retrieve_body( $response );
			$error_data   = json_decode( $raw_body, true );
			$error_detail = isset( $error_data['error']['message'] )
				? $error_data['error']['message']
				: $raw_body;

			return new WP_Error(
				'ai_api_error',
				sprintf(
					/* translators: 1: HTTP status code 2: error detail */
					__( 'OpenAI returned HTTP %1$d: %2$s', 'mauriel-service-directory' ),
					$http_code,
					$error_detail
				)
			);
		}

		$raw_body = wp_remote_retrieve_body( $response );
		$data     = json_decode( $raw_body, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'ai_invalid_response',
				__( 'OpenAI returned an invalid JSON response.', 'mauriel-service-directory' )
			);
		}

		if ( empty( $data['choices'][0]['message']['content'] ) ) {
			return new WP_Error(
				'ai_empty_response',
				__( 'OpenAI returned an empty completion.', 'mauriel-service-directory' )
			);
		}

		return (string) $data['choices'][0]['message']['content'];
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Builds the system-level prompt prefix.
	 *
	 * Combines any admin-configured preamble stored in the option
	 * `mauriel_ai_prompt_prefix` with a built-in baseline instruction.
	 *
	 * @return string
	 */
	public static function get_prompt_prefix() {
		$custom_prefix = (string) get_option( 'mauriel_ai_prompt_prefix', '' );
		$custom_prefix = trim( $custom_prefix );

		$base = 'You are a professional copywriter who specialises in creating compelling, '
			. 'accurate business descriptions and customer-facing copy for local service '
			. 'businesses. Your writing is confident, welcoming, and search-engine friendly. '
			. 'You never fabricate facts. You write in clear, grammatically correct English. '
			. 'You avoid jargon unless it is specific to the trade in question. '
			. 'You never use placeholder text such as [INSERT NAME] or lorem ipsum.';

		if ( '' !== $custom_prefix ) {
			return $custom_prefix . "\n\n" . $base;
		}

		return $base;
	}
}
