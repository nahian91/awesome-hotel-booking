<?php
/**
 * Rooms Inventory List View.
 *
 * Rewritten for custom SQL database tables:
 * {$wpdb->prefix}ahbn_rooms joined with {$wpdb->prefix}ahbn_bookings.
 * Styled with the Champagne Gold & Deep Navy enterprise UI system.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Flush rooms list cache when a room is modified.
 *
 * @return void
 */
function ahbn_clear_rooms_cache() {
	delete_transient( 'ahbn_all_rooms_list' );
	delete_transient( 'ahbn_rooms_metrics' );
}

/**
 * Render Room Inventory List.
 *
 * @return void
 */
function ahbn_rooms_list() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	global $wpdb;
	$table_rooms    = $wpdb->prefix . 'ahbn_rooms';
	$table_bookings = $wpdb->prefix . 'ahbn_bookings';

	$page_slug = 'awesome_hotel_booking';
	$base_url  = admin_url( 'admin.php?page=' . rawurlencode( $page_slug ) . '&tab=rooms' );

	// 1. Resolve Filter & Pagination Parameters.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$paged        = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
	$search_query = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$filter_type  = isset( $_GET['room_type'] ) ? sanitize_text_field( wp_unslash( $_GET['room_type'] ) ) : '';
	$filter_status= isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$per_page = 20;
	$offset   = ( $paged - 1 ) * $per_page;

	// 2. Build Dynamic SQL Filters.
	$where_clauses = array( '1=1' );
	$params        = array();

	if ( ! empty( $search_query ) ) {
		$where_clauses[] = '(r.title LIKE %s OR r.room_number LIKE %s OR r.description LIKE %s)';
		$like_val        = '%' . $wpdb->esc_like( $search_query ) . '%';
		$params[]        = $like_val;
		$params[]        = $like_val;
		$params[]        = $like_val;
	}

	if ( ! empty( $filter_type ) ) {
		$where_clauses[] = 'r.room_type = %s';
		$params[]        = $filter_type;
	}

	if ( ! empty( $filter_status ) ) {
		$where_clauses[] = 'r.status = %s';
		$params[]        = $filter_status;
	}

	$where_sql = implode( ' AND ', $where_clauses );

	// 3. Query Totals & Filtered Results with Booking History Aggregation.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( ! empty( $params ) ) {
		$total_records = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table_rooms} r WHERE {$where_sql}", $params )
		);
	} else {
		$total_records = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_rooms} r WHERE {$where_sql}" );
	}

	$total_pages = ceil( $total_records / $per_page );

	$fetch_sql = "SELECT 
					r.*,
					COUNT(b.id) AS total_bookings
				  FROM {$table_rooms} r
				  LEFT JOIN {$table_bookings} b ON r.id = b.room_id
				  WHERE {$where_sql}
				  GROUP BY r.id
				  ORDER BY r.menu_order ASC, r.id DESC
				  LIMIT %d OFFSET %d";

	$fetch_params   = $params;
	$fetch_params[] = $per_page;
	$fetch_params[] = $offset;

	$rooms = $wpdb->get_results( $wpdb->prepare( $fetch_sql, $fetch_params ) );

	// Aggregate KPIs.
	$metrics = $wpdb->get_row(
		"SELECT 
			COUNT(*) AS total_rooms,
			COUNT(CASE WHEN status = 'publish' THEN 1 END) AS active_rooms,
			COALESCE(AVG(price_per_night), 0.00) AS avg_rate,
			COALESCE(SUM(max_adults + max_children), 0) AS total_capacity
		 FROM {$table_rooms}"
	);

	// Distinct Room Types for Quick Filter Dropdown.
	$available_types = $wpdb->get_col( "SELECT DISTINCT room_type FROM {$table_rooms} WHERE room_type != '' ORDER BY room_type ASC" );
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	$total_rooms    = $metrics ? (int) $metrics->total_rooms : 0;
	$active_rooms   = $metrics ? (int) $metrics->active_rooms : 0;
	$avg_rate       = $metrics ? (float) $metrics->avg_rate : 0.00;
	$total_capacity = $metrics ? (int) $metrics->total_capacity : 0;
	?>

	<div class="ahbn-rooms-list-wrap">
		<!-- KPI Metric Cards Grid -->
		<div class="ahbn-stats-grid">
			<div class="ahbn-stat-card">
				<span class="ahbn-stat-label"><?php esc_html_e( 'Total Inventory', 'awesome-hotel-booking' ); ?></span>
				<span class="ahbn-stat-value"><?php echo esc_html( (string) $total_rooms ); ?></span>
			</div>
			<div class="ahbn-stat-card ahbn-stat-emerald">
				<span class="ahbn-stat-label"><?php esc_html_e( 'Available For Booking', 'awesome-hotel-booking' ); ?></span>
				<span class="ahbn-stat-value"><?php echo esc_html( (string) $active_rooms ); ?></span>
			</div>
			<div class="ahbn-stat-card ahbn-stat-gold">
				<span class="ahbn-stat-label"><?php esc_html_e( 'Average Nightly Rate (ADR)', 'awesome-hotel-booking' ); ?></span>
				<span class="ahbn-stat-value">
					<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $avg_rate ) : (string) number_format_i18n( $avg_rate, 2 ) ); ?>
				</span>
			</div>
			<div class="ahbn-stat-card">
				<span class="ahbn-stat-label"><?php esc_html_e( 'Max Guest Capacity (Sleeps)', 'awesome-hotel-booking' ); ?></span>
				<span class="ahbn-stat-value"><?php echo esc_html( (string) $total_capacity ); ?></span>
			</div>
		</div>

		<!-- Search & Filter Bar -->
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="ahbn-room-filter-bar">
			<input type="hidden" name="page" value="<?php echo esc_attr( $page_slug ); ?>">
			<input type="hidden" name="tab" value="rooms">
			<input type="hidden" name="sub" value="all">

			<div class="ahbn-filter-controls">
				<div class="ahbn-search-field">
					<input 
						type="text" 
						name="s" 
						class="ahbn-input" 
						placeholder="<?php esc_attr_e( 'Search by suite title, room number, or features...', 'awesome-hotel-booking' ); ?>"
						value="<?php echo esc_attr( $search_query ); ?>"
					>
				</div>

				<?php if ( ! empty( $available_types ) ) : ?>
					<div class="ahbn-select-field">
						<select name="room_type" class="ahbn-select" onchange="this.form.submit();">
							<option value=""><?php esc_html_e( 'All Room Types', 'awesome-hotel-booking' ); ?></option>
							<?php foreach ( $available_types as $type_opt ) : ?>
								<option value="<?php echo esc_attr( $type_opt ); ?>" <?php selected( $filter_type, $type_opt ); ?>>
									<?php echo esc_html( $type_opt ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>

				<div class="ahbn-select-field">
					<select name="status" class="ahbn-select" onchange="this.form.submit();">
						<option value=""><?php esc_html_e( 'All Statuses', 'awesome-hotel-booking' ); ?></option>
						<option value="publish" <?php selected( $filter_status, 'publish' ); ?>><?php esc_html_e( 'Published & Active', 'awesome-hotel-booking' ); ?></option>
						<option value="draft" <?php selected( $filter_status, 'draft' ); ?>><?php esc_html_e( 'Draft / Under Maintenance', 'awesome-hotel-booking' ); ?></option>
					</select>
				</div>

				<button type="submit" class="ahbn-btn-search">
					<span class="dashicons dashicons-search" aria-hidden="true"></span>
					<?php esc_html_e( 'Filter', 'awesome-hotel-booking' ); ?>
				</button>

				<?php if ( ! empty( $search_query ) || ! empty( $filter_type ) || ! empty( $filter_status ) ) : ?>
					<a href="<?php echo esc_url( $base_url ); ?>" class="ahbn-clear-filters">
						<?php esc_html_e( 'Reset Filters', 'awesome-hotel-booking' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</form>

		<!-- Room Inventory Table -->
		<div class="ahbn-table-container">
			<table class="ahbn-room-table widefat striped">
				<thead>
					<tr>
						<th style="width: 70px;"><?php esc_html_e( 'Preview', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Suite & Particulars', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Classification', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Capacity', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Nightly Rate', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Inventory Status', 'awesome-hotel-booking' ); ?></th>
						<th style="text-align: right; width: 120px;"><?php esc_html_e( 'Actions', 'awesome-hotel-booking' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $rooms ) ) : ?>
						<?php foreach ( $rooms as $room ) :
							$rid           = (int) $room->id;
							$is_ready      = ( 'publish' === $room->status );
							$status_class  = $is_ready ? 'status-active-now' : 'status-disabled';
							$status_label  = $is_ready ? __( 'Ready', 'awesome-hotel-booking' ) : __( 'Draft', 'awesome-hotel-booking' );
							$price_val     = (float) $room->price_per_night;
							$adults_num    = absint( $room->max_adults );
							$children_num  = absint( $room->max_children );
							$total_history = (int) $room->total_bookings;

							$view_url = add_query_arg( array( 'sub' => 'view', 'room_id' => $rid ), $base_url );
							$edit_url = add_query_arg( array( 'sub' => 'edit', 'room_id' => $rid ), $base_url );
							$del_url  = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'room_id' => $rid ), $base_url ), 'ahbn_room_action' );
							$thumb_url= $room->thumbnail_id ? wp_get_attachment_image_url( $room->thumbnail_id, 'thumbnail' ) : '';
							?>
							<tr>
								<td>
									<?php if ( ! empty( $thumb_url ) ) : ?>
										<img src="<?php echo esc_url( $thumb_url ); ?>" class="ahbn-room-thumb" alt="<?php echo esc_attr( $room->title ); ?>">
									<?php else : ?>
										<div class="ahbn-thumb-placeholder">
											<span class="dashicons dashicons-building" aria-hidden="true"></span>
										</div>
									<?php endif; ?>
								</td>
								<td>
									<div class="ahbn-room-title-stack">
										<div class="ahbn-title-meta-line">
											<span class="ahbn-room-num-badge">#<?php echo esc_html( ! empty( $room->room_number ) ? $room->room_number : $rid ); ?></span>
											<strong class="ahbn-room-title-text"><?php echo esc_html( $room->title ); ?></strong>
										</div>
										<div class="ahbn-history-sub">
											<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
											<?php
											printf(
												/* translators: %d: Total number of stays */
												esc_html( _n( '%d Stay Recorded', '%d Stays Recorded', $total_history, 'awesome-hotel-booking' ) ),
												absint( $total_history )
											);
											?>
										</div>
									</div>
								</td>
								<td>
									<span class="ahbn-type-badge">
										<?php echo esc_html( ! empty( $room->room_type ) ? $room->room_type : __( 'Standard', 'awesome-hotel-booking' ) ); ?>
									</span>
								</td>
								<td>
									<span class="ahbn-occupancy-pill">
										<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
										<?php
										printf(
											/* translators: 1: Adult count, 2: Child count */
											esc_html__( '%1$d Ad, %2$d Ch', 'awesome-hotel-booking' ),
											$adults_num,
											$children_num
										);
										?>
									</span>
								</td>
								<td>
									<strong class="ahbn-price-highlight">
										<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $price_val ) : (string) number_format_i18n( $price_val, 2 ) ); ?>
									</strong>
									<small class="ahbn-night-label">/ night</small>
								</td>
								<td>
									<span class="ahbn-status-pill <?php echo esc_attr( $status_class ); ?>">
										<?php echo esc_html( $status_label ); ?>
									</span>
								</td>
								<td style="text-align: right;">
									<div class="ahbn-action-btns">
										<a href="<?php echo esc_url( $view_url ); ?>" class="ahbn-quick-btn" title="<?php esc_attr_e( 'View Folio Details', 'awesome-hotel-booking' ); ?>">
											<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
										</a>
										<a href="<?php echo esc_url( $edit_url ); ?>" class="ahbn-quick-btn" title="<?php esc_attr_e( 'Edit Suite', 'awesome-hotel-booking' ); ?>">
											<span class="dashicons dashicons-edit" aria-hidden="true"></span>
										</a>
										<a 
											href="<?php echo esc_url( $del_url ); ?>" 
											class="ahbn-quick-btn is-delete" 
											title="<?php esc_attr_e( 'Delete Suite', 'awesome-hotel-booking' ); ?>"
											onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to permanently delete this room record?', 'awesome-hotel-booking' ) ); ?>');"
										>
											<span class="dashicons dashicons-trash" aria-hidden="true"></span>
										</a>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="7" class="ahbn-empty-row">
								<span class="dashicons dashicons-building" aria-hidden="true"></span>
								<p><?php esc_html_e( 'No rooms or suites found matching your search criteria.', 'awesome-hotel-booking' ); ?></p>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- Pagination Links -->
		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav" style="margin-top: 15px;">
				<div class="tablenav-pages">
					<?php
					$pagination_args = array(
						'base'      => add_query_arg( 'paged', '%#%' ),
						'format'    => '',
						'prev_text' => __( '&laquo; Previous', 'awesome-hotel-booking' ),
						'next_text' => __( 'Next &raquo;', 'awesome-hotel-booking' ),
						'total'     => $total_pages,
						'current'   => $paged,
					);
					echo wp_kses_post( paginate_links( $pagination_args ) );
					?>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<style>
		.ahbn-rooms-list-wrap {
			display: flex;
			flex-direction: column;
			gap: 16px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
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
			padding: 18px 20px;
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

		.ahbn-stat-value {
			font-family: 'JetBrains Mono', monospace;
			font-size: 24px;
			font-weight: 800;
			color: #0a192f;
		}

		.ahbn-stat-emerald { border-left: 4px solid #10b981; }
		.ahbn-stat-emerald .ahbn-stat-label,
		.ahbn-stat-emerald .ahbn-stat-value { color: #059669; }

		.ahbn-stat-gold { border-left: 4px solid #c5a880; }
		.ahbn-stat-gold .ahbn-stat-label { color: #b39264; }

		/* Filter Bar */
		.ahbn-room-filter-bar {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 14px 18px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-filter-controls {
			display: flex;
			align-items: center;
			gap: 12px;
			flex-wrap: wrap;
		}

		.ahbn-search-field {
			flex: 1;
			min-width: 240px;
		}

		.ahbn-input,
		.ahbn-select {
			height: 38px !important;
			border: 1px solid #cbd5e1 !important;
			border-radius: 8px !important;
			padding: 0 12px !important;
			font-size: 13px !important;
			background: #ffffff !important;
			color: #0f172a !important;
			box-shadow: none !important;
			width: 100%;
		}

		.ahbn-input:focus,
		.ahbn-select:focus {
			border-color: #0a192f !important;
			box-shadow: 0 0 0 2px rgba(10, 25, 47, 0.1) !important;
		}

		.ahbn-btn-search {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: #0a192f;
			color: #ffffff;
			border: 1px solid #0a192f;
			padding: 0 18px;
			height: 38px;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 700;
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.ahbn-btn-search:hover {
			background: #c5a880;
			border-color: #c5a880;
			color: #0a192f;
		}

		.ahbn-clear-filters {
			font-size: 12px;
			color: #ef4444;
			text-decoration: none;
			font-weight: 600;
		}

		.ahbn-clear-filters:hover {
			text-decoration: underline;
		}

		/* Table Structure */
		.ahbn-table-container {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			overflow: hidden;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-room-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 13px;
		}

		.ahbn-room-table th {
			background: #f8fafc !important;
			padding: 12px 16px !important;
			font-weight: 700 !important;
			font-size: 11px !important;
			text-transform: uppercase !important;
			letter-spacing: 0.05em !important;
			color: #64748b !important;
			border-bottom: 1px solid #e2e8f0 !important;
		}

		.ahbn-room-table td {
			padding: 14px 16px !important;
			vertical-align: middle !important;
			border-bottom: 1px solid #f1f5f9 !important;
		}

		.ahbn-room-thumb {
			width: 48px;
			height: 48px;
			border-radius: 8px;
			object-fit: cover;
			background: #f1f5f9;
			border: 1px solid #e2e8f0;
			display: block;
		}

		.ahbn-thumb-placeholder {
			width: 48px;
			height: 48px;
			border-radius: 8px;
			background: #f8fafc;
			border: 1px dashed #cbd5e1;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #94a3b8;
		}

		.ahbn-room-title-stack {
			display: flex;
			flex-direction: column;
			gap: 4px;
		}

		.ahbn-title-meta-line {
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.ahbn-room-num-badge {
			font-family: 'JetBrains Mono', monospace;
			background: #f8fafc;
			color: #0a192f;
			font-weight: 800;
			font-size: 11px;
			padding: 2px 7px;
			border-radius: 4px;
			border: 1px solid #e2e8f0;
			display: inline-block;
		}

		.ahbn-room-title-text {
			color: #0a192f;
			font-size: 14px;
		}

		.ahbn-history-sub {
			display: inline-flex;
			align-items: center;
			gap: 4px;
			font-size: 11px;
			color: #64748b;
		}

		.ahbn-history-sub .dashicons {
			font-size: 13px;
			width: 13px;
			height: 13px;
			color: #94a3b8;
		}

		.ahbn-type-badge {
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

		.ahbn-occupancy-pill {
			display: inline-flex;
			align-items: center;
			gap: 5px;
			font-size: 12px;
			color: #475569;
			background: #f1f5f9;
			padding: 4px 10px;
			border-radius: 6px;
			border: 1px solid #e2e8f0;
			font-weight: 600;
		}

		.ahbn-occupancy-pill .dashicons {
			font-size: 14px;
			width: 14px;
			height: 14px;
			color: #64748b;
		}

		.ahbn-price-highlight {
			font-family: 'JetBrains Mono', monospace;
			color: #059669;
			font-size: 14px;
			font-weight: 800;
		}

		.ahbn-night-label {
			font-size: 11px;
			color: #64748b;
		}

		.ahbn-status-pill {
			display: inline-block;
			padding: 4px 10px;
			border-radius: 9999px;
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
		}

		.status-active-now { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
		.status-disabled   { background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; }

		.ahbn-action-btns {
			display: inline-flex;
			gap: 5px;
		}

		.ahbn-quick-btn {
			width: 28px;
			height: 28px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 6px;
			color: #475569;
			cursor: pointer;
			text-decoration: none;
			transition: all 0.15s ease;
		}

		.ahbn-quick-btn .dashicons {
			font-size: 15px;
			width: 15px;
			height: 15px;
		}

		.ahbn-quick-btn:hover {
			background: #0a192f;
			color: #ffffff;
			border-color: #0a192f;
		}

		.ahbn-quick-btn.is-delete:hover {
			background: #e11d48;
			border-color: #e11d48;
			color: #ffffff;
		}

		.ahbn-empty-row {
			text-align: center;
			padding: 50px !important;
			color: #94a3b8;
		}

		.ahbn-empty-row .dashicons {
			font-size: 36px;
			width: 36px;
			height: 36px;
			color: #cbd5e1;
		}

		.ahbn-empty-row p {
			margin: 8px 0 0;
			font-size: 13px;
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
	</style>
	<?php
}