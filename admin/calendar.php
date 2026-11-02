<?php
/**
 * Interactive 12-Month Availability Matrix Calendar.
 *
 * Enterprise hospitality calendar engineered with fluid micro-interactions,
 * sleek typography, live month metrics, and instant day occupancy inspections.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the 12-Month Year Calendar View.
 *
 * @return void
 */
function ahbn_calendar_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	global $wpdb;
	$table_rooms        = $wpdb->prefix . 'ahbn_rooms';
	$table_bookings     = $wpdb->prefix . 'ahbn_bookings';
	$table_housekeeping = $wpdb->prefix . 'ahbn_housekeeping';

	// 1. Resolve Target Year & Filter Room.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$selected_year = isset( $_GET['cal_year'] ) ? absint( wp_unslash( $_GET['cal_year'] ) ) : (int) current_time( 'Y' );
	$filter_room   = isset( $_GET['room_id'] ) ? absint( wp_unslash( $_GET['room_id'] ) ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( $selected_year < 2000 || $selected_year > 2099 ) {
		$selected_year = (int) current_time( 'Y' );
	}

	$year_start = sprintf( '%04d-01-01', $selected_year );
	$year_end   = sprintf( '%04d-12-31', $selected_year );

	// 2. Fetch Active Published Rooms & Maintenance Statuses.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$all_rooms   = $wpdb->get_results( "SELECT id, room_number, title, room_type FROM {$table_rooms} WHERE status = 'publish' ORDER BY room_number ASC" );
	$total_rooms = ! empty( $all_rooms ) ? count( $all_rooms ) : 1;

	$maintenance_rooms = $wpdb->get_col( "SELECT room_id FROM {$table_housekeeping} WHERE clean_status = 'maintenance'" );
	$maintenance_rooms = array_map( 'absint', (array) $maintenance_rooms );

	// 3. Query Active Bookings for this Calendar Year.
	$booking_where = "WHERE status IN ('confirmed', 'checked_in', 'pending') AND checkin_date <= %s AND checkout_date >= %s";
	$query_params  = array( $year_end, $year_start );

	if ( $filter_room > 0 ) {
		$booking_where .= ' AND room_id = %d';
		$query_params[] = $filter_room;
	}

	$bookings = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT id, booking_code, room_id, guest_name, checkin_date, checkout_date, status 
			 FROM {$table_bookings} 
			 {$booking_where}",
			$query_params
		)
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	// 4. Build Daily Occupancy Map.
	$daily_occupancy = array();
	$year_booked_days = 0;

	if ( ! empty( $bookings ) ) {
		foreach ( $bookings as $b ) {
			$b_start = max( strtotime( $year_start ), strtotime( $b->checkin_date ) );
			$b_end   = min( strtotime( $year_end ), strtotime( $b->checkout_date ) );

			for ( $curr = $b_start; $curr < $b_end; $curr += DAY_IN_SECONDS ) {
				$d_key = gmdate( 'Y-m-d', $curr );
				if ( ! isset( $daily_occupancy[ $d_key ] ) ) {
					$daily_occupancy[ $d_key ] = 0;
					$year_booked_days++;
				}
				$daily_occupancy[ $d_key ]++;
			}
		}
	}

	$today_str = current_time( 'Y-m-d' );
	$prev_year = $selected_year - 1;
	$next_year = $selected_year + 1;

	$room_is_under_maint = ( $filter_room > 0 && in_array( $filter_room, $maintenance_rooms, true ) );

	$month_names = array(
		1  => __( 'January', 'awesome-hotel-booking' ),
		2  => __( 'February', 'awesome-hotel-booking' ),
		3  => __( 'March', 'awesome-hotel-booking' ),
		4  => __( 'April', 'awesome-hotel-booking' ),
		5  => __( 'May', 'awesome-hotel-booking' ),
		6  => __( 'June', 'awesome-hotel-booking' ),
		7  => __( 'July', 'awesome-hotel-booking' ),
		8  => __( 'August', 'awesome-hotel-booking' ),
		9  => __( 'September', 'awesome-hotel-booking' ),
		10 => __( 'October', 'awesome-hotel-booking' ),
		11 => __( 'November', 'awesome-hotel-booking' ),
		12 => __( 'December', 'awesome-hotel-booking' ),
	);

	$weekdays = array(
		__( 'Su', 'awesome-hotel-booking' ),
		__( 'Mo', 'awesome-hotel-booking' ),
		__( 'Tu', 'awesome-hotel-booking' ),
		__( 'We', 'awesome-hotel-booking' ),
		__( 'Th', 'awesome-hotel-booking' ),
		__( 'Fr', 'awesome-hotel-booking' ),
		__( 'Sa', 'awesome-hotel-booking' ),
	);
	?>

	<style>
		:root {
			--ahbn-c-navy: #0A192F;
			--ahbn-c-gold: #C5A880;
			--ahbn-c-gold-subtle: #F6F1EA;
			--ahbn-c-border: #E8ECEF;
			--ahbn-c-slate: #5A6A80;
			--ahbn-smooth-bounce: cubic-bezier(0.34, 1.56, 0.64, 1);
			--ahbn-smooth-fade: cubic-bezier(0.16, 1, 0.3, 1);
		}

		.ahbn-year-cal-wrap {
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0F172A;
			display: flex;
			flex-direction: column;
			gap: 20px;
			animation: ahbnCalFadeIn 0.35s var(--ahbn-smooth-fade);
		}

		@keyframes ahbnCalFadeIn {
			from { opacity: 0; transform: translateY(6px); }
			to { opacity: 1; transform: translateY(0); }
		}

		/* Executive Navigation & Filter Bar */
		.ahbn-year-cal-header {
			background: #FFFFFF;
			border: 1px solid var(--ahbn-c-border);
			border-radius: 16px;
			padding: 20px 24px;
			display: flex;
			align-items: center;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 16px;
			box-shadow: 0 4px 18px -4px rgba(10, 25, 47, 0.04);
		}

		.ahbn-year-title-group h2 {
			margin: 0 0 4px 0;
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 23px;
			font-weight: 700;
			color: var(--ahbn-c-navy);
			letter-spacing: -0.01em;
			display: flex;
			align-items: center;
			gap: 10px;
		}

		.ahbn-year-title-group p {
			margin: 0;
			font-size: 13px;
			color: var(--ahbn-c-slate);
		}

		.ahbn-year-controls {
			display: flex;
			align-items: center;
			gap: 12px;
			flex-wrap: wrap;
		}

		.ahbn-room-dropdown {
			height: 40px;
			padding: 0 16px;
			font-size: 13px;
			font-weight: 600;
			color: var(--ahbn-c-navy);
			background: #FFFFFF;
			border: 1px solid var(--ahbn-c-border);
			border-radius: 10px;
			outline: none;
			cursor: pointer;
			transition: all 0.2s var(--ahbn-smooth-fade);
		}

		.ahbn-room-dropdown:focus,
		.ahbn-room-dropdown:hover {
			border-color: var(--ahbn-c-gold);
			box-shadow: 0 0 0 3px rgba(197, 168, 128, 0.15);
		}

		.ahbn-year-nav {
			display: inline-flex;
			align-items: center;
			background: #F8FAFC;
			border: 1px solid var(--ahbn-c-border);
			border-radius: 10px;
			overflow: hidden;
		}

		.ahbn-year-nav a {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			padding: 10px 14px;
			text-decoration: none;
			color: #334155;
			font-weight: 700;
			font-size: 13px;
			transition: all 0.2s var(--ahbn-smooth-fade);
		}

		.ahbn-year-nav a:hover {
			background: var(--ahbn-c-navy);
			color: #FFFFFF;
		}

		.ahbn-current-year-badge {
			padding: 10px 18px;
			font-family: 'JetBrains Mono', monospace;
			font-weight: 800;
			font-size: 15px;
			color: var(--ahbn-c-navy);
			background: #FFFFFF;
			border-left: 1px solid var(--ahbn-c-border);
			border-right: 1px solid var(--ahbn-c-border);
		}

		/* Status Legend */
		.ahbn-cal-legend-bar {
			background: #FFFFFF;
			border: 1px solid var(--ahbn-c-border);
			border-radius: 12px;
			padding: 12px 20px;
			display: flex;
			align-items: center;
			justify-content: flex-start;
			gap: 22px;
			flex-wrap: wrap;
			font-size: 12px;
			color: #475569;
			font-weight: 600;
		}

		.ahbn-legend-item {
			display: inline-flex;
			align-items: center;
			gap: 8px;
		}

		.ahbn-swatch {
			width: 14px;
			height: 14px;
			border-radius: 4px;
			display: inline-block;
		}

		.ahbn-swatch-empty   { background: #F2FBF7; border: 1.5px solid #A7F3D0; }
		.ahbn-swatch-booked  { background: #0A192F; border: 1.5px solid #0A192F; }
		.ahbn-swatch-partial { background: #C5A880; border: 1.5px solid #B39264; }
		.ahbn-swatch-maint   { background: #EF4444; border: 1.5px solid #DC2626; }
		.ahbn-swatch-today   { background: #FFFBEB; border: 2px solid #F59E0B; }

		/* 12-Month Responsive Grid */
		.ahbn-12month-grid {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 16px;
		}

		.ahbn-month-card {
			background: #FFFFFF;
			border: 1px solid var(--ahbn-c-border);
			border-radius: 14px;
			padding: 16px;
			display: flex;
			flex-direction: column;
			gap: 8px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
			transition: border-color 0.25s var(--ahbn-smooth-fade), transform 0.25s var(--ahbn-smooth-fade), box-shadow 0.25s var(--ahbn-smooth-fade);
		}

		.ahbn-month-card:hover {
			border-color: #D1D5DB;
			transform: translateY(-2px);
			box-shadow: 0 8px 24px -6px rgba(10, 25, 47, 0.06);
		}

		.ahbn-month-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			padding-bottom: 8px;
			border-bottom: 1px solid #F1F5F9;
		}

		.ahbn-month-name {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 16px;
			font-weight: 700;
			color: var(--ahbn-c-navy);
		}

		.ahbn-month-stats {
			font-size: 10px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.06em;
			color: #94A3B8;
		}

		/* Month Mini-Calendar Grid */
		.ahbn-mini-cal-table {
			width: 100%;
			border-collapse: separate;
			border-spacing: 3px;
			margin-top: 2px;
		}

		.ahbn-mini-cal-table th {
			font-size: 10px;
			font-weight: 700;
			color: #94A3B8;
			text-align: center;
			padding: 4px 0;
			text-transform: uppercase;
			letter-spacing: 0.04em;
		}

		.ahbn-day-cell {
			width: 14.28%;
			height: 30px;
			text-align: center;
			vertical-align: middle;
			font-size: 11px;
			font-weight: 700;
			border-radius: 7px;
			cursor: default;
			position: relative;
			transition: transform 0.18s var(--ahbn-smooth-bounce), box-shadow 0.18s var(--ahbn-smooth-fade), filter 0.15s ease;
		}

		.ahbn-day-cell.is-empty-slot {
			background: transparent;
			border: none;
		}

		/* Status Color Palette */
		.ahbn-status-empty {
			background: #F2FBF7;
			color: #065F46;
			border: 1px solid #D1FAE5;
		}

		.ahbn-status-booked {
			background: #0A192F;
			color: #FFFFFF;
			border: 1px solid #0A192F;
		}

		.ahbn-status-partial {
			background: #C5A880;
			color: #0A192F;
			border: 1px solid #B39264;
		}

		.ahbn-status-maint {
			background: #EF4444;
			color: #FFFFFF;
			border: 1px solid #DC2626;
		}

		.ahbn-day-cell.is-today-ring {
			box-shadow: 0 0 0 2px #F59E0B !important;
		}

		.ahbn-day-cell:hover:not(.is-empty-slot) {
			transform: scale(1.18);
			z-index: 10;
			filter: brightness(1.05);
			box-shadow: 0 6px 14px rgba(10, 25, 47, 0.18);
		}

		@media (max-width: 1380px) {
			.ahbn-12month-grid {
				grid-template-columns: repeat(3, 1fr);
			}
		}

		@media (max-width: 1024px) {
			.ahbn-12month-grid {
				grid-template-columns: repeat(2, 1fr);
			}
		}

		@media (max-width: 640px) {
			.ahbn-12month-grid {
				grid-template-columns: 1fr;
			}
			.ahbn-year-cal-header {
				flex-direction: column;
				align-items: flex-start;
			}
			.ahbn-year-controls {
				width: 100%;
			}
			.ahbn-room-dropdown {
				width: 100%;
			}
		}
	</style>

	<div class="ahbn-year-cal-wrap">

		<!-- 1. Header Toolbar -->
		<div class="ahbn-year-cal-header">
			<div class="ahbn-year-title-group">
				<h2>
					<span class="dashicons dashicons-calendar" style="color: var(--ahbn-c-gold); font-size: 24px; width: 24px; height: 24px;" aria-hidden="true"></span>
					<?php
					printf(
						/* translators: %d: Selected Year */
						esc_html__( '%d Annual Availability Matrix', 'awesome-hotel-booking' ),
						absint( $selected_year )
					);
					?>
				</h2>
				<p>
					<?php if ( $filter_room > 0 ) : ?>
						<?php esc_html_e( 'Displaying daily booked, vacant, and repair status for selected suite.', 'awesome-hotel-booking' ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Global overview: daily capacity and occupancy across all suites in the property.', 'awesome-hotel-booking' ); ?>
					<?php endif; ?>
				</p>
			</div>

			<div class="ahbn-year-controls">
				<!-- Suite Selector Form -->
				<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
					<input type="hidden" name="page" value="awesome_hotel_booking">
					<input type="hidden" name="tab" value="calendar">
					<input type="hidden" name="cal_year" value="<?php echo esc_attr( (string) $selected_year ); ?>">

					<select name="room_id" onchange="this.form.submit();" class="ahbn-room-dropdown">
						<option value="0"><?php esc_html_e( '— Entire Hotel Inventory —', 'awesome-hotel-booking' ); ?></option>
						<?php foreach ( $all_rooms as $r ) : ?>
							<option value="<?php echo esc_attr( (string) $r->id ); ?>" <?php selected( $filter_room, $r->id ); ?>>
								#<?php echo esc_html( $r->room_number . ' - ' . $r->title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</form>

				<!-- Year Navigation -->
				<div class="ahbn-year-nav">
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'awesome_hotel_booking', 'tab' => 'calendar', 'cal_year' => $prev_year, 'room_id' => $filter_room ), admin_url( 'admin.php' ) ) ); ?>" title="<?php esc_attr_e( 'Previous Year', 'awesome-hotel-booking' ); ?>">
						&laquo; <?php echo esc_html( (string) $prev_year ); ?>
					</a>
					<span class="ahbn-current-year-badge"><?php echo esc_html( (string) $selected_year ); ?></span>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'awesome_hotel_booking', 'tab' => 'calendar', 'cal_year' => $next_year, 'room_id' => $filter_room ), admin_url( 'admin.php' ) ) ); ?>" title="<?php esc_attr_e( 'Next Year', 'awesome-hotel-booking' ); ?>">
						<?php echo esc_html( (string) $next_year ); ?> &raquo;
					</a>
				</div>
			</div>
		</div>

		<!-- 2. Color Status Legend -->
		<div class="ahbn-cal-legend-bar">
			<div class="ahbn-legend-item">
				<span class="ahbn-swatch ahbn-swatch-empty"></span>
				<span><?php esc_html_e( 'Empty / Available', 'awesome-hotel-booking' ); ?></span>
			</div>
			<div class="ahbn-legend-item">
				<span class="ahbn-swatch ahbn-swatch-booked"></span>
				<span><?php echo $filter_room > 0 ? esc_html__( 'Booked / Occupied', 'awesome-hotel-booking' ) : esc_html__( '100% Sold Out', 'awesome-hotel-booking' ); ?></span>
			</div>
			<?php if ( 0 === $filter_room ) : ?>
				<div class="ahbn-legend-item">
					<span class="ahbn-swatch ahbn-swatch-partial"></span>
					<span><?php esc_html_e( 'Partial Occupancy', 'awesome-hotel-booking' ); ?></span>
				</div>
			<?php endif; ?>
			<div class="ahbn-legend-item">
				<span class="ahbn-swatch ahbn-swatch-maint"></span>
				<span><?php esc_html_e( 'Maintenance / Repair', 'awesome-hotel-booking' ); ?></span>
			</div>
			<div class="ahbn-legend-item">
				<span class="ahbn-swatch ahbn-swatch-today"></span>
				<span><?php esc_html_e( 'Today\'s Date', 'awesome-hotel-booking' ); ?></span>
			</div>
		</div>

		<!-- 3. Full 12-Month Grid -->
		<div class="ahbn-12month-grid">
			<?php
			for ( $m = 1; $m <= 12; $m++ ) :
				$month_date_str = sprintf( '%04d-%02d-01', $selected_year, $m );
				$days_in_m      = (int) gmdate( 't', strtotime( $month_date_str ) );
				$first_dow      = (int) gmdate( 'w', strtotime( $month_date_str ) );
				?>
				<div class="ahbn-month-card">
					<div class="ahbn-month-header">
						<span class="ahbn-month-name"><?php echo esc_html( $month_names[ $m ] ); ?></span>
						<span class="ahbn-month-stats"><?php echo esc_html( sprintf( _n( '%d Day', '%d Days', $days_in_m, 'awesome-hotel-booking' ), $days_in_m ) ); ?></span>
					</div>

					<table class="ahbn-mini-cal-table">
						<thead>
							<tr>
								<?php foreach ( $weekdays as $wkd ) : ?>
									<th><?php echo esc_html( $wkd ); ?></th>
								<?php endforeach; ?>
							</tr>
						</thead>
						<tbody>
							<tr>
								<?php
								for ( $pad = 0; $pad < $first_dow; $pad++ ) {
									echo '<td class="ahbn-day-cell is-empty-slot"></td>';
								}

								$col_count = $first_dow;

								for ( $day = 1; $day <= $days_in_m; $day++ ) {
									if ( 7 === $col_count ) {
										echo '</tr><tr>';
										$col_count = 0;
									}

									$current_day_str = sprintf( '%04d-%02d-%02d', $selected_year, $m, $day );
									$is_today        = ( $current_day_str === $today_str );
									$occupied_count  = isset( $daily_occupancy[ $current_day_str ] ) ? $daily_occupancy[ $current_day_str ] : 0;

									// Determine state styling and status details.
									$status_class = 'ahbn-status-empty';
									$tooltip_info = __( 'Available', 'awesome-hotel-booking' );

									if ( $room_is_under_maint ) {
										$status_class = 'ahbn-status-maint';
										$tooltip_info = __( 'Room under maintenance', 'awesome-hotel-booking' );
									} elseif ( $filter_room > 0 ) {
										if ( $occupied_count > 0 ) {
											$status_class = 'ahbn-status-booked';
											$tooltip_info = __( 'Booked / Reserved', 'awesome-hotel-booking' );
										}
									} else {
										if ( $occupied_count >= $total_rooms && $total_rooms > 0 ) {
											$status_class = 'ahbn-status-booked';
											$tooltip_info = sprintf( __( '100%% Sold Out (%1$d/%2$d)', 'awesome-hotel-booking' ), $occupied_count, $total_rooms );
										} elseif ( $occupied_count > 0 ) {
											$status_class = 'ahbn-status-partial';
											$tooltip_info = sprintf( __( 'Partially Booked (%1$d/%2$d occupied)', 'awesome-hotel-booking' ), $occupied_count, $total_rooms );
										}
									}

									$today_class = $is_today ? ' is-today-ring' : '';
									?>
									<td 
										class="ahbn-day-cell <?php echo esc_attr( $status_class . $today_class ); ?>" 
										title="<?php echo esc_attr( wp_date( 'M j, Y', strtotime( $current_day_str ) ) . ': ' . $tooltip_info ); ?>"
									>
										<?php echo esc_html( (string) $day ); ?>
									</td>
									<?php
									$col_count++;
								}

								while ( $col_count > 0 && $col_count < 7 ) {
									echo '<td class="ahbn-day-cell is-empty-slot"></td>';
									$col_count++;
								}
								?>
							</tr>
						</tbody>
					</table>
				</div>
			<?php endfor; ?>
		</div>

	</div>
	<?php
}