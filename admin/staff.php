<?php
/**
 * Hotel Staff & Shift Management View with Sub-Navigation Tabs.
 *
 * Built on high-performance custom relational database tables:
 * {$wpdb->prefix}ahbn_staff
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Hotel Staff Management Tab with All Staff & Add Staff sub-views.
 *
 * @return void
 */
function ahbn_staff_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	global $wpdb;
	$table_staff = $wpdb->prefix . 'ahbn_staff';

	// 1. Resolve Routing Parameters.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$action   = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	$staff_id = isset( $_GET['staff_id'] ) ? absint( wp_unslash( $_GET['staff_id'] ) ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$base_staff_url = admin_url( 'admin.php?page=awesome_hotel_booking&tab=staff' );
	$add_staff_url  = add_query_arg( 'action', 'add', $base_staff_url );

	// 2. Handle POST Submissions (Save / Update / Delete).
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
		// Save / Update Staff Record.
		if ( isset( $_POST['ahbn_save_staff'] ) ) {
			$nonce = isset( $_POST['ahbn_staff_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_staff_nonce'] ) ) : '';

			if ( ! wp_verify_nonce( $nonce, 'ahbn_save_staff_action' ) ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please refresh the page.', 'awesome-hotel-booking' ) . '</p></div>';
			} else {
				$post_staff_id = isset( $_POST['staff_id'] ) ? absint( wp_unslash( $_POST['staff_id'] ) ) : 0;
				$user_id       = isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : 0;
				$full_name     = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
				$role          = isset( $_POST['role'] ) ? sanitize_key( wp_unslash( $_POST['role'] ) ) : 'reception';
				$phone         = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
				$email         = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
				$shift         = isset( $_POST['shift'] ) ? sanitize_key( wp_unslash( $_POST['shift'] ) ) : 'morning';
				$status        = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active';
				$hire_date     = isset( $_POST['hire_date'] ) ? sanitize_text_field( wp_unslash( $_POST['hire_date'] ) ) : current_time( 'Y-m-d' );
				$notes         = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

				if ( empty( $full_name ) ) {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please enter the staff member\'s full name.', 'awesome-hotel-booking' ) . '</p></div>';
				} else {
					$staff_data = array(
						'user_id'   => $user_id,
						'full_name' => $full_name,
						'role'      => $role,
						'phone'     => $phone,
						'email'     => $email,
						'shift'     => $shift,
						'status'    => $status,
						'hire_date' => ! empty( $hire_date ) ? $hire_date : current_time( 'Y-m-d' ),
						'notes'     => $notes,
					);

					$format = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

					// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					if ( $post_staff_id > 0 ) {
						$wpdb->update(
							$table_staff,
							$staff_data,
							array( 'id' => $post_staff_id ),
							$format,
							array( '%d' )
						);
						echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Staff member profile updated.', 'awesome-hotel-booking' ) . '</p></div>';
					} else {
						$wpdb->insert( $table_staff, $staff_data, $format );
						echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Staff member added to hotel roster.', 'awesome-hotel-booking' ) . '</p></div>';
					}
					// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

					$action   = '';
					$staff_id = 0;
				}
			}
		}

		// Delete Staff Record.
		if ( isset( $_POST['ahbn_delete_staff'] ) ) {
			$del_nonce = isset( $_POST['ahbn_del_staff_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_del_staff_nonce'] ) ) : '';
			$del_id    = isset( $_POST['del_staff_id'] ) ? absint( wp_unslash( $_POST['del_staff_id'] ) ) : 0;

			if ( wp_verify_nonce( $del_nonce, 'ahbn_delete_staff_action' ) && $del_id > 0 ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete( $table_staff, array( 'id' => $del_id ), array( '%d' ) );
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Staff record deleted.', 'awesome-hotel-booking' ) . '</p></div>';
			}
		}
	}

	// 3. Map Configs.
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

	$wp_users = get_users( array( 'fields' => array( 'ID', 'display_name', 'user_email' ), 'number' => 100 ) );

	// Determine Active Tab States.
	$is_all_active  = ( empty( $action ) );
	$is_add_active  = ( 'add' === $action );
	$is_edit_active = ( 'edit' === $action && $staff_id > 0 );
	?>

	<!-- Top Sub-Tabs Navigation Bar -->
	<div class="ahbn-subtabs-nav-bar">
		<a href="<?php echo esc_url( $base_staff_url ); ?>" class="ahbn-subtab-link <?php echo $is_all_active ? 'is-active' : ''; ?>">
			<span class="dashicons dashicons-groups" aria-hidden="true"></span>
			<span><?php esc_html_e( 'All Staff', 'awesome-hotel-booking' ); ?></span>
		</a>

		<a href="<?php echo esc_url( $add_staff_url ); ?>" class="ahbn-subtab-link <?php echo $is_add_active ? 'is-active' : ''; ?>">
			<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
			<span><?php esc_html_e( 'Add Staff', 'awesome-hotel-booking' ); ?></span>
		</a>

		<?php if ( $is_edit_active ) : ?>
			<span class="ahbn-subtab-link is-active is-context-tab">
				<span class="dashicons dashicons-edit" aria-hidden="true"></span>
				<span><?php echo esc_html( sprintf( /* translators: %d: staff ID */ __( 'Edit Staff #%d', 'awesome-hotel-booking' ), $staff_id ) ); ?></span>
			</span>
		<?php endif; ?>
	</div>

	<style>
		.ahbn-subtabs-nav-bar {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			padding: 5px;
			border-radius: 12px;
			margin-bottom: 22px;
		}

		.ahbn-subtab-link {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			padding: 8px 16px;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 700;
			color: #64748b;
			text-decoration: none;
			border: 1px solid transparent;
			transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
			cursor: pointer;
		}

		.ahbn-subtab-link .dashicons {
			font-size: 16px;
			width: 16px;
			height: 16px;
			color: #94a3b8;
			transition: color 0.2s ease;
		}

		.ahbn-subtab-link:hover {
			color: #0a192f;
			background: #ffffff;
			border-color: #e2e8f0;
		}

		.ahbn-subtab-link:hover .dashicons {
			color: #c5a880;
		}

		.ahbn-subtab-link.is-active {
			background: #0a192f;
			color: #ffffff !important;
			border-color: #040914;
			box-shadow: 0 4px 12px rgba(10, 25, 47, 0.15);
		}

		.ahbn-subtab-link.is-active .dashicons {
			color: #c5a880 !important;
		}

		.ahbn-subtab-link.is-context-tab {
			cursor: default;
		}

		.ahbn-staff-wrap {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-staff-header {
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

		.ahbn-staff-title {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 24px;
			font-weight: 700;
			color: #0a192f;
			margin: 0 0 4px;
		}

		.ahbn-staff-subtitle {
			color: #64748b;
			margin: 0;
			font-size: 13px;
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

		.ahbn-stat-active-duty { border-left: 4px solid #10b981; }
		.ahbn-stat-active-duty .ahbn-stat-label,
		.ahbn-stat-active-duty .ahbn-stat-value { color: #059669; }

		.ahbn-stat-hk { border-left: 4px solid #c5a880; }
		.ahbn-stat-hk .ahbn-stat-label { color: #b39264; }

		.ahbn-stat-rec { border-left: 4px solid #2563eb; }
		.ahbn-stat-rec .ahbn-stat-label { color: #2563eb; }

		.ahbn-stat-active {
			border-color: #0a192f !important;
			background: #fbf8f3 !important;
		}

		/* Filter Bar */
		.ahbn-staff-filter-bar {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 14px 18px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-filter-controls {
			display: flex;
			gap: 16px;
			align-items: center;
			flex-wrap: wrap;
		}

		.ahbn-filter-field {
			display: inline-flex;
			align-items: center;
			gap: 8px;
		}

		.ahbn-filter-label {
			font-size: 11px;
			font-weight: 700;
			color: #64748b;
			text-transform: uppercase;
			letter-spacing: 0.05em;
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

		/* Table Elements */
		.ahbn-table-container {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			overflow: hidden;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-staff-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 13px;
		}

		.ahbn-staff-table th {
			background: #f8fafc !important;
			padding: 12px 16px !important;
			font-weight: 700 !important;
			font-size: 11px !important;
			text-transform: uppercase !important;
			letter-spacing: 0.05em !important;
			color: #64748b !important;
			border-bottom: 1px solid #e2e8f0 !important;
		}

		.ahbn-staff-table td {
			padding: 14px 16px !important;
			vertical-align: middle !important;
			border-bottom: 1px solid #f1f5f9 !important;
		}

		.ahbn-emp-cell {
			display: flex;
			align-items: center;
			gap: 12px;
		}

		.ahbn-emp-avatar {
			width: 36px;
			height: 36px;
			border-radius: 50%;
			background: #f1f5f9;
			border: 1px solid #e2e8f0;
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 800;
			color: #0a192f;
			font-size: 13px;
		}

		.ahbn-emp-name {
			color: #0f172a;
			display: block;
			font-size: 13px;
		}

		.ahbn-emp-linked {
			display: block;
			font-size: 11px;
			color: #94a3b8;
			margin-top: 2px;
		}

		.ahbn-role-badge {
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

		.ahbn-contact-stack {
			font-size: 12px;
			line-height: 1.5;
			color: #475569;
		}

		.ahbn-contact-stack .dashicons {
			font-size: 14px;
			width: 14px;
			height: 14px;
			color: #94a3b8;
			vertical-align: middle;
			margin-right: 3px;
		}

		.ahbn-shift-text {
			font-size: 12px;
			font-weight: 600;
			color: #334155;
		}

		.ahbn-status-pill {
			display: inline-block;
			padding: 4px 10px;
			border-radius: 9999px;
			font-size: 11px;
			font-weight: 700;
		}

		.status-active   { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
		.status-leave    { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
		.status-inactive { background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; }

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

		/* Form Panels (Add / Edit Sub-View) */
		.ahbn-panel {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			overflow: hidden;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-panel-head {
			padding: 16px 20px;
			background: #fafafa;
			border-bottom: 1px solid #f1f5f9;
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.ahbn-panel-head h2 {
			margin: 0;
			font-size: 14px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #0a192f;
		}

		.ahbn-panel-head .dashicons {
			color: #c5a880;
			font-size: 18px;
			width: 18px;
			height: 18px;
		}

		.ahbn-panel-body {
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

		.ahbn-input,
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

		.ahbn-input:focus,
		.ahbn-select:focus,
		.ahbn-textarea:focus {
			border-color: #0a192f !important;
			box-shadow: 0 0 0 2px rgba(10, 25, 47, 0.1) !important;
		}

		.ahbn-panel-footer {
			padding: 16px 24px;
			background: #f8fafc;
			border-top: 1px solid #e2e8f0;
			display: flex;
			justify-content: flex-end;
			gap: 10px;
		}

		.ahbn-btn-cancel {
			padding: 9px 18px;
			background: #ffffff;
			border: 1px solid #cbd5e1;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 600;
			color: #475569;
			text-decoration: none;
			display: inline-flex;
			align-items: center;
			transition: all 0.2s ease;
		}

		.ahbn-btn-cancel:hover {
			background: #f1f5f9;
			color: #0f172a;
		}

		.ahbn-btn-save {
			padding: 9px 20px;
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

	<div class="ahbn-staff-wrap">
		<?php if ( 'add' === $action || 'edit' === $action ) : ?>
			<?php
			$edit_member = null;
			if ( 'edit' === $action && $staff_id > 0 ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$edit_member = $wpdb->get_row(
					$wpdb->prepare( "SELECT * FROM {$table_staff} WHERE id = %d", $staff_id )
				);
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
			?>
			<!-- Add / Edit Staff Dedicated Panel -->
			<div class="ahbn-panel" style="max-width: 720px; margin: 0 auto; width: 100%;">
				<div class="ahbn-panel-head">
					<span class="dashicons dashicons-id-alt" aria-hidden="true"></span>
					<h2>
						<?php
						if ( $edit_member ) {
							/* translators: %s: staff member name */
							printf( esc_html__( 'Edit Staff Profile: %s', 'awesome-hotel-booking' ), esc_html( $edit_member->full_name ) );
						} else {
							esc_html_e( 'Register New Hotel Staff Member', 'awesome-hotel-booking' );
						}
						?>
					</h2>
				</div>

				<form method="post" action="<?php echo esc_url( $base_staff_url ); ?>">
					<?php wp_nonce_field( 'ahbn_save_staff_action', 'ahbn_staff_nonce' ); ?>
					<input type="hidden" name="staff_id" value="<?php echo esc_attr( (string) ( $edit_member ? $edit_member->id : 0 ) ); ?>">

					<div class="ahbn-panel-body">
						<div class="ahbn-field-group">
							<label for="staff_full_name" class="ahbn-label"><?php esc_html_e( 'Full Name *', 'awesome-hotel-booking' ); ?></label>
							<input type="text" name="full_name" id="staff_full_name" class="ahbn-input" required value="<?php echo esc_attr( $edit_member ? $edit_member->full_name : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Maria Gonzalez', 'awesome-hotel-booking' ); ?>">
						</div>

						<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 14px;">
							<div class="ahbn-field-group">
								<label for="staff_role" class="ahbn-label"><?php esc_html_e( 'Department / Role', 'awesome-hotel-booking' ); ?></label>
								<select name="role" id="staff_role" class="ahbn-select">
									<?php foreach ( $roles_map as $r_k => $r_v ) : ?>
										<option value="<?php echo esc_attr( $r_k ); ?>" <?php selected( $edit_member ? $edit_member->role : 'reception', $r_k ); ?>>
											<?php echo esc_html( $r_v ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="ahbn-field-group">
								<label for="staff_shift" class="ahbn-label"><?php esc_html_e( 'Shift Schedule', 'awesome-hotel-booking' ); ?></label>
								<select name="shift" id="staff_shift" class="ahbn-select">
									<?php foreach ( $shifts_map as $s_k => $s_v ) : ?>
										<option value="<?php echo esc_attr( $s_k ); ?>" <?php selected( $edit_member ? $edit_member->shift : 'morning', $s_k ); ?>>
											<?php echo esc_html( $s_v ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 14px;">
							<div class="ahbn-field-group">
								<label for="staff_phone" class="ahbn-label"><?php esc_html_e( 'Phone Number', 'awesome-hotel-booking' ); ?></label>
								<input type="tel" name="phone" id="staff_phone" class="ahbn-input" value="<?php echo esc_attr( $edit_member ? $edit_member->phone : '' ); ?>" placeholder="<?php esc_attr_e( '+1 (555) 019-2834', 'awesome-hotel-booking' ); ?>">
							</div>

							<div class="ahbn-field-group">
								<label for="staff_email" class="ahbn-label"><?php esc_html_e( 'Email Address', 'awesome-hotel-booking' ); ?></label>
								<input type="email" name="email" id="staff_email" class="ahbn-input" value="<?php echo esc_attr( $edit_member ? $edit_member->email : '' ); ?>" placeholder="<?php esc_attr_e( 'staff@grandhotel.com', 'awesome-hotel-booking' ); ?>">
							</div>
						</div>

						<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 14px;">
							<div class="ahbn-field-group">
								<label for="staff_status" class="ahbn-label"><?php esc_html_e( 'Employment Status', 'awesome-hotel-booking' ); ?></label>
								<select name="status" id="staff_status" class="ahbn-select">
									<option value="active" <?php selected( $edit_member ? $edit_member->status : 'active', 'active' ); ?>><?php esc_html_e( 'Active on Duty', 'awesome-hotel-booking' ); ?></option>
									<option value="leave" <?php selected( $edit_member ? $edit_member->status : '', 'leave' ); ?>><?php esc_html_e( 'On Leave / Vacation', 'awesome-hotel-booking' ); ?></option>
									<option value="inactive" <?php selected( $edit_member ? $edit_member->status : '', 'inactive' ); ?>><?php esc_html_e( 'Inactive / Former', 'awesome-hotel-booking' ); ?></option>
								</select>
							</div>

							<div class="ahbn-field-group">
								<label for="staff_hire_date" class="ahbn-label"><?php esc_html_e( 'Hire Date', 'awesome-hotel-booking' ); ?></label>
								<input type="date" name="hire_date" id="staff_hire_date" class="ahbn-input" value="<?php echo esc_attr( $edit_member ? $edit_member->hire_date : current_time( 'Y-m-d' ) ); ?>">
							</div>
						</div>

						<div class="ahbn-field-group">
							<label for="staff_user_id" class="ahbn-label"><?php esc_html_e( 'Link WordPress User Account (Optional)', 'awesome-hotel-booking' ); ?></label>
							<select name="user_id" id="staff_user_id" class="ahbn-select">
								<option value="0"><?php esc_html_e( '— No User Account Linked —', 'awesome-hotel-booking' ); ?></option>
								<?php foreach ( $wp_users as $wpu ) : ?>
									<option value="<?php echo esc_attr( (string) $wpu->ID ); ?>" <?php selected( $edit_member ? $edit_member->user_id : 0, $wpu->ID ); ?>>
										<?php echo esc_html( $wpu->display_name . ' (' . $wpu->user_email . ')' ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="ahbn-field-group">
							<label for="staff_notes" class="ahbn-label"><?php esc_html_e( 'Staff Notes / Certification Details', 'awesome-hotel-booking' ); ?></label>
							<textarea name="notes" id="staff_notes" rows="3" class="ahbn-textarea" placeholder="<?php esc_attr_e( 'e.g. Master Suite inspection lead, CPR certified, key card level 3.', 'awesome-hotel-booking' ); ?>"><?php echo esc_textarea( $edit_member ? $edit_member->notes : '' ); ?></textarea>
						</div>
					</div>

					<div class="ahbn-panel-footer">
						<a href="<?php echo esc_url( $base_staff_url ); ?>" class="ahbn-btn-cancel">
							<?php esc_html_e( 'Cancel', 'awesome-hotel-booking' ); ?>
						</a>
						<button type="submit" name="ahbn_save_staff" class="ahbn-btn-save">
							<?php echo $edit_member ? esc_html__( 'Update Staff Member', 'awesome-hotel-booking' ) : esc_html__( 'Save Staff Member', 'awesome-hotel-booking' ); ?>
						</button>
					</div>
				</form>
			</div>

		<?php else : ?>

			<!-- Header Toolbar (All Staff View) -->
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
					<div class="ahbn-filter-field">
						<label for="ahbn_filter_role" class="ahbn-filter-label">
							<?php esc_html_e( 'Department:', 'awesome-hotel-booking' ); ?>
						</label>
						<select name="filter_role" id="ahbn_filter_role" class="ahbn-select" onchange="this.form.submit();">
							<option value=""><?php esc_html_e( 'All Departments', 'awesome-hotel-booking' ); ?></option>
							<?php foreach ( $roles_map as $r_key => $r_label ) : ?>
								<option value="<?php echo esc_attr( $r_key ); ?>" <?php selected( $filter_role, $r_key ); ?>>
									<?php echo esc_html( $r_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="ahbn-filter-field">
						<label for="ahbn_filter_status" class="ahbn-filter-label">
							<?php esc_html_e( 'Status:', 'awesome-hotel-booking' ); ?>
						</label>
						<select name="filter_status" id="ahbn_filter_status" class="ahbn-select" onchange="this.form.submit();">
							<option value=""><?php esc_html_e( 'All Statuses', 'awesome-hotel-booking' ); ?></option>
							<option value="active" <?php selected( $filter_status, 'active' ); ?>><?php esc_html_e( 'Active', 'awesome-hotel-booking' ); ?></option>
							<option value="leave" <?php selected( $filter_status, 'leave' ); ?>><?php esc_html_e( 'On Leave', 'awesome-hotel-booking' ); ?></option>
							<option value="inactive" <?php selected( $filter_status, 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'awesome-hotel-booking' ); ?></option>
						</select>
					</div>

					<?php if ( ! empty( $filter_role ) || ! empty( $filter_status ) ) : ?>
						<a href="<?php echo esc_url( $base_staff_url ); ?>" class="ahbn-clear-filters">
							<?php esc_html_e( 'Clear Filter', 'awesome-hotel-booking' ); ?>
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
							<th style="text-align: right; width: 120px;"><?php esc_html_e( 'Actions', 'awesome-hotel-booking' ); ?></th>
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
											<?php echo esc_html( ! empty( $member->hire_date ) ? wp_date( get_option( 'date_format' ), strtotime( $member->hire_date ) ) : '—' ); ?>
										</span>
									</td>
									<td style="text-align: right;">
										<div class="ahbn-action-btns">
											<a href="<?php echo esc_url( $edit_link ); ?>" class="ahbn-quick-btn" title="<?php esc_attr_e( 'Edit Staff', 'awesome-hotel-booking' ); ?>">
												<span class="dashicons dashicons-edit" aria-hidden="true"></span>
											</a>

											<form method="post" action="<?php echo esc_url( $base_staff_url ); ?>" style="display: inline-block; margin: 0;" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to remove this staff record?', 'awesome-hotel-booking' ) ); ?>');">
												<?php wp_nonce_field( 'ahbn_delete_staff_action', 'ahbn_del_staff_nonce' ); ?>
												<input type="hidden" name="del_staff_id" value="<?php echo esc_attr( (string) $member->id ); ?>">
												<button type="submit" name="ahbn_delete_staff" class="ahbn-quick-btn is-delete" title="<?php esc_attr_e( 'Delete Staff', 'awesome-hotel-booking' ); ?>">
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

		<?php endif; ?>
	</div>
	<?php
}