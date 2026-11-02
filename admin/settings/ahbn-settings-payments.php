<?php
/**
 * Payments Settings Sub-Tab View.
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

			echo '<div class="notice notice-success is-dismissible" style="border-radius:8px;"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Payment gateways updated successfully.', 'awesome-hotel-booking' ) . '</p></div>';
		}
	}

	// 2. Fetch Stored Values.
	$later_on  = get_option( 'ahbn_enable_pay_later', 'no' );
	$bank_on   = get_option( 'ahbn_enable_bank', 'no' );
	$cash_on   = get_option( 'ahbn_enable_cash', 'no' );
	$bank_info = get_option( 'ahbn_bank_details', '' );
	$cash_info = get_option( 'ahbn_cash_instructions', '' );
	?>

	<div class="ahbn-payments-container">
		<?php wp_nonce_field( 'ahbn_save_payments_action', 'ahbn_payments_nonce' ); ?>

		<header class="ahbn-settings-header" style="margin-bottom: 20px;">
			<h2 class="ahbn-tab-title" style="margin: 0 0 5px; font-size: 18px; color: #0A1A38;">
				<?php esc_html_e( 'Payment Gateways & Methods', 'awesome-hotel-booking' ); ?>
			</h2>
			<p class="ahbn-tab-desc" style="margin: 0; color: #64748b; font-size: 13px;">
				<?php esc_html_e( 'Enable and configure payment options available to guests during room checkout.', 'awesome-hotel-booking' ); ?>
			</p>
		</header>

		<div class="ahbn-payments-stack">
			
			<!-- Pay Later Method -->
			<div class="ahbn-method-card <?php echo ( 'yes' === $later_on ) ? 'is-active' : ''; ?>">
				<div class="ahbn-method-header">
					<div class="ahbn-method-info">
						<div class="ahbn-method-icon"><span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span></div>
						<div class="ahbn-method-title">
							<h4><?php esc_html_e( 'Book Now, Pay Later', 'awesome-hotel-booking' ); ?></h4>
							<p><?php esc_html_e( 'Reserve rooms immediately; guest pays balance upon arrival or check-in.', 'awesome-hotel-booking' ); ?></p>
						</div>
					</div>
					<label class="ahbn-toggle">
						<input type="checkbox" name="ahbn_enable_pay_later" value="yes" <?php checked( $later_on, 'yes' ); ?> class="ahbn-gateway-toggle">
						<span class="ahbn-slider"></span>
					</label>
				</div>
			</div>

			<!-- Direct Bank Transfer Method -->
			<div class="ahbn-method-card <?php echo ( 'yes' === $bank_on ) ? 'is-active' : ''; ?>">
				<div class="ahbn-method-header">
					<div class="ahbn-method-info">
						<div class="ahbn-method-icon"><span class="dashicons dashicons-bank" aria-hidden="true"></span></div>
						<div class="ahbn-method-title">
							<h4><?php esc_html_e( 'Direct Bank Transfer (BACS)', 'awesome-hotel-booking' ); ?></h4>
							<p><?php esc_html_e( 'Display wire transfer details and account info on invoice vouchers.', 'awesome-hotel-booking' ); ?></p>
						</div>
					</div>
					<label class="ahbn-toggle">
						<input type="checkbox" name="ahbn_enable_bank" value="yes" <?php checked( $bank_on, 'yes' ); ?> class="ahbn-gateway-toggle">
						<span class="ahbn-slider"></span>
					</label>
				</div>
				<div class="ahbn-method-content">
					<label for="ahbn_bank_details" class="ahbn-label" style="display:block; font-weight:600; margin-bottom:8px; font-size:13px;">
						<?php esc_html_e( 'Bank Account Details & Payment Instructions', 'awesome-hotel-booking' ); ?>
					</label>
					<textarea 
						id="ahbn_bank_details"
						name="ahbn_bank_details" 
						rows="5" 
						class="ahbn-textarea widefat" 
						placeholder="<?php esc_attr_e( "Account Name:\nIBAN / Account Number:\nBank Name:\nSWIFT / BIC Code:", 'awesome-hotel-booking' ); ?>"
						style="border-radius:6px;"
					><?php echo esc_textarea( $bank_info ); ?></textarea>
				</div>
			</div>

			<!-- Cash on Arrival Method -->
			<div class="ahbn-method-card <?php echo ( 'yes' === $cash_on ) ? 'is-active' : ''; ?>">
				<div class="ahbn-method-header">
					<div class="ahbn-method-info">
						<div class="ahbn-method-icon"><span class="dashicons dashicons-money-alt" aria-hidden="true"></span></div>
						<div class="ahbn-method-title">
							<h4><?php esc_html_e( 'Cash on Arrival (Front Desk)', 'awesome-hotel-booking' ); ?></h4>
							<p><?php esc_html_e( 'Accept physical cash or terminal cards directly at the hotel front desk.', 'awesome-hotel-booking' ); ?></p>
						</div>
					</div>
					<label class="ahbn-toggle">
						<input type="checkbox" name="ahbn_enable_cash" value="yes" <?php checked( $cash_on, 'yes' ); ?> class="ahbn-gateway-toggle">
						<span class="ahbn-slider"></span>
					</label>
				</div>
				<div class="ahbn-method-content">
					<label for="ahbn_cash_instructions" class="ahbn-label" style="display:block; font-weight:600; margin-bottom:8px; font-size:13px;">
						<?php esc_html_e( 'Front Desk Cash Instructions', 'awesome-hotel-booking' ); ?>
					</label>
					<textarea 
						id="ahbn_cash_instructions"
						name="ahbn_cash_instructions" 
						rows="4" 
						class="ahbn-textarea widefat" 
						placeholder="<?php esc_attr_e( 'e.g. Please present your booking voucher at the reception desk upon check-in.', 'awesome-hotel-booking' ); ?>"
						style="border-radius:6px;"
					><?php echo esc_textarea( $cash_info ); ?></textarea>
				</div>
			</div>

		</div>

		<div class="ahbn-footer-action" style="margin-top:25px; display:flex; justify-content:flex-end;">
			<button type="submit" name="ahbn_save_payments" class="button button-primary ahbn-btn-save" style="background:#0A1A38; border:none; height:42px; padding:0 28px; border-radius:6px; font-weight:600;">
				<?php esc_html_e( 'Save Payment Gateways', 'awesome-hotel-booking' ); ?>
			</button>
		</div>
	</div>

	<script>
	jQuery(document).ready(function($){
		$('.ahbn-gateway-toggle').on('change', function(){
			var $card = $(this).closest('.ahbn-method-card');
			if ($(this).is(':checked')) {
				$card.addClass('is-active');
			} else {
				$card.removeClass('is-active');
			}
		});
	});
	</script>
	<?php
}