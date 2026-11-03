<?php
/**
 * Payments Settings Sub-Tab View.
 *
 * Built with the Champagne Gold & Deep Navy enterprise UI system.
 * Configures guest payment methods, bank transfer wires, and front desk checkout rules.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render and process the Payment Gateways configuration view.
 *
 * @return void
 */
function ahbn_settings_payments_view() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	// 1. Process Form Submission.
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_payments'] ) ) {
		$ahbn_nonce = isset( $_POST['ahbn_payments_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_payments_nonce'] ) ) : '';

		if ( empty( $ahbn_nonce ) || ! wp_verify_nonce( $ahbn_nonce, 'ahbn_save_payments_action' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please try again.', 'awesome-hotel-booking' ) . '</p></div>';
		} else {
			$gateways = array( 'ahbn_enable_pay_later', 'ahbn_enable_bank', 'ahbn_enable_cash' );
			foreach ( $gateways as $gateway ) {
				update_option( $gateway, isset( $_POST[ $gateway ] ) ? 'yes' : 'no' );
			}

			$bank_details = isset( $_POST['ahbn_bank_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ahbn_bank_details'] ) ) : '';
			$cash_instr   = isset( $_POST['ahbn_cash_instructions'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ahbn_cash_instructions'] ) ) : '';

			update_option( 'ahbn_bank_details', $bank_details );
			update_option( 'ahbn_cash_instructions', $cash_instr );

			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Payment gateways updated successfully.', 'awesome-hotel-booking' ) . '</p></div>';
		}
	}

	// 2. Fetch Stored Values.
	$later_on  = get_option( 'ahbn_enable_pay_later', 'no' );
	$bank_on   = get_option( 'ahbn_enable_bank', 'no' );
	$cash_on   = get_option( 'ahbn_enable_cash', 'no' );
	$bank_info = get_option( 'ahbn_bank_details', '' );
	$cash_info = get_option( 'ahbn_cash_instructions', '' );
	?>

	<div class="ahbn-pay-settings-wrap">
		<?php wp_nonce_field( 'ahbn_save_payments_action', 'ahbn_payments_nonce' ); ?>

		<div class="ahbn-panel">
			<div class="ahbn-panel-head">
				<span class="dashicons dashicons-cart" aria-hidden="true"></span>
				<h2><?php esc_html_e( 'Checkout Gateways & Settlement Methods', 'awesome-hotel-booking' ); ?></h2>
			</div>

			<div class="ahbn-panel-body">
				<p class="ahbn-section-desc">
					<?php esc_html_e( 'Toggle and configure payment options presented to guests on reservation checkout forms.', 'awesome-hotel-booking' ); ?>
				</p>

				<div class="ahbn-methods-stack">
					
					<!-- Gateway 1: Pay Later (Arrival) -->
					<div class="ahbn-method-card <?php echo ( 'yes' === $later_on ) ? 'is-active' : ''; ?>">
						<div class="ahbn-method-header">
							<div class="ahbn-method-info">
								<div class="ahbn-method-icon">
									<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
								</div>
								<div>
									<h4 class="ahbn-method-name"><?php esc_html_e( 'Book Now, Pay Later', 'awesome-hotel-booking' ); ?></h4>
									<p class="ahbn-method-desc"><?php esc_html_e( 'Guests reserve suites immediately; the balance is collected in-person upon check-in.', 'awesome-hotel-booking' ); ?></p>
								</div>
							</div>
							<label class="ahbn-toggle">
								<input type="checkbox" name="ahbn_enable_pay_later" value="yes" <?php checked( $later_on, 'yes' ); ?> class="ahbn-gateway-toggle">
								<span class="ahbn-toggle-slider"></span>
							</label>
						</div>
					</div>

					<!-- Gateway 2: Direct Bank Transfer (BACS) -->
					<div class="ahbn-method-card <?php echo ( 'yes' === $bank_on ) ? 'is-active' : ''; ?>">
						<div class="ahbn-method-header">
							<div class="ahbn-method-info">
								<div class="ahbn-method-icon">
									<span class="dashicons dashicons-bank" aria-hidden="true"></span>
								</div>
								<div>
									<h4 class="ahbn-method-name"><?php esc_html_e( 'Direct Bank Wire / BACS Transfer', 'awesome-hotel-booking' ); ?></h4>
									<p class="ahbn-method-desc"><?php esc_html_e( 'Provide direct wire instructions and bank account details on reservation folios.', 'awesome-hotel-booking' ); ?></p>
								</div>
							</div>
							<label class="ahbn-toggle">
								<input type="checkbox" name="ahbn_enable_bank" value="yes" <?php checked( $bank_on, 'yes' ); ?> class="ahbn-gateway-toggle">
								<span class="ahbn-toggle-slider"></span>
							</label>
						</div>

						<div class="ahbn-method-content">
							<div class="ahbn-field-group">
								<label for="ahbn_bank_details" class="ahbn-label"><?php esc_html_e( 'Wire Transfer Account Details & Swift Code', 'awesome-hotel-booking' ); ?></label>
								<textarea 
									id="ahbn_bank_details"
									name="ahbn_bank_details" 
									rows="5" 
									class="ahbn-textarea font-mono" 
									placeholder="<?php esc_attr_e( "Beneficiary Name: Grand Palace Hotel LLC&#10;IBAN / Account Number: GB29 NWBK 6016 1331 9268 19&#10;Bank Name: National Westminster Bank Plc&#10;SWIFT / BIC Code: NWBKGB2L&#10;Payment Reference: Please quote your Booking ID Code", 'awesome-hotel-booking' ); ?>"
								><?php echo esc_textarea( $bank_info ); ?></textarea>
							</div>
						</div>
					</div>

					<!-- Gateway 3: Cash at Front Desk -->
					<div class="ahbn-method-card <?php echo ( 'yes' === $cash_on ) ? 'is-active' : ''; ?>">
						<div class="ahbn-method-header">
							<div class="ahbn-method-info">
								<div class="ahbn-method-icon">
									<span class="dashicons dashicons-money-alt" aria-hidden="true"></span>
								</div>
								<div>
									<h4 class="ahbn-method-name"><?php esc_html_e( 'Cash / POS Terminal on Arrival', 'awesome-hotel-booking' ); ?></h4>
									<p class="ahbn-method-desc"><?php esc_html_e( 'Accept physical local cash or chip & PIN credit/debit cards at reception.', 'awesome-hotel-booking' ); ?></p>
								</div>
							</div>
							<label class="ahbn-toggle">
								<input type="checkbox" name="ahbn_enable_cash" value="yes" <?php checked( $cash_on, 'yes' ); ?> class="ahbn-gateway-toggle">
								<span class="ahbn-toggle-slider"></span>
							</label>
						</div>

						<div class="ahbn-method-content">
							<div class="ahbn-field-group">
								<label for="ahbn_cash_instructions" class="ahbn-label"><?php esc_html_e( 'Front Desk Concierge Instructions', 'awesome-hotel-booking' ); ?></label>
								<textarea 
									id="ahbn_cash_instructions"
									name="ahbn_cash_instructions" 
									rows="3" 
									class="ahbn-textarea" 
									placeholder="<?php esc_attr_e( 'e.g. Please present your reservation confirmation number and government-issued photo ID at front desk check-in.', 'awesome-hotel-booking' ); ?>"
								><?php echo esc_textarea( $cash_info ); ?></textarea>
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>

		<div class="ahbn-panel-actions">
			<button type="submit" name="ahbn_save_payments" class="ahbn-btn-save">
				<span class="dashicons dashicons-saved" aria-hidden="true"></span>
				<?php esc_html_e( 'Save Payment Gateways', 'awesome-hotel-booking' ); ?>
			</button>
		</div>
	</div>

	<style>
		.ahbn-pay-settings-wrap {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
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

		.ahbn-section-desc {
			margin: 0 0 10px 0;
			color: #64748b;
			font-size: 13px;
		}

		.ahbn-methods-stack {
			display: flex;
			flex-direction: column;
			gap: 16px;
		}

		/* Method Card Container */
		.ahbn-method-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			overflow: hidden;
			transition: all 0.2s ease;
		}

		.ahbn-method-card:hover {
			border-color: #cbd5e1;
		}

		.ahbn-method-card.is-active {
			border-color: #0a192f;
			box-shadow: 0 4px 12px rgba(10, 25, 47, 0.05);
		}

		.ahbn-method-header {
			padding: 18px 20px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			background: #ffffff;
		}

		.ahbn-method-card.is-active .ahbn-method-header {
			background: #fafafa;
			border-bottom: 1px solid #f1f5f9;
		}

		.ahbn-method-info {
			display: flex;
			align-items: center;
			gap: 14px;
		}

		.ahbn-method-icon {
			width: 42px;
			height: 42px;
			border-radius: 10px;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #0a192f;
			flex-shrink: 0;
			transition: all 0.2s ease;
		}

		.ahbn-method-icon .dashicons {
			font-size: 20px;
			width: 20px;
			height: 20px;
			color: #64748b;
		}

		.ahbn-method-card.is-active .ahbn-method-icon {
			background: #0a192f;
			border-color: #0a192f;
		}

		.ahbn-method-card.is-active .ahbn-method-icon .dashicons {
			color: #c5a880;
		}

		.ahbn-method-name {
			margin: 0 0 2px 0;
			font-size: 14px;
			font-weight: 700;
			color: #0a192f;
		}

		.ahbn-method-desc {
			margin: 0;
			font-size: 12px;
			color: #64748b;
		}

		/* Expandable Content */
		.ahbn-method-content {
			padding: 20px;
			background: #ffffff;
			display: none;
		}

		.ahbn-method-card.is-active .ahbn-method-content {
			display: block;
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

		.ahbn-textarea {
			width: 100%;
			border: 1px solid #cbd5e1 !important;
			border-radius: 8px !important;
			padding: 10px 14px !important;
			font-size: 13px !important;
			background: #ffffff !important;
			color: #0f172a !important;
			box-shadow: none !important;
			line-height: 1.5 !important;
		}

		.ahbn-textarea:focus {
			border-color: #0a192f !important;
			box-shadow: 0 0 0 2px rgba(10, 25, 47, 0.1) !important;
		}

		.ahbn-textarea.font-mono {
			font-family: 'JetBrains Mono', monospace !important;
			font-size: 12px !important;
		}

		/* Modern Toggle Switches */
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
	</style>

	<script>
	jQuery(document).ready(function($) {
		$('.ahbn-gateway-toggle').on('change', function() {
			var $card = $(this).closest('.ahbn-method-card');
			var $content = $card.find('.ahbn-method-content');

			if ($(this).is(':checked')) {
				$card.addClass('is-active');
				$content.slideDown(200);
			} else {
				$card.removeClass('is-active');
				$content.slideUp(200);
			}
		});
	});
	</script>
	<?php
}