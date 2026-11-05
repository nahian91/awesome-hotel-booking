<?php
/**
 * Staff Sub-Navigation Tabs Template.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the sub-navigation tabs for staff management.
 *
 * @param string $action   Current view action.
 * @param int    $staff_id Active staff ID if editing.
 * @return void
 */
function ahbn_render_staff_tabs( $action = '', $staff_id = 0 ) {
	$base_staff_url = admin_url( 'admin.php?page=awesome_hotel_booking&tab=staff' );
	$add_staff_url  = add_query_arg( 'action', 'add', $base_staff_url );

	$is_all_active  = empty( $action );
	$is_add_active  = ( 'add' === $action );
	$is_edit_active = ( 'edit' === $action && $staff_id > 0 );
	?>
	<div class="ahbn-subtabs-nav-bar">
		<a href="<?php echo esc_url( $base_staff_url ); ?>" class="ahbn-subtab-link <?php echo $is_all_active ? 'is-active' : ''; ?>">
			<span class="dashicons dashicons-groups" aria-hidden="true"></span>
			<span><?php esc_html_e( 'All Staff', 'awesome-hotel-booking' ); ?></span>
		</a>

		<a href="<?php echo esc_url( $add_staff_url ); ?>" class="ahbn-subtab-link <?php echo $is_add_active ? 'is-active' : ''; ?>">
			<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
			<span><?php esc_html_e( 'Add Staff', 'awesome-hotel-booking' ); ?></span>
		</a>

		<?php if ( $is_edit_active ) : ?>
			<span class="ahbn-subtab-link is-active is-context-tab">
				<span class="dashicons dashicons-edit" aria-hidden="true"></span>
				<span><?php echo esc_html( sprintf( /* translators: %d: staff ID */ __( 'Edit Staff #%d', 'awesome-hotel-booking' ), $staff_id ) ); ?></span>
			</span>
		<?php endif; ?>
	</div>
	<?php
}