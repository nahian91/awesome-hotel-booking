<?php
/**
 * Coupon Sub-Navigation Tabs Template.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the sub-navigation tabs for coupons.
 *
 * @param string $action    Current active view action.
 * @param int    $coupon_id Active coupon ID if viewing/editing.
 * @return void
 */
function ahbn_render_coupon_tabs( $action = '', $coupon_id = 0 ) {
	$base_coupons_url = admin_url( 'admin.php?page=awesome_hotel_booking&tab=coupons' );
	$add_coupon_url   = add_query_arg( 'action', 'add', $base_coupons_url );

	$is_all_active  = empty( $action );
	$is_add_active  = ( 'add' === $action );
	$is_edit_active = ( 'edit' === $action && $coupon_id > 0 );
	$is_view_active = ( 'view' === $action && $coupon_id > 0 );
	?>
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
		<?php elseif ( $is_view_active ) : ?>
			<span class="ahbn-subtab-link is-active is-context-tab">
				<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
				<span><?php echo esc_html( sprintf( /* translators: %d: coupon ID */ __( 'View Coupon #%d', 'awesome-hotel-booking' ), $coupon_id ) ); ?></span>
			</span>
		<?php endif; ?>
	</div>
	<?php
}