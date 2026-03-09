<?php
defined('ABSPATH') || exit;

/**
 * Class Mauriel_Reviews
 *
 * Handles all review CRUD operations, moderation, average-rating calculation,
 * and owner-response management for mauriel_listing posts.
 *
 * @package MaurielServiceDirectory
 * @since   1.0.0
 */
class Mauriel_Reviews {

	// -------------------------------------------------------------------------
	// Submit
	// -------------------------------------------------------------------------

	/**
	 * Submits a new review for a listing.
	 *
	 * @param  int   $listing_id  Post ID of the mauriel_listing.
	 * @param  array $data        Review data: author_name, author_email,
	 *                            content, rating, user_id (optional).
	 * @param  bool  $approved    Whether to auto-approve the review.
	 * @return int|WP_Error       New comment ID or WP_Error on failure.
	 */
	public static function submit( $listing_id, array $data, $approved = false ) {
		$listing_id = absint( $listing_id );

		// Validate listing exists and is the correct post type.
		$listing = get_post( $listing_id );
		if ( ! $listing || 'mauriel_listing' !== $listing->post_type ) {
			return new WP_Error(
				'invalid_listing',
				__( 'Invalid listing ID.', 'mauriel-service-directory' )
			);
		}

		// Sanitise input.
		$author_name  = isset( $data['author_name'] )  ? sanitize_text_field( $data['author_name'] )  : '';
		$author_email = isset( $data['author_email'] ) ? sanitize_email( $data['author_email'] )       : '';
		$content      = isset( $data['content'] )      ? sanitize_textarea_field( $data['content'] )  : '';
		$rating       = isset( $data['rating'] )       ? (int) $data['rating']                        : 0;
		$user_id      = isset( $data['user_id'] )      ? absint( $data['user_id'] )                   : 0;

		// Validate required fields.
		if ( '' === $author_name ) {
			return new WP_Error(
				'missing_author_name',
				__( 'Reviewer name is required.', 'mauriel-service-directory' )
			);
		}

		if ( '' === $content ) {
			return new WP_Error(
				'missing_content',
				__( 'Review content is required.', 'mauriel-service-directory' )
			);
		}

		// Clamp rating 1–5.
		if ( $rating < 1 || $rating > 5 ) {
			return new WP_Error(
				'invalid_rating',
				__( 'Rating must be between 1 and 5.', 'mauriel-service-directory' )
			);
		}

		$comment_approved = $approved ? 1 : 0;

		$comment_data = array(
			'comment_post_ID'      => $listing_id,
			'comment_author'       => $author_name,
			'comment_author_email' => $author_email,
			'comment_content'      => $content,
			'comment_type'         => 'mauriel_review',
			'comment_approved'     => $comment_approved,
			'user_id'              => $user_id,
		);

		// wp_insert_comment returns comment ID (int) or false on failure.
		$comment_id = wp_insert_comment( $comment_data );

		if ( ! $comment_id ) {
			return new WP_Error(
				'insert_failed',
				__( 'Failed to save the review. Please try again.', 'mauriel-service-directory' )
			);
		}

		// Store the numeric rating as comment meta.
		add_comment_meta( $comment_id, '_mauriel_rating', $rating, true );

		// Recalculate the listing average only when the review is visible.
		if ( $approved ) {
			self::recalculate_average( $listing_id );
		}

		/**
		 * Fires after a review is submitted.
		 *
		 * @param int $comment_id  Newly created comment ID.
		 * @param int $listing_id  The listing the review belongs to.
		 */
		do_action( 'mauriel_review_submitted', $comment_id, $listing_id );

		return $comment_id;
	}

	// -------------------------------------------------------------------------
	// Fetch
	// -------------------------------------------------------------------------

	/**
	 * Retrieves paginated reviews for a listing, enriched with review meta.
	 *
	 * @param  int    $listing_id  Post ID of the listing.
	 * @param  string $status      Comment status: 'approve', 'hold', 'all', etc.
	 * @param  int    $page        1-based page number.
	 * @param  int    $per_page    Number of reviews per page.
	 * @return array{
	 *     comments: WP_Comment[],
	 *     total: int
	 * }
	 */
	public static function get_for_listing( $listing_id, $status = 'approve', $page = 1, $per_page = 10 ) {
		$listing_id = absint( $listing_id );
		$page       = max( 1, absint( $page ) );
		$per_page   = max( 1, absint( $per_page ) );
		$offset     = ( $page - 1 ) * $per_page;

		$args = array(
			'post_id'      => $listing_id,
			'type'         => 'mauriel_review',
			'status'       => $status,
			'number'       => $per_page,
			'offset'       => $offset,
			'orderby'      => 'comment_date',
			'order'        => 'DESC',
			'count'        => false,
		);

		$comments = get_comments( $args );

		// Get total without limit so we can build pagination.
		$total_args           = $args;
		$total_args['number'] = 0;
		$total_args['offset'] = 0;
		$total_args['count']  = true;
		$total                = (int) get_comments( $total_args );

		// Enrich each comment object with review-specific meta.
		foreach ( $comments as $comment ) {
			$comment->mauriel_rating                  = (int) get_comment_meta( $comment->comment_ID, '_mauriel_rating', true );
			$comment->mauriel_owner_response          = get_comment_meta( $comment->comment_ID, '_mauriel_owner_response', true );
			$comment->mauriel_owner_response_date     = get_comment_meta( $comment->comment_ID, '_mauriel_owner_response_date', true );
			$comment->mauriel_google_imported         = (bool) get_comment_meta( $comment->comment_ID, '_mauriel_google_imported', true );
		}

		return array(
			'comments' => $comments,
			'total'    => $total,
		);
	}

	// -------------------------------------------------------------------------
	// Average rating
	// -------------------------------------------------------------------------

	/**
	 * Recalculates and stores the average rating and total review count
	 * for a listing based on all approved reviews.
	 *
	 * @param  int $listing_id  Post ID of the listing.
	 * @return array{float, int}  [ average, count ]
	 */
	public static function recalculate_average( $listing_id ) {
		global $wpdb;

		$listing_id = absint( $listing_id );

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT AVG(m.meta_value) AS avg_rating, COUNT(m.meta_value) AS review_count
				 FROM {$wpdb->comments} c
				 INNER JOIN {$wpdb->commentmeta} m ON c.comment_ID = m.comment_id
				 WHERE c.comment_post_ID = %d
				   AND c.comment_type    = 'mauriel_review'
				   AND c.comment_approved = '1'
				   AND m.meta_key         = '_mauriel_rating'",
				$listing_id
			)
		);

		$avg   = $result ? round( (float) $result->avg_rating, 1 ) : 0.0;
		$count = $result ? (int) $result->review_count              : 0;

		update_post_meta( $listing_id, '_mauriel_avg_rating',    $avg );
		update_post_meta( $listing_id, '_mauriel_review_count',  $count );

		return array( $avg, $count );
	}

	// -------------------------------------------------------------------------
	// Moderation
	// -------------------------------------------------------------------------

	/**
	 * Sets the moderation status of a review comment and recalculates the
	 * listing average if the comment is being approved.
	 *
	 * @param  int    $comment_id  Comment ID to moderate.
	 * @param  string $status      'approve', 'hold', 'spam', or 'trash'.
	 * @return bool|WP_Error
	 */
	public static function moderate( $comment_id, $status ) {
		$comment_id = absint( $comment_id );

		$allowed_statuses = array( 'approve', 'hold', 'spam', 'trash' );
		if ( ! in_array( $status, $allowed_statuses, true ) ) {
			return new WP_Error(
				'invalid_status',
				__( 'Invalid review moderation status.', 'mauriel-service-directory' )
			);
		}

		$result = wp_set_comment_status( $comment_id, $status );

		if ( $result && 'approve' === $status ) {
			$comment = get_comment( $comment_id );
			if ( $comment ) {
				self::recalculate_average( (int) $comment->comment_post_ID );
			}
		}

		return $result;
	}

	// -------------------------------------------------------------------------
	// Owner response
	// -------------------------------------------------------------------------

	/**
	 * Saves or updates the business owner's response to a review.
	 *
	 * Verifies that $owner_id is actually the owner of the listing associated
	 * with the review before saving.
	 *
	 * @param  int    $comment_id    Comment (review) ID.
	 * @param  string $response_text The owner's response text.
	 * @param  int    $owner_id      User ID of the responding owner.
	 * @return bool|WP_Error
	 */
	public static function save_owner_response( $comment_id, $response_text, $owner_id ) {
		$comment_id = absint( $comment_id );
		$owner_id   = absint( $owner_id );

		$comment = get_comment( $comment_id );
		if ( ! $comment || 'mauriel_review' !== $comment->comment_type ) {
			return new WP_Error(
				'invalid_review',
				__( 'Review not found.', 'mauriel-service-directory' )
			);
		}

		$listing_id = (int) $comment->comment_post_ID;

		// Capability check: user must be the owner of the listing, or an admin.
		$stored_owner_id = (int) get_post_meta( $listing_id, '_mauriel_owner_id', true );
		$user            = get_userdata( $owner_id );

		if ( ! $user ) {
			return new WP_Error(
				'invalid_user',
				__( 'Invalid user.', 'mauriel-service-directory' )
			);
		}

		$is_admin = user_can( $owner_id, 'manage_options' )
			|| user_can( $owner_id, 'mauriel_admin' );

		if ( $stored_owner_id !== $owner_id && ! $is_admin ) {
			return new WP_Error(
				'unauthorized',
				__( 'You do not have permission to respond to this review.', 'mauriel-service-directory' )
			);
		}

		$sanitized_response = sanitize_textarea_field( $response_text );

		update_comment_meta( $comment_id, '_mauriel_owner_response',      $sanitized_response );
		update_comment_meta( $comment_id, '_mauriel_owner_response_date', current_time( 'mysql' ) );
		update_comment_meta( $comment_id, '_mauriel_owner_response_user', $owner_id );

		return true;
	}

	// -------------------------------------------------------------------------
	// Delete
	// -------------------------------------------------------------------------

	/**
	 * Permanently deletes a review and recalculates the listing average.
	 *
	 * @param  int $comment_id  Comment (review) ID to delete.
	 * @return bool|WP_Error
	 */
	public static function delete_review( $comment_id ) {
		$comment_id = absint( $comment_id );

		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			return new WP_Error(
				'not_found',
				__( 'Review not found.', 'mauriel-service-directory' )
			);
		}

		$listing_id = (int) $comment->comment_post_ID;

		$deleted = wp_delete_comment( $comment_id, true ); // force_delete = true

		if ( $deleted ) {
			self::recalculate_average( $listing_id );
		}

		return $deleted;
	}
}
