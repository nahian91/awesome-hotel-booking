<?php
/**
 * Add / Edit Coupon Form & Processor.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle form submission and render the Add/Edit form.
 *
 * @param int $coupon_id Optional. Coupon ID to edit. Defaults to 0.
 * @return void
 */
function ahbn_render_coupon_add_edit( $coupon_id = 0 ) {
	global $wpdb;
	$table_coupons    = $wpdb->prefix . 'ahbn_coupons';
	$base_coupons_url = admin_url( 'admin.php?page=awesome_hotel_booking&tab=coupons' );

	// Handle Save / Update POST Submission.
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_coupon'] ) ) {
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

					// Safe redirection without triggering "headers already sent" warning
					?>
					<script type="text/javascript">
						window.location.href = <?php echo wp_json_encode( esc_url_raw( $base_coupons_url ) ); ?>;
					</script>
					<noscript>
						<meta http-equiv="refresh" content="0;url=<?php echo esc_url( $base_coupons_url ); ?>">
					</noscript>
					<?php
					return;
				}
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
		}
	}

	// Fetch current coupon if editing.
	$edit_coupon = null;
	if ( $coupon_id > 0 ) {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$edit_coupon = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_coupons} WHERE id = %d", $coupon_id )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
	?>
	<div class="ahbn-panel" style="max-width: 740px; margin: 0 auto; width: 100%;">
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

		<form method="post" action="">
			<?php wp_nonce_field( 'ahbn_save_coupon_action', 'ahbn_coupon_nonce' ); ?>
			<input type="hidden" name="ahbn_save_coupon" value="1">
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

				<!-- Spending Guardrails -->
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
				<button type="submit" class="ahbn-btn-save">
					<?php echo $edit_coupon ? esc_html__( 'Update Coupon Code', 'awesome-hotel-booking' ) : esc_html__( 'Save Coupon Code', 'awesome-hotel-booking' ); ?>
				</button>
			</div>
		</form>
	</div>
	<?php
}