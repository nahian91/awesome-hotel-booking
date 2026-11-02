<?php
/**
 * Extra Services & Add-ons Management View.
 *
 * Built on high-performance custom relational database tables:
 * {$wpdb->prefix}ahbn_services
 * Features sub-tabs: All Extra Services & Add Extra Service.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Extra Services & Add-ons Management Tab with sub-views.
 *
 * @return void
 */
function ahbn_extra_service_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	global $wpdb;
	$table_services = $wpdb->prefix . 'ahbn_services';

	// 1. Resolve Routing Parameters.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$action     = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	$service_id = isset( $_GET['service_id'] ) ? absint( wp_unslash( $_GET['service_id'] ) ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$base_service_url = admin_url( 'admin.php?page=awesome_hotel_booking&tab=addons' );
	$add_service_url  = add_query_arg( 'action', 'add', $base_service_url );

	// 2. Handle POST Submissions (Save / Update / Delete).
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
		// Save or Edit Service.
		if ( isset( $_POST['ahbn_save_service'] ) ) {
			$nonce = isset( $_POST['ahbn_service_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_service_nonce'] ) ) : '';

			if ( ! wp_verify_nonce( $nonce, 'ahbn_save_service_action' ) ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please refresh the page.', 'awesome-hotel-booking' ) . '</p></div>';
			} else {
				$post_service_id = isset( $_POST['service_id'] ) ? absint( wp_unslash( $_POST['service_id'] ) ) : 0;
				$name            = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
				$price           = isset( $_POST['price'] ) ? (float) wp_unslash( $_POST['price'] ) : 0.00;
				$charge_type     = isset( $_POST['charge_type'] ) ? sanitize_key( wp_unslash( $_POST['charge_type'] ) ) : 'per_stay';
				$description     = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';
				$status          = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'publish';

				if ( empty( $name ) || $price < 0 ) {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please provide a valid service name and non-negative price.', 'awesome-hotel-booking' ) . '</p></div>';
				} else {
					$service_data = array(
						'name'        => $name,
						'price'       => $price,
						'charge_type' => $charge_type,
						'description' => $description,
						'status'      => $status,
					);

					$format = array( '%s', '%f', '%s', '%s', '%s' );

					// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					if ( $post_service_id > 0 ) {
						$wpdb->update(
							$table_services,
							$service_data,
							array( 'id' => $post_service_id ),
							$format,
							array( '%d' )
						);
						echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Extra service updated.', 'awesome-hotel-booking' ) . '</p></div>';
					} else {
						$wpdb->insert( $table_services, $service_data, $format );
						echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'New extra service created.', 'awesome-hotel-booking' ) . '</p></div>';
					}
					// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

					$action     = '';
					$service_id = 0;
				}
			}
		}

		// Delete Service.
		if ( isset( $_POST['ahbn_delete_service'] ) ) {
			$del_nonce = isset( $_POST['ahbn_del_service_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_del_service_nonce'] ) ) : '';
			$del_id    = isset( $_POST['del_service_id'] ) ? absint( wp_unslash( $_POST['del_service_id'] ) ) : 0;

			if ( wp_verify_nonce( $del_nonce, 'ahbn_delete_service_action' ) && $del_id > 0 ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete( $table_services, array( 'id' => $del_id ), array( '%d' ) );
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Extra service removed.', 'awesome-hotel-booking' ) . '</p></div>';
			}
		}
	}

	// 3. Mapping Definitions.
	$charge_types = array(
		'per_stay'             => __( 'Per Stay (One-time)', 'awesome-hotel-booking' ),
		'per_night'            => __( 'Per Night', 'awesome-hotel-booking' ),
		'per_person'           => __( 'Per Person (One-time)', 'awesome-hotel-booking' ),
		'per_night_per_person' => __( 'Per Person / Per Night', 'awesome-hotel-booking' ),
	);

	// 4. Query All Services & Aggregate Statistics.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$services = $wpdb->get_results( "SELECT * FROM {$table_services} ORDER BY id DESC" );

	$metrics = $wpdb->get_row(
		"SELECT 
			COUNT(*) AS total_services,
			COUNT(CASE WHEN status = 'publish' THEN 1 END) AS active_services,
			COALESCE(AVG(price), 0.00) AS avg_price
		 FROM {$table_services}"
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	$total_services  = $metrics ? (int) $metrics->total_services : 0;
	$active_services = $metrics ? (int) $metrics->active_services : 0;
	$avg_price       = $metrics ? (float) $metrics->avg_price : 0.00;

	// Determine Active Sub-tab State.
	$is_all_active  = ( empty( $action ) );
	$is_add_active  = ( 'add' === $action );
	$is_edit_active = ( 'edit' === $action && $service_id > 0 );
	?>

	<!-- Top Sub-Tabs Navigation Bar -->
	<div class="ahbn-subtabs-nav-bar">
		<a href="<?php echo esc_url( $base_service_url ); ?>" class="ahbn-subtab-link <?php echo $is_all_active ? 'is-active' : ''; ?>">
			<span class="dashicons dashicons-coffee" aria-hidden="true"></span>
			<span><?php esc_html_e( 'All Extra Services', 'awesome-hotel-booking' ); ?></span>
		</a>

		<a href="<?php echo esc_url( $add_service_url ); ?>" class="ahbn-subtab-link <?php echo $is_add_active ? 'is-active' : ''; ?>">
			<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
			<span><?php esc_html_e( 'Add Extra Service', 'awesome-hotel-booking' ); ?></span>
		</a>

		<?php if ( $is_edit_active ) : ?>
			<span class="ahbn-subtab-link is-active is-context-tab">
				<span class="dashicons dashicons-edit" aria-hidden="true"></span>
				<span><?php echo esc_html( sprintf( /* translators: %d: service ID */ __( 'Edit Service #%d', 'awesome-hotel-booking' ), $service_id ) ); ?></span>
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

		.ahbn-service-wrap {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-service-header {
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

		.ahbn-service-title {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 24px;
			font-weight: 700;
			color: #0a192f;
			margin: 0 0 4px;
		}

		.ahbn-service-subtitle {
			color: #64748b;
			margin: 0;
			font-size: 13px;
		}

		/* Stat Cards */
		.ahbn-stats-grid {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
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
			font-size: 26px;
			font-weight: 800;
			color: #0a192f;
		}

		.ahbn-stat-active-duty {
			border-left: 4px solid #10b981;
		}
		.ahbn-stat-active-duty .ahbn-stat-label,
		.ahbn-stat-active-duty .ahbn-stat-value {
			color: #059669;
		}

		.ahbn-stat-gold {
			border-left: 4px solid #c5a880;
		}
		.ahbn-stat-gold .ahbn-stat-label {
			color: #b39264;
		}

		/* Table Structure */
		.ahbn-table-container {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			overflow: hidden;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-service-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 13px;
		}

		.ahbn-service-table th {
			background: #f8fafc !important;
			padding: 12px 16px !important;
			font-weight: 700 !important;
			font-size: 11px !important;
			text-transform: uppercase !important;
			letter-spacing: 0.05em !important;
			color: #64748b !important;
			border-bottom: 1px solid #e2e8f0 !important;
		}

		.ahbn-service-table td {
			padding: 14px 16px !important;
			vertical-align: middle !important;
			border-bottom: 1px solid #f1f5f9 !important;
		}

		.ahbn-charge-badge {
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

		.ahbn-status-pill {
			display: inline-block;
			padding: 4px 10px;
			border-radius: 9999px;
			font-size: 11px;
			font-weight: 700;
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
				grid-template-columns: 1fr;
			}
		}
	</style>

	<div class="ahbn-service-wrap">
		<?php if ( 'add' === $action || 'edit' === $action ) : ?>
			<?php
			$edit_service = null;
			if ( 'edit' === $action && $service_id > 0 ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$edit_service = $wpdb->get_row(
					$wpdb->prepare( "SELECT * FROM {$table_services} WHERE id = %d", $service_id )
				);
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
			?>
			<!-- Add / Edit Service Dedicated Panel -->
			<div class="ahbn-panel" style="max-width: 720px; margin: 0 auto; width: 100%;">
				<div class="ahbn-panel-head">
					<span class="dashicons dashicons-coffee" aria-hidden="true"></span>
					<h2>
						<?php
						if ( $edit_service ) {
							/* translators: %s: service name */
							printf( esc_html__( 'Edit Extra Service: %s', 'awesome-hotel-booking' ), esc_html( $edit_service->name ) );
						} else {
							esc_html_e( 'Register New Extra Service / Add-on', 'awesome-hotel-booking' );
						}
						?>
					</h2>
				</div>

				<form method="post" action="<?php echo esc_url( $base_service_url ); ?>">
					<?php wp_nonce_field( 'ahbn_save_service_action', 'ahbn_service_nonce' ); ?>
					<input type="hidden" name="service_id" value="<?php echo esc_attr( (string) ( $edit_service ? $edit_service->id : 0 ) ); ?>">

					<div class="ahbn-panel-body">
						<div class="ahbn-field-group">
							<label for="service_name" class="ahbn-label"><?php esc_html_e( 'Service / Amenity Name *', 'awesome-hotel-booking' ); ?></label>
							<input type="text" name="name" id="service_name" class="ahbn-input" required value="<?php echo esc_attr( $edit_service ? $edit_service->name : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Airport Shuttle, Buffet Breakfast, Spa Access', 'awesome-hotel-booking' ); ?>">
						</div>

						<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 14px;">
							<div class="ahbn-field-group">
								<label for="service_price" class="ahbn-label"><?php esc_html_e( 'Rate / Price *', 'awesome-hotel-booking' ); ?></label>
								<input type="number" step="0.01" min="0" name="price" id="service_price" class="ahbn-input" required value="<?php echo esc_attr( (string) ( $edit_service ? $edit_service->price : '0.00' ) ); ?>" style="font-family: 'JetBrains Mono', monospace; font-weight: 700;">
							</div>

							<div class="ahbn-field-group">
								<label for="service_charge_type" class="ahbn-label"><?php esc_html_e( 'Charge Frequency Model', 'awesome-hotel-booking' ); ?></label>
								<select name="charge_type" id="service_charge_type" class="ahbn-select">
									<?php foreach ( $charge_types as $c_key => $c_label ) : ?>
										<option value="<?php echo esc_attr( $c_key ); ?>" <?php selected( $edit_service ? $edit_service->charge_type : 'per_stay', $c_key ); ?>>
											<?php echo esc_html( $c_label ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="ahbn-field-group">
							<label for="service_status" class="ahbn-label"><?php esc_html_e( 'Availability Status', 'awesome-hotel-booking' ); ?></label>
							<select name="status" id="service_status" class="ahbn-select">
								<option value="publish" <?php selected( $edit_service ? $edit_service->status : 'publish', 'publish' ); ?>>
									<?php esc_html_e( 'Active & Available for Guest Booking', 'awesome-hotel-booking' ); ?>
								</option>
								<option value="draft" <?php selected( $edit_service ? $edit_service->status : '', 'draft' ); ?>>
									<?php esc_html_e( 'Hidden / Draft', 'awesome-hotel-booking' ); ?>
								</option>
							</select>
						</div>

						<div class="ahbn-field-group">
							<label for="service_description" class="ahbn-label"><?php esc_html_e( 'Service Description / Inclusions', 'awesome-hotel-booking' ); ?></label>
							<textarea name="description" id="service_description" rows="3" class="ahbn-textarea" placeholder="<?php esc_attr_e( 'Explain what is included with this service (e.g. One-way airport transfer in premium vehicle).', 'awesome-hotel-booking' ); ?>"><?php echo esc_textarea( $edit_service ? $edit_service->description : '' ); ?></textarea>
						</div>
					</div>

					<div class="ahbn-panel-footer">
						<a href="<?php echo esc_url( $base_service_url ); ?>" class="ahbn-btn-cancel">
							<?php esc_html_e( 'Cancel', 'awesome-hotel-booking' ); ?>
						</a>
						<button type="submit" name="ahbn_save_service" class="ahbn-btn-save">
							<?php echo $edit_service ? esc_html__( 'Update Extra Service', 'awesome-hotel-booking' ) : esc_html__( 'Save Extra Service', 'awesome-hotel-booking' ); ?>
						</button>
					</div>
				</form>
			</div>

		<?php else : ?>

			<!-- Header Toolbar (All Services View) -->
			<header class="ahbn-service-header">
				<div>
					<h2 class="ahbn-service-title"><?php esc_html_e( 'Extra Services & Hospitality Add-ons', 'awesome-hotel-booking' ); ?></h2>
					<p class="ahbn-service-subtitle">
						<?php esc_html_e( 'Manage optional guest amenities, meal packages, airport logistics, and wellness experiences.', 'awesome-hotel-booking' ); ?>
					</p>
				</div>
			</header>

			<!-- Strategy KPI Cards -->
			<div class="ahbn-stats-grid">
				<div class="ahbn-stat-card">
					<span class="ahbn-stat-label"><?php esc_html_e( 'Total Extra Services', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-stat-value"><?php echo esc_html( (string) $total_services ); ?></span>
				</div>
				<div class="ahbn-stat-card ahbn-stat-active-duty">
					<span class="ahbn-stat-label"><?php esc_html_e( 'Active & Available', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-stat-value"><?php echo esc_html( (string) $active_services ); ?></span>
				</div>
				<div class="ahbn-stat-card ahbn-stat-gold">
					<span class="ahbn-stat-label"><?php esc_html_e( 'Average Add-on Price', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-stat-value"><?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $avg_price ) : (string) number_format_i18n( $avg_price, 2 ) ); ?></span>
				</div>
			</div>

			<!-- Services Table -->
			<div class="ahbn-table-container">
				<table class="ahbn-service-table widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Service / Add-on', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Price', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Billing Cycle / Model', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Description', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Status', 'awesome-hotel-booking' ); ?></th>
							<th style="text-align: right; width: 120px;"><?php esc_html_e( 'Actions', 'awesome-hotel-booking' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $services ) ) : ?>
							<?php foreach ( $services as $item ) :
								$is_active = ( 'publish' === $item->status );
								$status_class = $is_active ? 'status-active-now' : 'status-disabled';
								$status_label = $is_active ? __( 'Active', 'awesome-hotel-booking' ) : __( 'Disabled', 'awesome-hotel-booking' );
								$charge_label = isset( $charge_types[ $item->charge_type ] ) ? $charge_types[ $item->charge_type ] : ucfirst( str_replace( '_', ' ', $item->charge_type ) );
								$edit_link    = add_query_arg( array( 'action' => 'edit', 'service_id' => $item->id ), $base_service_url );
								?>
								<tr>
									<td>
										<div style="display:flex; align-items:center; gap: 8px;">
											<span class="dashicons dashicons-coffee" style="color: #c5a880;" aria-hidden="true"></span>
											<strong style="color: #0a192f; font-size: 14px;"><?php echo esc_html( $item->name ); ?></strong>
										</div>
									</td>
									<td>
										<strong style="color: #059669; font-size: 15px; font-family: 'JetBrains Mono', monospace;">
											<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $item->price ) : (string) number_format_i18n( $item->price, 2 ) ); ?>
										</strong>
									</td>
									<td>
										<span class="ahbn-charge-badge">
											<?php echo esc_html( $charge_label ); ?>
										</span>
									</td>
									<td>
										<span style="font-size: 12px; color: #64748b; line-height: 1.4;">
											<?php echo esc_html( ! empty( $item->description ) ? wp_trim_words( $item->description, 12, '...' ) : '—' ); ?>
										</span>
									</td>
									<td>
										<span class="ahbn-status-pill <?php echo esc_attr( $status_class ); ?>">
											<?php echo esc_html( $status_label ); ?>
										</span>
									</td>
									<td style="text-align: right;">
										<div class="ahbn-action-btns">
											<a href="<?php echo esc_url( $edit_link ); ?>" class="ahbn-quick-btn" title="<?php esc_attr_e( 'Edit Extra Service', 'awesome-hotel-booking' ); ?>">
												<span class="dashicons dashicons-edit" aria-hidden="true"></span>
											</a>

											<form method="post" action="<?php echo esc_url( $base_service_url ); ?>" style="display: inline-block; margin: 0;" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this extra service?', 'awesome-hotel-booking' ) ); ?>');">
												<?php wp_nonce_field( 'ahbn_delete_service_action', 'ahbn_del_service_nonce' ); ?>
												<input type="hidden" name="del_service_id" value="<?php echo esc_attr( (string) $item->id ); ?>">
												<button type="submit" name="ahbn_delete_service" class="ahbn-quick-btn is-delete" title="<?php esc_attr_e( 'Delete Extra Service', 'awesome-hotel-booking' ); ?>">
													<span class="dashicons dashicons-trash" aria-hidden="true"></span>
												</button>
											</form>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="6" style="text-align: center; padding: 50px; color: #94a3b8;">
									<span class="dashicons dashicons-coffee" style="font-size: 36px; width: 36px; height: 36px; color: #cbd5e1;" aria-hidden="true"></span>
									<p style="margin: 8px 0 0;"><?php esc_html_e( 'No extra services or add-ons found. Click "Add Extra Service" to create one.', 'awesome-hotel-booking' ); ?></p>
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