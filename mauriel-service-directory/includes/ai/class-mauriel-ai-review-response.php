<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_AI_Review_Response
 *
 * Generates suggested owner responses to customer reviews using AI.
 *
 * @package MaurielServiceDirectory
 * @since   1.0.0
 */
class Mauriel_AI_Review_Response {

	/**
	 * Suggests a professional, warm owner response to a customer review.
	 *
	 * @param  string     $review_text   The full text of the customer review.
	 * @param  int|float  $rating        Numeric star rating (1–5).
	 * @param  string     $business_name Name of the business whose owner is responding.
	 * @return string|WP_Error           Suggested response text or WP_Error on failure.
	 */
	public static function suggest( $review_text, $rating, $business_name ) {
		// -----------------------------------------------------------------------
		// Input validation & sanitisation.
		// -----------------------------------------------------------------------
		$review_text   = sanitize_textarea_field( (string) $review_text );
		$business_name = sanitize_text_field( (string) $business_name );
		$rating        = (int) $rating;

		if ( '' === $review_text ) {
			return new WP_Error(
				'missing_review_text',
				__( 'Review text is required to generate a response suggestion.', 'mauriel-service-directory' )
			);
		}

		if ( '' === $business_name ) {
			return new WP_Error(
				'missing_business_name',
				__( 'Business name is required to generate a response suggestion.', 'mauriel-service-directory' )
			);
		}

		// Clamp rating to 1–5.
		if ( $rating < 1 ) {
			$rating = 1;
		} elseif ( $rating > 5 ) {
			$rating = 5;
		}

		// -----------------------------------------------------------------------
		// Build prompt.
		// -----------------------------------------------------------------------
		$prompt = sprintf(
			'As the owner of %1$s, write a professional, warm response to this %2$d-star review: \'%3$s\'. '
			. 'Keep it under 150 words. '
			. 'Thank the customer (if positive) or acknowledge the concern (if negative) and offer to resolve it. '
			. 'Do not be defensive. '
			. 'Do not repeat the review back verbatim. '
			. 'Write only the response — no subject line, no signature, no preamble.',
			$business_name,
			$rating,
			$review_text
		);

		// -----------------------------------------------------------------------
		// Call AI.
		// -----------------------------------------------------------------------
		$max_tokens = absint( get_option( 'mauriel_ai_resp_max_tokens', 300 ) );
		if ( $max_tokens < 1 ) {
			$max_tokens = 300;
		}

		return Mauriel_AI::complete( $prompt, $max_tokens );
	}
}
