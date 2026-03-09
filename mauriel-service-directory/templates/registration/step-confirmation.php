<?php defined( 'ABSPATH' ) || exit;
$auto_approve  = get_option( 'mauriel_auto_approve_listings', 0 );
$dashboard_url = get_permalink( get_option( 'mauriel_dashboard_page_id' ) );
$listing_url   = $listing_id ? get_permalink( $listing_id ) : '';
?>
<div class="mauriel-registration mauriel-confirmation">
	<div class="mauriel-success-icon">&#10004;</div>
	<h2><?php esc_html_e( "You're all set!", 'mauriel-service-directory' ); ?></h2>
	<?php if ( $auto_approve ) : ?>
	<p><?php esc_html_e( 'Your listing is now live! You can edit and manage it from your dashboard.', 'mauriel-service-directory' ); ?></p>
	<?php else : ?>
	<p><?php esc_html_e( 'Your listing has been submitted and is pending review. We\'ll notify you by email once it\'s approved.', 'mauriel-service-directory' ); ?></p>
	<?php endif; ?>
	<div class="mauriel-confirmation-actions">
		<a href="<?php echo esc_url( $dashboard_url ); ?>" class="mauriel-btn mauriel-btn-primary">
			<?php esc_html_e( 'Go to My Dashboard', 'mauriel-service-directory' ); ?>
		</a>
		<?php if ( $auto_approve && $listing_url ) : ?>
		<a href="<?php echo esc_url( $listing_url ); ?>" class="mauriel-btn mauriel-btn-outline" target="_blank">
			<?php esc_html_e( 'View My Listing', 'mauriel-service-directory' ); ?>
		</a>
		<?php endif; ?>
	</div>
</div>
