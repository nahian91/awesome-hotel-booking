<?php
/**
 * Dynamic Pricing, Seasonal Rates & Minimum Stay Rules Management.
 *
 * Built on high-performance custom relational database tables:
 * {$wpdb->prefix}ahbn_rates & {$wpdb->prefix}ahbn_rooms.
 * Includes top sub-tabs: All Rate Tiers & Add Rate Tier.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Rates & Seasons Management Tab with sub-views.
 *
 * @return void
 */
function ahbn_pricing_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	global $wpdb;
	$table_rates = $wpdb->prefix . 'ahbn_rates';
	$table_rooms = $wpdb->prefix . 'ahbn_rooms';

	// 1. Resolve Routing Parameters.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$action  = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	$rate_id = isset( $_GET['rate_id'] ) ? absint( wp_unslash( $_GET['rate_id'] ) ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$base_rates_url = admin_url( 'admin.php?page=awesome_hotel_booking&tab=pricing' );
	$add_rate_url   = add_query_arg( 'action', 'add', $base_rates_url );

	// 2. Handle POST Submissions (Save / Update / Delete).
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
		// Save or Edit Rule.
		if ( isset( $_POST['ahbn_save_rate'] ) ) {
			$nonce = isset( $_POST['ahbn_rate_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_rate_nonce'] ) ) : '';

			if ( ! wp_verify_nonce( $nonce, 'ahbn_save_rate_action' ) ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please refresh the page.', 'awesome-hotel-booking' ) . '</p></div>';
			} else {
				$post_rate_id    = isset( $_POST['rate_id'] ) ? absint( wp_unslash( $_POST['rate_id'] ) ) : 0;
				$title           = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
				$room_id         = isset( $_POST['room_id'] ) ? absint( wp_unslash( $_POST['room_id'] ) ) : 0;
				$start_date      = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
				$end_date        = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '';
				$rate_multiplier = isset( $_POST['rate_multiplier'] ) ? (float) wp_unslash( $_POST['rate_multiplier'] ) : 1.00;
				$fixed_rate      = isset( $_POST['fixed_rate'] ) ? (float) wp_unslash( $_POST['fixed_rate'] ) : 0.00;
				$min_stay        = isset( $_POST['min_stay'] ) ? max( 1, absint( wp_unslash( $_POST['min_stay'] ) ) ) : 1;

				if ( empty( $title ) || empty( $start_date ) || empty( $end_date ) ) {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please complete the rule title and date ranges.', 'awesome-hotel-booking' ) . '</p></div>';
				} elseif ( strtotime( $end_date ) < strtotime( $start_date ) ) {
					echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'The end date cannot precede the start date.', 'awesome-hotel-booking' ) . '</p></div>';
				} else {
					$rate_data = array(
						'title'           => $title,
						'room_id'         => $room_id,
						'start_date'      => $start_date,
						'end_date'        => $end_date,
						'rate_multiplier' => $rate_multiplier > 0 ? $rate_multiplier : 1.00,
						'fixed_rate'      => $fixed_rate,
						'min_stay'        => $min_stay,
					);

					$format = array( '%s', '%d', '%s', '%s', '%f', '%f', '%d' );

					// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					if ( $post_rate_id > 0 ) {
						$wpdb->update(
							$table_rates,
							$rate_data,
							array( 'id' => $post_rate_id ),
							$format,
							array( '%d' )
						);
						echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Seasonal pricing rule updated.', 'awesome-hotel-booking' ) . '</p></div>';
					} else {
						$wpdb->insert( $table_rates, $rate_data, $format );
						echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'New seasonal pricing rule scheduled.', 'awesome-hotel-booking' ) . '</p></div>';
					}
					// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

					$action  = '';
					$rate_id = 0;
				}
			}
		}

		// Delete Rule.
		if ( isset( $_POST['ahbn_delete_rate'] ) ) {
			$del_nonce = isset( $_POST['ahbn_del_rate_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_del_rate_nonce'] ) ) : '';
			$del_id    = isset( $_POST['del_rate_id'] ) ? absint( wp_unslash( $_POST['del_rate_id'] ) ) : 0;

			if ( wp_verify_nonce( $del_nonce, 'ahbn_delete_rate_action' ) && $del_id > 0 ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete( $table_rates, array( 'id' => $del_id ), array( '%d' ) );
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Pricing rule removed.', 'awesome-hotel-booking' ) . '</p></div>';
			}
		}
	}

	// 3. Query Rooms for Assignment Selection.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$rooms = $wpdb->get_results(
		"SELECT id, room_number, title, room_type, price_per_night 
		 FROM {$table_rooms} 
		 WHERE status = 'publish' 
		 ORDER BY room_number ASC"
	);

	// Query Active Rate Rules with Room Joining.
	$today = current_time( 'Y-m-d' );
	$rates = $wpdb->get_results(
		"SELECT 
			rt.*,
			r.room_number,
			r.title AS room_title,
			r.price_per_night AS base_room_price
		 FROM {$table_rates} rt
		 LEFT JOIN {$table_rooms} r ON rt.room_id = r.id
		 ORDER BY rt.start_date DESC"
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	// Metrics Aggregations.
	$active_rules   = 0;
	$upcoming_rules = 0;
	if ( ! empty( $rates ) ) {
		foreach ( $rates as $r ) {
			if ( $r->start_date <= $today && $r->end_date >= $today ) {
				$active_rules++;
			} elseif ( $r->start_date > $today ) {
				$upcoming_rules++;
			}
		}
	}

	// Determine Active Tab States.
	$is_all_active  = ( empty( $action ) );
	$is_add_active  = ( 'add' === $action );
	$is_edit_active = ( 'edit' === $action && $rate_id > 0 );
	?>

	<!-- Top Sub-Tabs Navigation Bar -->
	<div class="ahbn-subtabs-nav-bar">
		<a href="<?php echo esc_url( $base_rates_url ); ?>" class="ahbn-subtab-link <?php echo $is_all_active ? 'is-active' : ''; ?>">
			<span class="dashicons dashicons-tag" aria-hidden="true"></span>
			<span><?php esc_html_e( 'All Rate Tiers', 'awesome-hotel-booking' ); ?></span>
		</a>

		<a href="<?php echo esc_url( $add_rate_url ); ?>" class="ahbn-subtab-link <?php echo $is_add_active ? 'is-active' : ''; ?>">
			<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
			<span><?php esc_html_e( 'Add Rate Tier', 'awesome-hotel-booking' ); ?></span>
		</a>

		<?php if ( $is_edit_active ) : ?>
			<span class="ahbn-subtab-link is-active is-context-tab">
				<span class="dashicons dashicons-edit" aria-hidden="true"></span>
				<span><?php echo esc_html( sprintf( /* translators: %d: rule ID */ __( 'Edit Rate Tier #%d', 'awesome-hotel-booking' ), $rate_id ) ); ?></span>
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

		.ahbn-pricing-wrap {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-pricing-header {
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

		.ahbn-pricing-title {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 24px;
			font-weight: 700;
			color: #0a192f;
			margin: 0 0 4px;
		}

		.ahbn-pricing-subtitle {
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

		.ahbn-stat-active-today {
			border-left: 4px solid #10b981;
		}
		.ahbn-stat-active-today .ahbn-stat-label,
		.ahbn-stat-active-today .ahbn-stat-value {
			color: #059669;
		}

		.ahbn-stat-upcoming {
			border-left: 4px solid #c5a880;
		}
		.ahbn-stat-upcoming .ahbn-stat-label {
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

		.ahbn-rates-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 13px;
		}

		.ahbn-rates-table th {
			background: #f8fafc !important;
			padding: 12px 16px !important;
			font-weight: 700 !important;
			font-size: 11px !important;
			text-transform: uppercase !important;
			letter-spacing: 0.05em !important;
			color: #64748b !important;
			border-bottom: 1px solid #e2e8f0 !important;
		}

		.ahbn-rates-table td {
			padding: 14px 16px !important;
			vertical-align: middle !important;
			border-bottom: 1px solid #f1f5f9 !important;
		}

		.ahbn-inventory-badge {
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
		.status-scheduled  { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
		.status-expired    { background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; }

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

		.ahbn-formula-card {
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 10px;
			padding: 16px;
		}

		.ahbn-formula-label {
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

	<div class="ahbn-pricing-wrap">
		<?php if ( 'add' === $action || 'edit' === $action ) : ?>
			<?php
			$edit_tier = null;
			if ( 'edit' === $action && $rate_id > 0 ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$edit_tier = $wpdb->get_row(
					$wpdb->prepare( "SELECT * FROM {$table_rates} WHERE id = %d", $rate_id )
				);
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
			?>
			<!-- Add / Edit Rate Tier Dedicated Panel -->
			<div class="ahbn-panel" style="max-width: 720px; margin: 0 auto; width: 100%;">
				<div class="ahbn-panel-head">
					<span class="dashicons dashicons-tag" aria-hidden="true"></span>
					<h2>
						<?php
						if ( $edit_tier ) {
							/* translators: %s: tier title */
							printf( esc_html__( 'Edit Rate Tier: %s', 'awesome-hotel-booking' ), esc_html( $edit_tier->title ) );
						} else {
							esc_html_e( 'Schedule Seasonal Pricing Rule', 'awesome-hotel-booking' );
						}
						?>
					</h2>
				</div>

				<form method="post" action="<?php echo esc_url( $base_rates_url ); ?>">
					<?php wp_nonce_field( 'ahbn_save_rate_action', 'ahbn_rate_nonce' ); ?>
					<input type="hidden" name="rate_id" value="<?php echo esc_attr( (string) ( $edit_tier ? $edit_tier->id : 0 ) ); ?>">

					<div class="ahbn-panel-body">
						<div class="ahbn-field-group">
							<label for="tier_title" class="ahbn-label"><?php esc_html_e( 'Rule / Season Name *', 'awesome-hotel-booking' ); ?></label>
							<input type="text" name="title" id="tier_title" class="ahbn-input" required value="<?php echo esc_attr( $edit_tier ? $edit_tier->title : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Summer Peak Surcharge, Christmas Week, Weekend Surcharge', 'awesome-hotel-booking' ); ?>">
						</div>

						<div class="ahbn-field-group">
							<label for="tier_room_id" class="ahbn-label"><?php esc_html_e( 'Target Inventory', 'awesome-hotel-booking' ); ?></label>
							<select name="room_id" id="tier_room_id" class="ahbn-select">
								<option value="0"><?php esc_html_e( '— Entire Property (All Suites) —', 'awesome-hotel-booking' ); ?></option>
								<?php if ( ! empty( $rooms ) ) : ?>
									<?php foreach ( $rooms as $room ) : ?>
										<option value="<?php echo esc_attr( (string) $room->id ); ?>" <?php selected( $edit_tier ? $edit_tier->room_id : 0, $room->id ); ?>>
											#<?php echo esc_html( $room->room_number . ' - ' . $room->title . ' (Base: ' . ( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $room->price_per_night ) : number_format_i18n( $room->price_per_night, 2 ) ) . ')' ); ?>
										</option>
									<?php endforeach; ?>
								<?php endif; ?>
							</select>
						</div>

						<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 14px;">
							<div class="ahbn-field-group">
								<label for="tier_start_date" class="ahbn-label"><?php esc_html_e( 'Start Date *', 'awesome-hotel-booking' ); ?></label>
								<input type="date" name="start_date" id="tier_start_date" class="ahbn-input" required value="<?php echo esc_attr( $edit_tier ? $edit_tier->start_date : current_time( 'Y-m-d' ) ); ?>">
							</div>

							<div class="ahbn-field-group">
								<label for="tier_end_date" class="ahbn-label"><?php esc_html_e( 'End Date *', 'awesome-hotel-booking' ); ?></label>
								<input type="date" name="end_date" id="tier_end_date" class="ahbn-input" required value="<?php echo esc_attr( $edit_tier ? $edit_tier->end_date : wp_date( 'Y-m-d', strtotime( '+7 days', current_time( 'timestamp' ) ) ) ); ?>">
							</div>
						</div>

						<!-- Pricing Adjustment Formula -->
						<div class="ahbn-formula-card">
							<span class="ahbn-formula-label">
								<?php esc_html_e( 'Pricing Adjustment Formula (Multiplier OR Fixed)', 'awesome-hotel-booking' ); ?>
							</span>

							<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 14px;">
								<div class="ahbn-field-group">
									<label for="tier_multiplier" class="ahbn-label"><?php esc_html_e( 'Rate Multiplier', 'awesome-hotel-booking' ); ?></label>
									<input type="number" step="0.05" min="0.10" max="10.00" name="rate_multiplier" id="tier_multiplier" class="ahbn-input" value="<?php echo esc_attr( (string) ( $edit_tier ? $edit_tier->rate_multiplier : '1.00' ) ); ?>">
									<small style="color:#64748b; font-size:11px;"><?php esc_html_e( '1.25 = +25% surge, 0.85 = -15% off', 'awesome-hotel-booking' ); ?></small>
								</div>

								<div class="ahbn-field-group">
									<label for="tier_fixed" class="ahbn-label"><?php esc_html_e( 'Fixed Price Override', 'awesome-hotel-booking' ); ?></label>
									<input type="number" step="0.01" min="0" name="fixed_rate" id="tier_fixed" class="ahbn-input" value="<?php echo esc_attr( (string) ( $edit_tier ? $edit_tier->fixed_rate : '0.00' ) ); ?>">
									<small style="color:#64748b; font-size:11px;"><?php esc_html_e( 'Leave 0 to use Multiplier', 'awesome-hotel-booking' ); ?></small>
								</div>
							</div>
						</div>

						<div class="ahbn-field-group">
							<label for="tier_minstay" class="ahbn-label"><?php esc_html_e( 'Minimum Length of Stay (LOS Nights)', 'awesome-hotel-booking' ); ?></label>
							<input type="number" min="1" max="90" name="min_stay" id="tier_minstay" class="ahbn-input" value="<?php echo esc_attr( (string) ( $edit_tier ? $edit_tier->min_stay : 1 ) ); ?>">
							<small style="color:#64748b; font-size:11px;"><?php esc_html_e( 'Guests must reserve at least this many nights during this period.', 'awesome-hotel-booking' ); ?></small>
						</div>
					</div>

					<div class="ahbn-panel-footer">
						<a href="<?php echo esc_url( $base_rates_url ); ?>" class="ahbn-btn-cancel">
							<?php esc_html_e( 'Cancel', 'awesome-hotel-booking' ); ?>
						</a>
						<button type="submit" name="ahbn_save_rate" class="ahbn-btn-save">
							<?php echo $edit_tier ? esc_html__( 'Update Rate Tier', 'awesome-hotel-booking' ) : esc_html__( 'Save Rate Tier', 'awesome-hotel-booking' ); ?>
						</button>
					</div>
				</form>
			</div>

		<?php else : ?>

			<!-- Header Toolbar (All Rate Tiers View) -->
			<header class="ahbn-pricing-header">
				<div>
					<h2 class="ahbn-pricing-title"><?php esc_html_e( 'Rates, Seasons & Yield Strategy', 'awesome-hotel-booking' ); ?></h2>
					<p class="ahbn-pricing-subtitle">
						<?php esc_html_e( 'Schedule peak season surges, low-season promotions, minimum length of stay (LOS), and custom room rates.', 'awesome-hotel-booking' ); ?>
					</p>
				</div>
			</header>

			<!-- Strategy KPI Cards -->
			<div class="ahbn-stats-grid">
				<div class="ahbn-stat-card">
					<span class="ahbn-stat-label"><?php esc_html_e( 'Total Rate Tiers', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-stat-value"><?php echo esc_html( (string) count( $rates ) ); ?></span>
				</div>
				<div class="ahbn-stat-card ahbn-stat-active-today">
					<span class="ahbn-stat-label"><?php esc_html_e( 'Currently Active Today', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-stat-value"><?php echo esc_html( (string) $active_rules ); ?></span>
				</div>
				<div class="ahbn-stat-card ahbn-stat-upcoming">
					<span class="ahbn-stat-label"><?php esc_html_e( 'Upcoming Scheduled Tiers', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-stat-value"><?php echo esc_html( (string) $upcoming_rules ); ?></span>
				</div>
			</div>

			<!-- Rates Grid Table -->
			<div class="ahbn-table-container">
				<table class="ahbn-rates-table widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Tier Name / Season', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Target Inventory', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Validity Period', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Pricing Formula', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Min. Stay (LOS)', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Status', 'awesome-hotel-booking' ); ?></th>
							<th style="text-align: right; width: 120px;"><?php esc_html_e( 'Actions', 'awesome-hotel-booking' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $rates ) ) : ?>
							<?php foreach ( $rates as $tier ) :
								$is_active = ( $tier->start_date <= $today && $tier->end_date >= $today );
								$is_future = ( $tier->start_date > $today );

								$status_label = __( 'Expired', 'awesome-hotel-booking' );
								$status_class = 'status-expired';

								if ( $is_active ) {
									$status_label = __( 'Active Now', 'awesome-hotel-booking' );
									$status_class = 'status-active-now';
								} elseif ( $is_future ) {
									$status_label = __( 'Scheduled', 'awesome-hotel-booking' );
									$status_class = 'status-scheduled';
								}

								$multiplier = (float) $tier->rate_multiplier;
								$fixed      = (float) $tier->fixed_rate;
								$edit_link  = add_query_arg( array( 'action' => 'edit', 'rate_id' => $tier->id ), $base_rates_url );
								?>
								<tr>
									<td>
										<strong style="color: #0a192f;"><?php echo esc_html( $tier->title ); ?></strong>
									</td>
									<td>
										<?php if ( ! empty( $tier->room_id ) && ! empty( $tier->room_title ) ) : ?>
											<span class="ahbn-inventory-badge">
												#<?php echo esc_html( $tier->room_number ); ?> <?php echo esc_html( $tier->room_title ); ?>
											</span>
										<?php else : ?>
											<span style="font-size: 11px; font-weight: 700; color: #b39264; text-transform: uppercase;">
												<?php esc_html_e( 'All Rooms (Global Tier)', 'awesome-hotel-booking' ); ?>
											</span>
										<?php endif; ?>
									</td>
									<td>
										<span style="font-size: 12px; font-weight: 600; color: #334155;">
											<?php echo esc_html( wp_date( 'M j, Y', strtotime( $tier->start_date ) ) ); ?>
											&rarr;
											<?php echo esc_html( wp_date( 'M j, Y', strtotime( $tier->end_date ) ) ); ?>
										</span>
									</td>
									<td>
										<?php if ( $fixed > 0.00 ) : ?>
											<strong style="color: #0a192f; font-family: 'JetBrains Mono', monospace;">
												<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $fixed ) : (string) number_format_i18n( $fixed, 2 ) ); ?>
											</strong>
											<small style="color: #64748b; display: block; font-size: 11px;"><?php esc_html_e( 'Fixed Override', 'awesome-hotel-booking' ); ?></small>
										<?php else : ?>
											<strong style="color: #059669; font-family: 'JetBrains Mono', monospace;">
												<?php echo esc_html( number_format( $multiplier, 2 ) ); ?>x
											</strong>
											<small style="color: #64748b; display: block; font-size: 11px;">
												<?php
												if ( $multiplier > 1.00 ) {
													printf(
														/* translators: %s: percentage surcharge */
														esc_html__( '+%s%% Surge', 'awesome-hotel-booking' ),
														esc_html( (string) round( ( $multiplier - 1.00 ) * 100 ) )
													);
												} elseif ( $multiplier < 1.00 ) {
													printf(
														/* translators: %s: percentage discount */
														esc_html__( '-%s%% Discount', 'awesome-hotel-booking' ),
														esc_html( (string) round( ( 1.00 - $multiplier ) * 100 ) )
													);
												} else {
													esc_html_e( 'Standard 1:1 Rate', 'awesome-hotel-booking' );
												}
												?>
											</small>
										<?php endif; ?>
									</td>
									<td>
										<span style="font-size: 13px; font-weight: 700; color: #0a192f;">
											<?php
											printf(
												/* translators: %d: number of nights */
												esc_html( _n( '%d Night', '%d Nights', $tier->min_stay, 'awesome-hotel-booking' ) ),
												absint( $tier->min_stay )
											);
											?>
										</span>
									</td>
									<td>
										<span class="ahbn-status-pill <?php echo esc_attr( $status_class ); ?>">
											<?php echo esc_html( $status_label ); ?>
										</span>
									</td>
									<td style="text-align: right;">
										<div class="ahbn-action-btns">
											<a href="<?php echo esc_url( $edit_link ); ?>" class="ahbn-quick-btn" title="<?php esc_attr_e( 'Edit Rate Tier', 'awesome-hotel-booking' ); ?>">
												<span class="dashicons dashicons-edit" aria-hidden="true"></span>
											</a>

											<form method="post" action="<?php echo esc_url( $base_rates_url ); ?>" style="display:inline-block; margin: 0;" onsubmit="return confirm('<?php echo esc_js( __( 'Remove this pricing rule?', 'awesome-hotel-booking' ) ); ?>');">
												<?php wp_nonce_field( 'ahbn_delete_rate_action', 'ahbn_del_rate_nonce' ); ?>
												<input type="hidden" name="del_rate_id" value="<?php echo esc_attr( (string) $tier->id ); ?>">
												<button type="submit" name="ahbn_delete_rate" class="ahbn-quick-btn is-delete" title="<?php esc_attr_e( 'Delete Rate Tier', 'awesome-hotel-booking' ); ?>">
													<span class="dashicons dashicons-trash" aria-hidden="true"></span>
												</button>
											</form>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="7" style="text-align:center; padding: 50px; color: #94a3b8;">
									<span class="dashicons dashicons-tag" style="font-size:36px; width:36px; height:36px; color:#cbd5e1;" aria-hidden="true"></span>
									<p style="margin: 8px 0 0;"><?php esc_html_e( 'No custom rate seasons or pricing rules scheduled yet. Standard suite base rates currently apply.', 'awesome-hotel-booking' ); ?></p>
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