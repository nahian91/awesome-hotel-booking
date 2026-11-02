<?php
/**
 * Room Deletion Handler.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle Room Deletion (Move to Trash).
 *
 * Hooked to admin_post_ahbn_delete_room.
 *
 * @return void
 */
function ahbn_handle_room_deletion() {
	// 1. Verify User Capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'awesome-hotel-booking' ) );
	}

	// 2. Validate & Sanitize Input.
	$ahbn_room_id = isset( $_GET['room'] ) ? absint( wp_unslash( $_GET['room'] ) ) : 0;
	$ahbn_nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

	// 3. Security Verification.
	if ( ! $ahbn_room_id || ! wp_verify_nonce( $ahbn_nonce, 'ahbn_delete_room_' . $ahbn_room_id ) ) {
		wp_die( esc_html__( 'Security verification failed. Please try again.', 'awesome-hotel-booking' ) );
	}

	if ( ! current_user_can( 'delete_post', $ahbn_room_id ) ) {
		wp_die( esc_html__( 'You are not allowed to delete this room.', 'awesome-hotel-booking' ) );
	}

	// 4. Verify Post Type.
	if ( 'ahbn_room' !== get_post_type( $ahbn_room_id ) ) {
		wp_die( esc_html__( 'Invalid room record.', 'awesome-hotel-booking' ) );
	}

	// 5. Execute Action (Trash the room).
	wp_trash_post( $ahbn_room_id );

	// Clear room list transient.
	delete_transient( 'ahbn_all_rooms_list' );

	// 6. Safe Redirect back to Inventory List with Notice.
	$redirect_url = add_query_arg(
		array(
			'page' => 'awesome_hotel_booking',
			'tab'  => 'rooms',
			'sub'  => 'all',
			'msg'  => 'deleted',
		),
		admin_url( 'admin.php' )
	);

	wp_safe_redirect( $redirect_url );
	exit;
}
add_action( 'admin_post_ahbn_delete_room', 'ahbn_handle_room_deletion' );

/**
 * Display Admin Notice when a room is deleted.
 *
 * @return void
 */
function ahbn_room_delete_admin_notice() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['page'], $_GET['tab'], $_GET['msg'] ) && 'awesome_hotel_booking' === $_GET['page'] && 'rooms' === $_GET['tab'] && 'deleted' === $_GET['msg'] ) {
		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			esc_html__( 'Room has been moved to trash successfully.', 'awesome-hotel-booking' )
		);
	}
}
add_action( 'admin_notices', 'ahbn_room_delete_admin_notice' );