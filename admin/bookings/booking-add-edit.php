<?php
/**
 * Add / Edit Booking Form View.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$table_bookings = $wpdb->prefix . 'ahbn_bookings';
$table_rooms    = $wpdb->prefix . 'ahbn_rooms';

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$current_action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
$ahbn_bid       = 0;

if ( 'edit' === $current_action ) {
	if ( isset( $booking_id ) && absint( $booking_id ) > 0 ) {
		$ahbn_bid = absint( $booking_id );
	} elseif ( isset( $_GET['booking_id'] ) ) {
		$ahbn_bid = absint( wp_unslash( $_GET['booking_id'] ) );
	}
}
// phpcs:enable WordPress.Security.NonceVerification.Recommended

// 1. Process Form POST Submission (Save / Update).
if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_booking_nonce'] ) ) {
	$nonce = sanitize_key( wp_unslash( $_POST['ahbn_save_booking_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'ahbn_save_booking_action' ) ) {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please try again.', 'awesome-hotel-booking' ) . '</p></div>';
	} else {
		$target_id      = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		$room_id        = isset( $_POST['room_id'] ) ? absint( wp_unslash( $_POST['room_id'] ) ) : 0;
		$guest_name     = isset( $_POST['guest_name'] ) ? sanitize_text_field( wp_unslash( $_POST['guest_name'] ) ) : '';
		$guest_email    = isset( $_POST['guest_email'] ) ? sanitize_email( wp_unslash( $_POST['guest_email'] ) ) : '';
		$guest_phone    = isset( $_POST['guest_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['guest_phone'] ) ) : '';
		$checkin_date   = isset( $_POST['checkin_date'] ) ? sanitize_text_field( wp_unslash( $_POST['checkin_date'] ) ) : '';
		$checkout_date  = isset( $_POST['checkout_date'] ) ? sanitize_text_field( wp_unslash( $_POST['checkout_date'] ) ) : '';
		$adults         = isset( $_POST['adults'] ) ? max( 1, absint( wp_unslash( $_POST['adults'] ) ) ) : 1;
		$children       = isset( $_POST['children'] ) ? absint( wp_unslash( $_POST['children'] ) ) : 0;
		$total_amount   = isset( $_POST['total_amount'] ) ? (float) wp_unslash( $_POST['total_amount'] ) : 0.00;
		$payment_status = isset( $_POST['payment_status'] ) ? sanitize_key( wp_unslash( $_POST['payment_status'] ) ) : 'unpaid';
		$status         = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'pending';
		$notes          = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

		$nights = 1;
		if ( ! empty( $checkin_date ) && ! empty( $checkout_date ) ) {
			$nights = max( 1, (int) round( ( strtotime( $checkout_date ) - strtotime( $checkin_date ) ) / DAY_IN_SECONDS ) );
		}

		$data = array(
			'room_id'        => $room_id,
			'guest_name'     => $guest_name,
			'guest_email'    => $guest_email,
			'guest_phone'    => $guest_phone,
			'checkin_date'   => $checkin_date,
			'checkout_date'  => $checkout_date,
			'nights'         => $nights,
			'adults'         => $adults,
			'children'       => $children,
			'total_amount'   => $total_amount,
			'payment_status' => $payment_status,
			'status'         => $status,
			'notes'          => $notes,
		);

		$format = array( '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%f', '%s', '%s', '%s' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( $target_id > 0 ) {
			$wpdb->update( $table_bookings, $data, array( 'id' => $target_id ), $format, array( '%d' ) );
			do_action( 'ahbn_after_booking_saved', $target_id );
			$ahbn_bid = $target_id;
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . sprintf( esc_html__( 'Reservation #%d updated.', 'awesome-hotel-booking' ), absint( $target_id ) ) . '</p></div>';
		} else {
			$data['booking_code'] = 'BK-' . strtoupper( wp_generate_password( 8, false ) );
			$format[]             = '%s';
			$wpdb->insert( $table_bookings, $data, $format );
			$ahbn_bid = (int) $wpdb->insert_id;
			do_action( 'ahbn_after_booking_saved', $ahbn_bid );
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'New reservation created successfully.', 'awesome-hotel-booking' ) . '</p></div>';
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}

// 2. Fetch record if editing existing booking.
$booking = null;
if ( $ahbn_bid > 0 ) {
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$booking = $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$table_bookings} WHERE id = %d", $ahbn_bid )
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
}

// 3. Fetch published rooms for the assignment dropdown.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$rooms = $wpdb->get_results(
	"SELECT id, room_number, title, room_type, price_per_night FROM {$table_rooms} WHERE status = 'publish' ORDER BY room_number ASC"
);
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

$list_url = admin_url( 'admin.php?page=awesome_hotel_booking&tab=bookings' );
?>

<div class="ahbn-saas-edit">
	<div class="ahbn-header-bar">
		<div>
			<h1 class="ahbn-header-title">
				<?php
				if ( $booking ) {
					/* translators: %s: booking reference code */
					printf( esc_html__( 'Edit Reservation (%s)', 'awesome-hotel-booking' ), esc_html( $booking->booking_code ) );
				} else {
					esc_html_e( 'Create New Reservation', 'awesome-hotel-booking' );
				}
				?>
			</h1>
			<p style="color:#64748b; margin:0; font-size:13px;">
				<?php esc_html_e( 'Configure room assignments, guest contact records, stay duration, and folios.', 'awesome-hotel-booking' ); ?>
			</p>
		</div>
		<a href="<?php echo esc_url( $list_url ); ?>" class="ahbn-back-link">
			<span class="dashicons dashicons-arrow-left-alt" aria-hidden="true"></span>
			<?php esc_html_e( 'Back to Directory', 'awesome-hotel-booking' ); ?>
		</a>
	</div>

	<form method="post" action="">
		<?php wp_nonce_field( 'ahbn_save_booking_action', 'ahbn_save_booking_nonce' ); ?>
		<input type="hidden" name="id" value="<?php echo esc_attr( (string) ( $booking ? $booking->id : 0 ) ); ?>">

		<div class="ahbn-grid-layout">
			<div class="ahbn-main-col">
				
				<!-- Stay & Room Settings -->
				<div class="ahbn-panel">
					<div class="ahbn-panel-head">
						<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
						<h2><?php esc_html_e( 'Stay & Inventory Allocation', 'awesome-hotel-booking' ); ?></h2>
					</div>
					<div class="ahbn-panel-body">
						<div class="ahbn-row">
							<div class="ahbn-group">
								<label for="checkin_date" class="ahbn-label"><?php esc_html_e( 'Check-in Date *', 'awesome-hotel-booking' ); ?></label>
								<input type="date" id="checkin_date" name="checkin_date" value="<?php echo esc_attr( $booking ? $booking->checkin_date : current_time( 'Y-m-d' ) ); ?>" class="ahbn-field" required>
							</div>
							<div class="ahbn-group">
								<label for="checkout_date" class="ahbn-label"><?php esc_html_e( 'Check-out Date *', 'awesome-hotel-booking' ); ?></label>
								<input type="date" id="checkout_date" name="checkout_date" value="<?php echo esc_attr( $booking ? $booking->checkout_date : wp_date( 'Y-m-d', strtotime( '+1 day', current_time( 'timestamp' ) ) ) ); ?>" class="ahbn-field" required>
							</div>
						</div>

						<div class="ahbn-group">
							<label for="room_id" class="ahbn-label"><?php esc_html_e( 'Assigned Suite / Room *', 'awesome-hotel-booking' ); ?></label>
							<select id="room_id" name="room_id" class="ahbn-field" required>
								<option value=""><?php esc_html_e( '— Select Room —', 'awesome-hotel-booking' ); ?></option>
								<?php if ( ! empty( $rooms ) ) : ?>
									<?php foreach ( $rooms as $rm ) : ?>
										<option value="<?php echo esc_attr( (string) $rm->id ); ?>" <?php selected( $booking ? $booking->room_id : 0, $rm->id ); ?>>
											#<?php echo esc_html( $rm->room_number . ' - ' . $rm->title . ' (' . $rm->room_type . ')' ); ?>
										</option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</div>

						<div class="ahbn-row">
							<div class="ahbn-group">
								<label for="adults" class="ahbn-label"><?php esc_html_e( 'Adult Guests', 'awesome-hotel-booking' ); ?></label>
								<input type="number" min="1" max="20" id="adults" name="adults" value="<?php echo esc_attr( (string) ( $booking ? $booking->adults : 1 ) ); ?>" class="ahbn-field">
							</div>
							<div class="ahbn-group">
								<label for="children" class="ahbn-label"><?php esc_html_e( 'Child Guests', 'awesome-hotel-booking' ); ?></label>
								<input type="number" min="0" max="20" id="children" name="children" value="<?php echo esc_attr( (string) ( $booking ? $booking->children : 0 ) ); ?>" class="ahbn-field">
							</div>
						</div>
					</div>
				</div>

				<!-- Guest Particulars -->
				<div class="ahbn-panel">
					<div class="ahbn-panel-head">
						<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
						<h2><?php esc_html_e( 'Guest Particulars', 'awesome-hotel-booking' ); ?></h2>
					</div>
					<div class="ahbn-panel-body">
						<div class="ahbn-group">
							<label for="guest_name" class="ahbn-label"><?php esc_html_e( 'Guest Full Name *', 'awesome-hotel-booking' ); ?></label>
							<input type="text" id="guest_name" name="guest_name" value="<?php echo esc_attr( $booking ? $booking->guest_name : '' ); ?>" class="ahbn-field" required placeholder="<?php esc_attr_e( 'e.g. John Doe', 'awesome-hotel-booking' ); ?>">
						</div>

						<div class="ahbn-row">
							<div class="ahbn-group">
								<label for="guest_email" class="ahbn-label"><?php esc_html_e( 'Email Address', 'awesome-hotel-booking' ); ?></label>
								<input type="email" id="guest_email" name="guest_email" value="<?php echo esc_attr( $booking ? $booking->guest_email : '' ); ?>" class="ahbn-field" placeholder="<?php esc_attr_e( 'guest@example.com', 'awesome-hotel-booking' ); ?>">
							</div>
							<div class="ahbn-group">
								<label for="guest_phone" class="ahbn-label"><?php esc_html_e( 'Phone Number', 'awesome-hotel-booking' ); ?></label>
								<input type="tel" id="guest_phone" name="guest_phone" value="<?php echo esc_attr( $booking ? $booking->guest_phone : '' ); ?>" class="ahbn-field" placeholder="<?php esc_attr_e( '+1 (555) 019-2834', 'awesome-hotel-booking' ); ?>">
							</div>
						</div>
					</div>
				</div>

				<!-- Concierge Notes -->
				<div class="ahbn-panel">
					<div class="ahbn-panel-head">
						<span class="dashicons dashicons-edit" aria-hidden="true"></span>
						<h2><?php esc_html_e( 'Internal Concierge Notes', 'awesome-hotel-booking' ); ?></h2>
					</div>
					<div class="ahbn-panel-body">
						<textarea id="notes" name="notes" class="ahbn-field" rows="3" placeholder="<?php esc_attr_e( 'Special requests, arrival notes, dietary requests...', 'awesome-hotel-booking' ); ?>"><?php echo esc_textarea( $booking ? $booking->notes : '' ); ?></textarea>
					</div>
				</div>
			</div>

			<!-- Sidebar Actions & Billing -->
			<div class="ahbn-sidebar-col">
				<div class="ahbn-panel">
					<div class="ahbn-panel-head">
						<span class="dashicons dashicons-money-alt" aria-hidden="true"></span>
						<h2><?php esc_html_e( 'Billing & Status', 'awesome-hotel-booking' ); ?></h2>
					</div>
					<div class="ahbn-panel-body">
						<div class="ahbn-group">
							<label for="total_amount" class="ahbn-label"><?php esc_html_e( 'Total Folio Amount', 'awesome-hotel-booking' ); ?></label>
							<input type="number" step="0.01" min="0" id="total_amount" name="total_amount" value="<?php echo esc_attr( (string) ( $booking ? $booking->total_amount : '0.00' ) ); ?>" class="ahbn-field" style="font-family:'JetBrains Mono', monospace; font-weight:700; font-size:15px;">
						</div>

						<div class="ahbn-group">
							<label for="payment_status" class="ahbn-label"><?php esc_html_e( 'Payment Status', 'awesome-hotel-booking' ); ?></label>
							<select id="payment_status" name="payment_status" class="ahbn-field">
								<option value="unpaid" <?php selected( $booking ? $booking->payment_status : 'unpaid', 'unpaid' ); ?>><?php esc_html_e( 'Unpaid / Pay on Arrival', 'awesome-hotel-booking' ); ?></option>
								<option value="paid" <?php selected( $booking ? $booking->payment_status : '', 'paid' ); ?>><?php esc_html_e( 'Settled / Paid in Full', 'awesome-hotel-booking' ); ?></option>
							</select>
						</div>

						<div class="ahbn-group">
							<label for="status" class="ahbn-label"><?php esc_html_e( 'Stay Status', 'awesome-hotel-booking' ); ?></label>
							<select id="status" name="status" class="ahbn-field">
								<option value="pending" <?php selected( $booking ? $booking->status : 'pending', 'pending' ); ?>><?php esc_html_e( 'Pending Approval', 'awesome-hotel-booking' ); ?></option>
								<option value="confirmed" <?php selected( $booking ? $booking->status : '', 'confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'awesome-hotel-booking' ); ?></option>
								<option value="checked_in" <?php selected( $booking ? $booking->status : '', 'checked_in' ); ?>><?php esc_html_e( 'In-House (Checked-In)', 'awesome-hotel-booking' ); ?></option>
								<option value="checked_out" <?php selected( $booking ? $booking->status : '', 'checked_out' ); ?>><?php esc_html_e( 'Checked Out', 'awesome-hotel-booking' ); ?></option>
								<option value="cancelled" <?php selected( $booking ? $booking->status : '', 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'awesome-hotel-booking' ); ?></option>
							</select>
						</div>
						
						<button type="submit" class="ahbn-btn-save">
							<?php echo $booking ? esc_html__( 'Update Reservation', 'awesome-hotel-booking' ) : esc_html__( 'Create Reservation', 'awesome-hotel-booking' ); ?>
						</button>
					</div>
				</div>
			</div>
		</div>
	</form>
</div>

<style>
	.ahbn-saas-edit { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
	.ahbn-header-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; flex-wrap: wrap; gap: 15px; }
	.ahbn-header-title { font-family: 'Playfair Display', Georgia, serif; font-size: 24px; font-weight: 700; color: #0a192f; margin: 0 0 4px 0; }
	.ahbn-back-link { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; color: #334155; font-size: 13px; font-weight: 600; text-decoration: none; transition: all 0.2s ease; }
	.ahbn-back-link:hover { border-color: #0a192f; color: #0a192f; background: #f8fafc; }
	.ahbn-grid-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: start; }
	.ahbn-panel { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 20px; overflow: hidden; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04); }
	.ahbn-panel-head { padding: 16px 20px; background: #fafafa; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 8px; }
	.ahbn-panel-head h2 { margin: 0; font-size: 14px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #0a192f; }
	.ahbn-panel-head .dashicons { color: #c5a880; font-size: 18px; width: 18px; height: 18px; }
	.ahbn-panel-body { padding: 20px; }
	.ahbn-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
	.ahbn-group { margin-bottom: 16px; }
	.ahbn-group:last-child { margin-bottom: 0; }
	.ahbn-label { display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.03em; }
	.ahbn-field { width: 100%; border: 1px solid #e2e8f0 !important; border-radius: 8px !important; padding: 8px 12px !important; font-size: 13px !important; background: #ffffff !important; box-shadow: none !important; outline: none !important; }
	.ahbn-field:focus { border-color: #0a192f !important; box-shadow: 0 0 0 3px rgba(10, 25, 47, 0.08) !important; }
	.ahbn-btn-save { width: 100%; height: 42px; background: #0a192f; color: #ffffff; border: none; border-radius: 8px; font-weight: 700; font-size: 13px; margin-top: 14px; cursor: pointer; transition: background 0.2s ease; }
	.ahbn-btn-save:hover { background: #c5a880; color: #0a192f; }
	@media (max-width: 1024px) {
		.ahbn-grid-layout { grid-template-columns: 1fr; }
		.ahbn-row { grid-template-columns: 1fr; }
	}
</style>