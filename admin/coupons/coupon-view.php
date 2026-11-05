<?php
/**
 * Master Coupon View & Single Coupon Folio Screen.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the dedicated single coupon view card.
 *
 * @param int $coupon_id Target coupon ID to display.
 * @return void
 */
function ahbn_render_single_coupon_view( $coupon_id = 0 ) {
	global $wpdb;
	$table_coupons    = $wpdb->prefix . 'ahbn_coupons';
	$base_coupons_url = admin_url( 'admin.php?page=awesome_hotel_booking&tab=coupons' );

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$coupon = $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$table_coupons} WHERE id = %d", absint( $coupon_id ) )
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	if ( ! $coupon ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Coupon record not found.', 'awesome-hotel-booking' ) . '</p></div>';
		return;
	}

	$today       = current_time( 'Y-m-d' );
	$is_expired  = ! empty( $coupon->expiry_date ) && $coupon->expiry_date < $today;
	$limit_hit   = ( $coupon->usage_limit > 0 && $coupon->usage_count >= $coupon->usage_limit );
	$status_base = sanitize_key( $coupon->status );

	$status_class = 'status-active-now';
	$status_text  = __( 'Active & Bookable', 'awesome-hotel-booking' );

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

	$edit_url = add_query_arg( array( 'action' => 'edit', 'coupon_id' => $coupon->id ), $base_coupons_url );
	?>
	<div class="ahbn-panel" style="max-width: 740px; margin: 0 auto; width: 100%;">
		<div class="ahbn-panel-head" style="display:flex; justify-content:space-between; align-items:center;">
			<div style="display:flex; align-items:center; gap:8px;">
				<span class="dashicons dashicons-tickets-alt" style="color:#c5a880; font-size:18px;"></span>
				<h2><?php printf( esc_html__( 'Coupon Folio: %s', 'awesome-hotel-booking' ), esc_html( $coupon->code ) ); ?></h2>
			</div>
			<span class="ahbn-status-pill <?php echo esc_attr( $status_class ); ?>">
				<?php echo esc_html( $status_text ); ?>
			</span>
		</div>

		<div class="ahbn-panel-body">
			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
				<div class="ahbn-field-group">
					<span class="ahbn-label"><?php esc_html_e( 'Promotional Code', 'awesome-hotel-booking' ); ?></span>
					<strong class="font-mono" style="font-size: 20px; color: #0a192f; letter-spacing:1px;"><?php echo esc_html( $coupon->code ); ?></strong>
				</div>

				<div class="ahbn-field-group">
					<span class="ahbn-label"><?php esc_html_e( 'Discount Benefit', 'awesome-hotel-booking' ); ?></span>
					<strong class="font-mono" style="font-size: 20px; color: #059669;">
						<?php
						if ( 'percentage' === $coupon->discount_type ) {
							echo esc_html( number_format( (float) $coupon->discount_value, 0 ) ) . '% OFF';
						} else {
							echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( (float) $coupon->discount_value ) : number_format_i18n( (float) $coupon->discount_value, 2 ) ) . ' OFF';
						}
						?>
					</strong>
				</div>
			</div>

			<div class="ahbn-spend-card" style="margin-top: 10px;">
				<span class="ahbn-spend-label"><?php esc_html_e( 'Reservation Spending Guardrails', 'awesome-hotel-booking' ); ?></span>
				<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
					<div>
						<span class="ahbn-label"><?php esc_html_e( 'Minimum Spend', 'awesome-hotel-booking' ); ?></span>
						<p style="margin: 4px 0 0; font-size: 14px; font-weight: 700; color: #0a192f;" class="font-mono">
							<?php echo (float) $coupon->min_spend > 0 ? esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( (float) $coupon->min_spend ) : number_format_i18n( (float) $coupon->min_spend, 2 ) ) : esc_html__( 'No minimum required', 'awesome-hotel-booking' ); ?>
						</p>
					</div>
					<div>
						<span class="ahbn-label"><?php esc_html_e( 'Maximum Eligible Cap', 'awesome-hotel-booking' ); ?></span>
						<p style="margin: 4px 0 0; font-size: 14px; font-weight: 700; color: #0a192f;" class="font-mono">
							<?php echo (float) $coupon->max_spend > 0 ? esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( (float) $coupon->max_spend ) : number_format_i18n( (float) $coupon->max_spend, 2 ) ) : esc_html__( 'No maximum cap', 'awesome-hotel-booking' ); ?>
						</p>
					</div>
				</div>
			</div>

			<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 8px;">
				<div class="ahbn-field-group">
					<span class="ahbn-label"><?php esc_html_e( 'Redemptions', 'awesome-hotel-booking' ); ?></span>
					<p style="margin: 4px 0 0; font-size: 14px; font-family: 'JetBrains Mono', monospace; font-weight: 700; color:#0a192f;">
						<?php echo esc_html( (string) $coupon->usage_count ); ?> / <?php echo $coupon->usage_limit > 0 ? esc_html( (string) $coupon->usage_limit ) : '&infin;'; ?>
					</p>
				</div>

				<div class="ahbn-field-group">
					<span class="ahbn-label"><?php esc_html_e( 'Expiration Date', 'awesome-hotel-booking' ); ?></span>
					<p style="margin: 4px 0 0; font-size: 13px; color: #64748b; font-weight:600;">
						<?php echo esc_html( ! empty( $coupon->expiry_date ) ? wp_date( get_option( 'date_format' ), strtotime( $coupon->expiry_date ) ) : __( 'Never Expires', 'awesome-hotel-booking' ) ); ?>
					</p>
				</div>
			</div>
		</div>

		<div class="ahbn-panel-footer" style="display:flex; justify-content:space-between; align-items:center;">
			<a href="<?php echo esc_url( $base_coupons_url ); ?>" class="ahbn-btn-cancel">
				&larr; <?php esc_html_e( 'Back to All Coupons', 'awesome-hotel-booking' ); ?>
			</a>
			<a href="<?php echo esc_url( $edit_url ); ?>" class="ahbn-btn-save" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
				<span class="dashicons dashicons-edit" style="font-size: 15px; width: 15px; height: 15px; color: #c5a880;"></span>
				<?php esc_html_e( 'Edit Coupon', 'awesome-hotel-booking' ); ?>
			</a>
		</div>
	</div>
	<?php
}

/**
 * Master coupon router wrapper.
 *
 * @return void
 */
function ahbn_render_coupon_view() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$action    = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	$coupon_id = isset( $_GET['coupon_id'] ) ? absint( wp_unslash( $_GET['coupon_id'] ) ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	?>

	<!-- 1. Top Notices Container -->
	<div id="ahbn-notices-container" class="ahbn-top-notices-area">
		<?php
		if ( ( 'add' === $action || 'edit' === $action ) && function_exists( 'ahbn_process_coupon_post' ) ) {
			ahbn_process_coupon_post();
		}
		?>
	</div>

	<!-- 2. Subtabs Navigation Bar -->
	<?php ahbn_render_coupon_tabs( $action, $coupon_id ); ?>

	<!-- 3. View Routing -->
	<div class="ahbn-coupon-wrap">
		<?php
		if ( 'view' === $action && $coupon_id > 0 ) {
			ahbn_render_single_coupon_view( $coupon_id );
		} elseif ( 'add' === $action || 'edit' === $action ) {
			ahbn_render_coupon_add_edit( $coupon_id );
		} else {
			ahbn_render_coupon_list();
		}
		?>
	</div>

	<style>
		/* Suppress WP notices from leaking into the sidebar */
		.ahbn-sidebar-container .notice,
		.ahbn-sidebar-container div.error,
		.ahbn-sidebar-container div.updated {
			display: none !important;
		}

		/* Top Notices Area */
		.ahbn-top-notices-area {
			margin-bottom: 18px;
		}

		.ahbn-top-notices-area:empty {
			margin-bottom: 0;
		}

		.ahbn-top-notices-area .notice,
		.ahbn-top-notices-area div.error,
		.ahbn-top-notices-area div.updated {
			margin: 0 0 16px 0 !important;
			padding: 12px 18px !important;
			border-radius: 10px !important;
			border-left-width: 4px !important;
			background: #ffffff !important;
			box-shadow: 0 2px 10px rgba(10, 25, 47, 0.05) !important;
			font-family: 'Plus Jakarta Sans', sans-serif !important;
			font-size: 13px !important;
			font-weight: 600 !important;
			display: flex;
			align-items: center;
			justify-content: space-between;
		}

		.ahbn-top-notices-area .notice-error,
		.ahbn-top-notices-area div.error {
			border-color: #ef4444 !important;
			background: #fff5f5 !important;
			color: #991b1b !important;
		}

		.ahbn-top-notices-area .notice-success,
		.ahbn-top-notices-area div.updated {
			border-color: #10b981 !important;
			background: #ecfdf5 !important;
			color: #065f46 !important;
		}

		/* Subtabs Navigation Bar */
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

		/* Master Container */
		.ahbn-coupon-wrap {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
			margin-top: 0 !important;
		}

		/* Header Card */
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

		.ahbn-stat-active-duty { border-left: 4px solid #10b981; }
		.ahbn-stat-active-duty .ahbn-stat-label,
		.ahbn-stat-active-duty .ahbn-stat-value { color: #059669; }

		.ahbn-stat-redemptions { border-left: 4px solid #c5a880; }
		.ahbn-stat-redemptions .ahbn-stat-label { color: #b39264; }

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

		/* Form Panels (Add / Edit / Single View) */
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

		.font-mono {
			font-family: 'JetBrains Mono', monospace !important;
		}

		@media (max-width: 1024px) {
			.ahbn-stats-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>
	<?php
}