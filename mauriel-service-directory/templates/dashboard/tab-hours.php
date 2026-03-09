<?php defined( 'ABSPATH' ) || exit;
$listing_id = $listing->ID;
$hours      = Mauriel_DB_Hours::get_hours( $listing_id );
$day_names  = array( 0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday' );
?>
<div class="mauriel-tab-panel mauriel-tab-hours">
	<h2><?php esc_html_e( 'Business Hours', 'mauriel-service-directory' ); ?></h2>
	<form method="post" action="" class="mauriel-hours-form">
		<?php wp_nonce_field( 'mauriel_save_hours' ); ?>
		<input type="hidden" name="mauriel_dashboard_action" value="save_hours">
		<input type="hidden" name="listing_id" value="<?php echo esc_attr( $listing_id ); ?>">

		<div class="mauriel-hours-quick-actions">
			<button type="button" class="mauriel-btn mauriel-btn-sm mauriel-btn-outline" id="mauriel-set-weekdays">
				<?php esc_html_e( 'Set Mon–Fri 9am–5pm', 'mauriel-service-directory' ); ?>
			</button>
			<button type="button" class="mauriel-btn mauriel-btn-sm mauriel-btn-outline" id="mauriel-set-all-open">
				<?php esc_html_e( 'Open All Days', 'mauriel-service-directory' ); ?>
			</button>
			<button type="button" class="mauriel-btn mauriel-btn-sm mauriel-btn-outline" id="mauriel-set-all-closed">
				<?php esc_html_e( 'Closed All Days', 'mauriel-service-directory' ); ?>
			</button>
		</div>

		<table class="mauriel-hours-editor-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Day', 'mauriel-service-directory' ); ?></th>
					<th><?php esc_html_e( 'Open?', 'mauriel-service-directory' ); ?></th>
					<th><?php esc_html_e( 'Open Time', 'mauriel-service-directory' ); ?></th>
					<th><?php esc_html_e( 'Close Time', 'mauriel-service-directory' ); ?></th>
					<th><?php esc_html_e( '24 Hours', 'mauriel-service-directory' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $day_names as $day_num => $day_name ) :
				$h = $hours[ $day_num ] ?? null;
				$is_open     = $h ? (bool) $h->is_open : ( $day_num >= 1 && $day_num <= 5 );
				$is_24       = $h ? (bool) $h->is_24_hours : false;
				$open_time   = $h ? $h->open_time : '09:00:00';
				$close_time  = $h ? $h->close_time : '17:00:00';
			?>
			<tr class="mauriel-hours-row" data-day="<?php echo esc_attr( $day_num ); ?>">
				<td class="mauriel-hours-day-name"><strong><?php echo esc_html( $day_name ); ?></strong></td>
				<td>
					<input type="checkbox" name="hours[<?php echo esc_attr( $day_num ); ?>][is_open]" value="1"
					       class="mauriel-hours-open-check"
					       <?php checked( $is_open ); ?>>
				</td>
				<td>
					<input type="time" name="hours[<?php echo esc_attr( $day_num ); ?>][open_time]"
					       class="mauriel-form-control mauriel-time-input"
					       value="<?php echo esc_attr( substr( $open_time, 0, 5 ) ); ?>"
					       <?php echo ! $is_open || $is_24 ? 'disabled' : ''; ?>>
				</td>
				<td>
					<input type="time" name="hours[<?php echo esc_attr( $day_num ); ?>][close_time]"
					       class="mauriel-form-control mauriel-time-input"
					       value="<?php echo esc_attr( substr( $close_time, 0, 5 ) ); ?>"
					       <?php echo ! $is_open || $is_24 ? 'disabled' : ''; ?>>
				</td>
				<td>
					<input type="checkbox" name="hours[<?php echo esc_attr( $day_num ); ?>][is_24_hours]" value="1"
					       class="mauriel-hours-24-check"
					       <?php checked( $is_24 ); ?>
					       <?php echo ! $is_open ? 'disabled' : ''; ?>>
				</td>
			</tr>
			<?php endforeach; ?>
			</tbody>
		</table>

		<div class="mauriel-form-actions">
			<button type="submit" class="mauriel-btn mauriel-btn-primary"><?php esc_html_e( 'Save Hours', 'mauriel-service-directory' ); ?></button>
		</div>
	</form>
</div>
