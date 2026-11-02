<?php
/**
 * Room Management Tab Controller & Sub-navigation.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Room Management Tab and route sub-actions.
 *
 * @return void
 */
function ahbn_rooms_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	// 1. Capture State.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$ahbn_sub     = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'all';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$ahbn_room_id = isset( $_GET['room_id'] ) ? absint( wp_unslash( $_GET['room_id'] ) ) : 0;

	$page_slug = 'awesome_hotel_booking';
	$base_url  = admin_url( 'admin.php?page=' . rawurlencode( $page_slug ) . '&tab=rooms' );

	// 2. Define Tab Navigation Items.
	$tabs = array(
		'all'    => array(
			'label' => __( 'All Rooms', 'awesome-hotel-booking' ),
			'icon'  => 'dashicons-list-view',
		),
		'add'    => array(
			'label' => __( 'Add New Room', 'awesome-hotel-booking' ),
			'icon'  => 'dashicons-plus-alt',
		),
		'extras' => array(
			'label' => __( 'Extras', 'awesome-hotel-booking' ),
			'icon'  => 'dashicons-admin-plugins',
		),
	);
	?>
	<div class="ahbn-admin-container" style="margin-right:20px; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen-Sans,Ubuntu,Cantarell,'Helvetica Neue',sans-serif;">
		
		<!-- 3. SaaS Header -->
		<header class="ahbn-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; margin-top:20px;">
			<div>
				<h1 style="font-size:26px; font-weight:800; color:#0A1A38; margin:0; line-height:1.2;">
					<?php esc_html_e( 'Room Management', 'awesome-hotel-booking' ); ?>
				</h1>
				<p style="margin:5px 0 0; color:#6b7280; font-size:14px;">
					<?php esc_html_e( 'Manage your luxury inventory, pricing, and amenities.', 'awesome-hotel-booking' ); ?>
				</p>
			</div>

			<?php if ( 'add' !== $ahbn_sub ) : ?>
				<?php $add_url = add_query_arg( 'sub', 'add', $base_url ); ?>
				<a href="<?php echo esc_url( $add_url ); ?>" class="button button-primary" style="background:#0A1A38; border:none; height:42px; padding:0 22px; display:inline-flex; align-items:center; gap:10px; border-radius:8px; font-weight:600; box-shadow:0 4px 10px rgba(10,26,56,0.15);">
					<span class="dashicons dashicons-plus-alt" style="margin-top:2px;" aria-hidden="true"></span>
					<?php esc_html_e( 'Add New Room', 'awesome-hotel-booking' ); ?>
				</a>
			<?php endif; ?>
		</header>

		<!-- 4. Sub-Navigation Bar -->
		<nav class="ahbn-nav-tabs" style="display:flex; gap:30px; border-bottom:1px solid #e5e7eb; margin-bottom:30px;">
			<?php
			foreach ( $tabs as $k => $data ) :
				$sub_url   = add_query_arg( 'sub', $k, $base_url );
				$is_active = ( $ahbn_sub === $k || ( 'all' === $k && in_array( $ahbn_sub, array( 'edit', 'view' ), true ) ) );
				$tab_style = $is_active
					? 'border-bottom:3px solid #D9B061; color:#0A1A38; font-weight:700;'
					: 'color:#9ca3af; border-bottom:3px solid transparent;';
				?>
				<a 
					href="<?php echo esc_url( $sub_url ); ?>" 
					style="text-decoration:none; padding:12px 5px; display:flex; align-items:center; gap:10px; transition:0.2s; font-size:14px; <?php echo esc_attr( $tab_style ); ?>"
				>
					<span class="dashicons <?php echo esc_attr( $data['icon'] ); ?>" aria-hidden="true"></span>
					<?php echo esc_html( $data['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<!-- 5. Router: Load requested sub-view -->
		<div class="ahbn-view-content">
			<?php
			switch ( $ahbn_sub ) {
				case 'add':
					if ( function_exists( 'ahbn_add_edit_room_tab' ) ) {
						ahbn_add_edit_room_tab();
					}
					break;

				case 'edit':
					if ( $ahbn_room_id > 0 && function_exists( 'ahbn_add_edit_room_tab' ) ) {
						ahbn_add_edit_room_tab( $ahbn_room_id );
					} elseif ( function_exists( 'ahbn_rooms_list' ) ) {
						ahbn_rooms_list();
					}
					break;

				case 'view':
					if ( $ahbn_room_id > 0 && function_exists( 'ahbn_view_room_tab' ) ) {
						ahbn_view_room_tab( $ahbn_room_id );
					} elseif ( function_exists( 'ahbn_rooms_list' ) ) {
						ahbn_rooms_list();
					}
					break;

				case 'extras':
					if ( function_exists( 'ahbn_rooms_settings' ) ) {
						ahbn_rooms_settings();
					} else {
						echo '<div class="notice notice-warning"><p>' . esc_html__( 'Extras configuration function not found.', 'awesome-hotel-booking' ) . '</p></div>';
					}
					break;

				default:
					if ( function_exists( 'ahbn_rooms_list' ) ) {
						ahbn_rooms_list();
					}
					break;
			}
			?>
		</div>
	</div>
	<?php
}