<?php
/**
 * Coupon Directory Table & Metrics View.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the coupons table and KPI cards.
 *
 * @return void
 */
function ahbn_render_coupon_list() {
	global $wpdb;
	$table_coupons    = $wpdb->prefix . 'ahbn_coupons';$base_coupons_url = admin_url( 'admin.php?page=awesome_hotel_booking&tab=coupons' );
	$today            = current_time( 'Y-m-d' );

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$coupons = $wpdb->get_results( "SELECT * FROM {$table_coupons} ORDER BY created_at DESC" );

	$metrics =$wpdb->get_row(
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
	?>

	<!-- Header Toolbar -->
	<header class="ahbn-coupon-header">
		<div>
			<h2 class="ahbn-coupon-title"><?php esc_html_e( 'Promotional Codes & Member Discounts', 'awesome-hotel-booking' ); ?></h2>
			<p class="ahbn-coupon-subtitle">
				<?php esc_html_e( 'Manage guest promotional campaigns, percentage vouchers, minimum stay spending thresholds, and usage caps.', 'awesome-hotel-booking' ); ?>
			</p>
		</div>
	</header>

	<!-- KPI Metrics -->
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

	<!-- Table Container -->
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
					<th style="text-align: right; width: 140px;"><?php esc_html_e( 'Actions', 'awesome-hotel-booking' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $coupons ) ) : ?>
					<?php foreach ( $coupons as$item ) :
						$is_expired  = ! empty( $item->expiry_date ) && $item->expiry_date <$today;
						$limit_hit   = ($item->usage_limit > 0 && $item->usage_count >=$item->usage_limit );
						$status_base = sanitize_key($item->status );

						$status_class = 'status-active-now';$status_text  = __( 'Active', 'awesome-hotel-booking' );

						if ( 'inactive' === $status_base ) {
							$status_class = 'status-disabled';$status_text  = __( 'Disabled', 'awesome-hotel-booking' );
						} elseif ( $is_expired ) {
							$status_class = 'status-disabled';$status_text  = __( 'Expired', 'awesome-hotel-booking' );
						} elseif ( $limit_hit ) {
							$status_class = 'status-capped';$status_text  = __( 'Limit Reached', 'awesome-hotel-booking' );
						}

						$discount_val = (float)$item->discount_value;
						$min_sp       = (float)$item->min_spend;
						$max_sp       = (float)$item->max_spend;
						$view_link    = add_query_arg( array( 'action' => 'view', 'coupon_id' =>$item->id ), $base_coupons_url );$edit_link    = add_query_arg( array( 'action' => 'edit', 'coupon_id' => $item->id ),$base_coupons_url );
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
										<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $discount_val ) : (string) number_format_i18n($discount_val, 2 ) ); ?> OFF
									</strong>
									<small style="display: block; color: #64748b; font-size: 11px;"><?php esc_html_e( 'Flat stay discount', 'awesome-hotel-booking' ); ?></small>
								<?php endif; ?>
							</td>
							<td>
								<div style="font-size: 12px; line-height: 1.5; color: #334155;">
									<?php if ( $min_sp > 0.00 ) : ?>
										<div><strong>Min:</strong> <?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $min_sp ) : number_format_i18n($min_sp, 2 ) ); ?></div>
									<?php else : ?>
										<div style="color: #94a3b8;"><?php esc_html_e( 'No min spend', 'awesome-hotel-booking' ); ?></div>
									<?php endif; ?>

									<?php if ( $max_sp > 0.00 ) : ?>
										<div><strong>Max:</strong> <?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $max_sp ) : number_format_i18n($max_sp, 2 ) ); ?></div>
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
									<!-- View Page Button (Routes to coupon-view.php) -->
									<a href="<?php echo esc_url( $view_link ); ?>" class="ahbn-quick-btn" title="<?php esc_attr_e( 'View Details', 'awesome-hotel-booking' ); ?>">
										<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
									</a>

									<!-- Edit Coupon Button -->
									<a href="<?php echo esc_url( $edit_link ); ?>" class="ahbn-quick-btn" title="<?php esc_attr_e( 'Edit Coupon', 'awesome-hotel-booking' ); ?>">
										<span class="dashicons dashicons-edit" aria-hidden="true"></span>
									</a>

									<!-- Delete Coupon Button -->
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
	<?php
}