<?php
/**
 * Housekeeping, Cleaning Schedules & Room Turnaround Management.
 *
 * Built on high-performance custom relational database tables.
 * Styled with Champagne Gold & Deep Navy enterprise UI system.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Housekeeping Management Tab.
 *
 * @return void
 */
function ahbn_housekeeping_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	global $wpdb;
	$table_rooms        = $wpdb->prefix . 'ahbn_rooms';
	$table_housekeeping = $wpdb->prefix . 'ahbn_housekeeping';
	$table_staff        = $wpdb->prefix . 'ahbn_staff';

	// 1. Process Form Updates.
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_housekeeping'] ) ) {
		$nonce = isset( $_POST['ahbn_hk_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_hk_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'ahbn_save_housekeeping_action' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please refresh.', 'awesome-hotel-booking' ) . '</p></div>';
		} else {
			$hk_id          = isset( $_POST['hk_id'] ) ? absint( wp_unslash( $_POST['hk_id'] ) ) : 0;
			$clean_status   = isset( $_POST['clean_status'] ) ? sanitize_key( wp_unslash( $_POST['clean_status'] ) ) : 'dirty';
			$priority       = isset( $_POST['priority'] ) ? sanitize_key( wp_unslash( $_POST['priority'] ) ) : 'normal';
			$inspector_stat = isset( $_POST['inspection_status'] ) ? sanitize_key( wp_unslash( $_POST['inspection_status'] ) ) : 'pending';
			$staff_id       = isset( $_POST['housekeeper_id'] ) ? absint( wp_unslash( $_POST['housekeeper_id'] ) ) : 0;
			$maint_notes    = isset( $_POST['maintenance_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['maintenance_notes'] ) ) : '';

			$update_data = array(
				'clean_status'      => $clean_status,
				'priority'          => $priority,
				'inspection_status' => $inspector_stat,
				'housekeeper_id'    => $staff_id,
				'maintenance_notes' => $maint_notes,
			);
			$data_formats = array( '%s', '%s', '%s', '%d', '%s' );

			if ( 'clean' === $clean_status ) {
				$update_data['last_cleaned_at'] = current_time( 'mysql' );
				$data_formats[]                 = '%s';
			}

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$table_housekeeping,
				$update_data,
				array( 'id' => $hk_id ),
				$data_formats,
				array( '%d' )
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			// Invalidate cached radar stats on the main dashboard.
			do_action( 'ahbn_after_room_saved' );

			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Room housekeeping status updated.', 'awesome-hotel-booking' ) . '</p></div>';
		}
	}

	// 2. Ensure Every Room Has a Corresponding Housekeeping Record (Auto-sync).
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		"INSERT INTO {$table_housekeeping} (room_id, clean_status, priority, inspection_status)
		 SELECT r.id, 'dirty', 'normal', 'pending'
		 FROM {$table_rooms} r
		 LEFT JOIN {$table_housekeeping} hk ON r.id = hk.room_id
		 WHERE hk.id IS NULL AND r.status = 'publish'"
	);

	// 3. Status Filter Param.
	$filter_status = isset( $_GET['filter_status'] ) ? sanitize_key( wp_unslash( $_GET['filter_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$where_clause = "WHERE r.status = 'publish'";
	$where_params = array();

	if ( ! empty( $filter_status ) ) {
		$where_clause  .= ' AND hk.clean_status = %s';
		$where_params[] = $filter_status;
	}

	// 4. Query All Housekeeping Items Joined with Room Details & Staff Name.
	$sql = "SELECT 
				hk.id AS hk_id,
				hk.clean_status,
				hk.priority,
				hk.inspection_status,
				hk.maintenance_notes,
				hk.last_cleaned_at,
				hk.housekeeper_id,
				r.id AS room_id,
				r.room_number,
				r.title AS room_title,
				r.room_type,
				s.full_name AS staff_name
			 FROM {$table_housekeeping} hk
			 INNER JOIN {$table_rooms} r ON hk.room_id = r.id
			 LEFT JOIN {$table_staff} s ON hk.housekeeper_id = s.id
			 {$where_clause}
			 ORDER BY 
				CASE hk.priority 
					WHEN 'urgent' THEN 1 
					WHEN 'high' THEN 2 
					WHEN 'normal' THEN 3 
					ELSE 4 
				END ASC,
				r.room_number ASC";

	if ( ! empty( $where_params ) ) {
		$items = $wpdb->get_results( $wpdb->prepare( $sql, $where_params ) );
	} else {
		$items = $wpdb->get_results( $sql );
	}

	// 5. Aggregate Counters.
	$stats = $wpdb->get_row(
		"SELECT 
			COUNT(CASE WHEN clean_status = 'clean' THEN 1 END) AS total_clean,
			COUNT(CASE WHEN clean_status = 'dirty' THEN 1 END) AS total_dirty,
			COUNT(CASE WHEN clean_status = 'maintenance' THEN 1 END) AS total_maint,
			COUNT(CASE WHEN clean_status = 'inspecting' THEN 1 END) AS total_inspect
		 FROM {$table_housekeeping}"
	);

	// 6. Fetch Housekeeping Staff Members for Assignment.
	$cleaners = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT id, full_name, shift FROM {$table_staff} WHERE status = %s ORDER BY full_name ASC",
			'active'
		)
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	$total_clean   = $stats ? (int) $stats->total_clean : 0;
	$total_dirty   = $stats ? (int) $stats->total_dirty : 0;
	$total_maint   = $stats ? (int) $stats->total_maint : 0;
	$total_inspect = $stats ? (int) $stats->total_inspect : 0;
	$total_all     = $total_clean + $total_dirty + $total_maint + $total_inspect;
	$ready_pct     = $total_all > 0 ? round( ( $total_clean / $total_all ) * 100 ) : 100;
	$base_url      = admin_url( 'admin.php?page=awesome_hotel_booking&tab=housekeeping' );
	?>

	<div class="ahbn-hk-wrap">
		<!-- Header -->
		<header class="ahbn-hk-header">
			<div>
				<h2 class="ahbn-hk-title"><?php esc_html_e( 'Housekeeping & Room Readiness', 'awesome-hotel-booking' ); ?></h2>
				<p class="ahbn-hk-subtitle">
					<?php esc_html_e( 'Manage room turnaround schedules, assign cleaners, inspect ready suites, and track maintenance tickets.', 'awesome-hotel-booking' ); ?>
				</p>
			</div>
			<div class="ahbn-hk-readiness-pill">
				<span class="readiness-label"><?php esc_html_e( 'Inventory Ready', 'awesome-hotel-booking' ); ?></span>
				<strong class="readiness-pct"><?php echo esc_html( (string) $ready_pct ); ?>%</strong>
			</div>
		</header>

		<!-- Operational Metric Cards -->
		<div class="ahbn-stats-grid">
			<a href="<?php echo esc_url( $base_url ); ?>" class="ahbn-stat-card <?php echo empty( $filter_status ) ? 'ahbn-stat-active' : ''; ?>">
				<span class="ahbn-stat-label"><?php esc_html_e( 'All Suites', 'awesome-hotel-booking' ); ?></span>
				<span class="ahbn-stat-value"><?php echo esc_html( (string) $total_all ); ?></span>
			</a>

			<a href="<?php echo esc_url( add_query_arg( 'filter_status', 'clean', $base_url ) ); ?>" class="ahbn-stat-card ahbn-stat-clean <?php echo 'clean' === $filter_status ? 'ahbn-stat-active' : ''; ?>">
				<span class="ahbn-stat-label"><?php esc_html_e( 'Clean & Ready', 'awesome-hotel-booking' ); ?></span>
				<span class="ahbn-stat-value"><?php echo esc_html( (string) $total_clean ); ?></span>
			</a>

			<a href="<?php echo esc_url( add_query_arg( 'filter_status', 'dirty', $base_url ) ); ?>" class="ahbn-stat-card ahbn-stat-dirty <?php echo 'dirty' === $filter_status ? 'ahbn-stat-active' : ''; ?>">
				<span class="ahbn-stat-label"><?php esc_html_e( 'Needs Cleaning', 'awesome-hotel-booking' ); ?></span>
				<span class="ahbn-stat-value"><?php echo esc_html( (string) $total_dirty ); ?></span>
			</a>

			<a href="<?php echo esc_url( add_query_arg( 'filter_status', 'maintenance', $base_url ) ); ?>" class="ahbn-stat-card ahbn-stat-maint <?php echo 'maintenance' === $filter_status ? 'ahbn-stat-active' : ''; ?>">
				<span class="ahbn-stat-label"><?php esc_html_e( 'Out of Order / Repairs', 'awesome-hotel-booking' ); ?></span>
				<span class="ahbn-stat-value"><?php echo esc_html( (string) $total_maint ); ?></span>
			</a>
		</div>

		<!-- Main Housekeeping Table -->
		<div class="ahbn-table-container">
			<table class="ahbn-hk-table widefat striped">
				<thead>
					<tr>
						<th style="width: 110px;"><?php esc_html_e( 'Room #', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Suite Name & Type', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Cleanliness Status', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Priority', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Assigned Cleaner', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Last Cleaned', 'awesome-hotel-booking' ); ?></th>
						<th style="text-align:right; width: 120px;"><?php esc_html_e( 'Action', 'awesome-hotel-booking' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $items ) ) : ?>
						<?php foreach ( $items as $row ) :
							$status_key  = sanitize_key( $row->clean_status );
							$badge_class = 'hk-status-dirty';
							$status_text = __( 'Needs Cleaning', 'awesome-hotel-booking' );

							if ( 'clean' === $status_key ) {
								$badge_class = 'hk-status-clean';
								$status_text = __( 'Clean & Ready', 'awesome-hotel-booking' );
							} elseif ( 'maintenance' === $status_key ) {
								$badge_class = 'hk-status-maint';
								$status_text = __( 'Maintenance', 'awesome-hotel-booking' );
							} elseif ( 'inspecting' === $status_key ) {
								$badge_class = 'hk-status-inspect';
								$status_text = __( 'Inspection Pending', 'awesome-hotel-booking' );
							}

							$priority_key   = sanitize_key( $row->priority );
							$priority_color = '#64748b';
							if ( 'urgent' === $priority_key ) {
								$priority_color = '#e11d48';
							} elseif ( 'high' === $priority_key ) {
								$priority_color = '#d97706';
							}
							?>
							<tr>
								<td>
									<span class="ahbn-room-badge">
										#<?php echo esc_html( $row->room_number ); ?>
									</span>
								</td>
								<td>
									<strong style="color: #0a192f;"><?php echo esc_html( $row->room_title ); ?></strong>
									<span class="ahbn-suite-meta">
										<?php echo esc_html( $row->room_type ); ?>
									</span>
									<?php if ( ! empty( $row->maintenance_notes ) ) : ?>
										<small class="ahbn-maint-ticket">
											&#9888; <?php echo esc_html( $row->maintenance_notes ); ?>
										</small>
									<?php endif; ?>
								</td>
								<td>
									<span class="ahbn-status-pill <?php echo esc_attr( $badge_class ); ?>">
										<?php echo esc_html( $status_text ); ?>
									</span>
								</td>
								<td>
									<strong style="color: <?php echo esc_attr( $priority_color ); ?>; font-size:11px; text-transform:uppercase;">
										<?php echo esc_html( ucfirst( $priority_key ) ); ?>
									</strong>
								</td>
								<td>
									<?php if ( ! empty( $row->staff_name ) ) : ?>
										<span class="ahbn-staff-assigned">
											<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
											<?php echo esc_html( $row->staff_name ); ?>
										</span>
									<?php else : ?>
										<span style="color: #94a3b8; font-style: italic; font-size: 12px;">
											<?php esc_html_e( 'Unassigned', 'awesome-hotel-booking' ); ?>
										</span>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( ! empty( $row->last_cleaned_at ) && '0000-00-00 00:00:00' !== $row->last_cleaned_at ) : ?>
										<span style="font-size:12px; color: #475569;">
											<?php echo esc_html( human_time_diff( strtotime( $row->last_cleaned_at ), time() ) . ' ago' ); ?>
										</span>
									<?php else : ?>
										<span style="color: #94a3b8; font-size: 12px;">—</span>
									<?php endif; ?>
								</td>
								<td style="text-align:right;">
									<button 
										type="button" 
										class="ahbn-btn-edit-hk js-ahbn-open-hk-modal"
										data-hk-id="<?php echo esc_attr( (string) $row->hk_id ); ?>"
										data-room-no="<?php echo esc_attr( (string) $row->room_number ); ?>"
										data-room-title="<?php echo esc_attr( $row->room_title ); ?>"
										data-status="<?php echo esc_attr( $status_key ); ?>"
										data-priority="<?php echo esc_attr( $priority_key ); ?>"
										data-cleaner="<?php echo esc_attr( (string) $row->housekeeper_id ); ?>"
										data-notes="<?php echo esc_attr( $row->maintenance_notes ); ?>"
									>
										<span class="dashicons dashicons-edit" aria-hidden="true"></span>
										<?php esc_html_e( 'Update', 'awesome-hotel-booking' ); ?>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="7" style="text-align:center; padding: 50px; color: #94a3b8;">
								<?php esc_html_e( 'No rooms found under the selected housekeeping filter.', 'awesome-hotel-booking' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Modal Dialog for Fast Room Status Updates -->
	<div id="ahbn-hk-modal" class="ahbn-modal-backdrop" style="display:none;" role="dialog" aria-modal="true">
		<div class="ahbn-modal-card">
			<div class="ahbn-modal-header">
				<h3 style="margin:0; font-size: 16px; color: #0a192f; font-weight: 800;" id="ahbn-modal-title">
					<?php esc_html_e( 'Update Room Status', 'awesome-hotel-booking' ); ?>
				</h3>
				<button type="button" class="ahbn-modal-close js-ahbn-close-modal">&times;</button>
			</div>

			<form method="post" action="">
				<?php wp_nonce_field( 'ahbn_save_housekeeping_action', 'ahbn_hk_nonce' ); ?>
				<input type="hidden" name="hk_id" id="modal_hk_id" value="0">

				<div class="ahbn-modal-body">
					<div class="ahbn-field-group">
						<label class="ahbn-label"><?php esc_html_e( 'Cleanliness Status', 'awesome-hotel-booking' ); ?></label>
						<select name="clean_status" id="modal_clean_status" class="ahbn-select">
							<option value="clean"><?php esc_html_e( 'Clean & Ready (Inspected)', 'awesome-hotel-booking' ); ?></option>
							<option value="inspecting"><?php esc_html_e( 'Cleaned (Awaiting Inspection)', 'awesome-hotel-booking' ); ?></option>
							<option value="dirty"><?php esc_html_e( 'Needs Cleaning (Dirty)', 'awesome-hotel-booking' ); ?></option>
							<option value="maintenance"><?php esc_html_e( 'Out of Order / Under Maintenance', 'awesome-hotel-booking' ); ?></option>
						</select>
					</div>

					<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 14px;">
						<div class="ahbn-field-group">
							<label class="ahbn-label"><?php esc_html_e( 'Turnaround Priority', 'awesome-hotel-booking' ); ?></label>
							<select name="priority" id="modal_priority" class="ahbn-select">
								<option value="normal"><?php esc_html_e( 'Normal', 'awesome-hotel-booking' ); ?></option>
								<option value="high"><?php esc_html_e( 'High', 'awesome-hotel-booking' ); ?></option>
								<option value="urgent"><?php esc_html_e( 'Urgent (Arriving Soon)', 'awesome-hotel-booking' ); ?></option>
							</select>
						</div>

						<div class="ahbn-field-group">
							<label class="ahbn-label"><?php esc_html_e( 'Assign Housekeeper', 'awesome-hotel-booking' ); ?></label>
							<select name="housekeeper_id" id="modal_cleaner" class="ahbn-select">
								<option value="0"><?php esc_html_e( '— Unassigned —', 'awesome-hotel-booking' ); ?></option>
								<?php if ( ! empty( $cleaners ) ) : ?>
									<?php foreach ( $cleaners as $cleaner ) : ?>
										<option value="<?php echo esc_attr( (string) $cleaner->id ); ?>">
											<?php echo esc_html( $cleaner->full_name . ' (' . ucfirst( $cleaner->shift ) . ')' ); ?>
										</option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</div>
					</div>

					<div class="ahbn-field-group">
						<label class="ahbn-label"><?php esc_html_e( 'Maintenance / Damage Notes', 'awesome-hotel-booking' ); ?></label>
						<textarea name="maintenance_notes" id="modal_notes" rows="3" class="ahbn-textarea" placeholder="<?php esc_attr_e( 'e.g. Light bulb in bathroom needs replacement, AC filter checked.', 'awesome-hotel-booking' ); ?>"></textarea>
					</div>
				</div>

				<div class="ahbn-modal-footer">
					<button type="button" class="ahbn-btn-cancel js-ahbn-close-modal">
						<?php esc_html_e( 'Cancel', 'awesome-hotel-booking' ); ?>
					</button>
					<button type="submit" name="ahbn_save_housekeeping" class="ahbn-btn-save">
						<?php esc_html_e( 'Save Status', 'awesome-hotel-booking' ); ?>
					</button>
				</div>
			</form>
		</div>
	</div>

	<style>
		.ahbn-hk-wrap {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-hk-header {
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

		.ahbn-hk-title {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 24px;
			font-weight: 700;
			color: #0a192f;
			margin: 0 0 4px;
		}

		.ahbn-hk-subtitle {
			color: #64748b;
			margin: 0;
			font-size: 13px;
		}

		.ahbn-hk-readiness-pill {
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 10px 20px;
			text-align: right;
		}

		.readiness-label {
			display: block;
			font-size: 10px;
			font-weight: 800;
			text-transform: uppercase;
			color: #64748b;
			letter-spacing: 0.06em;
		}

		.readiness-pct {
			font-family: 'JetBrains Mono', monospace;
			font-size: 24px;
			font-weight: 800;
			color: #059669;
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
			text-decoration: none;
			color: inherit;
			transition: all 0.2s ease;
		}

		.ahbn-stat-card:hover {
			border-color: #cbd5e1;
			box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
			transform: translateY(-2px);
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
			font-size: 26px;
			font-weight: 800;
			color: #0a192f;
		}

		.ahbn-stat-clean { border-left: 4px solid #10b981; }
		.ahbn-stat-clean .ahbn-stat-label,
		.ahbn-stat-clean .ahbn-stat-value { color: #059669; }

		.ahbn-stat-dirty { border-left: 4px solid #f59e0b; }
		.ahbn-stat-dirty .ahbn-stat-label,
		.ahbn-stat-dirty .ahbn-stat-value { color: #d97706; }

		.ahbn-stat-maint { border-left: 4px solid #ef4444; }
		.ahbn-stat-maint .ahbn-stat-label,
		.ahbn-stat-maint .ahbn-stat-value { color: #dc2626; }

		.ahbn-stat-active {
			border-color: #0a192f !important;
			background: #fbf8f3 !important;
		}

		/* Table Elements */
		.ahbn-table-container {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			overflow: hidden;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-hk-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 13px;
		}

		.ahbn-hk-table th {
			background: #f8fafc !important;
			padding: 12px 16px !important;
			font-weight: 700 !important;
			font-size: 11px !important;
			text-transform: uppercase !important;
			letter-spacing: 0.05em !important;
			color: #64748b !important;
			border-bottom: 1px solid #e2e8f0 !important;
		}

		.ahbn-hk-table td {
			padding: 14px 16px !important;
			vertical-align: middle !important;
			border-bottom: 1px solid #f1f5f9 !important;
		}

		.ahbn-room-badge {
			font-family: 'JetBrains Mono', monospace;
			background: #f1f5f9;
			color: #0a192f;
			font-weight: 800;
			font-size: 12px;
			padding: 4px 8px;
			border-radius: 6px;
			border: 1px solid #e2e8f0;
			display: inline-block;
		}

		.ahbn-suite-meta {
			display: block;
			font-size: 11px;
			color: #64748b;
			text-transform: uppercase;
			margin-top: 2px;
		}

		.ahbn-maint-ticket {
			display: block;
			color: #dc2626;
			margin-top: 4px;
			font-weight: 600;
			font-size: 11px;
		}

		.ahbn-status-pill {
			display: inline-block;
			padding: 4px 10px;
			border-radius: 9999px;
			font-size: 11px;
			font-weight: 700;
		}

		.hk-status-clean   { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
		.hk-status-dirty   { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
		.hk-status-maint   { background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; }
		.hk-status-inspect { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }

		.ahbn-staff-assigned {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			font-weight: 600;
			color: #334155;
		}

		.ahbn-staff-assigned .dashicons {
			font-size: 16px;
			width: 16px;
			height: 16px;
			color: #c5a880;
		}

		.ahbn-btn-edit-hk {
			display: inline-flex;
			align-items: center;
			gap: 5px;
			padding: 6px 12px;
			background: #ffffff;
			border: 1px solid #cbd5e1;
			border-radius: 6px;
			color: #334155;
			font-size: 12px;
			font-weight: 700;
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.ahbn-btn-edit-hk .dashicons {
			font-size: 14px;
			width: 14px;
			height: 14px;
		}

		.ahbn-btn-edit-hk:hover {
			background: #0a192f;
			color: #ffffff;
			border-color: #0a192f;
		}

		/* Modal UI */
		.ahbn-modal-backdrop {
			position: fixed;
			inset: 0;
			background: rgba(10, 25, 47, 0.65);
			z-index: 99999;
			display: flex;
			align-items: center;
			justify-content: center;
			backdrop-filter: blur(2px);
		}

		.ahbn-modal-card {
			background: #ffffff;
			width: 100%;
			max-width: 520px;
			border-radius: 16px;
			box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
			overflow: hidden;
			animation: ahbnModalFade 0.2s ease-out;
		}

		.ahbn-modal-header {
			padding: 20px 24px;
			border-bottom: 1px solid #e2e8f0;
			display: flex;
			justify-content: space-between;
			align-items: center;
			background: #fafafa;
		}

		.ahbn-modal-close {
			background: none;
			border: none;
			font-size: 24px;
			line-height: 1;
			cursor: pointer;
			color: #94a3b8;
		}

		.ahbn-modal-close:hover {
			color: #0a192f;
		}

		.ahbn-modal-body {
			padding: 24px;
			display: flex;
			flex-direction: column;
			gap: 16px;
		}

		.ahbn-field-group {
			display: flex;
			flex-direction: column;
			gap: 6px;
		}

		.ahbn-label {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #334155;
		}

		.ahbn-select,
		.ahbn-textarea {
			width: 100%;
			border: 1px solid #cbd5e1 !important;
			border-radius: 8px !important;
			padding: 8px 12px !important;
			font-size: 13px !important;
			background: #ffffff !important;
			color: #0f172a !important;
			box-shadow: none !important;
		}

		.ahbn-select:focus,
		.ahbn-textarea:focus {
			border-color: #0a192f !important;
			box-shadow: 0 0 0 2px rgba(10, 25, 47, 0.1) !important;
		}

		.ahbn-modal-footer {
			padding: 16px 24px;
			background: #f8fafc;
			border-top: 1px solid #e2e8f0;
			display: flex;
			justify-content: flex-end;
			gap: 10px;
		}

		.ahbn-btn-cancel {
			padding: 8px 16px;
			background: #ffffff;
			border: 1px solid #cbd5e1;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 600;
			color: #475569;
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.ahbn-btn-cancel:hover {
			background: #f1f5f9;
			color: #0f172a;
		}

		.ahbn-btn-save {
			padding: 8px 18px;
			background: #0a192f;
			border: 1px solid #0a192f;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 700;
			color: #ffffff;
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.ahbn-btn-save:hover {
			background: #c5a880;
			border-color: #c5a880;
			color: #0a192f;
		}

		@keyframes ahbnModalFade {
			from { opacity: 0; transform: translateY(-10px); }
			to { opacity: 1; transform: translateY(0); }
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

	<script>
	jQuery(document).ready(function($) {
		const $modal = $('#ahbn-hk-modal');

		$('.js-ahbn-open-hk-modal').on('click', function(e) {
			e.preventDefault();
			const $btn = $(this);
			const hkId = $btn.data('hk-id');
			const roomNo = $btn.data('room-no');
			const roomTitle = $btn.data('room-title');
			const status = $btn.data('status');
			const priority = $btn.data('priority');
			const cleaner = $btn.data('cleaner');
			const notes = $btn.data('notes');

			$('#modal_hk_id').val(hkId);
			$('#ahbn-modal-title').text('<?php echo esc_js( __( 'Update Suite #', 'awesome-hotel-booking' ) ); ?>' + roomNo + ' - ' + roomTitle);
			$('#modal_clean_status').val(status);
			$('#modal_priority').val(priority);
			$('#modal_cleaner').val(cleaner);
			$('#modal_notes').val(notes);

			$modal.fadeIn(150);
		});

		$('.js-ahbn-close-modal').on('click', function() {
			$modal.fadeOut(150);
		});

		// Close modal on escape key or backdrop click.
		$(document).on('keydown', function(e) {
			if (e.key === 'Escape') $modal.fadeOut(150);
		});
		$modal.on('click', function(e) {
			if ($(e.target).hasClass('ahbn-modal-backdrop')) $modal.fadeOut(150);
		});
	});
	</script>
	<?php
}