<?php
/**
 * General Settings Sub-Tab View.
 *
 * Built with the Champagne Gold & Deep Navy enterprise UI system.
 * Handles property rules, regional currency, tax rates, and check-in/out policies.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render and process the General Settings view.
 *
 * @return void
 */
function ahbn_settings_general_view() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	// 1. Supported Currency Configuration.
	$currencies = array(
		'USD' => array(
			'name'   => __( 'US Dollar', 'awesome-hotel-booking' ),
			'symbol' => '$',
		),
		'EUR' => array(
			'name'   => __( 'Euro', 'awesome-hotel-booking' ),
			'symbol' => '€',
		),
		'GBP' => array(
			'name'   => __( 'British Pound', 'awesome-hotel-booking' ),
			'symbol' => '£',
		),
		'BDT' => array(
			'name'   => __( 'Bangladeshi Taka', 'awesome-hotel-booking' ),
			'symbol' => '৳',
		),
		'INR' => array(
			'name'   => __( 'Indian Rupee', 'awesome-hotel-booking' ),
			'symbol' => '₹',
		),
		'AED' => array(
			'name'   => __( 'UAE Dirham', 'awesome-hotel-booking' ),
			'symbol' => 'د.إ',
		),
		'CAD' => array(
			'name'   => __( 'Canadian Dollar', 'awesome-hotel-booking' ),
			'symbol' => 'CA$',
		),
		'AUD' => array(
			'name'   => __( 'Australian Dollar', 'awesome-hotel-booking' ),
			'symbol' => 'AU$',
		),
	);

	// 2. Process Settings Form Submission.
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_general'] ) ) {
		$ahbn_nonce = isset( $_POST['ahbn_general_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_general_nonce'] ) ) : '';

		if ( empty( $ahbn_nonce ) || ! wp_verify_nonce( $ahbn_nonce, 'ahbn_save_general_settings' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please refresh and try again.', 'awesome-hotel-booking' ) . '</p></div>';
		} else {
			// Hotel Identity.
			$hotel_name    = isset( $_POST['ahbn_hotel_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_hotel_name'] ) ) : get_bloginfo( 'name' );
			$hotel_phone   = isset( $_POST['ahbn_hotel_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_hotel_phone'] ) ) : '';
			$hotel_email   = isset( $_POST['ahbn_hotel_email'] ) ? sanitize_email( wp_unslash( $_POST['ahbn_hotel_email'] ) ) : get_bloginfo( 'admin_email' );
			$hotel_address = isset( $_POST['ahbn_hotel_address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ahbn_hotel_address'] ) ) : '';

			update_option( 'ahbn_hotel_name', $hotel_name );
			update_option( 'ahbn_hotel_phone', $hotel_phone );
			update_option( 'ahbn_hotel_email', $hotel_email );
			update_option( 'ahbn_hotel_address', $hotel_address );

			// Currency validation & whitelisting.
			$selected_code = isset( $_POST['ahbn_currency_code'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_currency_code'] ) ) : 'USD';
			if ( ! array_key_exists( $selected_code, $currencies ) ) {
				$selected_code = 'USD';
			}

			update_option( 'ahbn_currency_code', $selected_code );
			update_option( 'ahbn_currency', $currencies[ $selected_code ]['symbol'] );

			// Currency position validation.
			$curr_pos_input = isset( $_POST['ahbn_currency_pos'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_currency_pos'] ) ) : 'left';
			$curr_pos_val   = in_array( $curr_pos_input, array( 'left', 'right' ), true ) ? $curr_pos_input : 'left';
			update_option( 'ahbn_currency_pos', $curr_pos_val );

			// Check-in and Check-out Times.
			$checkin_raw  = isset( $_POST['ahbn_checkin_time'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_checkin_time'] ) ) : '14:00';
			$checkout_raw = isset( $_POST['ahbn_checkout_time'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_checkout_time'] ) ) : '11:00';

			update_option( 'ahbn_checkin_time', preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $checkin_raw ) ? $checkin_raw : '14:00' );
			update_option( 'ahbn_checkout_time', preg_match( '/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $checkout_raw ) ? $checkout_raw : '11:00' );

			// Numeric policies.
			$min_stay_input = isset( $_POST['ahbn_min_stay'] ) ? absint( wp_unslash( $_POST['ahbn_min_stay'] ) ) : 1;
			update_option( 'ahbn_min_stay', max( 1, $min_stay_input ) );

			$tax_rate_input = isset( $_POST['ahbn_tax_rate'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['ahbn_tax_rate'] ) ) : 0.00;
			update_option( 'ahbn_tax_rate', max( 0.00, $tax_rate_input ) );

			// Toggle Switches.
			update_option( 'ahbn_weekend_booking', isset( $_POST['ahbn_weekend_booking'] ) ? 'yes' : 'no' );
			update_option( 'ahbn_instant_booking', isset( $_POST['ahbn_instant_booking'] ) ? 'yes' : 'no' );

			// Clear cached stats.
			delete_transient( 'ahbn_dashboard_stats' );

			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Global hotel configuration saved.', 'awesome-hotel-booking' ) . '</p></div>';
		}
	}

	// 3. Fetch Current Values.
	$hotel_name    = get_option( 'ahbn_hotel_name', get_bloginfo( 'name' ) );
	$hotel_phone   = get_option( 'ahbn_hotel_phone', '' );
	$hotel_email   = get_option( 'ahbn_hotel_email', get_bloginfo( 'admin_email' ) );
	$hotel_address = get_option( 'ahbn_hotel_address', '' );

	$curr_code = get_option( 'ahbn_currency_code', 'USD' );
	$curr_pos  = get_option( 'ahbn_currency_pos', 'left' );
	$min_stay  = get_option( 'ahbn_min_stay', 1 );
	$checkin   = get_option( 'ahbn_checkin_time', '14:00' );
	$checkout  = get_option( 'ahbn_checkout_time', '11:00' );
	$tax_rate  = get_option( 'ahbn_tax_rate', 0 );
	$weekend   = get_option( 'ahbn_weekend_booking', 'yes' );
	$instant   = get_option( 'ahbn_instant_booking', 'yes' );
	?>

	<div class="ahbn-gen-settings-wrap">
		<?php wp_nonce_field( 'ahbn_save_general_settings', 'ahbn_general_nonce' ); ?>

		<div class="ahbn-settings-grid-two">
			
			<!-- Card 1: Property Identity -->
			<div class="ahbn-panel">
				<div class="ahbn-panel-head">
					<span class="dashicons dashicons-building" aria-hidden="true"></span>
					<h2><?php esc_html_e( 'Property Identity & Contact', 'awesome-hotel-booking' ); ?></h2>
				</div>
				<div class="ahbn-panel-body">
					<div class="ahbn-field-group">
						<label for="ahbn_hotel_name" class="ahbn-label"><?php esc_html_e( 'Hotel / Property Legal Name *', 'awesome-hotel-booking' ); ?></label>
						<input type="text" id="ahbn_hotel_name" name="ahbn_hotel_name" value="<?php echo esc_attr( $hotel_name ); ?>" class="ahbn-input" required placeholder="<?php esc_attr_e( 'e.g. The Grand Palace Resort & Spa', 'awesome-hotel-booking' ); ?>">
					</div>

					<div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
						<div class="ahbn-field-group">
							<label for="ahbn_hotel_email" class="ahbn-label"><?php esc_html_e( 'Reservations Email', 'awesome-hotel-booking' ); ?></label>
							<input type="email" id="ahbn_hotel_email" name="ahbn_hotel_email" value="<?php echo esc_attr( $hotel_email ); ?>" class="ahbn-input" placeholder="reservations@hotel.com">
						</div>

						<div class="ahbn-field-group">
							<label for="ahbn_hotel_phone" class="ahbn-label"><?php esc_html_e( 'Front Desk Telephone', 'awesome-hotel-booking' ); ?></label>
							<input type="tel" id="ahbn_hotel_phone" name="ahbn_hotel_phone" value="<?php echo esc_attr( $hotel_phone ); ?>" class="ahbn-input" placeholder="+1 (555) 019-2834">
						</div>
					</div>

					<div class="ahbn-field-group">
						<label for="ahbn_hotel_address" class="ahbn-label"><?php esc_html_e( 'Physical Property Address', 'awesome-hotel-booking' ); ?></label>
						<textarea id="ahbn_hotel_address" name="ahbn_hotel_address" rows="3" class="ahbn-textarea" placeholder="<?php esc_attr_e( 'e.g. 742 Evergreen Terrace, Ocean View Boulevard', 'awesome-hotel-booking' ); ?>"><?php echo esc_textarea( $hotel_address ); ?></textarea>
					</div>
				</div>
			</div>

			<!-- Card 2: Financial & Currency Localization -->
			<div class="ahbn-panel">
				<div class="ahbn-panel-head">
					<span class="dashicons dashicons-money-alt" aria-hidden="true"></span>
					<h2><?php esc_html_e( 'Currency & Tax Compliance', 'awesome-hotel-booking' ); ?></h2>
				</div>
				<div class="ahbn-panel-body">
					<div style="display:grid; grid-template-columns:2fr 1fr; gap:14px;">
						<div class="ahbn-field-group">
							<label for="ahbn_currency_code" class="ahbn-label"><?php esc_html_e( 'Standard Currency', 'awesome-hotel-booking' ); ?></label>
							<select id="ahbn_currency_code" name="ahbn_currency_code" class="ahbn-select">
								<?php foreach ( $currencies as $code => $data ) : ?>
									<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $curr_code, $code ); ?>>
										<?php echo esc_html( $data['symbol'] . ' — ' . $data['name'] . ' (' . $code . ')' ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="ahbn-field-group">
							<label for="ahbn_currency_pos" class="ahbn-label"><?php esc_html_e( 'Symbol Position', 'awesome-hotel-booking' ); ?></label>
							<select id="ahbn_currency_pos" name="ahbn_currency_pos" class="ahbn-select">
								<option value="left" <?php selected( $curr_pos, 'left' ); ?>><?php esc_html_e( 'Left ($100)', 'awesome-hotel-booking' ); ?></option>
								<option value="right" <?php selected( $curr_pos, 'right' ); ?>><?php esc_html_e( 'Right (100$)', 'awesome-hotel-booking' ); ?></option>
							</select>
						</div>
					</div>

					<div class="ahbn-field-group">
						<label for="ahbn_tax_rate" class="ahbn-label"><?php esc_html_e( 'State / VAT Tax Rate (%)', 'awesome-hotel-booking' ); ?></label>
						<input type="number" step="0.01" min="0" max="100" id="ahbn_tax_rate" name="ahbn_tax_rate" value="<?php echo esc_attr( (string) $tax_rate ); ?>" class="ahbn-input" style="font-family:'JetBrains Mono', monospace; font-weight:700;">
						<small class="ahbn-hint"><?php esc_html_e( 'Tax percentage automatically calculated upon folios and room rates.', 'awesome-hotel-booking' ); ?></small>
					</div>

					<!-- Instant Booking Switch -->
					<div class="ahbn-switch-box">
						<div class="ahbn-switch-info">
							<strong class="ahbn-switch-title"><?php esc_html_e( 'Instant Booking Confirmation', 'awesome-hotel-booking' ); ?></strong>
							<span class="ahbn-switch-desc"><?php esc_html_e( 'Automatically transition online reservations to "Confirmed" without admin review.', 'awesome-hotel-booking' ); ?></span>
						</div>
						<label class="ahbn-toggle">
							<input type="checkbox" name="ahbn_instant_booking" value="yes" <?php checked( $instant, 'yes' ); ?>>
							<span class="ahbn-toggle-slider"></span>
						</label>
					</div>
				</div>
			</div>

			<!-- Card 3: Stay & Reception Policies -->
			<div class="ahbn-panel" style="grid-column: 1 / -1;">
				<div class="ahbn-panel-head">
					<span class="dashicons dashicons-clock" aria-hidden="true"></span>
					<h2><?php esc_html_e( 'Reception Hours & Stay Policies', 'awesome-hotel-booking' ); ?></h2>
				</div>
				<div class="ahbn-panel-body">
					<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:16px;">
						<div class="ahbn-field-group">
							<label for="ahbn_checkin_time" class="ahbn-label"><?php esc_html_e( 'Check-in Hour (From)', 'awesome-hotel-booking' ); ?></label>
							<input type="time" id="ahbn_checkin_time" name="ahbn_checkin_time" value="<?php echo esc_attr( $checkin ); ?>" class="ahbn-input">
						</div>

						<div class="ahbn-field-group">
							<label for="ahbn_checkout_time" class="ahbn-label"><?php esc_html_e( 'Check-out Hour (Until)', 'awesome-hotel-booking' ); ?></label>
							<input type="time" id="ahbn_checkout_time" name="ahbn_checkout_time" value="<?php echo esc_attr( $checkout ); ?>" class="ahbn-input">
						</div>

						<div class="ahbn-field-group">
							<label for="ahbn_min_stay" class="ahbn-label"><?php esc_html_e( 'Default Minimum Stay', 'awesome-hotel-booking' ); ?></label>
							<input type="number" min="1" max="90" id="ahbn_min_stay" name="ahbn_min_stay" value="<?php echo esc_attr( (string) $min_stay ); ?>" class="ahbn-input">
						</div>
					</div>

					<!-- Weekend Arrival Switch -->
					<div class="ahbn-switch-box" style="margin-top: 10px;">
						<div class="ahbn-switch-info">
							<strong class="ahbn-switch-title"><?php esc_html_e( 'Weekend Arrivals (Sat / Sun)', 'awesome-hotel-booking' ); ?></strong>
							<span class="ahbn-switch-desc"><?php esc_html_e( 'Permit guests to initiate check-in schedules on Saturdays and Sundays.', 'awesome-hotel-booking' ); ?></span>
						</div>
						<label class="ahbn-toggle">
							<input type="checkbox" name="ahbn_weekend_booking" value="yes" <?php checked( $weekend, 'yes' ); ?>>
							<span class="ahbn-toggle-slider"></span>
						</label>
					</div>
				</div>
			</div>

		</div>

		<div class="ahbn-panel-actions">
			<button type="submit" name="ahbn_save_general" class="ahbn-btn-save">
				<span class="dashicons dashicons-saved" aria-hidden="true"></span>
				<?php esc_html_e( 'Save General Configuration', 'awesome-hotel-booking' ); ?>
			</button>
		</div>
	</div>

	<style>
		.ahbn-gen-settings-wrap {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-settings-grid-two {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 20px;
		}

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
			font-size: 13px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #0a192f;
		}

		.ahbn-panel-head .dashicons {
			color: #c5a880;
			font-size: 17px;
			width: 17px;
			height: 17px;
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

		.ahbn-hint {
			font-size: 11px;
			color: #64748b;
		}

		/* Modern Toggle Switches */
		.ahbn-switch-box {
			display: flex;
			justify-content: space-between;
			align-items: center;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 10px;
			padding: 14px 18px;
			gap: 14px;
		}

		.ahbn-switch-info {
			display: flex;
			flex-direction: column;
			gap: 2px;
		}

		.ahbn-switch-title {
			font-size: 13px;
			font-weight: 700;
			color: #0a192f;
		}

		.ahbn-switch-desc {
			font-size: 11px;
			color: #64748b;
		}

		.ahbn-toggle {
			position: relative;
			display: inline-block;
			width: 44px;
			height: 24px;
			flex-shrink: 0;
		}

		.ahbn-toggle input {
			opacity: 0;
			width: 0;
			height: 0;
		}

		.ahbn-toggle-slider {
			position: absolute;
			cursor: pointer;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background-color: #cbd5e1;
			transition: 0.25s cubic-bezier(0.16, 1, 0.3, 1);
			border-radius: 99px;
		}

		.ahbn-toggle-slider:before {
			position: absolute;
			content: "";
			height: 18px;
			width: 18px;
			left: 3px;
			bottom: 3px;
			background-color: white;
			transition: 0.25s cubic-bezier(0.16, 1, 0.3, 1);
			border-radius: 50%;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
		}

		.ahbn-toggle input:checked + .ahbn-toggle-slider {
			background-color: #0a192f;
		}

		.ahbn-toggle input:checked + .ahbn-toggle-slider:before {
			transform: translateX(20px);
			background-color: #c5a880;
		}

		/* Actions Bar */
		.ahbn-panel-actions {
			display: flex;
			justify-content: flex-end;
			margin-top: 5px;
		}

		.ahbn-btn-save {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 11px 22px;
			background: #0a192f;
			border: 1px solid #0a192f;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 700;
			color: #ffffff;
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.ahbn-btn-save .dashicons {
			font-size: 17px;
			width: 17px;
			height: 17px;
			color: #c5a880;
		}

		.ahbn-btn-save:hover {
			background: #c5a880;
			border-color: #c5a880;
			color: #0a192f;
		}

		.ahbn-btn-save:hover .dashicons {
			color: #0a192f;
		}

		@media (max-width: 1024px) {
			.ahbn-settings-grid-two {
				grid-template-columns: 1fr;
			}
			.ahbn-panel[style*="grid-column"] {
				grid-column: auto !important;
			}
		}
	</style>
	<?php
}