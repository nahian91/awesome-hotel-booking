<?php
/**
 * [ahbn_thanks] & [ahbn_thanks_details] Booking Confirmation & Voucher View.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the booking confirmation receipt shortcode.
 *
 * @return string Rendered HTML.
 */
function ahbn_thanks_details_shortcode_callback() {
	// 1. Resolve Booking ID from query parameters (supports both booking_id and id).
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$booking_id = 0;
	if ( isset( $_GET['booking_id'] ) ) {
		$booking_id = absint( wp_unslash( $_GET['booking_id'] ) );
	} elseif ( isset( $_GET['id'] ) ) {
		$booking_id = absint( wp_unslash( $_GET['id'] ) );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	// 2. Validate Reservation Record.
	$booking = $booking_id > 0 ? get_post( $booking_id ) : null;

	if ( ! $booking || 'ahbn_booking' !== $booking->post_type ) {
		return "<div class='ahbn-empty-state' style='text-align:center; padding:100px 20px; font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;'>
					<span class='dashicons dashicons-calendar-alt' style='font-size:48px; width:48px; height:48px; color:#cbd5e1;' aria-hidden='true'></span>
					<h2 style='color:#0A1A38; margin:16px 0 8px; font-size:24px; font-weight:800;'>" . esc_html__( 'No Active Reservation Found', 'awesome-hotel-booking' ) . "</h2>
					<p style='color:#64748b; font-size:15px; margin:0 0 20px;'>" . esc_html__( 'We could not locate a record for this reservation session.', 'awesome-hotel-booking' ) . "</p>
					<a href='" . esc_url( home_url( '/rooms/' ) ) . "' style='display:inline-block; background:#0A1A38; color:#fff; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:700;'>" . esc_html__( 'Return to Suites', 'awesome-hotel-booking' ) . '</a>
				</div>';
	}

	// Guest Isolation Security: Guest can only view their own booking unless they are an admin.
	if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) {
		$booking_author = (int) $booking->post_author;
		$current_uid    = get_current_user_id();
		$booking_email  = (string) get_post_meta( $booking_id, 'ahbn_book_email', true );
		$current_email  = wp_get_current_user()->user_email;

		if ( $booking_author > 0 && $booking_author !== $current_uid && $booking_email !== $current_email ) {
			return "<div class='ahbn-empty-state' style='text-align:center; padding:100px 20px; font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;'>
						<h2 style='color:#ef4444; font-size:24px; font-weight:800;'>" . esc_html__( 'Unauthorized Access', 'awesome-hotel-booking' ) . "</h2>
						<p style='color:#64748b; font-size:15px;'>" . esc_html__( 'You do not have permission to view this reservation voucher.', 'awesome-hotel-booking' ) . "</p>
					</div>";
		}
	}

	// 3. Retrieve Reservation Meta.
	$customer    = (string) get_post_meta( $booking_id, 'ahbn_book_customer', true );
	$customer    = ! empty( $customer ) ? $customer : __( 'Valued Guest', 'awesome-hotel-booking' );
	$checkin     = (string) get_post_meta( $booking_id, 'ahbn_book_checkin', true );
	$checkout    = (string) get_post_meta( $booking_id, 'ahbn_book_checkout', true );
	$guests      = (string) get_post_meta( $booking_id, 'ahbn_book_guests', true );
	$guests_val  = ! empty( $guests ) ? $guests : __( '1 Adult', 'awesome-hotel-booking' );
	$raw_total   = get_post_meta( $booking_id, 'ahbn_book_total', true );
	$total_clean = (float) preg_replace( '/[^\d.]/', '', (string) $raw_total );
	$room_id     = absint( get_post_meta( $booking_id, 'ahbn_book_room_id', true ) );
	$room_name   = $room_id ? get_the_title( $room_id ) : __( 'Luxury Suite', 'awesome-hotel-booking' );
	$status      = (string) get_post_meta( $booking_id, 'status', true );
	$status_lbl  = ! empty( $status ) ? ucfirst( sanitize_key( $status ) ) : __( 'Pending Verification', 'awesome-hotel-booking' );

	ob_start();
	?>
	<style>
		.ahbn-thanks-wrapper {
			font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
			min-height: 80vh;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 40px 20px;
		}

		.ahbn-thanks-card {
			max-width: 560px;
			width: 100%;
			background: #ffffff;
			border-radius: 28px;
			padding: 45px;
			text-align: center;
			box-shadow: 0 30px 70px rgba(15, 23, 42, 0.08);
			border: 1px solid #e2e8f0;
			position: relative;
			overflow: hidden;
		}

		.ahbn-icon-box {
			position: relative;
			width: 90px;
			height: 90px;
			margin: 0 auto 24px;
		}

		.ahbn-pulse {
			position: absolute;
			inset: 0;
			background: #fef9c3;
			border-radius: 50%;
			animation: ahbn-pulse-animation 2s infinite;
		}

		.ahbn-icon-main {
			position: relative;
			z-index: 2;
			width: 100%;
			height: 100%;
			background: #fde68a;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			box-shadow: 0 10px 20px rgba(202, 138, 4, 0.15);
		}

		@keyframes ahbn-pulse-animation {
			0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(254, 249, 195, 0.7); }
			70% { transform: scale(1.05); box-shadow: 0 0 0 18px rgba(254, 249, 195, 0); }
			100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(254, 249, 195, 0); }
		}

		.ahbn-status-badge {
			background: #fffbeb;
			color: #92400e;
			padding: 6px 16px;
			border-radius: 100px;
			font-size: 12px;
			font-weight: 700;
			letter-spacing: 0.5px;
			text-transform: uppercase;
			border: 1px solid #fde68a;
			display: inline-block;
			margin-bottom: 16px;
		}

		.ahbn-thanks-card h2 {
			font-size: 30px;
			font-weight: 800;
			color: #0f172a;
			margin: 0 0 12px;
			letter-spacing: -0.5px;
		}

		.ahbn-thanks-card p.ahbn-lead-text {
			color: #64748b;
			font-size: 15px;
			line-height: 1.6;
			margin: 0 0 30px;
		}

		.ahbn-summary-grid {
			background: #f8fafc;
			border-radius: 18px;
			padding: 24px;
			text-align: left;
			border: 1px solid #f1f5f9;
		}

		.ahbn-summary-item {
			display: flex;
			justify-content: space-between;
			margin-bottom: 12px;
			font-size: 14px;
		}

		.ahbn-label { color: #64748b; font-weight: 500; }
		.ahbn-value { color: #0f172a; font-weight: 700; }

		.ahbn-total-row {
			margin-top: 16px;
			padding-top: 16px;
			border-top: 2px dashed #e2e8f0;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.ahbn-total-label { font-size: 16px; font-weight: 800; color: #0f172a; }
		.ahbn-total-value { font-size: 22px; font-weight: 900; color: #bf9441; }

		.ahbn-actions {
			margin-top: 30px;
			display: grid;
			grid-template-columns: 1fr;
			gap: 12px;
		}

		.ahbn-btn-primary {
			background: #0f172a;
			color: #ffffff !important;
			padding: 16px;
			border-radius: 12px;
			font-weight: 700;
			text-decoration: none;
			transition: all 0.3s ease;
			display: block;
		}

		.ahbn-btn-primary:hover {
			background: #1e293b;
			transform: translateY(-1px);
		}

		.ahbn-btn-print {
			background: transparent;
			border: none;
			color: #64748b;
			font-weight: 600;
			font-size: 14px;
			cursor: pointer;
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 6px;
			padding: 8px;
		}

		.ahbn-btn-print:hover { color: #0f172a; }

		@media print {
			.ahbn-actions, .ahbn-pulse { display: none !important; }
			.ahbn-thanks-wrapper { background: white; padding: 0; min-height: auto; }
			.ahbn-thanks-card { box-shadow: none; border: 1px solid #ddd; padding: 20px; }
		}
	</style>

	<div class="ahbn-thanks-wrapper">
		<div class="ahbn-thanks-card">
			<div class="ahbn-icon-box">
				<div class="ahbn-pulse"></div>
				<div class="ahbn-icon-main">
					<svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="#ca8a04" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
						<circle cx="12" cy="12" r="10"></circle>
						<polyline points="12 6 12 12 16 14"></polyline>
					</svg>
				</div>
			</div>

			<div class="ahbn-status-badge"><?php echo esc_html( $status_lbl ); ?></div>
			
			<h2><?php esc_html_e( 'Reservation Received', 'awesome-hotel-booking' ); ?></h2>
			
			<p class="ahbn-lead-text">
				<?php
				printf(
					/* translators: 1: Customer Name, 2: Room/Suite Name */
					esc_html__( 'Hello %1$s, your reservation for %2$s has been recorded. Our front desk team is reviewing your stay details.', 'awesome-hotel-booking' ),
					'<strong>' . esc_html( $customer ) . '</strong>',
					'<strong>' . esc_html( $room_name ) . '</strong>'
				);
				?>
			</p>
			
			<div class="ahbn-summary-grid">
				<div class="ahbn-summary-item">
					<span class="ahbn-label"><?php esc_html_e( 'Reservation ID', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-value">#<?php echo esc_html( (string) $booking_id ); ?></span>
				</div>
				<div class="ahbn-summary-item">
					<span class="ahbn-label"><?php esc_html_e( 'Selected Suite', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-value"><?php echo esc_html( $room_name ); ?></span>
				</div>
				<div class="ahbn-summary-item">
					<span class="ahbn-label"><?php esc_html_e( 'Stay Period', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-value">
						<?php echo esc_html( ( ! empty( $checkin ) ? $checkin : '—' ) . ' — ' . ( ! empty( $checkout ) ? $checkout : '—' ) ); ?>
					</span>
				</div>
				<div class="ahbn-summary-item">
					<span class="ahbn-label"><?php esc_html_e( 'Guests', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-value"><?php echo esc_html( $guests_val ); ?></span>
				</div>
				
				<div class="ahbn-total-row">
					<span class="ahbn-total-label"><?php esc_html_e( 'Total Amount', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-total-value">
						<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $total_clean ) : (string) number_format_i18n( $total_clean, 2 ) ); ?>
					</span>
				</div>
			</div>

			<div class="ahbn-actions">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="ahbn-btn-primary">
					<?php esc_html_e( 'Return to Homepage', 'awesome-hotel-booking' ); ?>
				</a>
				<button type="button" onclick="window.print();" class="ahbn-btn-print">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
					<?php esc_html_e( 'Print Reservation Voucher', 'awesome-hotel-booking' ); ?>
				</button>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ahbn_thanks_details', 'ahbn_thanks_details_shortcode_callback' );
add_shortcode( 'ahbn_thanks', 'ahbn_thanks_details_shortcode_callback' );