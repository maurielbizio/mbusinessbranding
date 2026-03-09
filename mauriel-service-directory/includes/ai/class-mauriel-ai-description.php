<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_AI_Description
 *
 * Generates AI-written business descriptions for directory listings.
 *
 * @package MaurielServiceDirectory
 * @since   1.0.0
 */
class Mauriel_AI_Description {

	/**
	 * Generates a professional 2–3 paragraph business description using
	 * the OpenAI API via Mauriel_AI::complete().
	 *
	 * Expected keys in $data:
	 *   - business_name (string) required
	 *   - category      (string) e.g. "Plumbing"
	 *   - city          (string)
	 *   - state         (string)
	 *   - services      (string|array) comma-separated or array of service names
	 *   - keywords      (string|array) comma-separated or array of SEO keywords
	 *
	 * @param  array $data  Listing details used to construct the prompt.
	 * @return string|WP_Error  Generated description text or WP_Error on failure.
	 */
	public static function generate( array $data ) {
		// -----------------------------------------------------------------------
		// Validate required field.
		// -----------------------------------------------------------------------
		$business_name = isset( $data['business_name'] ) ? sanitize_text_field( $data['business_name'] ) : '';

		if ( '' === $business_name ) {
			return new WP_Error(
				'missing_business_name',
				__( 'A business name is required to generate a description.', 'mauriel-service-directory' )
			);
		}

		// -----------------------------------------------------------------------
		// Normalise optional fields.
		// -----------------------------------------------------------------------
		$category = isset( $data['category'] ) ? sanitize_text_field( $data['category'] ) : '';
		$city     = isset( $data['city'] )     ? sanitize_text_field( $data['city'] )     : '';
		$state    = isset( $data['state'] )    ? sanitize_text_field( $data['state'] )    : '';

		// Accept services and keywords as either a plain string or an array.
		$services = '';
		if ( isset( $data['services'] ) ) {
			if ( is_array( $data['services'] ) ) {
				$services = implode( ', ', array_map( 'sanitize_text_field', $data['services'] ) );
			} else {
				$services = sanitize_text_field( (string) $data['services'] );
			}
		}

		$keywords = '';
		if ( isset( $data['keywords'] ) ) {
			if ( is_array( $data['keywords'] ) ) {
				$keywords = implode( ', ', array_map( 'sanitize_text_field', $data['keywords'] ) );
			} else {
				$keywords = sanitize_text_field( (string) $data['keywords'] );
			}
		}

		// -----------------------------------------------------------------------
		// Build location string.
		// -----------------------------------------------------------------------
		$location_parts = array_filter( array( $city, $state ) );
		$location       = implode( ', ', $location_parts );

		// -----------------------------------------------------------------------
		// Assemble prompt.
		// -----------------------------------------------------------------------
		$prompt_parts = array();

		$prompt_parts[] = sprintf(
			'Write a professional, engaging business description (2–3 paragraphs) for %s',
			$business_name
		);

		if ( '' !== $category ) {
			$prompt_parts[] = sprintf( ', a %s business', $category );
		}

		if ( '' !== $location ) {
			$prompt_parts[] = sprintf( ' located in %s', $location );
		}

		$prompt = implode( '', $prompt_parts ) . '.';

		if ( '' !== $services ) {
			$prompt .= sprintf( ' Key services: %s.', $services );
		}

		if ( '' !== $keywords ) {
			$prompt .= sprintf( ' Keywords to include naturally: %s.', $keywords );
		}

		$prompt .= ' Tone should be confident and welcoming.'
			. ' Do not use placeholder text.'
			. ' Do not repeat the business name excessively.'
			. ' Each paragraph should be 3–5 sentences.'
			. ' Write only the description — no headings, no preamble, no sign-off.';

		// -----------------------------------------------------------------------
		// Call AI.
		// -----------------------------------------------------------------------
		$max_tokens = absint( get_option( 'mauriel_ai_desc_max_tokens', 400 ) );
		if ( $max_tokens < 1 ) {
			$max_tokens = 400;
		}

		return Mauriel_AI::complete( $prompt, $max_tokens );
	}
}
