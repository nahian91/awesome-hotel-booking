<?php
/**
 * Detailed Booking Folio & Guest View.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have permission to view this reservation.', 'awesome-hotel-booking' ) );
}

global $wpdb;

$table_bookings = $wpdb->prefix . 'ahbn_bookings';
$table_rooms    = $wpdb->prefix . 'ahbn_rooms';
$list_url       = admin_url( 'admin.php?page=awesome_hotel_booking&tab=bookings' );

// 1. Resolve Booking ID from passed variable or any URL query parameter variant.
$ahbn_booking_id = 0;

if ( isset( $booking_id ) && absint( $booking_id ) > 0 ) {
	$ahbn_booking_id = absint( $booking_id );
} elseif ( isset( $_GET['booking_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$ahbn_booking_id = absint( wp_unslash( $_GET['booking_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
} elseif ( isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$ahbn_booking_id = absint( wp_unslash( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
} elseif ( isset( $_GET['bid'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$ahbn_booking_id = absint( wp_unslash( $_GET['bid'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

if ( ! $ahbn_booking_id ) {
	?>
	<div class="notice notice-error">
		<p>
			<strong><?php esc_html_e( 'Error:', 'awesome-hotel-booking' ); ?></strong>
			<?php esc_html_e( 'No valid booking ID was provided.', 'awesome-hotel-booking' ); ?>
			<a href="<?php echo esc_url( $list_url ); ?>" style="margin-left:8px; font-weight:700;">
				&larr; <?php esc_html_e( 'Return to Bookings Directory', 'awesome-hotel-booking' ); ?>
			</a>
		</p>
	</div>
	<?php
	return;
}

// 2. Query record from custom database table.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$booking = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT 
			b.*,
			r.room_number,
			r.title AS room_title,
			r.room_type,
			r.price_per_night,
			r.thumbnail_id
		 FROM {$table_bookings} b
		 LEFT JOIN {$table_rooms} r ON b.room_id = r.id
		 WHERE b.id = %d",
		$ahbn_booking_id
	)
);
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

if ( ! $booking ) {
	?>
	<div class="notice notice-error">
		<p>
			<strong><?php esc_html_e( 'Error:', 'awesome-hotel-booking' ); ?></strong>
			<?php
			printf(
				/* translators: %d: booking ID */
				esc_html__( 'Reservation #%d does not exist in the database.', 'awesome-hotel-booking' ),
				absint( $ahbn_booking_id )
			);
			?>
			<a href="<?php echo esc_url( $list_url ); ?>" style="margin-left:8px; font-weight:700;">
				&larr; <?php esc_html_e( 'Return to Bookings Directory', 'awesome-hotel-booking' ); ?>
			</a>
		</p>
	</div>
	<?php
	return;
}

// 3. Compute Display Values.
$nights          = max( 1, (int) $booking->nights );
if ( $nights <= 1 && ! empty( $booking->checkin_date ) && ! empty( $booking->checkout_date ) ) {
	$calc_nights = (int) round( ( strtotime( $booking->checkout_date ) - strtotime( $booking->checkin_date ) ) / DAY_IN_SECONDS );
	$nights      = max( 1, $calc_nights );
}

$total_amount    = (float) $booking->total_amount;
$nightly_rate    = (float) $booking->room_rate > 0 ? (float) $booking->room_rate : ( (float) $booking->price_per_night > 0 ? (float) $booking->price_per_night : ( $total_amount / $nights ) );
$room_thumb_url  = ! empty( $booking->thumbnail_id ) ? wp_get_attachment_image_url( $booking->thumbnail_id, 'medium' ) : '';
$edit_url        = add_query_arg( array( 'action' => 'edit', 'booking_id' => $booking->id ), $list_url );
$clean_whatsapp  = preg_replace( '/[^0-9]/', '', (string) $booking->guest_phone );
$room_title      = ! empty( $booking->room_title ) ? $booking->room_title : __( 'Standard Room', 'awesome-hotel-booking' );
$room_type       = ! empty( $booking->room_type ) ? $booking->room_type : __( 'Standard', 'awesome-hotel-booking' );
$room_number     = ! empty( $booking->room_number ) ? '#' . $booking->room_number : __( 'Unassigned', 'awesome-hotel-booking' );
?>

<div class="ahbn-view-container">
	<div class="ahbn-toolbar">
		<a href="<?php echo esc_url( $list_url ); ?>" class="btn-back">
			<span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span> 
			<?php esc_html_e( 'Back to Bookings', 'awesome-hotel-booking' ); ?>
		</a>
		<div class="ahbn-actions">
			<span class="ahbn-code-badge" style="font-size:12px; margin-right:8px;">
				<?php echo esc_html( ! empty( $booking->booking_code ) ? $booking->booking_code : '#' . $booking->id ); ?>
			</span>
			<button type="button" onclick="window.print();" class="button button-secondary">
				<span class="dashicons dashicons-printer" aria-hidden="true"></span>
				<?php esc_html_e( 'Print Folio', 'awesome-hotel-booking' ); ?>
			</button>
			<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-primary">
				<?php esc_html_e( 'Edit Reservation', 'awesome-hotel-booking' ); ?>
			</a>
		</div>
	</div>

	<div class="ahbn-view-grid">
		<div class="ahbn-main-col">
			
			<div class="ahbn-card">
				<div class="stay-banner">
					<div class="stay-date-box">
						<h4><?php esc_html_e( 'Check-In', 'awesome-hotel-booking' ); ?></h4>
						<div>
							<?php echo esc_html( ! empty( $booking->checkin_date ) ? wp_date( get_option( 'date_format' ), strtotime( $booking->checkin_date ) ) : '—' ); ?>
						</div>
					</div>
					<div class="stay-duration">
						<span class="dashicons dashicons-moon" style="font-size:14px; margin-top:3px;" aria-hidden="true"></span> 
						<?php
						printf(
							/* translators: %d: number of nights */
							esc_html( _n( '%d Night Stay', '%d Nights Stay', $nights, 'awesome-hotel-booking' ) ),
							absint( $nights )
						);
						?>
					</div>
					<div class="stay-date-box">
						<h4><?php esc_html_e( 'Check-Out', 'awesome-hotel-booking' ); ?></h4>
						<div>
							<?php echo esc_html( ! empty( $booking->checkout_date ) ? wp_date( get_option( 'date_format' ), strtotime( $booking->checkout_date ) ) : '—' ); ?>
						</div>
					</div>
				</div>
			</div>

			<div class="ahbn-card">
				<div class="card-header">
					<span class="dashicons dashicons-admin-home" aria-hidden="true"></span> 
					<?php esc_html_e( 'Room Information', 'awesome-hotel-booking' ); ?>
				</div>
				<div class="data-row">
					<span class="data-label"><?php esc_html_e( 'Assigned Room Number', 'awesome-hotel-booking' ); ?></span>
					<span class="data-value" style="color:#c5a880; font-size:16px; font-weight:700;">
						<?php echo esc_html( $room_number ); ?>
					</span>
				</div>
				<div class="data-row">
					<span class="data-label"><?php esc_html_e( 'Suite Name', 'awesome-hotel-booking' ); ?></span>
					<span class="data-value" style="font-weight:700; color:#0a192f;"><?php echo esc_html( $room_title ); ?></span>
				</div>
				<div class="data-row">
					<span class="data-label"><?php esc_html_e( 'Room Category', 'awesome-hotel-booking' ); ?></span>
					<span class="data-value"><?php echo esc_html( $room_type ); ?></span>
				</div>
				<div class="data-row">
					<span class="data-label"><?php esc_html_e( 'Nightly Rate', 'awesome-hotel-booking' ); ?></span>
					<span class="data-value">
						<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $nightly_rate ) : (string) number_format_i18n( $nightly_rate, 2 ) ); ?>
					</span>
				</div>
			</div>

			<div class="ahbn-card">
				<div class="card-header">
					<span class="dashicons dashicons-admin-users" aria-hidden="true"></span> 
					<?php esc_html_e( 'Guest Particulars', 'awesome-hotel-booking' ); ?>
				</div>
				<div class="data-row">
					<span class="data-label"><?php esc_html_e( 'Guest Name', 'awesome-hotel-booking' ); ?></span>
					<span class="data-value" style="font-weight:700; color:#0a192f;">
						<?php echo esc_html( ! empty( $booking->guest_name ) ? $booking->guest_name : __( 'Guest User', 'awesome-hotel-booking' ) ); ?>
					</span>
				</div>
				<div class="data-row">
					<span class="data-label"><?php esc_html_e( 'Email Address', 'awesome-hotel-booking' ); ?></span>
					<span class="data-value"><?php echo esc_html( ! empty( $booking->guest_email ) ? $booking->guest_email : __( 'Not provided', 'awesome-hotel-booking' ) ); ?></span>
				</div>
				<div class="data-row">
					<span class="data-label"><?php esc_html_e( 'Phone Number', 'awesome-hotel-booking' ); ?></span>
					<span class="data-value"><?php echo esc_html( ! empty( $booking->guest_phone ) ? $booking->guest_phone : __( 'Not provided', 'awesome-hotel-booking' ) ); ?></span>
				</div>
			</div>

			<div class="ahbn-card">
				<div class="card-header">
					<span class="dashicons dashicons-cart" aria-hidden="true"></span> 
					<?php esc_html_e( 'Payment & Settlement Summary', 'awesome-hotel-booking' ); ?>
				</div>
				<div class="data-row">
					<span class="data-label"><?php esc_html_e( 'Payment Status', 'awesome-hotel-booking' ); ?></span>
					<span class="data-value" style="font-weight:700; color:<?php echo 'paid' === $booking->payment_status ? '#10b981' : '#d97706'; ?>;">
						<?php echo 'paid' === $booking->payment_status ? '&#10003; ' . esc_html__( 'SETTLED', 'awesome-hotel-booking' ) : '&#9679; ' . esc_html__( 'UNPAID', 'awesome-hotel-booking' ); ?>
					</span>
				</div>
				<div class="data-row">
					<span class="data-label"><?php esc_html_e( 'Stay Status', 'awesome-hotel-booking' ); ?></span>
					<span class="data-value" style="font-weight:700; text-transform:uppercase;">
						<?php echo esc_html( str_replace( '_', ' ', (string) $booking->status ) ); ?>
					</span>
				</div>

				<?php if ( ! empty( $booking->notes ) ) : ?>
					<div class="data-row" style="flex-direction:column; align-items:flex-start; gap:4px;">
						<span class="data-label"><?php esc_html_e( 'Concierge Notes', 'awesome-hotel-booking' ); ?></span>
						<p style="margin:0; font-size:13px; color:#475569; background:#f8fafc; padding:10px 14px; border-radius:6px; width:100%; border:1px solid #e2e8f0;">
							<?php echo esc_html( $booking->notes ); ?>
						</p>
					</div>
				<?php endif; ?>

				<div class="total-footer">
					<div style="display:flex; justify-content:space-between; align-items:flex-end;">
						<div>
							<div class="pricing-math">
								<?php
								$rate_str = function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $nightly_rate ) : (string) $nightly_rate;
								printf(
									/* translators: 1: nightly rate, 2: number of nights */
									esc_html__( '%1$s × %2$d Nights', 'awesome-hotel-booking' ),
									esc_html( $rate_str ),
									absint( $nights )
								);
								?>
							</div>
							<span class="data-label"><?php esc_html_e( 'Total Amount', 'awesome-hotel-booking' ); ?></span>
						</div>
						<span class="price-large">
							<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $total_amount ) : (string) number_format_i18n( $total_amount, 2 ) ); ?>
						</span>
					</div>
				</div>
			</div>
		</div>

		<div class="ahbn-sidebar">
			<div class="ahbn-card">
				<div class="card-header">
					<span class="dashicons dashicons-admin-home" aria-hidden="true"></span> 
					<?php esc_html_e( 'Room Preview', 'awesome-hotel-booking' ); ?>
				</div>
				<div class="room-preview">
					<?php if ( ! empty( $room_thumb_url ) ) : ?>
						<img src="<?php echo esc_url( $room_thumb_url ); ?>" class="room-img" alt="<?php echo esc_attr( $room_title ); ?>" style="width:100%; height:140px; object-fit:cover; border-radius:8px; margin-bottom:12px;">
					<?php else : ?>
						<div style="height:120px; background:#f3f4f6; border-radius:8px; display:flex; align-items:center; justify-content:center; margin-bottom:15px;">
							<span class="dashicons dashicons-admin-home" style="font-size:40px; width:40px; height:40px; color:#d1d5db;" aria-hidden="true"></span>
						</div>
					<?php endif; ?>
					<div style="font-weight:800; font-size:18px; color:#0a192f;"><?php echo esc_html( $room_title ); ?></div>
				</div>
			</div>

			<div class="ahbn-card" style="padding:15px;">
				<?php if ( ! empty( $booking->guest_email ) ) : ?>
					<a href="<?php echo esc_url( 'mailto:' . sanitize_email( $booking->guest_email ) ); ?>" class="button button-secondary" style="width:100%; text-align:center; margin-bottom:10px; display:block;">
						<span class="dashicons dashicons-email" style="vertical-align:middle; margin-right:4px;"></span>
						<?php esc_html_e( 'Email Guest', 'awesome-hotel-booking' ); ?>
					</a>
				<?php endif; ?>

				<?php if ( ! empty( $clean_whatsapp ) ) : ?>
					<a href="<?php echo esc_url( 'https://wa.me/' . $clean_whatsapp ); ?>" target="_blank" rel="noopener noreferrer" class="button" style="width:100%; text-align:center; background:#25d366; color:white; border:none; display:block;">
						<span class="dashicons dashicons-format-chat" style="vertical-align:middle; margin-right:4px;"></span>
						<?php esc_html_e( 'WhatsApp Guest', 'awesome-hotel-booking' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<style>
	.ahbn-view-container { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
	.ahbn-toolbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:10px; }
	.btn-back { display:inline-flex; align-items:center; text-decoration:none; font-weight:700; color:#334155; }
	.ahbn-view-grid { display:grid; grid-template-columns: 2fr 1fr; gap:20px; align-items:start; }
	.ahbn-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; margin-bottom:20px; overflow:hidden; box-shadow:0 1px 3px rgba(15,23,42,0.04); }
	.card-header { padding:14px 20px; background:#fafafa; border-bottom:1px solid #f1f5f9; font-weight:800; font-size:12px; text-transform:uppercase; color:#0a192f; display:flex; align-items:center; gap:8px; }
	.card-header .dashicons { color:#c5a880; font-size:16px; width:16px; height:16px; }
	.data-row { padding:14px 20px; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:center; font-size:13px; }
	.data-row:last-child { border-bottom:none; }
	.data-label { color:#64748b; font-weight:600; }
	.data-value { color:#0f172a; }
	.stay-banner { display:flex; justify-content:space-around; align-items:center; padding:24px; text-align:center; }
	.stay-date-box h4 { margin:0 0 4px; font-size:11px; text-transform:uppercase; color:#64748b; }
	.stay-date-box div { font-size:16px; font-weight:800; color:#0a192f; }
	.stay-duration { font-family:'JetBrains Mono',monospace; font-size:12px; font-weight:700; background:#f8fafc; border:1px solid #e2e8f0; padding:6px 14px; border-radius:99px; color:#0a192f; }
	.total-footer { padding:20px; background:#f8fafc; border-top:1px solid #e2e8f0; }
	.pricing-math { font-size:11px; color:#64748b; margin-bottom:2px; }
	.price-large { font-family:'JetBrains Mono',monospace; font-size:22px; font-weight:800; color:#0a192f; }
	.room-preview { padding:20px; }
	@media (max-width: 1024px) { .ahbn-view-grid { grid-template-columns:1fr; } }
	@media print {
		.ahbn-toolbar, .ahbn-sidebar { display:none !important; }
		.ahbn-view-grid { grid-template-columns:1fr !important; }
	}
</style>