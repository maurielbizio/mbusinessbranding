<?php defined( 'ABSPATH' ) || exit;
$listing_id  = $listing->ID;
$status_filter = isset( $_GET['lead_status'] ) ? sanitize_key( $_GET['lead_status'] ) : '';
$page        = max( 1, absint( $_GET['leads_page'] ?? 1 ) );
$per_page    = 15;
$offset      = ( $page - 1 ) * $per_page;
$leads       = Mauriel_DB_Leads::get_for_listing( $listing_id, $status_filter, $per_page, $offset );
$total       = Mauriel_DB_Leads::count_for_listing( $listing_id, $status_filter );
$new_count   = Mauriel_DB_Leads::count_for_listing( $listing_id, 'new' );
$tab_url     = add_query_arg( 'tab', 'leads', get_permalink() );
?>
<div class="mauriel-tab-panel mauriel-tab-leads">
	<h2><?php esc_html_e( 'Lead Inbox', 'mauriel-service-directory' ); ?>
		<?php if ( $new_count > 0 ) : ?><span class="mauriel-badge-count"><?php echo esc_html( $new_count ); ?></span><?php endif; ?>
	</h2>

	<div class="mauriel-lead-filters">
		<?php foreach ( array( '' => __( 'All', 'mauriel-service-directory' ), 'new' => __( 'New', 'mauriel-service-directory' ), 'read' => __( 'Read', 'mauriel-service-directory' ), 'archived' => __( 'Archived', 'mauriel-service-directory' ) ) as $status => $label ) : ?>
		<a href="<?php echo esc_url( add_query_arg( 'lead_status', $status, $tab_url ) ); ?>"
		   class="mauriel-btn mauriel-btn-sm <?php echo $status === $status_filter ? 'mauriel-btn-primary' : 'mauriel-btn-outline'; ?>">
			<?php echo esc_html( $label ); ?>
		</a>
		<?php endforeach; ?>
	</div>

	<?php if ( empty( $leads ) ) : ?>
	<div class="mauriel-empty-state">
		<div class="mauriel-empty-icon">📩</div>
		<h3><?php esc_html_e( 'No leads yet', 'mauriel-service-directory' ); ?></h3>
		<p><?php esc_html_e( 'When someone contacts you through your listing, their message will appear here.', 'mauriel-service-directory' ); ?></p>
	</div>
	<?php else : ?>
	<div class="mauriel-leads-list">
		<?php foreach ( $leads as $lead ) :
			$type_labels = array( 'contact' => __( 'Contact', 'mauriel-service-directory' ), 'quote' => __( 'Quote Request', 'mauriel-service-directory' ), 'phone_click' => __( 'Phone Click', 'mauriel-service-directory' ), 'email_click' => __( 'Email Click', 'mauriel-service-directory' ) );
			$type_label = $type_labels[ $lead->lead_type ] ?? $lead->lead_type;
		?>
		<div class="mauriel-lead-card <?php echo 'new' === $lead->status ? 'mauriel-lead-new' : ''; ?>"
		     id="lead-<?php echo esc_attr( $lead->id ); ?>">
			<div class="mauriel-lead-header">
				<span class="mauriel-lead-type-badge mauriel-type-<?php echo esc_attr( $lead->lead_type ); ?>"><?php echo esc_html( $type_label ); ?></span>
				<span class="mauriel-lead-date"><?php echo esc_html( human_time_diff( strtotime( $lead->created_at ) ) . ' ago' ); ?></span>
				<?php if ( 'new' === $lead->status ) : ?><span class="mauriel-new-badge"><?php esc_html_e( 'NEW', 'mauriel-service-directory' ); ?></span><?php endif; ?>
			</div>
			<div class="mauriel-lead-body">
				<?php if ( $lead->name ) : ?><p><strong><?php esc_html_e( 'Name:', 'mauriel-service-directory' ); ?></strong> <?php echo esc_html( $lead->name ); ?></p><?php endif; ?>
				<?php if ( $lead->email ) : ?><p><strong><?php esc_html_e( 'Email:', 'mauriel-service-directory' ); ?></strong> <a href="mailto:<?php echo esc_attr( $lead->email ); ?>"><?php echo esc_html( $lead->email ); ?></a></p><?php endif; ?>
				<?php if ( $lead->phone ) : ?><p><strong><?php esc_html_e( 'Phone:', 'mauriel-service-directory' ); ?></strong> <a href="tel:<?php echo esc_attr( $lead->phone ); ?>"><?php echo esc_html( $lead->phone ); ?></a></p><?php endif; ?>
				<?php if ( $lead->message ) : ?><p class="mauriel-lead-message"><?php echo esc_html( wp_trim_words( $lead->message, 30 ) ); ?></p><?php endif; ?>
			</div>
			<?php if ( 'new' === $lead->status ) : ?>
			<div class="mauriel-lead-actions">
				<button class="mauriel-btn mauriel-btn-sm mauriel-btn-outline mauriel-mark-lead-read"
				        data-lead-id="<?php echo esc_attr( $lead->id ); ?>">
					<?php esc_html_e( 'Mark as Read', 'mauriel-service-directory' ); ?>
				</button>
			</div>
			<?php endif; ?>
		</div>
		<?php endforeach; ?>
	</div>
	<?php if ( ceil( $total / $per_page ) > 1 ) : ?>
	<div class="mauriel-pagination">
		<?php for ( $p = 1; $p <= ceil( $total / $per_page ); $p++ ) : ?>
		<a href="<?php echo esc_url( add_query_arg( 'leads_page', $p, $tab_url ) ); ?>"
		   class="mauriel-page-btn <?php echo $p === $page ? 'active' : ''; ?>">
			<?php echo esc_html( $p ); ?>
		</a>
		<?php endfor; ?>
	</div>
	<?php endif; ?>
	<?php endif; ?>
</div>
