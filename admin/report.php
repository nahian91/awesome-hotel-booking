<?php
/**
 * Reports Tab - Financial Analytics & Revenue Intelligence.
 *
 * Rewritten for custom SQL database tables:
 * {$wpdb->prefix}ahbn_bookings joined with {$wpdb->prefix}ahbn_rooms and {$wpdb->prefix}ahbn_expenses.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Revenue & Reports tab.
 *
 * @return void
 */
function ahbn_reports_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	global $wpdb;

	$table_bookings = $wpdb->prefix . 'ahbn_bookings';
	$table_rooms    = $wpdb->prefix . 'ahbn_rooms';
	$table_expenses = $wpdb->prefix . 'ahbn_expenses';

	$ahbn_page_slug = 'awesome_hotel_booking';

	// 1. Date Range Input & Sanitization.
	$default_from = current_time( 'Y-m-01' );
	$default_to   = current_time( 'Y-m-d' );

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$raw_from = isset( $_GET['fd_from'] ) ? sanitize_text_field( wp_unslash( $_GET['fd_from'] ) ) : '';
	$raw_to   = isset( $_GET['fd_to'] ) ? sanitize_text_field( wp_unslash( $_GET['fd_to'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$ahbn_filter_from = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw_from ) ? $raw_from : $default_from;
	$ahbn_filter_to   = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw_to ) ? $raw_to : $default_to;

	if ( strtotime( $ahbn_filter_from ) > strtotime( $ahbn_filter_to ) ) {
		$ahbn_filter_from = $default_from;
		$ahbn_filter_to   = $default_to;
	}

	// 2. Fetch Aggregations directly from Custom SQL Tables.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$booking_stats = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT 
				COUNT(*) AS total_bookings,
				COALESCE(SUM(total_amount), 0.00) AS total_revenue,
				COALESCE(AVG(total_amount), 0.00) AS avg_booking_val,
				COALESCE(SUM(nights), 0) AS total_room_nights
			 FROM {$table_bookings}
			 WHERE status IN ('confirmed', 'checked_in', 'checked_out')
			   AND checkin_date >= %s
			   AND checkin_date <= %s",
			$ahbn_filter_from,
			$ahbn_filter_to
		)
	);

	$total_rev         = $booking_stats ? (float) $booking_stats->total_revenue : 0.00;
	$confirmed_count   = $booking_stats ? (int) $booking_stats->total_bookings : 0;
	$avg_booking_val   = $booking_stats ? (float) $booking_stats->avg_booking_val : 0.00;
	$total_room_nights = $booking_stats ? (int) $booking_stats->total_room_nights : 0;

	// Total Expenses in the same period.
	$total_expenses = (float) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COALESCE(SUM(amount), 0.00) 
			 FROM {$table_expenses}
			 WHERE expense_date >= %s 
			   AND expense_date <= %s",
			$ahbn_filter_from,
			$ahbn_filter_to
		)
	);

	$net_operating_profit = $total_rev - $total_expenses;

	// 3. Fetch Transaction Log.
	$transactions = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT 
				b.id,
				b.booking_code,
				b.guest_name,
				b.guest_email,
				b.checkin_date,
				b.checkout_date,
				b.nights,
				b.total_amount,
				b.payment_status,
				b.payment_method,
				b.status,
				b.created_at,
				r.room_number,
				r.title AS room_title,
				r.room_type
			 FROM {$table_bookings} b
			 LEFT JOIN {$table_rooms} r ON b.room_id = r.id
			 WHERE b.status IN ('confirmed', 'checked_in', 'checked_out')
			   AND b.checkin_date >= %s
			   AND b.checkin_date <= %s
			 ORDER BY b.checkin_date DESC, b.id DESC",
			$ahbn_filter_from,
			$ahbn_filter_to
		)
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	$hotel_name = get_option( 'ahbn_hotel_name', get_bloginfo( 'name' ) );
	?>

	<div class="ahbn-reports-container">
		<!-- Executive Header -->
		<header class="ahbn-reports-header">
			<div>
				<h1 class="ahbn-reports-title"><?php esc_html_e( 'Financial Analytics & Revenue Intelligence', 'awesome-hotel-booking' ); ?></h1>
				<p class="ahbn-reports-subtitle">
					<?php
					printf(
						/* translators: 1: hotel name, 2: Start date, 3: End date */
						esc_html__( '%1$s &bull; Audited Period: %2$s &mdash; %3$s', 'awesome-hotel-booking' ),
						esc_html( $hotel_name ),
						esc_html( wp_date( 'M j, Y', strtotime( $ahbn_filter_from ) ) ),
						esc_html( wp_date( 'M j, Y', strtotime( $ahbn_filter_to ) ) )
					);
					?>
				</p>
			</div>
			<div class="ahbn-header-actions no-print">
				<button type="button" class="ahbn-btn-print" id="ahbn_print_report_btn">
					<span class="dashicons dashicons-printer" aria-hidden="true"></span>
					<?php esc_html_e( 'Print Financial Statement', 'awesome-hotel-booking' ); ?>
				</button>
			</div>
		</header>

		<!-- Date Range Filter Form -->
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="ahbn-rep-filters no-print">
			<input type="hidden" name="page" value="<?php echo esc_attr( $ahbn_page_slug ); ?>">
			<input type="hidden" name="tab" value="reports">

			<div class="ahbn-filter-controls">
				<div class="ahbn-date-group">
					<label for="ahbn_fd_from"><?php esc_html_e( 'From Date', 'awesome-hotel-booking' ); ?></label>
					<input type="date" id="ahbn_fd_from" name="fd_from" value="<?php echo esc_attr( $ahbn_filter_from ); ?>" class="ahbn-field">
				</div>

				<div class="ahbn-date-group">
					<label for="ahbn_fd_to"><?php esc_html_e( 'To Date', 'awesome-hotel-booking' ); ?></label>
					<input type="date" id="ahbn_fd_to" name="fd_to" value="<?php echo esc_attr( $ahbn_filter_to ); ?>" class="ahbn-field">
				</div>

				<button type="submit" class="ahbn-btn-primary">
					<span class="dashicons dashicons-filter" aria-hidden="true"></span>
					<?php esc_html_e( 'Recalculate Statement', 'awesome-hotel-booking' ); ?>
				</button>

				<?php if ( $ahbn_filter_from !== $default_from || $ahbn_filter_to !== $default_to ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $ahbn_page_slug . '&tab=reports' ) ); ?>" class="ahbn-clear-link">
						<?php esc_html_e( 'Reset to Current Month', 'awesome-hotel-booking' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</form>

		<!-- KPI Metric Cards Grid -->
		<div class="ahbn-stats-grid">
			<div class="ahbn-stat-card border-emerald">
				<span class="ahbn-stat-label"><?php esc_html_e( 'Gross Revenue', 'awesome-hotel-booking' ); ?></span>
				<span class="ahbn-stat-val text-emerald">
					<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $total_rev ) : (string) number_format_i18n( $total_rev, 2 ) ); ?>
				</span>
				<small class="ahbn-stat-sub">
					<?php
					printf(
						/* translators: %d: number of realized nights */
						esc_html__( '%d Total Room Nights', 'awesome-hotel-booking' ),
						absint( $total_room_nights )
					);
					?>
				</small>
			</div>

			<div class="ahbn-stat-card border-rose">
				<span class="ahbn-stat-label"><?php esc_html_e( 'Total Expenses', 'awesome-hotel-booking' ); ?></span>
				<span class="ahbn-stat-val text-rose">
					<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $total_expenses ) : (string) number_format_i18n( $total_expenses, 2 ) ); ?>
				</span>
				<small class="ahbn-stat-sub"><?php esc_html_e( 'Operating & Maintenance Outflows', 'awesome-hotel-booking' ); ?></small>
			</div>

			<div class="ahbn-stat-card border-navy">
				<span class="ahbn-stat-label"><?php esc_html_e( 'Net Operating Margin', 'awesome-hotel-booking' ); ?></span>
				<span class="ahbn-stat-val <?php echo $net_operating_profit >= 0 ? 'text-navy' : 'text-rose'; ?>">
					<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $net_operating_profit ) : (string) number_format_i18n( $net_operating_profit, 2 ) ); ?>
				</span>
				<small class="ahbn-stat-sub">
					<?php
					$margin_pct = $total_rev > 0 ? ( ( $net_operating_profit / $total_rev ) * 100 ) : 0;
					printf(
						/* translators: %s: percentage margin */
						esc_html__( '%s%% Realized Margin', 'awesome-hotel-booking' ),
						esc_html( number_format( $margin_pct, 1 ) )
					);
					?>
				</small>
			</div>

			<div class="ahbn-stat-card border-gold">
				<span class="ahbn-stat-label"><?php esc_html_e( 'Average Daily Booking (ADR)', 'awesome-hotel-booking' ); ?></span>
				<span class="ahbn-stat-val text-navy">
					<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $avg_booking_val ) : (string) number_format_i18n( $avg_booking_val, 2 ) ); ?>
				</span>
				<small class="ahbn-stat-sub">
					<?php
					printf(
						/* translators: %d: number of confirmed bookings */
						esc_html__( 'Across %d Confirmed Stays', 'awesome-hotel-booking' ),
						absint( $confirmed_count )
					);
					?>
				</small>
			</div>
		</div>

		<!-- Transaction Log Table -->
		<div class="ahbn-table-container">
			<div class="ahbn-table-header">
				<h3 class="ahbn-table-title"><?php esc_html_e( 'Realized Revenue Transactions', 'awesome-hotel-booking' ); ?></h3>
				<span class="ahbn-table-badge">
					<?php
					printf(
						/* translators: %d: number of records */
						esc_html__( '%d Entries', 'awesome-hotel-booking' ),
						count( $transactions )
					);
					?>
				</span>
			</div>

			<table class="ahbn-main-table widefat striped">
				<thead>
					<tr>
						<th style="width: 120px;"><?php esc_html_e( 'Folio Ref', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Guest Particulars', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Assigned Suite', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Stay Timeline', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Settlement', 'awesome-hotel-booking' ); ?></th>
						<th style="text-align: right;"><?php esc_html_e( 'Revenue Realized', 'awesome-hotel-booking' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $transactions ) ) : ?>
						<?php foreach ( $transactions as $t ) :
							$folio_code = ! empty( $t->booking_code ) ? $t->booking_code : '#' . $t->id;
							$suite_name = ! empty( $t->room_title ) ? $t->room_title : __( 'Standard Room', 'awesome-hotel-booking' );
							if ( ! empty( $t->room_number ) ) {
								$suite_name = '#' . $t->room_number . ' ' . $suite_name;
							}
							$total_val   = (float) $t->total_amount;
							$is_paid     = ( 'paid' === sanitize_key( $t->payment_status ) );
							$stay_nights = max( 1, absint( $t->nights ) );
							?>
							<tr>
								<td>
									<span class="ahbn-code-badge"><?php echo esc_html( $folio_code ); ?></span>
								</td>
								<td>
									<strong style="color:#0f172a;"><?php echo esc_html( ! empty( $t->guest_name ) ? $t->guest_name : __( 'Guest User', 'awesome-hotel-booking' ) ); ?></strong>
									<?php if ( ! empty( $t->guest_email ) ) : ?>
										<small style="display:block; color:#64748b; font-size:11px;"><?php echo esc_html( $t->guest_email ); ?></small>
									<?php endif; ?>
								</td>
								<td>
									<strong style="color:#0a192f;"><?php echo esc_html( $suite_name ); ?></strong>
									<span style="display:block; font-size:11px; color:#64748b; text-transform:uppercase;">
										<?php echo esc_html( ! empty( $t->room_type ) ? $t->room_type : 'Standard' ); ?>
									</span>
								</td>
								<td>
									<span style="font-size:12px; font-weight:600; color:#334155;">
										<?php echo esc_html( wp_date( 'M j', strtotime( $t->checkin_date ) ) ); ?> &rarr; <?php echo esc_html( wp_date( 'M j, Y', strtotime( $t->checkout_date ) ) ); ?>
									</span>
									<small style="display:block; color:#64748b; font-size:11px;">
										<?php
										printf(
											/* translators: %d: number of nights */
											esc_html( _n( '%d Night', '%d Nights', $stay_nights, 'awesome-hotel-booking' ) ),
											absint( $stay_nights )
										);
										?>
									</small>
								</td>
								<td>
									<?php if ( $is_paid ) : ?>
										<span class="ahbn-pay-badge is-paid">&#10003; <?php esc_html_e( 'Settled', 'awesome-hotel-booking' ); ?></span>
									<?php else : ?>
										<span class="ahbn-pay-badge is-unpaid">&#9679; <?php esc_html_e( 'Unpaid', 'awesome-hotel-booking' ); ?></span>
									<?php endif; ?>
								</td>
								<td style="text-align: right;">
									<strong class="ahbn-amount-large">
										<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $total_val ) : (string) number_format_i18n( $total_val, 2 ) ); ?>
									</strong>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="6" style="text-align: center; padding: 50px; color: #94a3b8;">
								<span class="dashicons dashicons-chart-bar" style="font-size: 36px; width: 36px; height: 36px; color: #cbd5e1;" aria-hidden="true"></span>
								<p style="margin: 8px 0 0; font-size: 13px;">
									<?php esc_html_e( 'No revenue transactions recorded for the selected audit range.', 'awesome-hotel-booking' ); ?>
								</p>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<style>
		.ahbn-reports-container {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-reports-header {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 16px;
			padding: 22px 28px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 16px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-reports-title {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 24px;
			font-weight: 700;
			color: #0a192f;
			margin: 0 0 4px;
		}

		.ahbn-reports-subtitle {
			color: #64748b;
			margin: 0;
			font-size: 13px;
		}

		.ahbn-btn-print {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			background: #0a192f;
			color: #ffffff;
			border: 1px solid #0a192f;
			padding: 10px 18px;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 700;
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.ahbn-btn-print:hover {
			background: #c5a880;
			border-color: #c5a880;
			color: #0a192f;
		}

		/* Filters */
		.ahbn-rep-filters {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 16px 20px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-filter-controls {
			display: flex;
			align-items: flex-end;
			gap: 16px;
			flex-wrap: wrap;
		}

		.ahbn-date-group {
			display: flex;
			flex-direction: column;
			gap: 6px;
		}

		.ahbn-date-group label {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #64748b;
		}

		.ahbn-field {
			height: 38px !important;
			border: 1px solid #cbd5e1 !important;
			border-radius: 8px !important;
			padding: 0 12px !important;
			font-size: 13px !important;
			background: #ffffff !important;
			color: #0f172a !important;
			box-shadow: none !important;
		}

		.ahbn-field:focus {
			border-color: #0a192f !important;
			box-shadow: 0 0 0 2px rgba(10, 25, 47, 0.1) !important;
		}

		.ahbn-btn-primary {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: #0a192f;
			color: #ffffff;
			border: 1px solid #0a192f;
			padding: 9px 18px;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 700;
			cursor: pointer;
			transition: all 0.2s ease;
			height: 38px;
		}

		.ahbn-btn-primary:hover {
			background: #c5a880;
			border-color: #c5a880;
			color: #0a192f;
		}

		.ahbn-clear-link {
			font-size: 12px;
			font-weight: 600;
			color: #ef4444;
			text-decoration: none;
			margin-bottom: 8px;
		}

		.ahbn-clear-link:hover {
			text-decoration: underline;
		}

		/* Stat Cards */
		.ahbn-stats-grid {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 16px;
		}

		.ahbn-stat-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 20px;
			display: flex;
			flex-direction: column;
			gap: 4px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-stat-label {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.06em;
			color: #64748b;
		}

		.ahbn-stat-val {
			font-family: 'JetBrains Mono', monospace;
			font-size: 24px;
			font-weight: 800;
		}

		.ahbn-stat-sub {
			font-size: 11px;
			color: #94a3b8;
			margin-top: 4px;
		}

		.border-emerald { border-left: 4px solid #10b981; }
		.border-rose    { border-left: 4px solid #ef4444; }
		.border-navy    { border-left: 4px solid #0a192f; }
		.border-gold    { border-left: 4px solid #c5a880; }

		.text-emerald { color: #059669; }
		.text-rose    { color: #dc2626; }
		.text-navy    { color: #0a192f; }

		/* Table Elements */
		.ahbn-table-container {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			overflow: hidden;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-table-header {
			padding: 16px 20px;
			background: #fafafa;
			border-bottom: 1px solid #f1f5f9;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.ahbn-table-title {
			margin: 0;
			font-size: 13px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #0a192f;
		}

		.ahbn-table-badge {
			font-family: 'JetBrains Mono', monospace;
			font-size: 11px;
			font-weight: 700;
			color: #64748b;
			background: #f1f5f9;
			padding: 3px 8px;
			border-radius: 4px;
		}

		.ahbn-main-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 13px;
		}

		.ahbn-main-table th {
			background: #f8fafc !important;
			padding: 12px 16px !important;
			font-weight: 700 !important;
			font-size: 11px !important;
			text-transform: uppercase !important;
			letter-spacing: 0.05em !important;
			color: #64748b !important;
			border-bottom: 1px solid #e2e8f0 !important;
		}

		.ahbn-main-table td {
			padding: 14px 16px !important;
			vertical-align: middle !important;
			border-bottom: 1px solid #f1f5f9 !important;
		}

		.ahbn-code-badge {
			font-family: 'JetBrains Mono', monospace;
			background: #f8fafc;
			color: #0a192f;
			font-weight: 700;
			font-size: 11px;
			padding: 3px 8px;
			border-radius: 4px;
			border: 1px solid #e2e8f0;
			display: inline-block;
		}

		.ahbn-pay-badge {
			font-size: 11px;
			font-weight: 700;
		}
		.ahbn-pay-badge.is-paid   { color: #059669; }
		.ahbn-pay-badge.is-unpaid { color: #d97706; }

		.ahbn-amount-large {
			font-family: 'JetBrains Mono', monospace;
			font-size: 14px;
			color: #059669;
		}

		@media (max-width: 1024px) {
			.ahbn-stats-grid {
				grid-template-columns: repeat(2, 1fr);
			}
		}

		@media (max-width: 600px) {
			.ahbn-stats-grid {
				grid-template-columns: 1fr;
			}
		}

		@media print {
			.no-print {
				display: none !important;
			}
			.ahbn-reports-container {
				gap: 10px;
			}
			.ahbn-reports-header {
				border: none !important;
				box-shadow: none !important;
				padding: 0 0 15px 0 !important;
			}
			.ahbn-table-container {
				border: 1px solid #000 !important;
				box-shadow: none !important;
			}
		}
	</style>

	<script>
	jQuery(document).ready(function($){
		$('#ahbn_print_report_btn').on('click', function(e){
			e.preventDefault();
			window.print();
		});
	});
	</script>
	<?php
}