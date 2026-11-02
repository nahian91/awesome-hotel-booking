<?php
/**
 * Promotional Codes & Discount Coupons Management View with Sub-Navigation Tabs.
 *
 * Built on high-performance custom relational database tables:
 * {$wpdb->prefix}ahbn_coupons
 * Includes top sub-tabs: All Coupons & Add Coupon.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Coupons & Promo Codes Tab with sub-views.
 *
 * @return void
 */
function ahbn_coupons_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	global $wpdb;
	$table_coupons = $wpdb->prefix . 'ahbn_coupons';

	// 1. Resolve Routing Parameters.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$action    = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	$coupon_id = isset( $_GET['coupon_id'] ) ? absint( wp_unslash( $_GET['coupon_id'] ) ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$base_coupons_url = admin_url( 'admin.php?page=awesome_hotel_booking&tab=coupons' );
	$add_coupon_url   = add_query_arg( 'action', 'add', $base_coupons_url );

	// 2. Handle POST Submissions (Save / Update / Delete).
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
		// Save or Edit Coupon.
		if ( isset( $_POST['ahbn_save_coupon'] ) ) {
			$nonce = isset( $_POST['ahbn_coupon_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_coupon_nonce'] ) ) : '';

			if ( ! wp_verify_nonce( $nonce, 'ahbn_save_coupon_action' ) ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please refresh the page.', 'awesome-hotel-booking' ) . '</p></div>';
			} else {
				$post_coupon_id = isset( $_POST['coupon_id'] ) ? absint( wp_unslash( $_POST['coupon_id'] ) ) : 0;
				$code           = isset( $_POST['code'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['code'] ) ) ) : '';
				$discount_type  = isset( $_POST['discount_type'] ) ? sanitize_key( wp_unslash( $_POST['discount_type'] ) ) : 'percentage';
				$discount_value = isset( $_POST['discount_value'] ) ? (float) wp_unslash( $_POST['discount_value'] ) : 0.00;
				$min_spend      = isset( $_POST['min_spend'] ) ? (float) wp_unslash( $_POST['min_spend'] ) : 0.00;
				$max_spend      = isset( $_POST['max_spend'] ) ? (float) wp_unslash( $_POST['max_spend'] ) : 0.00;
				$usage_limit    = isset( $_POST['usage_limit'] ) ? absint( wp_unslash( $_POST['usage_limit'] ) ) : 0;
				$expiry_date    = isset( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : '';
				$status         = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'active';

				if ( empty( $code ) || $discount_value <= 0 ) {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please provide a valid coupon code and discount value.', 'awesome-hotel-booking' ) . '</p></div>';
				} else {
					// Check for duplicate code.
					// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$existing = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT id FROM {$table_coupons} WHERE code = %s AND id != %d",
							$code,
							$post_coupon_id
						)
					);

					if ( $existing ) {
						echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'This coupon code already exists. Please choose a unique code.', 'awesome-hotel-booking' ) . '</p></div>';
					} else {
						$coupon_data = array(
							'code'           => $code,
							'discount_type'  => $discount_type,
							'discount_value' => $discount_value,
							'min_spend'      => $min_spend,
							'max_spend'      => $max_spend,
							'usage_limit'    => $usage_limit,
							'expiry_date'    => ! empty( $expiry_date ) ? $expiry_date : null,
							'status'         => $status,
						);

						$format = array( '%s', '%s', '%f', '%f', '%f', '%d', '%s', '%s' );

						if ( $post_coupon_id > 0 ) {
							$wpdb->update(
								$table_coupons,
								$coupon_data,
								array( 'id' => $post_coupon_id ),
								$format,
								array( '%d' )
							);
							echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Promotional coupon updated.', 'awesome-hotel-booking' ) . '</p></div>';
						} else {
							$wpdb->insert( $table_coupons, $coupon_data, $format );
							echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'New promotional code created.', 'awesome-hotel-booking' ) . '</p></div>';
						}

						$action    = '';
						$coupon_id = 0;
					}
					// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				}
			}
		}

		// Delete Coupon.
		if ( isset( $_POST['ahbn_delete_coupon'] ) ) {
			$del_nonce = isset( $_POST['ahbn_del_coupon_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_del_coupon_nonce'] ) ) : '';
			$del_id    = isset( $_POST['del_coupon_id'] ) ? absint( wp_unslash( $_POST['del_coupon_id'] ) ) : 0;

			if ( wp_verify_nonce( $del_nonce, 'ahbn_delete_coupon_action' ) && $del_id > 0 ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete( $table_coupons, array( 'id' => $del_id ), array( '%d' ) );
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Coupon removed.', 'awesome-hotel-booking' ) . '</p></div>';
			}
		}
	}

	// 3. Query All Coupons & Metrics.
	$today = current_time( 'Y-m-d' );
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$coupons = $wpdb->get_results( "SELECT * FROM {$table_coupons} ORDER BY created_at DESC" );

	$metrics = $wpdb->get_row(
		"SELECT 
			COUNT(*) AS total_coupons,
			COUNT(CASE WHEN status = 'active' AND (expiry_date IS NULL OR expiry_date >= CURDATE()) THEN 1 END) AS active_coupons,
			COALESCE(SUM(usage_count), 0) AS total_redemptions
		 FROM {$table_coupons}"
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	$total_coupons     = $metrics ? (int) $metrics->total_coupons : 0;
	$active_coupons    = $metrics ? (int) $metrics->active_coupons : 0;
	$total_redemptions = $metrics ? (int) $metrics->total_redemptions : 0;

	// Determine Active Tab States.
	$is_all_active  = ( empty( $action ) );
	$is_add_active  = ( 'add' === $action );
	$is_edit_active = ( 'edit' === $action && $coupon_id > 0 );
	?>

	<!-- Top Sub-Tabs Navigation Bar -->
	<div class="ahbn-subtabs-nav-bar">
		<a href="<?php echo esc_url( $base_coupons_url ); ?>" class="ahbn-subtab-link <?php echo $is_all_active ? 'is-active' : ''; ?>">
			<span class="dashicons dashicons-tickets-alt" aria-hidden="true"></span>
			<span><?php esc_html_e( 'All Coupons', 'awesome-hotel-booking' ); ?></span>
		</a>

		<a href="<?php echo esc_url( $add_coupon_url ); ?>" class="ahbn-subtab-link <?php echo $is_add_active ? 'is-active' : ''; ?>">
			<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
			<span><?php esc_html_e( 'Add Coupon', 'awesome-hotel-booking' ); ?></span>
		</a>

		<?php if ( $is_edit_active ) : ?>
			<span class="ahbn-subtab-link is-active is-context-tab">
				<span class="dashicons dashicons-edit" aria-hidden="true"></span>
				<span><?php echo esc_html( sprintf( /* translators: %d: coupon ID */ __( 'Edit Coupon #%d', 'awesome-hotel-booking' ), $coupon_id ) ); ?></span>
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

		.ahbn-coupon-wrap {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-coupon-header {
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

		.ahbn-coupon-title {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 24px;
			font-weight: 700;
			color: #0a192f;
			margin: 0 0 4px;
		}

		.ahbn-coupon-subtitle {
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

		.ahbn-stat-redemptions {
			border-left: 4px solid #c5a880;
		}
		.ahbn-stat-redemptions .ahbn-stat-label {
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

		.ahbn-coupons-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 13px;
		}

		.ahbn-coupons-table th {
			background: #f8fafc !important;
			padding: 12px 16px !important;
			font-weight: 700 !important;
			font-size: 11px !important;
			text-transform: uppercase !important;
			letter-spacing: 0.05em !important;
			color: #64748b !important;
			border-bottom: 1px solid #e2e8f0 !important;
		}

		.ahbn-coupons-table td {
			padding: 14px 16px !important;
			vertical-align: middle !important;
			border-bottom: 1px solid #f1f5f9 !important;
		}

		.ahbn-coupon-badge {
			font-family: 'JetBrains Mono', monospace;
			background: #f8fafc;
			color: #0a192f;
			font-weight: 800;
			font-size: 12px;
			padding: 4px 10px;
			border-radius: 6px;
			border: 1px solid #e2e8f0;
			letter-spacing: 1px;
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
		.status-capped     { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
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
		.ahbn-select {
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
		.ahbn-select:focus {
			border-color: #0a192f !important;
			box-shadow: 0 0 0 2px rgba(10, 25, 47, 0.1) !important;
		}

		.ahbn-spend-card {
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 10px;
			padding: 16px;
		}

		.ahbn-spend-label {
			display: block;
			font-size: 11px;
			font-weight: 800;
			text-transform: uppercase;
			color: #64748b;
			margin-bottom: 12px;
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

	<div class="ahbn-coupon-wrap">
		<?php if ( 'add' === $action || 'edit' === $action ) : ?>
			<?php
			$edit_coupon = null;
			if ( 'edit' === $action && $coupon_id > 0 ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$edit_coupon = $wpdb->get_row(
					$wpdb->prepare( "SELECT * FROM {$table_coupons} WHERE id = %d", $coupon_id )
				);
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
			?>
			<!-- Add / Edit Coupon Dedicated Panel -->
			<div class="ahbn-panel" style="max-width: 720px; margin: 0 auto; width: 100%;">
				<div class="ahbn-panel-head">
					<span class="dashicons dashicons-tickets-alt" aria-hidden="true"></span>
					<h2>
						<?php
						if ( $edit_coupon ) {
							/* translators: %s: coupon code */
							printf( esc_html__( 'Edit Promo Code: %s', 'awesome-hotel-booking' ), esc_html( $edit_coupon->code ) );
						} else {
							esc_html_e( 'Create Promotional Discount Code', 'awesome-hotel-booking' );
						}
						?>
					</h2>
				</div>

				<form method="post" action="<?php echo esc_url( $base_coupons_url ); ?>">
					<?php wp_nonce_field( 'ahbn_save_coupon_action', 'ahbn_coupon_nonce' ); ?>
					<input type="hidden" name="coupon_id" value="<?php echo esc_attr( (string) ( $edit_coupon ? $edit_coupon->id : 0 ) ); ?>">

					<div class="ahbn-panel-body">
						<div class="ahbn-field-group">
							<label for="coupon_code" class="ahbn-label"><?php esc_html_e( 'Promotional Code * (Uppercase)', 'awesome-hotel-booking' ); ?></label>
							<input type="text" name="code" id="coupon_code" class="ahbn-input" required value="<?php echo esc_attr( $edit_coupon ? $edit_coupon->code : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. SUMMER25, VIPGUEST, WELCOME10', 'awesome-hotel-booking' ); ?>" style="text-transform: uppercase; font-family: 'JetBrains Mono', monospace; font-weight: 700; letter-spacing: 1px;">
						</div>

						<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 14px;">
							<div class="ahbn-field-group">
								<label for="coupon_discount_type" class="ahbn-label"><?php esc_html_e( 'Discount Benefit Type', 'awesome-hotel-booking' ); ?></label>
								<select name="discount_type" id="coupon_discount_type" class="ahbn-select">
									<option value="percentage" <?php selected( $edit_coupon ? $edit_coupon->discount_type : 'percentage', 'percentage' ); ?>>
										<?php esc_html_e( 'Percentage Discount (%)', 'awesome-hotel-booking' ); ?>
									</option>
									<option value="fixed" <?php selected( $edit_coupon ? $edit_coupon->discount_type : '', 'fixed' ); ?>>
										<?php esc_html_e( 'Fixed Amount Off (Currency)', 'awesome-hotel-booking' ); ?>
									</option>
								</select>
							</div>

							<div class="ahbn-field-group">
								<label for="coupon_discount_value" class="ahbn-label"><?php esc_html_e( 'Discount Value *', 'awesome-hotel-booking' ); ?></label>
								<input type="number" step="0.01" min="0.01" name="discount_value" id="coupon_discount_value" class="ahbn-input" required value="<?php echo esc_attr( (string) ( $edit_coupon ? $edit_coupon->discount_value : '10' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. 15 for 15% or 50.00', 'awesome-hotel-booking' ); ?>">
							</div>
						</div>

						<!-- Reservation Spending Guardrails -->
						<div class="ahbn-spend-card">
							<span class="ahbn-spend-label">
								<?php esc_html_e( 'Reservation Spending Guardrails (Optional)', 'awesome-hotel-booking' ); ?>
							</span>

							<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 14px;">
								<div class="ahbn-field-group">
									<label for="coupon_min_spend" class="ahbn-label"><?php esc_html_e( 'Minimum Spend', 'awesome-hotel-booking' ); ?></label>
									<input type="number" step="0.01" min="0" name="min_spend" id="coupon_min_spend" class="ahbn-input" value="<?php echo esc_attr( (string) ( $edit_coupon ? $edit_coupon->min_spend : '0.00' ) ); ?>">
									<small style="color:#64748b; font-size:11px;"><?php esc_html_e( '0 = No minimum booking required', 'awesome-hotel-booking' ); ?></small>
								</div>

								<div class="ahbn-field-group">
									<label for="coupon_max_spend" class="ahbn-label"><?php esc_html_e( 'Maximum Eligible Spend', 'awesome-hotel-booking' ); ?></label>
									<input type="number" step="0.01" min="0" name="max_spend" id="coupon_max_spend" class="ahbn-input" value="<?php echo esc_attr( (string) ( $edit_coupon ? $edit_coupon->max_spend : '0.00' ) ); ?>">
									<small style="color:#64748b; font-size:11px;"><?php esc_html_e( '0 = No maximum spend cap', 'awesome-hotel-booking' ); ?></small>
								</div>
							</div>
						</div>

						<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 14px;">
							<div class="ahbn-field-group">
								<label for="coupon_usage_limit" class="ahbn-label"><?php esc_html_e( 'Usage Limit Cap', 'awesome-hotel-booking' ); ?></label>
								<input type="number" min="0" name="usage_limit" id="coupon_usage_limit" class="ahbn-input" value="<?php echo esc_attr( (string) ( $edit_coupon ? $edit_coupon->usage_limit : 0 ) ); ?>">
								<small style="color:#64748b; font-size:11px;"><?php esc_html_e( '0 = Unlimited redemptions', 'awesome-hotel-booking' ); ?></small>
							</div>

							<div class="ahbn-field-group">
								<label for="coupon_expiry_date" class="ahbn-label"><?php esc_html_e( 'Expiration Date', 'awesome-hotel-booking' ); ?></label>
								<input type="date" name="expiry_date" id="coupon_expiry_date" class="ahbn-input" value="<?php echo esc_attr( (string) ( $edit_coupon && $edit_coupon->expiry_date ? $edit_coupon->expiry_date : '' ) ); ?>">
							</div>
						</div>

						<div class="ahbn-field-group">
							<label for="coupon_status" class="ahbn-label"><?php esc_html_e( 'Campaign Status', 'awesome-hotel-booking' ); ?></label>
							<select name="status" id="coupon_status" class="ahbn-select">
								<option value="active" <?php selected( $edit_coupon ? $edit_coupon->status : 'active', 'active' ); ?>>
									<?php esc_html_e( 'Active & Available for Booking', 'awesome-hotel-booking' ); ?>
								</option>
								<option value="inactive" <?php selected( $edit_coupon ? $edit_coupon->status : '', 'inactive' ); ?>>
									<?php esc_html_e( 'Inactive / Disabled', 'awesome-hotel-booking' ); ?>
								</option>
							</select>
						</div>
					</div>

					<div class="ahbn-panel-footer">
						<a href="<?php echo esc_url( $base_coupons_url ); ?>" class="ahbn-btn-cancel">
							<?php esc_html_e( 'Cancel', 'awesome-hotel-booking' ); ?>
						</a>
						<button type="submit" name="ahbn_save_coupon" class="ahbn-btn-save">
							<?php echo $edit_coupon ? esc_html__( 'Update Coupon Code', 'awesome-hotel-booking' ) : esc_html__( 'Save Coupon Code', 'awesome-hotel-booking' ); ?>
						</button>
					</div>
				</form>
			</div>

		<?php else : ?>

			<!-- Header Toolbar (All Coupons View) -->
			<header class="ahbn-coupon-header">
				<div>
					<h2 class="ahbn-coupon-title"><?php esc_html_e( 'Promotional Codes & Member Discounts', 'awesome-hotel-booking' ); ?></h2>
					<p class="ahbn-coupon-subtitle">
						<?php esc_html_e( 'Manage guest promotional campaigns, percentage vouchers, minimum stay spending thresholds, and usage caps.', 'awesome-hotel-booking' ); ?>
					</p>
				</div>
			</header>

			<!-- Strategy KPI Cards -->
			<div class="ahbn-stats-grid">
				<div class="ahbn-stat-card">
					<span class="ahbn-stat-label"><?php esc_html_e( 'Total Promo Codes', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-stat-value"><?php echo esc_html( (string) $total_coupons ); ?></span>
				</div>
				<div class="ahbn-stat-card ahbn-stat-active-duty">
					<span class="ahbn-stat-label"><?php esc_html_e( 'Active & Redeemable', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-stat-value"><?php echo esc_html( (string) $active_coupons ); ?></span>
				</div>
				<div class="ahbn-stat-card ahbn-stat-redemptions">
					<span class="ahbn-stat-label"><?php esc_html_e( 'Lifetime Redemptions', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-stat-value"><?php echo esc_html( (string) $total_redemptions ); ?></span>
				</div>
			</div>

			<!-- Coupons Table -->
			<div class="ahbn-table-container">
				<table class="ahbn-coupons-table widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Coupon Code', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Discount Benefit', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Spend Limits (Min / Max)', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Usage / Limit', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Expiration Date', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Status', 'awesome-hotel-booking' ); ?></th>
							<th style="text-align: right; width: 120px;"><?php esc_html_e( 'Actions', 'awesome-hotel-booking' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $coupons ) ) : ?>
							<?php foreach ( $coupons as $item ) :
								$is_expired  = ! empty( $item->expiry_date ) && $item->expiry_date < $today;
								$limit_hit   = ( $item->usage_limit > 0 && $item->usage_count >= $item->usage_limit );
								$status_base = sanitize_key( $item->status );

								$status_class = 'status-active-now';
								$status_text  = __( 'Active', 'awesome-hotel-booking' );

								if ( 'inactive' === $status_base ) {
									$status_class = 'status-disabled';
									$status_text  = __( 'Disabled', 'awesome-hotel-booking' );
								} elseif ( $is_expired ) {
									$status_class = 'status-disabled';
									$status_text  = __( 'Expired', 'awesome-hotel-booking' );
								} elseif ( $limit_hit ) {
									$status_class = 'status-capped';
									$status_text  = __( 'Limit Reached', 'awesome-hotel-booking' );
								}

								$discount_val = (float) $item->discount_value;
								$min_sp       = (float) $item->min_spend;
								$max_sp       = (float) $item->max_spend;
								$edit_link    = add_query_arg( array( 'action' => 'edit', 'coupon_id' => $item->id ), $base_coupons_url );
								?>
								<tr>
									<td>
										<span class="ahbn-coupon-badge">
											<?php echo esc_html( $item->code ); ?>
										</span>
									</td>
									<td>
										<?php if ( 'percentage' === $item->discount_type ) : ?>
											<strong style="color: #059669; font-size: 14px; font-family: 'JetBrains Mono', monospace;">
												<?php echo esc_html( number_format( $discount_val, 0 ) ); ?>% OFF
											</strong>
											<small style="display: block; color: #64748b; font-size: 11px;"><?php esc_html_e( 'Percentage deduction', 'awesome-hotel-booking' ); ?></small>
										<?php else : ?>
											<strong style="color: #0a192f; font-size: 14px; font-family: 'JetBrains Mono', monospace;">
												<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $discount_val ) : (string) number_format_i18n( $discount_val, 2 ) ); ?> OFF
											</strong>
											<small style="display: block; color: #64748b; font-size: 11px;"><?php esc_html_e( 'Flat stay discount', 'awesome-hotel-booking' ); ?></small>
										<?php endif; ?>
									</td>
									<td>
										<div style="font-size: 12px; line-height: 1.5; color: #334155;">
											<?php if ( $min_sp > 0.00 ) : ?>
												<div><strong>Min:</strong> <?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $min_sp ) : number_format_i18n( $min_sp, 2 ) ); ?></div>
											<?php else : ?>
												<div style="color: #94a3b8;"><?php esc_html_e( 'No min spend', 'awesome-hotel-booking' ); ?></div>
											<?php endif; ?>

											<?php if ( $max_sp > 0.00 ) : ?>
												<div><strong>Max:</strong> <?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $max_sp ) : number_format_i18n( $max_sp, 2 ) ); ?></div>
											<?php endif; ?>
										</div>
									</td>
									<td>
										<span style="font-size: 13px; font-weight: 700; color: #0a192f; font-family: 'JetBrains Mono', monospace;">
											<?php echo esc_html( (string) $item->usage_count ); ?>
											<span style="color: #94a3b8; font-weight: 400;">
												/ <?php echo $item->usage_limit > 0 ? esc_html( (string) $item->usage_limit ) : '&infin;'; ?>
											</span>
										</span>
									</td>
									<td>
										<span style="font-size: 12px; color: #64748b;">
											<?php echo esc_html( ! empty( $item->expiry_date ) ? wp_date( get_option( 'date_format' ), strtotime( $item->expiry_date ) ) : __( 'Never Expires', 'awesome-hotel-booking' ) ); ?>
										</span>
									</td>
									<td>
										<span class="ahbn-status-pill <?php echo esc_attr( $status_class ); ?>">
											<?php echo esc_html( $status_text ); ?>
										</span>
									</td>
									<td style="text-align: right;">
										<div class="ahbn-action-btns">
											<a href="<?php echo esc_url( $edit_link ); ?>" class="ahbn-quick-btn" title="<?php esc_attr_e( 'Edit Coupon', 'awesome-hotel-booking' ); ?>">
												<span class="dashicons dashicons-edit" aria-hidden="true"></span>
											</a>

											<form method="post" action="<?php echo esc_url( $base_coupons_url ); ?>" style="display: inline-block; margin: 0;" onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this coupon code?', 'awesome-hotel-booking' ) ); ?>');">
												<?php wp_nonce_field( 'ahbn_delete_coupon_action', 'ahbn_del_coupon_nonce' ); ?>
												<input type="hidden" name="del_coupon_id" value="<?php echo esc_attr( (string) $item->id ); ?>">
												<button type="submit" name="ahbn_delete_coupon" class="ahbn-quick-btn is-delete" title="<?php esc_attr_e( 'Delete Coupon', 'awesome-hotel-booking' ); ?>">
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
									<span class="dashicons dashicons-tickets-alt" style="font-size: 36px; width: 36px; height: 36px; color: #cbd5e1;" aria-hidden="true"></span>
									<p style="margin: 8px 0 0;"><?php esc_html_e( 'No promotional codes found. Create your first campaign above.', 'awesome-hotel-booking' ); ?></p>
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