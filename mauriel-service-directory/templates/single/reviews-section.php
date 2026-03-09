<?php defined( 'ABSPATH' ) || exit;
/**
 * Template: Reviews Section
 * Variables: $listing_id, $reviews, $total, $avg_rating, $review_count
 */
$avg_rating   = floatval( get_post_meta( $listing_id, '_mauriel_avg_rating', true ) );
$review_count = (int) get_post_meta( $listing_id, '_mauriel_review_count', true );
$reviews_data = Mauriel_Reviews::get_for_listing( $listing_id, 'approve', 1, 10 );
$reviews      = $reviews_data['comments'] ?? array();
$total        = $reviews_data['total'] ?? 0;
$guest_ok     = get_option( 'mauriel_guest_reviews', 1 );
$auto_approve = get_option( 'mauriel_auto_approve_reviews', 0 );
?>
<section class="mauriel-reviews-section" id="reviews">
	<h2 class="mauriel-section-title"><?php esc_html_e( 'Reviews', 'mauriel-service-directory' ); ?></h2>

	<?php if ( $avg_rating > 0 ) : ?>
	<div class="mauriel-rating-summary">
		<div class="mauriel-rating-avg">
			<span class="mauriel-rating-number"><?php echo number_format( $avg_rating, 1 ); ?></span>
			<div class="mauriel-stars" aria-label="<?php echo esc_attr( $avg_rating ); ?> out of 5">
				<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
					<span class="mauriel-star <?php echo $i <= round( $avg_rating ) ? 'filled' : ''; ?>">&#9733;</span>
				<?php endfor; ?>
			</div>
			<span class="mauriel-rating-count"><?php echo esc_html( sprintf( _n( '%d review', '%d reviews', $review_count, 'mauriel-service-directory' ), $review_count ) ); ?></span>
		</div>
		<div class="mauriel-rating-bars">
			<?php for ( $star = 5; $star >= 1; $star-- ) : ?>
				<?php
				$star_count = get_comments( array( 'post_id' => $listing_id, 'comment_type' => 'mauriel_review', 'status' => 'approve', 'meta_query' => array( array( 'key' => '_mauriel_rating', 'value' => $star, 'compare' => '=', 'type' => 'NUMERIC' ) ), 'count' => true ) );
				$pct = $review_count > 0 ? round( ( $star_count / $review_count ) * 100 ) : 0;
				?>
				<div class="mauriel-rating-bar-row">
					<span class="mauriel-bar-label"><?php echo esc_html( $star ); ?> &#9733;</span>
					<div class="mauriel-bar-track"><div class="mauriel-bar-fill" style="width:<?php echo esc_attr( $pct ); ?>%"></div></div>
					<span class="mauriel-bar-count"><?php echo esc_html( $star_count ); ?></span>
				</div>
			<?php endfor; ?>
		</div>
	</div>
	<?php endif; ?>

	<div class="mauriel-reviews-list" id="mauriel-reviews-list">
		<?php if ( empty( $reviews ) ) : ?>
			<p class="mauriel-no-reviews"><?php esc_html_e( 'No reviews yet. Be the first to review this business!', 'mauriel-service-directory' ); ?></p>
		<?php else : ?>
			<?php foreach ( $reviews as $review ) :
				$rating   = (int) get_comment_meta( $review->comment_ID, '_mauriel_rating', true );
				$response = get_comment_meta( $review->comment_ID, '_mauriel_owner_response', true );
				$resp_date = get_comment_meta( $review->comment_ID, '_mauriel_owner_response_date', true );
				$is_google = get_comment_meta( $review->comment_ID, '_mauriel_google_imported', true );
			?>
			<div class="mauriel-review-card" id="review-<?php echo esc_attr( $review->comment_ID ); ?>">
				<div class="mauriel-review-header">
					<div class="mauriel-reviewer-info">
						<?php echo get_avatar( $review->comment_author_email, 48, '', esc_attr( $review->comment_author ), array( 'class' => 'mauriel-reviewer-avatar' ) ); ?>
						<div>
							<strong class="mauriel-reviewer-name"><?php echo esc_html( $review->comment_author ); ?></strong>
							<?php if ( $is_google ) : ?><span class="mauriel-badge mauriel-badge-google">Google</span><?php endif; ?>
							<div class="mauriel-review-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $review->comment_date ) ) ); ?></div>
						</div>
					</div>
					<div class="mauriel-stars" aria-label="<?php echo esc_attr( $rating ); ?> out of 5">
						<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
							<span class="mauriel-star <?php echo $i <= $rating ? 'filled' : ''; ?>">&#9733;</span>
						<?php endfor; ?>
					</div>
				</div>
				<div class="mauriel-review-body">
					<p><?php echo wp_kses_post( $review->comment_content ); ?></p>
				</div>
				<?php if ( $response ) : ?>
				<div class="mauriel-owner-response">
					<strong><?php esc_html_e( 'Response from the owner:', 'mauriel-service-directory' ); ?></strong>
					<?php if ( $resp_date ) : ?>
						<span class="mauriel-response-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $resp_date ) ) ); ?></span>
					<?php endif; ?>
					<p><?php echo wp_kses_post( $response ); ?></p>
				</div>
				<?php endif; ?>
			</div>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>

	<?php if ( is_user_logged_in() || $guest_ok ) : ?>
	<div class="mauriel-review-form-wrap">
		<h3><?php esc_html_e( 'Write a Review', 'mauriel-service-directory' ); ?></h3>
		<div class="mauriel-notice mauriel-review-success" style="display:none;"></div>
		<div class="mauriel-notice mauriel-notice-error mauriel-review-error" style="display:none;"></div>
		<form class="mauriel-review-form" id="mauriel-review-form" data-listing-id="<?php echo esc_attr( $listing_id ); ?>">
			<?php wp_nonce_field( 'mauriel_review_' . $listing_id, 'mauriel_review_nonce' ); ?>
			<input type="hidden" name="listing_id" value="<?php echo esc_attr( $listing_id ); ?>">
			<?php if ( ! is_user_logged_in() ) : ?>
			<div class="mauriel-form-row mauriel-form-row-2">
				<div class="mauriel-form-group">
					<label for="review-author"><?php esc_html_e( 'Your Name', 'mauriel-service-directory' ); ?> <span class="required">*</span></label>
					<input type="text" id="review-author" name="author_name" class="mauriel-form-control" required>
				</div>
				<div class="mauriel-form-group">
					<label for="review-email"><?php esc_html_e( 'Email Address', 'mauriel-service-directory' ); ?> <span class="required">*</span></label>
					<input type="email" id="review-email" name="author_email" class="mauriel-form-control" required>
				</div>
			</div>
			<?php endif; ?>
			<div class="mauriel-form-group">
				<label><?php esc_html_e( 'Rating', 'mauriel-service-directory' ); ?> <span class="required">*</span></label>
				<div class="mauriel-star-selector" id="mauriel-star-selector">
					<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
					<input type="radio" id="star-<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
					<label for="star-<?php echo $i; ?>" title="<?php echo esc_attr( $i ); ?> stars">&#9733;</label>
					<?php endfor; ?>
				</div>
			</div>
			<div class="mauriel-form-group">
				<label for="review-content"><?php esc_html_e( 'Your Review', 'mauriel-service-directory' ); ?> <span class="required">*</span></label>
				<textarea id="review-content" name="content" class="mauriel-form-control" rows="5" required placeholder="<?php esc_attr_e( 'Share your experience...', 'mauriel-service-directory' ); ?>"></textarea>
			</div>
			<button type="submit" class="mauriel-btn mauriel-btn-primary"><?php esc_html_e( 'Submit Review', 'mauriel-service-directory' ); ?></button>
		</form>
	</div>
	<?php endif; ?>
</section>
