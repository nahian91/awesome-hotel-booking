<?php
/**
 * Standalone Print Voucher / Guest Invoice Folio.
 *
 * Built directly on custom database tables:
 * {$wpdb->prefix}ahbn_bookings & {$wpdb->prefix}ahbn_rooms.
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
$ahbn_booking_id = isset( $booking_id ) ? absint( $booking_id ) : 0;
if ( ! $ahbn_booking_id && isset( $_GET['booking_id'] ) ) {
	$ahbn_booking_id = absint( wp_unslash( $_GET['booking_id'] ) );
}
// phpcs:enable WordPress.Security.NonceVerification.Recommended

if ( ! $ahbn_booking_id || ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Unauthorized access or invalid reservation ID.', 'awesome-hotel-booking' ) );
}

// Fetch reservation joined with room specs.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$booking = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT 
			b.*,
			r.room_number,
			r.title AS room_title,
			r.room_type,
			r.price_per_night
		 FROM {$table_bookings} b
		 LEFT JOIN {$table_rooms} r ON b.room_id = r.id
		 WHERE b.id = %d",
		$ahbn_booking_id
	)
);
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

if ( ! $booking ) {
	wp_die( esc_html__( 'Reservation record not found.', 'awesome-hotel-booking' ) );
}

// Property Branding Options.
$hotel_name    = get_option( 'ahbn_hotel_name', get_bloginfo( 'name' ) );
$hotel_address = get_option( 'ahbn_hotel_address', '' );
$hotel_phone   = get_option( 'ahbn_hotel_phone', '' );
$hotel_email   = get_option( 'ahbn_hotel_email', get_bloginfo( 'admin_email' ) );

// Reservation Data.
$folio_code     = ! empty( $booking->booking_code ) ? $booking->booking_code : '#' . $booking->id;
$guest_name     = ! empty( $booking->guest_name ) ? $booking->guest_name : __( 'Guest User', 'awesome-hotel-booking' );
$guest_phone    = ! empty( $booking->guest_phone ) ? $booking->guest_phone : __( 'N/A', 'awesome-hotel-booking' );
$guest_email    = ! empty( $booking->guest_email ) ? $booking->guest_email : __( 'N/A', 'awesome-hotel-booking' );
$checkin_date   = (string) $booking->checkin_date;
$checkout_date  = (string) $booking->checkout_date;
$adults         = max( 1, absint( $booking->adults ) );
$children       = absint( $booking->children );
$total_guests   = $adults + $children;
$room_number    = (string) $booking->room_number;
$room_title     = ! empty( $booking->room_title ) ? $booking->room_title : __( 'Standard Room', 'awesome-hotel-booking' );
$room_type      = ! empty( $booking->room_type ) ? $booking->room_type : __( 'Standard', 'awesome-hotel-booking' );
$total_amount   = (float) $booking->total_amount;
$payment_status = ! empty( $booking->payment_status ) ? sanitize_key( $booking->payment_status ) : 'unpaid';
$payment_method = ! empty( $booking->payment_method ) ? strtoupper( str_replace( '_', ' ', $booking->payment_method ) ) : __( 'CASH / DIRECT', 'awesome-hotel-booking' );
$notes          = (string) $booking->notes;

// Calculate Nights.
$nights = absint( $booking->nights );
if ( $nights < 1 && ! empty( $checkin_date ) && ! empty( $checkout_date ) ) {
	$nights = max( 1, (int) round( ( strtotime( $checkout_date ) - strtotime( $checkin_date ) ) / DAY_IN_SECONDS ) );
}

$nightly_rate = (float) $booking->room_rate > 0 ? (float) $booking->room_rate : ( (float) $booking->price_per_night > 0 ? (float) $booking->price_per_night : ( $nights > 0 ? ( $total_amount / $nights ) : $total_amount ) );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( sprintf( /* translators: %s: folio code */ __( 'Folio_%s', 'awesome-hotel-booking' ), $folio_code ) ); ?></title>
	
	<!-- Executive Typography -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@600;800&family=Playfair+Display:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

	<style>
		:root {
			--ahbn-navy: #0A192F;
			--ahbn-gold: #C5A880;
			--ahbn-gold-dark: #B39264;
			--ahbn-slate-50: #F8FAFC;
			--ahbn-slate-100: #F1F5F9;
			--ahbn-slate-200: #E2E8F0;
			--ahbn-slate-500: #64748B;
			--ahbn-slate-700: #334155;
			--ahbn-slate-900: #0F172A;
		}

		* {
			box-sizing: border-box;
		}

		body {
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			background: #EAEEF3;
			color: var(--ahbn-slate-900);
			margin: 0;
			padding: 40px 20px;
			-webkit-font-smoothing: antialiased;
		}

		/* Sticky Toolbar */
		.ahbn-print-toolbar {
			max-width: 840px;
			margin: 0 auto 20px;
			display: flex;
			align-items: center;
			justify-content: space-between;
		}

		.ahbn-btn-toolbar {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			padding: 10px 18px;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 700;
			text-decoration: none;
			cursor: pointer;
			border: none;
			transition: all 0.2s ease;
		}

		.ahbn-btn-back {
			background: #FFFFFF;
			color: var(--ahbn-slate-700);
			border: 1px solid var(--ahbn-slate-200);
		}

		.ahbn-btn-back:hover {
			background: var(--ahbn-slate-50);
			color: var(--ahbn-navy);
		}

		.ahbn-btn-print {
			background: var(--ahbn-navy);
			color: #FFFFFF;
			box-shadow: 0 4px 12px rgba(10, 25, 47, 0.2);
		}

		.ahbn-btn-print:hover {
			background: var(--ahbn-gold);
			color: var(--ahbn-navy);
		}

		/* Document Sheet */
		.ahbn-invoice-sheet {
			max-width: 840px;
			margin: 0 auto;
			background: #FFFFFF;
			border: 1px solid var(--ahbn-slate-200);
			border-radius: 16px;
			padding: 50px 60px;
			box-shadow: 0 10px 30px rgba(10, 25, 47, 0.08);
			position: relative;
		}

		/* Header Section */
		.ahbn-sheet-header {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			border-bottom: 2px solid var(--ahbn-slate-200);
			padding-bottom: 30px;
			margin-bottom: 35px;
		}

		.ahbn-hotel-brand h1 {
			margin: 0 0 6px 0;
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 28px;
			font-weight: 800;
			color: var(--ahbn-navy);
			letter-spacing: -0.02em;
		}

		.ahbn-hotel-meta {
			font-size: 12px;
			line-height: 1.6;
			color: var(--ahbn-slate-500);
		}

		.ahbn-folio-badge-wrap {
			text-align: right;
		}

		.ahbn-folio-label {
			display: block;
			font-size: 11px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.1em;
			color: var(--ahbn-gold-dark);
			margin-bottom: 4px;
		}

		.ahbn-folio-code {
			font-family: 'JetBrains Mono', monospace;
			font-size: 24px;
			font-weight: 800;
			color: var(--ahbn-navy);
			display: block;
			margin-bottom: 6px;
		}

		.ahbn-issued-date {
			font-size: 11px;
			color: var(--ahbn-slate-500);
		}

		/* 2-Column Info Grid */
		.ahbn-info-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 40px;
			background: var(--ahbn-slate-50);
			border: 1px solid var(--ahbn-slate-200);
			border-radius: 12px;
			padding: 24px 28px;
			margin-bottom: 35px;
		}

		.ahbn-section-label {
			display: block;
			font-size: 10px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.08em;
			color: var(--ahbn-slate-500);
			border-bottom: 1px solid var(--ahbn-slate-200);
			padding-bottom: 6px;
			margin-bottom: 12px;
		}

		.ahbn-guest-name {
			font-size: 16px;
			font-weight: 700;
			color: var(--ahbn-navy);
			margin: 0 0 6px 0;
		}

		.ahbn-detail-row {
			font-size: 13px;
			line-height: 1.6;
			color: var(--ahbn-slate-700);
		}

		.ahbn-detail-row b {
			color: var(--ahbn-slate-900);
		}

		/* Line Item Table */
		.ahbn-line-table {
			width: 100%;
			border-collapse: collapse;
			margin-bottom: 30px;
		}

		.ahbn-line-table th {
			background: var(--ahbn-slate-50);
			border-bottom: 2px solid var(--ahbn-slate-200);
			padding: 12px 16px;
			font-size: 11px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: var(--ahbn-slate-700);
			text-align: left;
		}

		.ahbn-line-table td {
			padding: 18px 16px;
			border-bottom: 1px solid var(--ahbn-slate-100);
			font-size: 13px;
			vertical-align: middle;
		}

		.ahbn-item-title {
			font-size: 14px;
			font-weight: 700;
			color: var(--ahbn-navy);
			display: block;
		}

		.ahbn-item-sub {
			font-size: 11px;
			color: var(--ahbn-slate-500);
			margin-top: 3px;
		}

		.ahbn-mono {
			font-family: 'JetBrains Mono', monospace;
			font-weight: 700;
		}

		/* Settlement Summary & Signatures */
		.ahbn-total-section {
			display: flex;
			justify-content: space-between;
			align-items: flex-start;
			gap: 30px;
			margin-top: 20px;
		}

		.ahbn-payment-badge-box {
			flex: 1;
			background: var(--ahbn-slate-50);
			border: 1px solid var(--ahbn-slate-200);
			border-radius: 10px;
			padding: 16px 20px;
		}

		.ahbn-total-box {
			background: var(--ahbn-navy);
			color: #FFFFFF;
			padding: 18px 32px;
			border-radius: 12px;
			text-align: right;
			min-width: 260px;
			box-shadow: 0 4px 14px rgba(10, 25, 47, 0.2);
		}

		.ahbn-total-box small {
			display: block;
			font-size: 10px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.1em;
			color: var(--ahbn-gold);
			margin-bottom: 4px;
		}

		.ahbn-total-box .grand-price {
			font-family: 'JetBrains Mono', monospace;
			font-size: 26px;
			font-weight: 800;
			color: #FFFFFF;
		}

		.ahbn-signature-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 80px;
			margin-top: 70px;
			padding-top: 20px;
		}

		.ahbn-sig-line {
			border-top: 1px solid var(--ahbn-slate-300);
			padding-top: 10px;
			text-align: center;
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: var(--ahbn-slate-500);
		}

		.ahbn-footer-note {
			text-align: center;
			margin-top: 50px;
			font-size: 11px;
			color: var(--ahbn-slate-400);
		}

		/* Print Optimization */
		@media print {
			body {
				background: #FFFFFF !important;
				padding: 0 !important;
			}
			.ahbn-print-toolbar {
				display: none !important;
			}
			.ahbn-invoice-sheet {
				border: none !important;
				box-shadow: none !important;
				padding: 0 !important;
				max-width: 100% !important;
			}
		}
	</style>
</head>
<body>

	<!-- Top Admin Actions -->
	<div class="ahbn-print-toolbar">
		<a href="javascript:window.history.back()" class="ahbn-btn-toolbar ahbn-btn-back">
			&larr; <?php esc_html_e( 'Back to Reservation', 'awesome-hotel-booking' ); ?>
		</a>
		<button type="button" class="ahbn-btn-toolbar ahbn-btn-print" onclick="window.print();">
			<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:2px;"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
			<?php esc_html_e( 'Print Guest Folio', 'awesome-hotel-booking' ); ?>
		</button>
	</div>

	<!-- Printable Sheet Canvas -->
	<div class="ahbn-invoice-sheet">

		<!-- Header -->
		<div class="ahbn-sheet-header">
			<div class="ahbn-hotel-brand">
				<h1><?php echo esc_html( $hotel_name ); ?></h1>
				<div class="ahbn-hotel-meta">
					<?php if ( ! empty( $hotel_address ) ) : ?>
						<?php echo esc_html( $hotel_address ); ?><br>
					<?php endif; ?>
					<?php if ( ! empty( $hotel_phone ) ) : ?>
						<?php esc_html_e( 'Tel:', 'awesome-hotel-booking' ); ?> <?php echo esc_html( $hotel_phone ); ?> &bull; 
					<?php endif; ?>
					<?php echo esc_html( $hotel_email ); ?>
				</div>
			</div>

			<div class="ahbn-folio-badge-wrap">
				<span class="ahbn-folio-label"><?php esc_html_e( 'Official Guest Folio', 'awesome-hotel-booking' ); ?></span>
				<span class="ahbn-folio-code"><?php echo esc_html( $folio_code ); ?></span>
				<span class="ahbn-issued-date">
					<?php
					printf(
						/* translators: %s: current date */
						esc_html__( 'Issued: %s', 'awesome-hotel-booking' ),
						esc_html( wp_date( get_option( 'date_format' ) ) )
					);
					?>
				</span>
			</div>
		</div>

		<!-- 2-Column Guest & Schedule Info -->
		<div class="ahbn-info-grid">
			<div>
				<span class="ahbn-section-label"><?php esc_html_e( 'Guest Particulars', 'awesome-hotel-booking' ); ?></span>
				<h3 class="ahbn-guest-name"><?php echo esc_html( $guest_name ); ?></h3>
				<div class="ahbn-detail-row">
					<b><?php esc_html_e( 'Phone:', 'awesome-hotel-booking' ); ?></b> <?php echo esc_html( $guest_phone ); ?><br>
					<b><?php esc_html_e( 'Email:', 'awesome-hotel-booking' ); ?></b> <?php echo esc_html( $guest_email ); ?><br>
					<b><?php esc_html_e( 'Total Guests:', 'awesome-hotel-booking' ); ?></b> 
					<?php
					printf(
						/* translators: 1: total guests, 2: adults, 3: children */
						esc_html__( '%1$d Person(s) (%2$d Adults, %3$d Children)', 'awesome-hotel-booking' ),
						absint( $total_guests ),
						absint( $adults ),
						absint( $children )
					);
					?>
				</div>
			</div>

			<div>
				<span class="ahbn-section-label"><?php esc_html_e( 'Stay Timeline & Room Allocation', 'awesome-hotel-booking' ); ?></span>
				<div class="ahbn-detail-row">
					<b><?php esc_html_e( 'Assigned Suite:', 'awesome-hotel-booking' ); ?></b> #<?php echo esc_html( ! empty( $room_number ) ? $room_number : '—' ); ?> <?php echo esc_html( $room_title ); ?><br>
					<b><?php esc_html_e( 'Room Category:', 'awesome-hotel-booking' ); ?></b> <?php echo esc_html( $room_type ); ?><br>
					<b><?php esc_html_e( 'Check-in:', 'awesome-hotel-booking' ); ?></b> <?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $checkin_date ) ) ); ?><br>
					<b><?php esc_html_e( 'Check-out:', 'awesome-hotel-booking' ); ?></b> <?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $checkout_date ) ) ); ?>
				</div>
			</div>
		</div>

		<!-- Billing Table -->
		<table class="ahbn-line-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Description & Accommodation', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Daily Rate', 'awesome-hotel-booking' ); ?></th>
					<th style="text-align:center;"><?php esc_html_e( 'Nights', 'awesome-hotel-booking' ); ?></th>
					<th style="text-align:right;"><?php esc_html_e( 'Amount', 'awesome-hotel-booking' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>
						<span class="ahbn-item-title"><?php echo esc_html( $room_title ); ?> (Suite #<?php echo esc_html( $room_number ); ?>)</span>
						<span class="ahbn-item-sub">
							<?php echo esc_html( wp_date( 'M j, Y', strtotime( $checkin_date ) ) ); ?> &rarr; <?php echo esc_html( wp_date( 'M j, Y', strtotime( $checkout_date ) ) ); ?>
						</span>
					</td>
					<td class="ahbn-mono">
						<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $nightly_rate ) : (string) number_format_i18n( $nightly_rate, 2 ) ); ?>
					</td>
					<td style="text-align:center;" class="ahbn-mono"><?php echo esc_html( (string) $nights ); ?></td>
					<td style="text-align:right;" class="ahbn-mono">
						<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $total_amount ) : (string) number_format_i18n( $total_amount, 2 ) ); ?>
					</td>
				</tr>
			</tbody>
		</table>

		<!-- Notes -->
		<?php if ( ! empty( $notes ) ) : ?>
			<div style="background:var(--ahbn-slate-50); border:1px solid var(--ahbn-slate-200); border-radius:10px; padding:16px 20px; margin-bottom:25px;">
				<span class="ahbn-section-label" style="border:none; padding:0; margin-bottom:6px;"><?php esc_html_e( 'Special Requests & Concierge Instructions', 'awesome-hotel-booking' ); ?></span>
				<p style="margin:0; font-size:12px; line-height:1.6; color:var(--ahbn-slate-700);"><?php echo nl2br( esc_html( $notes ) ); ?></p>
			</div>
		<?php endif; ?>

		<!-- Summary & Status -->
		<div class="ahbn-total-section">
			<div class="ahbn-payment-badge-box">
				<span class="ahbn-section-label" style="border:none; padding:0; margin-bottom:8px;"><?php esc_html_e( 'Payment & Settlement Record', 'awesome-hotel-booking' ); ?></span>
				<div style="font-size:13px; color:var(--ahbn-slate-700); line-height:1.5;">
					<b><?php esc_html_e( 'Payment Method:', 'awesome-hotel-booking' ); ?></b> <?php echo esc_html( $payment_method ); ?><br>
					<b><?php esc_html_e( 'Account Status:', 'awesome-hotel-booking' ); ?></b> 
					<span style="font-weight:800; color:<?php echo 'paid' === $payment_status ? '#059669' : '#d97706'; ?>;">
						<?php echo 'paid' === $payment_status ? '&#10003; ' . esc_html__( 'PAID IN FULL', 'awesome-hotel-booking' ) : '&#9679; ' . esc_html__( 'UNPAID / BALANCE OUTSTANDING', 'awesome-hotel-booking' ); ?>
					</span>
				</div>
			</div>

			<div class="ahbn-total-box">
				<small><?php esc_html_e( 'Total Realized Balance', 'awesome-hotel-booking' ); ?></small>
				<span class="grand-price">
					<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $total_amount ) : (string) number_format_i18n( $total_amount, 2 ) ); ?>
				</span>
			</div>
		</div>

		<!-- Signatures -->
		<div class="ahbn-signature-grid">
			<div class="ahbn-sig-line">
				<?php esc_html_e( 'Guest Signature', 'awesome-hotel-booking' ); ?>
			</div>
			<div class="ahbn-sig-line">
				<?php esc_html_e( 'Duty Manager / Authorized Front Desk', 'awesome-hotel-booking' ); ?>
			</div>
		</div>

		<div class="ahbn-footer-note">
			<?php
			printf(
				/* translators: 1: hotel name, 2: current formatted time */
				esc_html__( 'Thank you for choosing %1$s. System folio generated on %2$s.', 'awesome-hotel-booking' ),
				esc_html( $hotel_name ),
				esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) )
			);
			?>
		</div>

	</div>

</body>
</html>
<?php
exit;