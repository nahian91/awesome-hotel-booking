<?php
/**
 * General Settings Sub-Tab View.
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

	// 1. Data Configuration.
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
	);

	// 2. Form Processing.
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_general'] ) ) {
		$ahbn_nonce = isset( $_POST['ahbn_general_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_general_nonce'] ) ) : '';

		if ( empty( $ahbn_nonce ) || ! wp_verify_nonce( $ahbn_nonce, 'ahbn_save_general_settings' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please refresh and try again.', 'awesome-hotel-booking' ) . '</p></div>';
		} else {
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

			// Flush dashboard and price transients.
			delete_transient( 'ahbn_dashboard_stats' );

			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Global configuration updated successfully.', 'awesome-hotel-booking' ) . '</p></div>';
		}
	}

	// 3. Fetch Current Values.
	$curr_code = get_option( 'ahbn_currency_code', 'USD' );
	$curr_pos  = get_option( 'ahbn_currency_pos', 'left' );
	$min_stay  = get_option( 'ahbn_min_stay', 1 );
	$checkin   = get_option( 'ahbn_checkin_time', '14:00' );
	$checkout  = get_option( 'ahbn_checkout_time', '11:00' );
	$tax_rate  = get_option( 'ahbn_tax_rate', 0 );
	$weekend   = get_option( 'ahbn_weekend_booking', 'yes' );
	$instant   = get_option( 'ahbn_instant_booking', 'yes' );
	?>

	<div class="ahbn-admin-wrapper">
		<?php wp_nonce_field( 'ahbn_save_general_settings', 'ahbn_general_nonce' ); ?>
		
		<div class="ahbn-admin-grid">
			
			<!-- Localization & Financial Settings Card -->
			<div class="ahbn-admin-card">
				<div class="ahbn-admin-card-header">
					<span class="dashicons dashicons-admin-site" aria-hidden="true"></span>
					<div>
						<h3 class="ahbn-admin-card-title"><?php esc_html_e( 'Localization & Tax', 'awesome-hotel-booking' ); ?></h3>
						<p class="ahbn-admin-card-subtitle"><?php esc_html_e( 'Regional currency and tax settings.', 'awesome-hotel-booking' ); ?></p>
					</div>
				</div>

				<div class="ahbn-admin-row">
					<div class="ahbn-admin-field-group">
						<label for="ahbn_currency_code" class="ahbn-admin-label"><?php esc_html_e( 'Currency', 'awesome-hotel-booking' ); ?></label>
						<select id="ahbn_currency_code" name="ahbn_currency_code" class="ahbn-admin-select">
							<?php foreach ( $currencies as $code => $data ) : ?>
								<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $curr_code, $code ); ?>>
									<?php echo esc_html( $data['symbol'] . ' — ' . $data['name'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="ahbn-admin-field-group">
						<label for="ahbn_currency_pos" class="ahbn-admin-label"><?php esc_html_e( 'Position', 'awesome-hotel-booking' ); ?></label>
						<select id="ahbn_currency_pos" name="ahbn_currency_pos" class="ahbn-admin-select">
							<option value="left" <?php selected( $curr_pos, 'left' ); ?>><?php esc_html_e( 'Left ($100)', 'awesome-hotel-booking' ); ?></option>
							<option value="right" <?php selected( $curr_pos, 'right' ); ?>><?php esc_html_e( 'Right (100$)', 'awesome-hotel-booking' ); ?></option>
						</select>
					</div>
				</div>

				<div class="ahbn-admin-field-group">
					<label for="ahbn_tax_rate" class="ahbn-admin-label"><?php esc_html_e( 'Tax Rate (%)', 'awesome-hotel-booking' ); ?></label>
					<input type="number" step="0.01" min="0" id="ahbn_tax_rate" name="ahbn_tax_rate" value="<?php echo esc_attr( (string) $tax_rate ); ?>" class="ahbn-admin-input">
				</div>

				<div class="ahbn-switch-row">
					<div class="ahbn-switch-info">
						<span class="ahbn-admin-label" style="margin:0;"><?php esc_html_e( 'Instant Booking', 'awesome-hotel-booking' ); ?></span>
						<span style="font-size:12px; color:#64748b;"><?php esc_html_e( 'Confirm reservations automatically upon receipt.', 'awesome-hotel-booking' ); ?></span>
					</div>
					<label class="ahbn-modern-switch">
						<input type="checkbox" name="ahbn_instant_booking" value="yes" <?php checked( $instant, 'yes' ); ?>>
						<span class="ahbn-slider-round"></span>
					</label>
				</div>
			</div>

			<!-- Stay Policies Card -->
			<div class="ahbn-admin-card">
				<div class="ahbn-admin-card-header">
					<span class="dashicons dashicons-clock" aria-hidden="true"></span>
					<div>
						<h3 class="ahbn-admin-card-title"><?php esc_html_e( 'Stay Policies', 'awesome-hotel-booking' ); ?></h3>
						<p class="ahbn-admin-card-subtitle"><?php esc_html_e( 'Check-in times and night rules.', 'awesome-hotel-booking' ); ?></p>
					</div>
				</div>

				<div class="ahbn-admin-row">
					<div class="ahbn-admin-field-group">
						<label for="ahbn_checkin_time" class="ahbn-admin-label"><?php esc_html_e( 'Check-in After', 'awesome-hotel-booking' ); ?></label>
						<input type="time" id="ahbn_checkin_time" name="ahbn_checkin_time" value="<?php echo esc_attr( $checkin ); ?>" class="ahbn-admin-input">
					</div>
					<div class="ahbn-admin-field-group">
						<label for="ahbn_checkout_time" class="ahbn-admin-label"><?php esc_html_e( 'Check-out Before', 'awesome-hotel-booking' ); ?></label>
						<input type="time" id="ahbn_checkout_time" name="ahbn_checkout_time" value="<?php echo esc_attr( $checkout ); ?>" class="ahbn-admin-input">
					</div>
				</div>

				<div class="ahbn-admin-field-group">
					<label for="ahbn_min_stay" class="ahbn-admin-label"><?php esc_html_e( 'Minimum Stay (Nights)', 'awesome-hotel-booking' ); ?></label>
					<input type="number" min="1" id="ahbn_min_stay" name="ahbn_min_stay" value="<?php echo esc_attr( (string) $min_stay ); ?>" class="ahbn-admin-input">
				</div>

				<div class="ahbn-switch-row">
					<div class="ahbn-switch-info">
						<span class="ahbn-admin-label" style="margin:0;"><?php esc_html_e( 'Weekend Arrivals', 'awesome-hotel-booking' ); ?></span>
						<span style="font-size:12px; color:#64748b;"><?php esc_html_e( 'Allow guest check-ins on Saturdays and Sundays.', 'awesome-hotel-booking' ); ?></span>
					</div>
					<label class="ahbn-modern-switch">
						<input type="checkbox" name="ahbn_weekend_booking" value="yes" <?php checked( $weekend, 'yes' ); ?>>
						<span class="ahbn-slider-round"></span>
					</label>
				</div>
			</div>
		</div>

		<div class="ahbn-admin-footer">
			<button type="submit" name="ahbn_save_general" class="button button-primary ahbn-admin-btn-save">
				<?php esc_html_e( 'Save Hotel Configuration', 'awesome-hotel-booking' ); ?>
			</button>
		</div>
	</div>
	<?php
}