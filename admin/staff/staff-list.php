<?php
/**
 * Staff Directory Table & Metrics View.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the staff directory list view.
 *
 * @return void
 */
function ahbn_render_staff_list() {
	global $wpdb;
	$table_staff    = $wpdb->prefix . 'ahbn_staff';
	$base_staff_url = admin_url( 'admin.php?page=awesome_hotel_booking&tab=staff' );

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$filter_role   = isset( $_GET['filter_role'] ) ? sanitize_key( wp_unslash( $_GET['filter_role'] ) ) : '';
	$filter_status = isset( $_GET['filter_status'] ) ? sanitize_key( wp_unslash( $_GET['filter_status'] ) ) : '';
	$search_query  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$roles_map = array(
		'management'   => __( 'Management / Admin', 'awesome-hotel-booking' ),
		'reception'    => __( 'Front Desk / Reception', 'awesome-hotel-booking' ),
		'housekeeping' => __( 'Housekeeping Staff', 'awesome-hotel-booking' ),
		'concierge'    => __( 'Concierge & Guest Relations', 'awesome-hotel-booking' ),
		'maintenance'  => __( 'Facility & Maintenance', 'awesome-hotel-booking' ),
	);

	$shifts_map = array(
		'morning'  => __( 'Morning (07:00 - 15:00)', 'awesome-hotel-booking' ),
		'evening'  => __( 'Evening (15:00 - 23:00)', 'awesome-hotel-booking' ),
		'night'    => __( 'Night Audit (23:00 - 07:00)', 'awesome-hotel-booking' ),
		'flexible' => __( 'Flexible / On-Call', 'awesome-hotel-booking' ),
	);

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$metrics = $wpdb->get_row(
		"SELECT 
			COUNT(*) AS total_count,
			COUNT(CASE WHEN status = 'active' THEN 1 END) AS active_count,
			COUNT(CASE WHEN role = 'housekeeping' AND status = 'active' THEN 1 END) AS housekeeping_count,
			COUNT(CASE WHEN role = 'reception' AND status = 'active' THEN 1 END) AS reception_count
		 FROM {$table_staff}"
	);

	$total_count        = $metrics ? (int) $metrics->total_count : 0;
	$active_count       = $metrics ? (int) $metrics->active_count : 0;
	$housekeeping_count = $metrics ? (int) $metrics->housekeeping_count : 0;
	$reception_count    = $metrics ? (int) $metrics->reception_count : 0;

	// Build filter queries.
	$where_clauses = array( '1=1' );
	$params        = array();

	if ( ! empty( $search_query ) ) {
		$where_clauses[] = '(full_name LIKE %s OR phone LIKE %s OR email LIKE %s)';
		$like_val        = '%' . $wpdb->esc_like( $search_query ) . '%';
		$params[]        = $like_val;
		$params[]        = $like_val;
		$params[]        = $like_val;
	}

	if ( ! empty( $filter_role ) ) {
		$where_clauses[] = 'role = %s';
		$params[]        = $filter_role;
	}

	if ( ! empty( $filter_status ) ) {
		$where_clauses[] = 'status = %s';
		$params[]        = $filter_status;
	}

	$where_sql = implode( ' AND ', $where_clauses );

	if ( ! empty( $params ) ) {
		$staff_members = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table_staff} WHERE {$where_sql} ORDER BY id DESC", $params )
		);
	} else {
		$staff_members = $wpdb->get_results( "SELECT * FROM {$table_staff} WHERE {$where_sql} ORDER BY id DESC" );
	}
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	?>

	<!-- Header Toolbar -->
	<header class="ahbn-staff-header">
		<div>
			<h2 class="ahbn-staff-title"><?php esc_html_e( 'Hotel Staff & Shift Roster', 'awesome-hotel-booking' ); ?></h2>
			<p class="ahbn-staff-subtitle">
				<?php esc_html_e( 'Manage hospitality departments, cleaning crew assignments, reception desks, and employee shift schedules.', 'awesome-hotel-booking' ); ?>
			</p>
		</div>
	</header>

	<!-- Roster Metrics Cards -->
	<div class="ahbn-stats-grid">
		<a href="<?php echo esc_url( $base_staff_url ); ?>" class="ahbn-stat-card <?php echo ( empty( $filter_role ) && empty( $filter_status ) ) ? 'ahbn-stat-active' : ''; ?>">
			<span class="ahbn-stat-label"><?php esc_html_e( 'Total Hotel Staff', 'awesome-hotel-booking' ); ?></span>
			<span class="ahbn-stat-value"><?php echo esc_html( (string) $total_count ); ?></span>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'filter_status', 'active', $base_staff_url ) ); ?>" class="ahbn-stat-card ahbn-stat-active-duty <?php echo ( 'active' === $filter_status && empty( $filter_role ) ) ? 'ahbn-stat-active' : ''; ?>">
			<span class="ahbn-stat-label"><?php esc_html_e( 'Active on Duty', 'awesome-hotel-booking' ); ?></span>
			<span class="ahbn-stat-value"><?php echo esc_html( (string) $active_count ); ?></span>
		</a>
		<a href="<?php echo esc_url( add_query_arg( array( 'filter_role' => 'housekeeping', 'filter_status' => 'active' ), $base_staff_url ) ); ?>" class="ahbn-stat-card ahbn-stat-hk <?php echo ( 'housekeeping' === $filter_role && 'active' === $filter_status ) ? 'ahbn-stat-active' : ''; ?>">
			<span class="ahbn-stat-label"><?php esc_html_e( 'Housekeeping Crew', 'awesome-hotel-booking' ); ?></span>
			<span class="ahbn-stat-value"><?php echo esc_html( (string) $housekeeping_count ); ?></span>
		</a>
		<a href="<?php echo esc_url( add_query_arg( array( 'filter_role' => 'reception', 'filter_status' => 'active' ), $base_staff_url ) ); ?>" class="ahbn-stat-card ahbn-stat-rec <?php echo ( 'reception' === $filter_role && 'active' === $filter_status ) ? 'ahbn-stat-active' : ''; ?>">
			<span class="ahbn-stat-label"><?php esc_html_e( 'Reception Desk', 'awesome-hotel-booking' ); ?></span>
			<span class="ahbn-stat-value"><?php echo esc_html( (string) $reception_count ); ?></span>
		</a>
	</div>

	<!-- Filter Bar -->
	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="ahbn-staff-filter-bar">
		<input type="hidden" name="page" value="awesome_hotel_booking">
		<input type="hidden" name="tab" value="staff">

		<div class="ahbn-filter-controls">
			<div class="ahbn-search-field">
				<input 
					type="text" 
					name="s" 
					class="ahbn-input" 
					placeholder="<?php esc_attr_e( 'Search by employee name, phone, or email...', 'awesome-hotel-booking' ); ?>"
					value="<?php echo esc_attr( $search_query ); ?>"
				>
			</div>

			<div style="min-width: 180px;">
				<select name="filter_role" class="ahbn-select" onchange="this.form.submit();">
					<option value=""><?php esc_html_e( 'All Departments', 'awesome-hotel-booking' ); ?></option>
					<?php foreach ( $roles_map as $r_key => $r_label ) : ?>
						<option value="<?php echo esc_attr( $r_key ); ?>" <?php selected( $filter_role, $r_key ); ?>>
							<?php echo esc_html( $r_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<div style="min-width: 150px;">
				<select name="filter_status" class="ahbn-select" onchange="this.form.submit();">
					<option value=""><?php esc_html_e( 'All Statuses', 'awesome-hotel-booking' ); ?></option>
					<option value="active" <?php selected( $filter_status, 'active' ); ?>><?php esc_html_e( 'Active', 'awesome-hotel-booking' ); ?></option>
					<option value="leave" <?php selected( $filter_status, 'leave' ); ?>><?php esc_html_e( 'On Leave', 'awesome-hotel-booking' ); ?></option>
					<option value="inactive" <?php selected( $filter_status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'awesome-hotel-booking' ); ?></option>
				</select>
			</div>

			<button type="submit" class="ahbn-btn-search">
				<span class="dashicons dashicons-search" aria-hidden="true"></span>
				<?php esc_html_e( 'Filter', 'awesome-hotel-booking' ); ?>
			</button>

			<?php if ( ! empty( $filter_role ) || ! empty( $filter_status ) || ! empty( $search_query ) ) : ?>
				<a href="<?php echo esc_url( $base_staff_url ); ?>" class="ahbn-clear-filters">
					<?php esc_html_e( 'Reset Filters', 'awesome-hotel-booking' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</form>

	<!-- Staff Roster Table -->
	<div class="ahbn-table-container">
		<table class="ahbn-staff-table widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Employee', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Department / Role', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Contact Info', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Shift Schedule', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Status', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Hire Date', 'awesome-hotel-booking' ); ?></th>
					<th style="text-align: right; width: 140px;"><?php esc_html_e( 'Actions', 'awesome-hotel-booking' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $staff_members ) ) : ?>
					<?php foreach ( $staff_members as $member ) :
						$status_val   = sanitize_key( $member->status );
						$status_badge = 'status-active';
						$status_label = __( 'Active', 'awesome-hotel-booking' );

						if ( 'leave' === $status_val ) {
							$status_badge = 'status-leave';
							$status_label = __( 'On Leave', 'awesome-hotel-booking' );
						} elseif ( 'inactive' === $status_val ) {
							$status_badge = 'status-inactive';
							$status_label = __( 'Inactive', 'awesome-hotel-booking' );
						}

						$role_display  = isset( $roles_map[ $member->role ] ) ? $roles_map[ $member->role ] : ucfirst( $member->role );
						$shift_display = isset( $shifts_map[ $member->shift ] ) ? $shifts_map[ $member->shift ] : ucfirst( $member->shift );
						$edit_link     = add_query_arg( array( 'action' => 'edit', 'staff_id' => $member->id ), $base_staff_url );
						$hire_formatted = ! empty( $member->hire_date ) ? wp_date( get_option( 'date_format' ), strtotime( $member->hire_date ) ) : '—';
						?>
						<tr>
							<td>
								<div class="ahbn-emp-cell">
									<div class="ahbn-emp-avatar">
										<?php echo esc_html( strtoupper( substr( $member->full_name, 0, 1 ) ) ); ?>
									</div>
									<div>
										<strong class="ahbn-emp-name"><?php echo esc_html( $member->full_name ); ?></strong>
										<?php if ( $member->user_id > 0 ) : ?>
											<small class="ahbn-emp-linked">
												<?php
												printf(
													/* translators: %d: WP user ID */
													esc_html__( 'Linked User #%d', 'awesome-hotel-booking' ),
													absint( $member->user_id )
												);
												?>
											</small>
										<?php endif; ?>
									</div>
								</div>
							</td>
							<td>
								<span class="ahbn-role-badge">
									<?php echo esc_html( $role_display ); ?>
								</span>
							</td>
							<td>
								<div class="ahbn-contact-stack">
									<?php if ( ! empty( $member->phone ) ) : ?>
										<div>
											<span class="dashicons dashicons-phone" aria-hidden="true"></span>
											<span><?php echo esc_html( $member->phone ); ?></span>
										</div>
									<?php endif; ?>
									<?php if ( ! empty( $member->email ) ) : ?>
										<div>
											<span class="dashicons dashicons-email" aria-hidden="true"></span>
											<span><?php echo esc_html( $member->email ); ?></span>
										</div>
									<?php endif; ?>
									<?php if ( empty( $member->phone ) && empty( $member->email ) ) : ?>
										<span style="color: #94a3b8; font-size: 12px;">—</span>
									<?php endif; ?>
								</div>
							</td>
							<td>
								<span class="ahbn-shift-text">
									<?php echo esc_html( $shift_display ); ?>
								</span>
							</td>
							<td>
								<span class="ahbn-status-pill <?php echo esc_attr( $status_badge ); ?>">
									<?php echo esc_html( $status_label ); ?>
								</span>
							</td>
							<td>
								<span style="font-size: 12px; color: #64748b;">
									<?php echo esc_html( $hire_formatted ); ?>
								</span>
							</td>
							<td style="text-align: right;">
								<div class="ahbn-action-btns">
									<!-- View Profile Action Button (Missing icon restored) -->
									<button 
										type="button" 
										class="ahbn-quick-btn js-ahbn-view-staff" 
										title="<?php esc_attr_e( 'View Employee Card', 'awesome-hotel-booking' ); ?>"
										data-name="<?php echo esc_attr( $member->full_name ); ?>"
										data-role="<?php echo esc_attr( $role_display ); ?>"
										data-status="<?php echo esc_attr( $status_label ); ?>"
										data-status-class="<?php echo esc_attr( $status_badge ); ?>"
										data-shift="<?php echo esc_attr( $shift_display ); ?>"
										data-phone="<?php echo esc_attr( $member->phone ? $member->phone : '—' ); ?>"
										data-email="<?php echo esc_attr( $member->email ? $member->email : '—' ); ?>"
										data-hire="<?php echo esc_attr( $hire_formatted ); ?>"
										data-userid="<?php echo esc_attr( (string) $member->user_id ); ?>"
										data-notes="<?php echo esc_attr( $member->notes ? $member->notes : __( 'No internal notes on file.', 'awesome-hotel-booking' ) ); ?>"
									>
										<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
									</button>

									<!-- Edit Profile Action Button -->
									<a href="<?php echo esc_url( $edit_link ); ?>" class="ahbn-quick-btn" title="<?php esc_attr_e( 'Edit Staff Member', 'awesome-hotel-booking' ); ?>">
										<span class="dashicons dashicons-edit" aria-hidden="true"></span>
									</a>

									<!-- Delete Profile Action Button -->
									<form method="post" action="<?php echo esc_url( $base_staff_url ); ?>" style="display: inline-block; margin: 0;" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to remove this staff record?', 'awesome-hotel-booking' ) ); ?>');">
										<?php wp_nonce_field( 'ahbn_delete_staff_action', 'ahbn_del_staff_nonce' ); ?>
										<input type="hidden" name="del_staff_id" value="<?php echo esc_attr( (string) $member->id ); ?>">
										<button type="submit" name="ahbn_delete_staff" class="ahbn-quick-btn is-delete" title="<?php esc_attr_e( 'Delete Staff Member', 'awesome-hotel-booking' ); ?>">
											<span class="dashicons dashicons-trash" aria-hidden="true"></span>
										</button>
									</form>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="7" style="text-align: center; padding: 50px; color: #94a3b8;">
							<span class="dashicons dashicons-id-alt" style="font-size: 36px; width: 36px; height: 36px; color: #cbd5e1;" aria-hidden="true"></span>
							<p style="margin: 8px 0 0;"><?php esc_html_e( 'No staff records found matching your selection.', 'awesome-hotel-booking' ); ?></p>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<!-- Modal Backdrop & Employee Information Card -->
	<div id="ahbn-staff-modal" class="ahbn-modal-backdrop" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="ahbn-modal-name">
		<div class="ahbn-modal-dialog">
			<div class="ahbn-modal-header">
				<div style="display: flex; align-items: center; gap: 12px;">
					<div id="ahbn-modal-avatar" class="ahbn-emp-avatar" style="width: 44px; height: 44px; font-size: 16px;"></div>
					<div>
						<h3 id="ahbn-modal-name" class="ahbn-modal-title"></h3>
						<span id="ahbn-modal-role" class="ahbn-role-badge"></span>
					</div>
				</div>
				<button type="button" class="ahbn-modal-close" id="js-ahbn-modal-close" aria-label="<?php esc_attr_e( 'Close', 'awesome-hotel-booking' ); ?>">&times;</button>
			</div>

			<div class="ahbn-modal-body">
				<div class="ahbn-modal-grid">
					<div>
						<span class="ahbn-modal-label"><?php esc_html_e( 'Status', 'awesome-hotel-booking' ); ?></span>
						<span id="ahbn-modal-status" class="ahbn-status-pill"></span>
					</div>
					<div>
						<span class="ahbn-modal-label"><?php esc_html_e( 'Shift Schedule', 'awesome-hotel-booking' ); ?></span>
						<strong id="ahbn-modal-shift" class="ahbn-modal-val"></strong>
					</div>
					<div>
						<span class="ahbn-modal-label"><?php esc_html_e( 'Direct Phone', 'awesome-hotel-booking' ); ?></span>
						<strong id="ahbn-modal-phone" class="ahbn-modal-val font-mono"></strong>
					</div>
					<div>
						<span class="ahbn-modal-label"><?php esc_html_e( 'Email Address', 'awesome-hotel-booking' ); ?></span>
						<strong id="ahbn-modal-email" class="ahbn-modal-val"></strong>
					</div>
					<div>
						<span class="ahbn-modal-label"><?php esc_html_e( 'Hire Date', 'awesome-hotel-booking' ); ?></span>
						<strong id="ahbn-modal-hire" class="ahbn-modal-val"></strong>
					</div>
					<div>
						<span class="ahbn-modal-label"><?php esc_html_e( 'WordPress Account', 'awesome-hotel-booking' ); ?></span>
						<strong id="ahbn-modal-userid" class="ahbn-modal-val font-mono"></strong>
					</div>
				</div>

				<div style="margin-top: 18px;">
					<span class="ahbn-modal-label"><?php esc_html_e( 'Notes & Certifications', 'awesome-hotel-booking' ); ?></span>
					<div id="ahbn-modal-notes" class="ahbn-notes-box"></div>
				</div>
			</div>
		</div>
	</div>

	<style>
		/* View Quick Action Hover Effect */
		.ahbn-quick-btn.js-ahbn-view-staff:hover {
			background: #c5a880 !important;
			border-color: #c5a880 !important;
			color: #0a192f !important;
		}

		/* Modal Styles */
		.ahbn-modal-backdrop {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background: rgba(10, 25, 47, 0.7);
			backdrop-filter: blur(4px);
			z-index: 999999;
			display: flex;
			align-items: center;
			justify-content: center;
			padding: 20px;
			box-sizing: border-box;
		}

		.ahbn-modal-dialog {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 16px;
			max-width: 520px;
			width: 100%;
			box-shadow: 0 20px 40px rgba(10, 25, 47, 0.25);
			overflow: hidden;
			animation: ahbnModalFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
		}

		@keyframes ahbnModalFadeIn {
			from { opacity: 0; transform: translateY(12px) scale(0.97); }
			to   { opacity: 1; transform: translateY(0) scale(1); }
		}

		.ahbn-modal-header {
			padding: 20px 24px;
			background: #0a192f;
			color: #ffffff;
			display: flex;
			align-items: center;
			justify-content: space-between;
			border-bottom: 1px solid rgba(197, 168, 128, 0.3);
		}

		.ahbn-modal-title {
			margin: 0 0 4px 0;
			color: #ffffff;
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 19px;
			font-weight: 700;
		}

		.ahbn-modal-close {
			background: none;
			border: none;
			color: #cbd5e1;
			font-size: 26px;
			line-height: 1;
			cursor: pointer;
			padding: 0;
			transition: color 0.15s ease;
		}

		.ahbn-modal-close:hover {
			color: #c5a880;
		}

		.ahbn-modal-body {
			padding: 24px;
		}

		.ahbn-modal-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 16px;
		}

		.ahbn-modal-label {
			display: block;
			font-size: 10px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.06em;
			color: #64748b;
			margin-bottom: 4px;
		}

		.ahbn-modal-val {
			font-size: 13px;
			color: #0f172a;
		}

		.ahbn-notes-box {
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 8px;
			padding: 12px 14px;
			font-size: 12px;
			line-height: 1.6;
			color: #475569;
		}
	</style>

	<script>
	jQuery(document).ready(function($) {
		// Open View Modal with dynamic employee attributes
		$('.js-ahbn-view-staff').on('click', function(e) {
			e.preventDefault();
			const $btn   = $(this);
			const name   = $btn.data('name');
			const initial = name ? name.charAt(0).toUpperCase() : '?';

			$('#ahbn-modal-avatar').text(initial);
			$('#ahbn-modal-name').text(name);
			$('#ahbn-modal-role').text($btn.data('role'));
			
			$('#ahbn-modal-status')
				.attr('class', 'ahbn-status-pill ' + $btn.data('status-class'))
				.text($btn.data('status'));

			$('#ahbn-modal-shift').text($btn.data('shift'));
			$('#ahbn-modal-phone').text($btn.data('phone'));
			$('#ahbn-modal-email').text($btn.data('email'));
			$('#ahbn-modal-hire').text($btn.data('hire'));

			const uid = parseInt($btn.data('userid'), 10);
			$('#ahbn-modal-userid').text(uid > 0 ? '#' + uid : '<?php echo esc_js( __( 'Not Linked', 'awesome-hotel-booking' ) ); ?>');

			$('#ahbn-modal-notes').text($btn.data('notes'));

			$('#ahbn-staff-modal').fadeIn(150);
		});

		// Close modal interactions
		$('#js-ahbn-modal-close').on('click', function() {
			$('#ahbn-staff-modal').fadeOut(150);
		});

		$(document).on('click', function(e) {
			if ($(e.target).is('#ahbn-staff-modal')) {
				$('#ahbn-staff-modal').fadeOut(150);
			}
		});

		$(document).on('keydown', function(e) {
			if (e.key === 'Escape' && $('#ahbn-staff-modal').is(':visible')) {
				$('#ahbn-staff-modal').fadeOut(150);
			}
		});
	});
	</script>
	<?php
}