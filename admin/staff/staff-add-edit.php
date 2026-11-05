<?php
/**
 * Staff Add / Edit Form & Processing View.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle form submission and render the Add/Edit staff form.
 *
 * @param int $staff_id Optional. Staff ID to edit. Defaults to 0.
 * @return void
 */
function ahbn_render_staff_add_edit( $staff_id = 0 ) {
	global $wpdb;
	$table_staff    = $wpdb->prefix . 'ahbn_staff';
	$base_staff_url = admin_url( 'admin.php?page=awesome_hotel_booking&tab=staff' );

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

	// Handle Save / Update POST Submission.
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_staff'] ) ) {
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
					$updated = $wpdb->update(
						$table_staff,
						$staff_data,
						array( 'id' => $post_staff_id ),
						$format,
						array( '%d' )
					);

					if ( false === $updated ) {
						echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Database error: Could not update staff member profile.', 'awesome-hotel-booking' ) . ' ' . esc_html( $wpdb->last_error ) . '</p></div>';
					} else {
						wp_safe_redirect( $base_staff_url );
						exit;
					}
				} else {
					$inserted = $wpdb->insert( $table_staff, $staff_data, $format );

					if ( false === $inserted ) {
						echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Database error: Could not save staff member to roster.', 'awesome-hotel-booking' ) . ' ' . esc_html( $wpdb->last_error ) . '</p></div>';
					} else {
						wp_safe_redirect( $base_staff_url );
						exit;
					}
				}
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
		}
	}

	// Fetch staff member if editing.
	$edit_member = null;
	if ( $staff_id > 0 ) {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$edit_member = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_staff} WHERE id = %d", $staff_id )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	$wp_users = get_users( array( 'fields' => array( 'ID', 'display_name', 'user_email' ), 'number' => 100 ) );
	?>
	<div class="ahbn-panel" style="max-width: 740px; margin: 0 auto; width: 100%;">
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

		<form method="post" action="">
			<?php wp_nonce_field( 'ahbn_save_staff_action', 'ahbn_staff_nonce' ); ?>
			<input type="hidden" name="ahbn_save_staff" value="1">
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
				<button type="submit" class="ahbn-btn-save">
					<span class="dashicons dashicons-saved" aria-hidden="true"></span>
					<?php echo $edit_member ? esc_html__( 'Update Staff Member', 'awesome-hotel-booking' ) : esc_html__( 'Save Staff Member', 'awesome-hotel-booking' ); ?>
				</button>
			</div>
		</form>
	</div>
	<?php
}