<?php defined( 'ABSPATH' ) || exit;
/**
 * Template: Business Hours Display
 * Variables: $listing_id
 */
$hours     = Mauriel_DB_Hours::get_hours( $listing_id );
$is_open   = Mauriel_DB_Hours::is_open_now( $listing_id );
$day_names = array(
	0 => __( 'Sunday', 'mauriel-service-directory' ),
	1 => __( 'Monday', 'mauriel-service-directory' ),
	2 => __( 'Tuesday', 'mauriel-service-directory' ),
	3 => __( 'Wednesday', 'mauriel-service-directory' ),
	4 => __( 'Thursday', 'mauriel-service-directory' ),
	5 => __( 'Friday', 'mauriel-service-directory' ),
	6 => __( 'Saturday', 'mauriel-service-directory' ),
);
$today = (int) date_i18n( 'w' );
?>
<div class="mauriel-hours-wrap">
	<div class="mauriel-open-status <?php echo $is_open ? 'is-open' : 'is-closed'; ?>">
		<span class="mauriel-status-dot"></span>
		<?php echo $is_open ? esc_html__( 'Open Now', 'mauriel-service-directory' ) : esc_html__( 'Closed Now', 'mauriel-service-directory' ); ?>
	</div>
	<table class="mauriel-hours-table">
		<tbody>
		<?php foreach ( $day_names as $day_num => $day_name ) :
			$day  = $hours[ $day_num ] ?? null;
			$active_class = ( $day_num === $today ) ? 'mauriel-today' : '';
		?>
			<tr class="mauriel-hours-row <?php echo esc_attr( $active_class ); ?>">
				<td class="mauriel-hours-day">
					<?php echo esc_html( $day_name ); ?>
					<?php if ( $day_num === $today ) : ?><span class="mauriel-today-badge"><?php esc_html_e( 'Today', 'mauriel-service-directory' ); ?></span><?php endif; ?>
				</td>
				<td class="mauriel-hours-time">
					<?php if ( ! $day || ! $day->is_open ) : ?>
						<span class="mauriel-closed"><?php esc_html_e( 'Closed', 'mauriel-service-directory' ); ?></span>
					<?php elseif ( $day->is_24_hours ) : ?>
						<span class="mauriel-open-24"><?php esc_html_e( 'Open 24 Hours', 'mauriel-service-directory' ); ?></span>
					<?php else : ?>
						<?php
						$open  = $day->open_time ? date( 'g:i A', strtotime( $day->open_time ) ) : '';
						$close = $day->close_time ? date( 'g:i A', strtotime( $day->close_time ) ) : '';
						echo esc_html( $open . ' – ' . $close );
						?>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
</div>
