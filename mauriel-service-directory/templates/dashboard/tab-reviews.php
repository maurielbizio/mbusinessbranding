<?php defined( 'ABSPATH' ) || exit;
$listing_id  = $listing->ID;
$avg_rating  = floatval( get_post_meta( $listing_id, '_mauriel_avg_rating', true ) );
$review_count = (int) get_post_meta( $listing_id, '_mauriel_review_count', true );
$all_reviews = Mauriel_Reviews::get_for_listing( $listing_id, 'all', 1, 50 );
$reviews     = $all_reviews['comments'] ?? array();
$ai_enabled  = Mauriel_AI::is_enabled();
$place_key   = get_option( 'mauriel_google_places_key', '' );
$place_id    = get_post_meta( $listing_id, '_mauriel_place_id', true );
?>
<div class="mauriel-tab-panel mauriel-tab-reviews">
	<h2><?php esc_html_e( 'Reviews', 'mauriel-service-directory' ); ?></h2>

	<div class="mauriel-reviews-summary-bar">
		<div class="mauriel-avg-display">
			<span class="mauriel-avg-number"><?php echo number_format( $avg_rating, 1 ); ?></span>
			<div class="mauriel-stars">
				<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
				<span class="mauriel-star <?php echo $i <= round( $avg_rating ) ? 'filled' : ''; ?>">&#9733;</span>
				<?php endfor; ?>
			</div>
			<span><?php echo esc_html( sprintf( _n( '%d review', '%d reviews', $review_count, 'mauriel-service-directory' ), $review_count ) ); ?></span>
		</div>

		<?php if ( $place_key ) : ?>
		<div class="mauriel-google-import">
			<label><?php esc_html_e( 'Google Place ID:', 'mauriel-service-directory' ); ?></label>
			<input type="text" id="mauriel-place-id-input" class="mauriel-form-control" value="<?php echo esc_attr( $place_id ); ?>" placeholder="ChIJ...">
			<button type="button" class="mauriel-btn mauriel-btn-outline" id="mauriel-import-google-reviews"
			        data-listing-id="<?php echo esc_attr( $listing_id ); ?>">
				<?php esc_html_e( 'Import Google Reviews', 'mauriel-service-directory' ); ?>
			</button>
			<div class="mauriel-import-result" style="display:none;"></div>
		</div>
		<?php endif; ?>
	</div>

	<?php if ( empty( $reviews ) ) : ?>
	<div class="mauriel-empty-state">
		<div class="mauriel-empty-icon">⭐</div>
		<h3><?php esc_html_e( 'No reviews yet', 'mauriel-service-directory' ); ?></h3>
	</div>
	<?php else : ?>
	<div class="mauriel-reviews-manage-list">
		<?php foreach ( $reviews as $review ) :
			$rating    = (int) get_comment_meta( $review->comment_ID, '_mauriel_rating', true );
			$response  = get_comment_meta( $review->comment_ID, '_mauriel_owner_response', true );
			$is_google = get_comment_meta( $review->comment_ID, '_mauriel_google_imported', true );
			$status    = $review->comment_approved;
		?>
		<div class="mauriel-review-manage-card <?php echo $is_google ? 'mauriel-google-review' : ''; ?>"
		     id="manage-review-<?php echo esc_attr( $review->comment_ID ); ?>">
			<div class="mauriel-review-manage-header">
				<div>
					<strong><?php echo esc_html( $review->comment_author ); ?></strong>
					<?php if ( $is_google ) : ?><span class="mauriel-badge-google">Google</span><?php endif; ?>
					<span class="mauriel-stars">
						<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
						<span class="mauriel-star <?php echo $i <= $rating ? 'filled' : ''; ?>">&#9733;</span>
						<?php endfor; ?>
					</span>
				</div>
				<div class="mauriel-review-manage-actions">
					<span class="mauriel-status-badge <?php echo '1' === $status ? 'approved' : 'pending'; ?>">
						<?php echo '1' === $status ? esc_html__( 'Approved', 'mauriel-service-directory' ) : esc_html__( 'Pending', 'mauriel-service-directory' ); ?>
					</span>
					<button type="button" class="mauriel-btn mauriel-btn-sm mauriel-btn-outline mauriel-toggle-response"
					        data-review-id="<?php echo esc_attr( $review->comment_ID ); ?>">
						<?php echo $response ? esc_html__( 'Edit Response', 'mauriel-service-directory' ) : esc_html__( 'Reply', 'mauriel-service-directory' ); ?>
					</button>
					<button type="button" class="mauriel-btn mauriel-btn-sm mauriel-btn-danger mauriel-trash-review"
					        data-review-id="<?php echo esc_attr( $review->comment_ID ); ?>">
						<?php esc_html_e( 'Trash', 'mauriel-service-directory' ); ?>
					</button>
				</div>
			</div>
			<p class="mauriel-review-text"><?php echo wp_kses_post( $review->comment_content ); ?></p>

			<?php if ( $response ) : ?>
			<div class="mauriel-existing-response">
				<strong><?php esc_html_e( 'Your response:', 'mauriel-service-directory' ); ?></strong>
				<p><?php echo wp_kses_post( $response ); ?></p>
			</div>
			<?php endif; ?>

			<div class="mauriel-response-form" id="response-form-<?php echo esc_attr( $review->comment_ID ); ?>" style="display:none;">
				<?php if ( $ai_enabled ) : ?>
				<button type="button" class="mauriel-btn mauriel-btn-sm mauriel-btn-outline mauriel-ai-suggest-response"
				        data-comment-id="<?php echo esc_attr( $review->comment_ID ); ?>"
				        data-listing-id="<?php echo esc_attr( $listing_id ); ?>">
					&#129302; <?php esc_html_e( 'Generate AI Response', 'mauriel-service-directory' ); ?>
				</button>
				<?php endif; ?>
				<textarea class="mauriel-form-control mauriel-response-textarea" rows="4"
				          placeholder="<?php esc_attr_e( 'Write your response...', 'mauriel-service-directory' ); ?>"><?php echo esc_textarea( $response ); ?></textarea>
				<button type="button" class="mauriel-btn mauriel-btn-primary mauriel-submit-response"
				        data-comment-id="<?php echo esc_attr( $review->comment_ID ); ?>"
				        data-listing-id="<?php echo esc_attr( $listing_id ); ?>">
					<?php esc_html_e( 'Submit Response', 'mauriel-service-directory' ); ?>
				</button>
			</div>
		</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
</div>
