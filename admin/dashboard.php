<?php
/**
 * Executive Admin Dashboard View for Awesome Hotel Booking.
 *
 * Real-time operational dashboard focused strictly on Today's performance.
 * Built on high-performance custom relational database tables.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch and calculate cached dashboard metrics specifically for Today.
 *
 * @return array Real-time daily metrics.
 */
function ahbn_get_today_dashboard_stats() {
	$stats = get_transient( 'ahbn_today_dashboard_stats' );

	if ( false === $stats ) {
		global $wpdb;

		$table_bookings     = $wpdb->prefix . 'ahbn_bookings';
		$table_rooms        = $wpdb->prefix . 'ahbn_rooms';
		$table_housekeeping = $wpdb->prefix . 'ahbn_housekeeping';
		$today              = current_time( 'Y-m-d' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		// 1. Total Active Inventory Units.
		$total_rooms = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_rooms} WHERE status = %s",
				'publish'
			)
		);

		// 2. Today's Core Flight Runway & Guest Population.
		$today_ops = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
					COUNT(CASE WHEN checkin_date = %s AND status IN ('confirmed', 'pending') THEN 1 END) AS arrivals,
					COUNT(CASE WHEN checkout_date = %s AND status IN ('confirmed', 'checked_in') THEN 1 END) AS departures,
					COUNT(CASE WHEN checkin_date <= %s AND checkout_date > %s AND status = 'checked_in' THEN 1 END) AS in_house_rooms,
					COALESCE(SUM(CASE WHEN checkin_date <= %s AND checkout_date > %s AND status = 'checked_in' THEN (adults + children) ELSE 0 END), 0) AS in_house_guests
				 FROM {$table_bookings}",
				$today,
				$today,
				$today,
				$today,
				$today,
				$today
			)
		);

		// 3. Today's Realized Room Revenue (Daily pro-rated room revenue for occupied stays).
		$today_revenue = (float) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(total_amount / NULLIF(nights, 0)), 0.00) 
				 FROM {$table_bookings} 
				 WHERE checkin_date <= %s AND checkout_date > %s AND status IN ('confirmed', 'checked_in')",
				$today,
				$today
			)
		);

		// 4. Today's Bookings Created Today.
		$new_today_bookings = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_bookings} WHERE DATE(created_at) = %s",
				$today
			)
		);

		// 5. Hospitality Yield Metrics: Occupancy, ADR, RevPAR.
		$occupied_rooms = $today_ops ? (int) $today_ops->in_house_rooms : 0;
		$occupancy_rate = $total_rooms > 0 ? round( ( $occupied_rooms / $total_rooms ) * 100, 1 ) : 0;
		$adr            = $occupied_rooms > 0 ? ( $today_revenue / $occupied_rooms ) : 0.00;
		$revpar         = $total_rooms > 0 ? ( $today_revenue / $total_rooms ) : 0.00;

		// 6. Housekeeping Real-Time Breakdown.
		$housekeeping = $wpdb->get_row(
			"SELECT 
				COUNT(CASE WHEN clean_status = 'clean' THEN 1 END) AS rooms_clean,
				COUNT(CASE WHEN clean_status = 'dirty' THEN 1 END) AS rooms_dirty,
				COUNT(CASE WHEN clean_status = 'maintenance' THEN 1 END) AS rooms_maint
			 FROM {$table_housekeeping}"
		);

		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$stats = array(
			'today_revenue'    => $today_revenue,
			'total_rooms'      => $total_rooms,
			'arrivals'         => $today_ops ? (int) $today_ops->arrivals : 0,
			'departures'       => $today_ops ? (int) $today_ops->departures : 0,
			'in_house_rooms'   => $occupied_rooms,
			'in_house_guests'  => $today_ops ? (int) $today_ops->in_house_guests : 0,
			'new_booked_today' => $new_today_bookings,
			'occupancy_rate'   => $occupancy_rate,
			'adr'              => $adr,
			'revpar'           => $revpar,
			'clean_rooms'      => $housekeeping ? (int) $housekeeping->rooms_clean : 0,
			'dirty_rooms'      => $housekeeping ? (int) $housekeeping->rooms_dirty : 0,
			'maint_rooms'      => $housekeeping ? (int) $housekeeping->rooms_maint : 0,
		);

		set_transient( 'ahbn_today_dashboard_stats', $stats, 15 * MINUTE_IN_SECONDS );
	}

	return $stats;
}

/**
 * Flush all dashboard-related transients on data modification.
 *
 * @return void
 */
function ahbn_clear_today_dashboard_transients() {
	delete_transient( 'ahbn_today_dashboard_stats' );
	delete_transient( 'ahbn_today_pending_stays' );
	delete_transient( 'ahbn_rooms_active_count' );
}
add_action( 'ahbn_after_booking_saved', 'ahbn_clear_today_dashboard_transients' );
add_action( 'ahbn_after_booking_deleted', 'ahbn_clear_dashboard_transients' );
add_action( 'ahbn_after_room_saved', 'ahbn_clear_today_dashboard_transients' );
add_action( 'ahbn_after_room_deleted', 'ahbn_clear_today_dashboard_transients' );

/**
 * Render the Executive Admin Dashboard Tab.
 *
 * @return void
 */
function ahbn_dashboard_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	global $wpdb;
	$plugin_slug    = 'awesome_hotel_booking';
	$base_url       = admin_url( 'admin.php?page=' . rawurlencode( $plugin_slug ) );
	$directory_url  = add_query_arg( 'tab', 'bookings', $base_url );
	$calendar_url   = add_query_arg( 'tab', 'calendar', $base_url );
	$housekeep_url  = add_query_arg( 'tab', 'housekeeping', $base_url );

	$table_bookings = $wpdb->prefix . 'ahbn_bookings';
	$table_rooms    = $wpdb->prefix . 'ahbn_rooms';
	$today          = current_time( 'Y-m-d' );

	// 1. Fetch Inquiries created today OR reservations arriving today requiring action.
	$cache_key     = 'ahbn_today_pending_stays';
	$pending_stays = get_transient( $cache_key );

	if ( false === $pending_stays ) {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$pending_stays = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					b.id,
					b.booking_code,
					b.guest_name,
					b.guest_email,
					b.guest_phone,
					b.total_amount,
					b.checkin_date,
					b.checkout_date,
					b.nights,
					b.created_at,
					b.status,
					r.title AS room_title,
					r.room_number,
					r.room_type
				 FROM {$table_bookings} b
				 LEFT JOIN {$table_rooms} r ON b.room_id = r.id
				 WHERE b.status = %s 
				    OR (b.checkin_date = %s AND b.status IN ('confirmed', 'pending'))
				 ORDER BY (b.checkin_date = %s) DESC, b.created_at DESC
				 LIMIT %d",
				'pending',
				$today,
				$today,
				6
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		set_transient( $cache_key, $pending_stays, 15 * MINUTE_IN_SECONDS );
	}

	$stats         = ahbn_get_today_dashboard_stats();
	$total_pending = is_array( $pending_stays ) ? count( $pending_stays ) : 0;
	?>

	<style>
		.ahbn-exec-dashboard {
			display: flex;
			flex-direction: column;
			gap: 24px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		/* Executive Top Bar */
		.ahbn-exec-header {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 16px;
			padding: 24px 28px;
			display: flex;
			align-items: center;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 20px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-exec-title-group h1 {
			margin: 0 0 6px 0;
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 24px;
			font-weight: 700;
			letter-spacing: -0.02em;
			color: #0a192f;
			display: flex;
			align-items: center;
			gap: 10px;
		}

		.ahbn-exec-title-group p {
			margin: 0;
			font-size: 13px;
			color: #64748b;
		}

		/* Live Property Clock */
		.ahbn-live-clock-card {
			background: linear-gradient(145deg, #07111e 0%, #0a192f 100%);
			border: 1px solid rgba(197, 168, 128, 0.25);
			border-radius: 14px;
			padding: 16px 22px;
			color: #ffffff;
			display: flex;
			flex-direction: column;
			gap: 6px;
			min-width: 250px;
			box-shadow: 0 4px 14px rgba(10, 25, 47, 0.15);
		}

		.ahbn-clock-header {
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.ahbn-clock-status-dot {
			width: 8px;
			height: 8px;
			background: #10b981;
			border-radius: 50%;
			display: inline-block;
			box-shadow: 0 0 8px #10b981;
			animation: ahbnPulseDot 2s infinite ease-in-out;
		}

		@keyframes ahbnPulseDot {
			0%, 100% { opacity: 1; transform: scale(1); }
			50% { opacity: 0.4; transform: scale(0.85); }
		}

		.ahbn-clock-subtitle {
			font-size: 10px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.08em;
			color: #94a3b8;
			flex-grow: 1;
		}

		.ahbn-tz-badge {
			font-family: 'JetBrains Mono', monospace;
			background: rgba(197, 168, 128, 0.15);
			color: #c5a880;
			border: 1px solid rgba(197, 168, 128, 0.35);
			padding: 2px 6px;
			border-radius: 99px;
			font-size: 9px;
			font-weight: 700;
		}

		.ahbn-clock-display {
			display: flex;
			align-items: baseline;
			gap: 6px;
			line-height: 1;
		}

		.ahbn-time-digits {
			font-family: 'JetBrains Mono', monospace;
			font-size: 30px;
			font-weight: 800;
			letter-spacing: -0.02em;
			color: #ffffff;
		}

		.ahbn-time-ampm {
			font-size: 12px;
			font-weight: 800;
			color: #c5a880;
			text-transform: uppercase;
		}

		.ahbn-clock-date-row {
			display: flex;
			align-items: center;
			gap: 6px;
			font-size: 11px;
			color: #cbd5e1;
			font-weight: 500;
		}

		.ahbn-clock-date-row .dashicons {
			font-size: 14px;
			width: 14px;
			height: 14px;
			color: #c5a880;
		}

		/* Executive Buttons */
		.ahbn-btn-solid {
			background: #0a192f !important;
			color: #ffffff !important;
			border: 1px solid #0a192f !important;
			padding: 9px 16px !important;
			border-radius: 8px !important;
			font-size: 13px !important;
			font-weight: 700 !important;
			text-decoration: none !important;
			display: inline-flex !important;
			align-items: center !important;
			gap: 6px !important;
			transition: all 0.2s ease !important;
			cursor: pointer;
		}

		.ahbn-btn-solid:hover {
			background: #c5a880 !important;
			border-color: #c5a880 !important;
			color: #0a192f !important;
		}

		.ahbn-btn-outline {
			background: #ffffff !important;
			color: #334155 !important;
			border: 1px solid #cbd5e1 !important;
			padding: 9px 14px !important;
			border-radius: 8px !important;
			font-size: 13px !important;
			font-weight: 600 !important;
			text-decoration: none !important;
			display: inline-flex !important;
			align-items: center !important;
			gap: 6px !important;
			transition: all 0.2s ease !important;
		}

		.ahbn-btn-outline:hover {
			border-color: #0a192f !important;
			color: #0a192f !important;
			background: #f8fafc !important;
		}

		/* Operational Runway */
		.ahbn-runway-grid {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 16px;
		}

		.ahbn-runway-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 18px 20px;
			display: flex;
			align-items: center;
			justify-content: space-between;
			text-decoration: none;
			color: inherit;
			transition: all 0.2s ease;
		}

		.ahbn-runway-card:hover {
			border-color: #cbd5e1;
			box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
			transform: translateY(-2px);
		}

		.ahbn-runway-label {
			display: block;
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.06em;
			color: #64748b;
			margin-bottom: 4px;
		}

		.ahbn-runway-val {
			font-size: 26px;
			font-weight: 800;
			color: #0a192f;
			line-height: 1;
		}

		.ahbn-runway-icon {
			width: 44px;
			height: 44px;
			border-radius: 10px;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 20px;
		}

		/* KPI Matrix */
		.ahbn-kpi-grid {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 16px;
		}

		.ahbn-kpi-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 20px 22px;
			position: relative;
		}

		.ahbn-kpi-card::before {
			content: "";
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			height: 3px;
			background: #e2e8f0;
		}

		.ahbn-kpi-primary::before { background: #0a192f; }
		.ahbn-kpi-gold::before    { background: #c5a880; }
		.ahbn-kpi-green::before   { background: #10b981; }
		.ahbn-kpi-blue::before    { background: #3b82f6; }

		.ahbn-kpi-label {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.06em;
			color: #64748b;
			display: flex;
			align-items: center;
			justify-content: space-between;
			margin-bottom: 10px;
		}

		.ahbn-kpi-val {
			font-size: 28px;
			font-weight: 800;
			color: #0a192f;
			letter-spacing: -0.02em;
			line-height: 1;
			margin-bottom: 8px;
		}

		.ahbn-kpi-sub {
			font-size: 12px;
			color: #64748b;
		}

		/* Split View */
		.ahbn-split-view {
			display: grid;
			grid-template-columns: 2fr 1fr;
			gap: 20px;
			align-items: start;
		}

		.ahbn-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			overflow: hidden;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-card-header {
			padding: 18px 24px;
			border-bottom: 1px solid #f1f5f9;
			display: flex;
			align-items: center;
			justify-content: space-between;
			background: #fafafa;
		}

		.ahbn-card-title {
			margin: 0;
			font-size: 13px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.06em;
			color: #0a192f;
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.ahbn-table-compact {
			width: 100%;
			border-collapse: collapse;
			font-size: 13px;
		}

		.ahbn-table-compact th {
			background: #f8fafc;
			padding: 12px 18px;
			font-weight: 700;
			font-size: 11px;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #64748b;
			border-bottom: 1px solid #e2e8f0;
			text-align: left;
		}

		.ahbn-table-compact td {
			padding: 14px 18px;
			border-bottom: 1px solid #f1f5f9;
			vertical-align: middle;
		}

		.ahbn-table-compact tr:last-child td {
			border-bottom: none;
		}

		.ahbn-table-compact tr:hover td {
			background: #f8fafc;
		}

		.ahbn-guest-cell strong {
			display: block;
			color: #0f172a;
			font-size: 13px;
		}

		.ahbn-guest-cell span {
			color: #64748b;
			font-size: 12px;
		}

		/* Pure Typographic Housekeeping Readiness */
		.ahbn-hk-block {
			padding: 24px;
			display: flex;
			flex-direction: column;
			gap: 16px;
		}

		.ahbn-hk-item {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding: 14px 16px;
			border-radius: 10px;
			border: 1px solid #e2e8f0;
			background: #f8fafc;
		}

		.ahbn-hk-info {
			display: flex;
			align-items: center;
			gap: 12px;
		}

		.ahbn-hk-dot {
			width: 10px;
			height: 10px;
			border-radius: 50%;
		}

		.ahbn-hk-title-text {
			font-size: 13px;
			font-weight: 700;
			color: #0f172a;
			display: block;
		}

		.ahbn-hk-subtext {
			font-size: 11px;
			color: #64748b;
		}

		.ahbn-hk-count {
			font-family: 'JetBrains Mono', monospace;
			font-size: 20px;
			font-weight: 800;
			color: #0a192f;
		}

		@media (max-width: 1200px) {
			.ahbn-runway-grid,
			.ahbn-kpi-grid {
				grid-template-columns: repeat(2, 1fr);
			}
			.ahbn-split-view {
				grid-template-columns: 1fr;
			}
		}

		@media (max-width: 768px) {
			.ahbn-runway-grid,
			.ahbn-kpi-grid {
				grid-template-columns: 1fr;
			}
			.ahbn-exec-header {
				flex-direction: column;
				align-items: flex-start;
			}
			.ahbn-live-clock-card {
				width: 100%;
			}
		}
	</style>

	<div class="ahbn-exec-dashboard">

		<!-- 1. Executive Top Bar with Live Property Clock -->
		<div class="ahbn-exec-header">
			<div class="ahbn-exec-title-group">
				<h1>
					<span class="dashicons dashicons-building" style="color: #c5a880; font-size: 24px; width: 24px; height: 24px;" aria-hidden="true"></span>
					<?php esc_html_e( 'Hotel Daily Operations Center', 'awesome-hotel-booking' ); ?>
				</h1>
				<p>
					<?php
					printf(
						/* translators: 1: total published suites, 2: today's occupancy percentage */
						esc_html__( 'Live property status for %1$s: %2$s suites occupied (%3$s%% capacity).', 'awesome-hotel-booking' ),
						'<strong>' . esc_html( wp_date( 'l, M j, Y' ) ) . '</strong>',
						'<strong>' . absint( $stats['in_house_rooms'] ) . '/' . absint( $stats['total_rooms'] ) . '</strong>',
						'<strong>' . esc_html( (string) $stats['occupancy_rate'] ) . '</strong>'
					);
					?>
				</p>
				<div style="margin-top: 14px; display: flex; gap: 8px; flex-wrap: wrap;">
					<a href="<?php echo esc_url( add_query_arg( 'action', 'add', $directory_url ) ); ?>" class="ahbn-btn-solid">
						<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
						<?php esc_html_e( 'Create Reservation', 'awesome-hotel-booking' ); ?>
					</a>
					<a href="<?php echo esc_url( $calendar_url ); ?>" class="ahbn-btn-outline">
						<span class="dashicons dashicons-calendar" aria-hidden="true"></span>
						<?php esc_html_e( 'Matrix Calendar', 'awesome-hotel-booking' ); ?>
					</a>
				</div>
			</div>

			<!-- Live Property Clock Widget -->
			<div class="ahbn-live-clock-card">
				<div class="ahbn-clock-header">
					<span class="ahbn-clock-status-dot"></span>
					<span class="ahbn-clock-subtitle"><?php esc_html_e( 'Hotel Operating Time', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-tz-badge"><?php echo esc_html( wp_timezone_string() ); ?></span>
				</div>
				<div class="ahbn-clock-display">
					<span id="ahbn-live-time" class="ahbn-time-digits">--:--:--</span>
					<span id="ahbn-live-ampm" class="ahbn-time-ampm">--</span>
				</div>
				<div class="ahbn-clock-date-row">
					<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
					<span id="ahbn-live-date"><?php esc_html_e( 'Syncing...', 'awesome-hotel-booking' ); ?></span>
				</div>
			</div>
		</div>

		<!-- 2. Today's Front-Desk Operational Runway -->
		<div class="ahbn-runway-grid">
			<a href="<?php echo esc_url( add_query_arg( array( 'filter_date' => $today, 'filter_turn' => 'arrival' ), $directory_url ) ); ?>" class="ahbn-runway-card">
				<div>
					<span class="ahbn-runway-label"><?php esc_html_e( 'Arriving Today', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-runway-val"><?php echo esc_html( (string) $stats['arrivals'] ); ?></span>
				</div>
				<div class="ahbn-runway-icon" style="background: #ecfdf5; color: #059669;">
					<span class="dashicons dashicons-arrow-down-alt" aria-hidden="true"></span>
				</div>
			</a>

			<a href="<?php echo esc_url( add_query_arg( array( 'filter_date' => $today, 'filter_turn' => 'departure' ), $directory_url ) ); ?>" class="ahbn-runway-card">
				<div>
					<span class="ahbn-runway-label"><?php esc_html_e( 'Departing Today', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-runway-val"><?php echo esc_html( (string) $stats['departures'] ); ?></span>
				</div>
				<div class="ahbn-runway-icon" style="background: #fff1f2; color: #e11d48;">
					<span class="dashicons dashicons-arrow-up-alt" aria-hidden="true"></span>
				</div>
			</a>

			<a href="<?php echo esc_url( add_query_arg( 'filter_status', 'checked_in', $directory_url ) ); ?>" class="ahbn-runway-card">
				<div>
					<span class="ahbn-runway-label"><?php esc_html_e( 'In-House Guests', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-runway-val"><?php echo esc_html( (string) $stats['in_house_guests'] ); ?></span>
				</div>
				<div class="ahbn-runway-icon" style="background: #eff6ff; color: #2563eb;">
					<span class="dashicons dashicons-groups" aria-hidden="true"></span>
				</div>
			</a>

			<div class="ahbn-runway-card">
				<div>
					<span class="ahbn-runway-label"><?php esc_html_e( 'Today\'s Occupancy', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-runway-val"><?php echo esc_html( (string) $stats['occupancy_rate'] ); ?>%</span>
				</div>
				<div class="ahbn-runway-icon" style="background: #fffbeb; color: #d97706;">
					<span class="dashicons dashicons-chart-pie" aria-hidden="true"></span>
				</div>
			</div>
		</div>

		<!-- 3. Key Hotel Performance Indicators (Today's Hospitality Yield) -->
		<div class="ahbn-kpi-grid">
			<div class="ahbn-kpi-card ahbn-kpi-green">
				<div class="ahbn-kpi-label">
					<span><?php esc_html_e( 'Today\'s Room Revenue', 'awesome-hotel-booking' ); ?></span>
					<span class="dashicons dashicons-money-alt" aria-hidden="true"></span>
				</div>
				<div class="ahbn-kpi-val" style="color: #0f766e;">
					<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $stats['today_revenue'] ) : (string) number_format_i18n( $stats['today_revenue'], 2 ) ); ?>
				</div>
				<div class="ahbn-kpi-sub">
					<?php esc_html_e( 'Prorated stay value for occupied rooms today', 'awesome-hotel-booking' ); ?>
				</div>
			</div>

			<div class="ahbn-kpi-card ahbn-kpi-primary">
				<div class="ahbn-kpi-label">
					<span><?php esc_html_e( 'Today\'s ADR', 'awesome-hotel-booking' ); ?></span>
					<span class="dashicons dashicons-chart-bar" aria-hidden="true"></span>
				</div>
				<div class="ahbn-kpi-val">
					<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $stats['adr'] ) : (string) number_format_i18n( $stats['adr'], 2 ) ); ?>
				</div>
				<div class="ahbn-kpi-sub">
					<?php esc_html_e( 'Average daily rate across active rooms today', 'awesome-hotel-booking' ); ?>
				</div>
			</div>

			<div class="ahbn-kpi-card ahbn-kpi-gold">
				<div class="ahbn-kpi-label">
					<span><?php esc_html_e( 'Today\'s RevPAR', 'awesome-hotel-booking' ); ?></span>
					<span class="dashicons dashicons-performance" aria-hidden="true"></span>
				</div>
				<div class="ahbn-kpi-val">
					<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $stats['revpar'] ) : (string) number_format_i18n( $stats['revpar'], 2 ) ); ?>
				</div>
				<div class="ahbn-kpi-sub">
					<?php esc_html_e( 'Yield per total available inventory unit', 'awesome-hotel-booking' ); ?>
				</div>
			</div>

			<div class="ahbn-kpi-card ahbn-kpi-blue">
				<div class="ahbn-kpi-label">
					<span><?php esc_html_e( 'Bookings Placed Today', 'awesome-hotel-booking' ); ?></span>
					<span class="dashicons dashicons-calendar" aria-hidden="true"></span>
				</div>
				<div class="ahbn-kpi-val">
					<?php echo esc_html( (string) $stats['new_booked_today'] ); ?>
					<small style="font-size: 13px; font-weight: 600; color: #64748b;"><?php esc_html_e( 'New', 'awesome-hotel-booking' ); ?></small>
				</div>
				<div class="ahbn-kpi-sub">
					<?php esc_html_e( 'Reservations created across the property today', 'awesome-hotel-booking' ); ?>
				</div>
			</div>
		</div>

		<!-- 4. Operations Split View: Today's Action Queue & Real-Time Housekeeping -->
		<div class="ahbn-split-view">

			<!-- Left Column: Pending Actions & Today's Check-ins -->
			<div class="ahbn-card">
				<div class="ahbn-card-header">
					<h2 class="ahbn-card-title">
						<span class="dashicons dashicons-bell" style="color: #c5a880;" aria-hidden="true"></span>
						<?php esc_html_e( 'Today\'s Priority Arrivals & Pending Review', 'awesome-hotel-booking' ); ?>
					</h2>
					<span class="ahbn-id-badge">
						<?php
						printf(
							/* translators: %d: count of priority items */
							esc_html__( '%d Actions Required', 'awesome-hotel-booking' ),
							absint( $total_pending )
						);
						?>
					</span>
				</div>

				<?php if ( ! empty( $pending_stays ) ) : ?>
					<table class="ahbn-table-compact">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Guest Details', 'awesome-hotel-booking' ); ?></th>
								<th><?php esc_html_e( 'Assigned Suite', 'awesome-hotel-booking' ); ?></th>
								<th><?php esc_html_e( 'Stay Timeline', 'awesome-hotel-booking' ); ?></th>
								<th><?php esc_html_e( 'Total Amount', 'awesome-hotel-booking' ); ?></th>
								<th style="text-align: right;"><?php esc_html_e( 'Action', 'awesome-hotel-booking' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $pending_stays as $stay ) :
								$customer_name = ! empty( $stay->guest_name ) ? $stay->guest_name : __( 'Guest User', 'awesome-hotel-booking' );
								$display_price = (float) $stay->total_amount;
								$room_title    = ! empty( $stay->room_title ) ? $stay->room_title : __( 'Unassigned Room', 'awesome-hotel-booking' );
								if ( ! empty( $stay->room_number ) ) {
									$room_title = '#' . $stay->room_number . ' ' . $room_title;
								}

								$review_url = wp_nonce_url(
									add_query_arg(
										array(
											'sub'        => 'view',
											'booking_id' => absint( $stay->id ),
										),
										$directory_url
									),
									'ahbn_booking_action'
								);

								$is_arriving_today = ( $stay->checkin_date === $today );
								?>
								<tr>
									<td>
										<div class="ahbn-guest-cell">
											<strong><?php echo esc_html( $customer_name ); ?></strong>
											<span><?php echo esc_html( ! empty( $stay->guest_phone ) ? $stay->guest_phone : $stay->guest_email ); ?></span>
										</div>
									</td>
									<td>
										<span style="font-weight: 600; color: #0f172a;"><?php echo esc_html( $room_title ); ?></span>
										<small style="display: block; color: #94a3b8; font-size: 11px; text-transform: uppercase;">
											<?php echo esc_html( ! empty( $stay->room_type ) ? $stay->room_type : 'Standard' ); ?>
										</small>
									</td>
									<td>
										<?php if ( $is_arriving_today ) : ?>
											<span style="background: #ecfdf5; color: #065f46; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-bottom: 3px;">
												<?php esc_html_e( 'ARRIVING TODAY', 'awesome-hotel-booking' ); ?>
											</span>
										<?php endif; ?>
										<span style="display: block; font-weight: 600; color: #334155;">
											<?php echo esc_html( wp_date( 'M j', strtotime( $stay->checkin_date ) ) . ' – ' . wp_date( 'M j, Y', strtotime( $stay->checkout_date ) ) ); ?>
										</span>
										<small style="display: block; color: #64748b; font-size: 11px;">
											<?php
											printf(
												/* translators: %d: nights count */
												esc_html__( '%d night(s)', 'awesome-hotel-booking' ),
												absint( $stay->nights )
											);
											?>
										</small>
									</td>
									<td>
										<span style="font-family: 'JetBrains Mono', monospace; font-weight: 700; color: #0a192f;">
											<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $display_price ) : (string) number_format_i18n( $display_price, 2 ) ); ?>
										</span>
									</td>
									<td style="text-align: right;">
										<a href="<?php echo esc_url( $review_url ); ?>" class="ahbn-btn-action" style="padding: 4px 10px !important;">
											<?php echo $is_arriving_today ? esc_html__( 'Check-in', 'awesome-hotel-booking' ) : esc_html__( 'Review', 'awesome-hotel-booking' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<div style="padding: 14px 24px; background: #fafafa; border-top: 1px solid #f1f5f9; text-align: right;">
						<a href="<?php echo esc_url( $directory_url ); ?>" style="color: #0a192f; font-weight: 700; text-decoration: none; font-size: 12px;">
							<?php esc_html_e( 'Access Full Reservation Directory &rarr;', 'awesome-hotel-booking' ); ?>
						</a>
					</div>
				<?php else : ?>
					<div style="padding: 48px 24px; text-align: center;">
						<span class="dashicons dashicons-yes-alt" style="font-size: 36px; width: 36px; height: 36px; color: #10b981;" aria-hidden="true"></span>
						<h3 style="margin: 10px 0 4px; font-size: 15px; color: #0a192f;"><?php esc_html_e( 'Today\'s Queue is Clean', 'awesome-hotel-booking' ); ?></h3>
						<p style="margin: 0; font-size: 13px; color: #64748b;"><?php esc_html_e( 'No immediate guest check-ins or pending inquiries require review.', 'awesome-hotel-booking' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<!-- Right Column: Today's Real-time Housekeeping Readiness -->
			<div class="ahbn-card">
				<div class="ahbn-card-header">
					<h2 class="ahbn-card-title">
						<span class="dashicons dashicons-forms" style="color: #c5a880;" aria-hidden="true"></span>
						<?php esc_html_e( 'Today\'s Inventory Readiness', 'awesome-hotel-booking' ); ?>
					</h2>
					<a href="<?php echo esc_url( $housekeep_url ); ?>" style="font-size: 12px; font-weight: 700; color: #0a192f; text-decoration: none;">
						<?php esc_html_e( 'Manage &rarr;', 'awesome-hotel-booking' ); ?>
					</a>
				</div>

				<div class="ahbn-hk-block">
					<div class="ahbn-hk-item" style="border-left: 3px solid #10b981;">
						<div class="ahbn-hk-info">
							<span class="ahbn-hk-dot" style="background: #10b981;"></span>
							<div>
								<span class="ahbn-hk-title-text"><?php esc_html_e( 'Ready for Check-in', 'awesome-hotel-booking' ); ?></span>
								<span class="ahbn-hk-subtext"><?php esc_html_e( 'Cleaned & inspected suites', 'awesome-hotel-booking' ); ?></span>
							</div>
						</div>
						<span class="ahbn-hk-count" style="color: #059669;"><?php echo esc_html( (string) $stats['clean_rooms'] ); ?></span>
					</div>

					<div class="ahbn-hk-item" style="border-left: 3px solid #f59e0b;">
						<div class="ahbn-hk-info">
							<span class="ahbn-hk-dot" style="background: #f59e0b;"></span>
							<div>
								<span class="ahbn-hk-title-text"><?php esc_html_e( 'Awaiting Turnaround', 'awesome-hotel-booking' ); ?></span>
								<span class="ahbn-hk-subtext"><?php esc_html_e( 'Dirty / departing today queue', 'awesome-hotel-booking' ); ?></span>
							</div>
						</div>
						<span class="ahbn-hk-count" style="color: #d97706;"><?php echo esc_html( (string) $stats['dirty_rooms'] ); ?></span>
					</div>

					<div class="ahbn-hk-item" style="border-left: 3px solid #ef4444;">
						<div class="ahbn-hk-info">
							<span class="ahbn-hk-dot" style="background: #ef4444;"></span>
							<div>
								<span class="ahbn-hk-title-text"><?php esc_html_e( 'Out of Order / Repairs', 'awesome-hotel-booking' ); ?></span>
								<span class="ahbn-hk-subtext"><?php esc_html_e( 'Under maintenance today', 'awesome-hotel-booking' ); ?></span>
							</div>
						</div>
						<span class="ahbn-hk-count" style="color: #dc2626;"><?php echo esc_html( (string) $stats['maint_rooms'] ); ?></span>
					</div>

					<div style="padding-top: 10px; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
						<span style="font-size: 11px; color: #64748b; font-weight: 600;">
							<?php
							printf(
								/* translators: %d: count of published rooms */
								esc_html__( '%d total operational suites', 'awesome-hotel-booking' ),
								absint( $stats['total_rooms'] )
							);
							?>
						</span>
						<a href="<?php echo esc_url( $housekeep_url ); ?>" class="ahbn-btn-outline" style="padding: 4px 10px !important; font-size: 11px !important;">
							<?php esc_html_e( 'Assign Crew', 'awesome-hotel-booking' ); ?>
						</a>
					</div>
				</div>
			</div>

		</div>

	</div>

	<!-- Live Property Clock Script -->
	<script>
	(function() {
		function updateAhbnClock() {
			const now = new Date();

			let hours = now.getHours();
			const ampm = hours >= 12 ? 'PM' : 'AM';
			hours = hours % 12;
			hours = hours ? hours : 12;
			const hoursStr   = String(hours).padStart(2, '0');
			const minutesStr = String(now.getMinutes()).padStart(2, '0');
			const secondsStr = String(now.getSeconds()).padStart(2, '0');

			const timeEl = document.getElementById('ahbn-live-time');
			const ampmEl = document.getElementById('ahbn-live-ampm');
			if (timeEl && ampmEl) {
				timeEl.textContent = hoursStr + ':' + minutesStr + ':' + secondsStr;
				ampmEl.textContent = ampm;
			}

			const options = { weekday: 'long', year: 'numeric', month: 'short', day: 'numeric' };
			const dateStr = now.toLocaleDateString(undefined, options);

			const dateEl = document.getElementById('ahbn-live-date');
			if (dateEl) {
				dateEl.textContent = dateStr;
			}
		}

		updateAhbnClock();
		setInterval(updateAhbnClock, 1000);
	})();
	</script>
	<?php
}