<?php
/**
 * Room Management Tab Controller & Sub-navigation.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 1. Auto-load Room Sub-View Modules if present in the admin/rooms/ directory.
$ahbn_rooms_files = array(
	AHBN_PATH . 'admin/rooms/ahbn-rooms-list.php',
	AHBN_PATH . 'admin/rooms/ahbn-room-add-edit.php',
	AHBN_PATH . 'admin/rooms/ahbn-room-view.php',
	AHBN_PATH . 'admin/rooms/ahbn-rooms-settings.php',
);

foreach ( $ahbn_rooms_files as $ahbn_file ) {
	if ( file_exists( $ahbn_file ) ) {
		require_once $ahbn_file;
	}
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

	global $wpdb;
	$table_rooms = $wpdb->prefix . 'ahbn_rooms';

	// 1. Capture State.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$ahbn_sub     = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'all';
	$ahbn_room_id = isset( $_GET['room_id'] ) ? absint( wp_unslash( $_GET['room_id'] ) ) : 0;
	$action       = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$page_slug = 'awesome_hotel_booking';
	$base_url  = admin_url( 'admin.php?page=' . rawurlencode( $page_slug ) . '&tab=rooms' );

	// 2. Handle Permanent Room Deletion via SQL.
	if ( 'delete' === $action && $ahbn_room_id > 0 ) {
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'ahbn_room_action' ) ) {
			wp_die( esc_html__( 'Security check failed. Please refresh and try again.', 'awesome-hotel-booking' ) );
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			$table_rooms,
			array( 'id' => $ahbn_room_id ),
			array( '%d' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $deleted ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				sprintf(
					/* translators: %d: Room Record ID */
					esc_html__( 'Room record #%d has been permanently removed from inventory.', 'awesome-hotel-booking' ),
					absint( $ahbn_room_id )
				)
			);
		}

		$ahbn_sub     = 'all';
		$ahbn_room_id = 0;
	}

	// 3. Define Tab Navigation Items.
	$tabs = array(
		'all'    => array(
			'label' => __( 'All Rooms', 'awesome-hotel-booking' ),
			'icon'  => 'dashicons-list-view',
		),
		'add'    => array(
			'label' => __( 'Add New Room', 'awesome-hotel-booking' ),
			'icon'  => 'dashicons-plus-alt2',
		),
		'extras' => array(
			'label' => __( 'Extras', 'awesome-hotel-booking' ),
			'icon'  => 'dashicons-admin-plugins',
		),
	);

	$is_edit_active = ( 'edit' === $ahbn_sub && $ahbn_room_id > 0 );
	$is_view_active = ( 'view' === $ahbn_sub && $ahbn_room_id > 0 );
	?>
	<div class="ahbn-rooms-container">
		
		<!-- Executive Brand Header -->
		<header class="ahbn-rooms-header">
			<div>
				<h1 class="ahbn-rooms-title"><?php esc_html_e( 'Room Management', 'awesome-hotel-booking' ); ?></h1>
				<p class="ahbn-rooms-subtitle">
					<?php esc_html_e( 'Manage your luxury inventory, pricing schedules, and amenities.', 'awesome-hotel-booking' ); ?>
				</p>
			</div>

			<?php if ( 'add' !== $ahbn_sub && ! $is_edit_active && ! $is_view_active ) : ?>
				<?php $add_url = add_query_arg( 'sub', 'add', $base_url ); ?>
				<a href="<?php echo esc_url( $add_url ); ?>" class="ahbn-btn-primary">
					<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
					<?php esc_html_e( 'Add New Room', 'awesome-hotel-booking' ); ?>
				</a>
			<?php endif; ?>
		</header>

		<!-- Sub-Navigation Pill Bar -->
		<div class="ahbn-subtabs-nav-bar">
			<?php
			foreach ( $tabs as $k => $data ) :
				$sub_url   = add_query_arg( 'sub', $k, $base_url );
				$is_active = ( $ahbn_sub === $k || ( 'all' === $k && in_array( $ahbn_sub, array( 'edit', 'view' ), true ) && ! $is_edit_active && ! $is_view_active ) );
				?>
				<a href="<?php echo esc_url( $sub_url ); ?>" class="ahbn-subtab-link <?php echo $is_active ? 'is-active' : ''; ?>">
					<span class="dashicons <?php echo esc_attr( $data['icon'] ); ?>" aria-hidden="true"></span>
					<span><?php echo esc_html( $data['label'] ); ?></span>
				</a>
			<?php endforeach; ?>

			<?php if ( $is_edit_active ) : ?>
				<span class="ahbn-subtab-link is-active is-context-tab">
					<span class="dashicons dashicons-edit" aria-hidden="true"></span>
					<span><?php echo esc_html( sprintf( /* translators: %d: Room ID */ __( 'Edit Room #%d', 'awesome-hotel-booking' ), $ahbn_room_id ) ); ?></span>
				</span>
			<?php elseif ( $is_view_active ) : ?>
				<span class="ahbn-subtab-link is-active is-context-tab">
					<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
					<span><?php echo esc_html( sprintf( /* translators: %d: Room ID */ __( 'Room Details #%d', 'awesome-hotel-booking' ), $ahbn_room_id ) ); ?></span>
				</span>
			<?php endif; ?>
		</div>

		<!-- Main Content Router -->
		<main class="ahbn-rooms-canvas">
			<?php
			switch ( $ahbn_sub ) {
				case 'add':
					if ( function_exists( 'ahbn_add_edit_room_tab' ) ) {
						ahbn_add_edit_room_tab();
					} else {
						echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Add room view file (admin/rooms/ahbn-room-add-edit.php) is missing.', 'awesome-hotel-booking' ) . '</p></div>';
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
						echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Extras configuration view file (admin/rooms/ahbn-rooms-settings.php) is missing.', 'awesome-hotel-booking' ) . '</p></div>';
					}
					break;

				default:
					if ( function_exists( 'ahbn_rooms_list' ) ) {
						ahbn_rooms_list();
					} else {
						echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Rooms list view file (admin/rooms/ahbn-rooms-list.php) is missing.', 'awesome-hotel-booking' ) . '</p></div>';
					}
					break;
			}
			?>
		</main>
	</div>

	<style>
		.ahbn-rooms-container {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-rooms-header {
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

		.ahbn-rooms-title {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 24px;
			font-weight: 700;
			color: #0a192f;
			margin: 0 0 4px;
		}

		.ahbn-rooms-subtitle {
			color: #64748b;
			margin: 0;
			font-size: 13px;
		}

		.ahbn-btn-primary {
			background: #0a192f !important;
			color: #ffffff !important;
			border: 1px solid #0a192f !important;
			padding: 9px 18px !important;
			border-radius: 8px !important;
			font-size: 13px !important;
			font-weight: 700 !important;
			text-decoration: none !important;
			display: inline-flex !important;
			align-items: center !important;
			gap: 6px !important;
			cursor: pointer;
			transition: all 0.2s ease !important;
		}

		.ahbn-btn-primary:hover {
			background: #c5a880 !important;
			border-color: #c5a880 !important;
			color: #0a192f !important;
		}

		/* Sub-tabs Capsule Nav Bar */
		.ahbn-subtabs-nav-bar {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			padding: 5px;
			border-radius: 12px;
			width: fit-content;
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

		.ahbn-rooms-canvas {
			min-width: 0;
		}
	</style>
	<?php
}