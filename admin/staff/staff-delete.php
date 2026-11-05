<?php
/**
 * Staff Deletion Action Handler.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check and process staff deletion requests.
 *
 * @return void
 */
function ahbn_handle_staff_deletion() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
		return;
	}

	if ( ! isset( $_POST['ahbn_delete_staff'] ) ) {
		return;
	}

	global $wpdb;
	$table_staff = $wpdb->prefix . 'ahbn_staff';

	$del_nonce = isset( $_POST['ahbn_del_staff_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_del_staff_nonce'] ) ) : '';
	$del_id    = isset( $_POST['del_staff_id'] ) ? absint( wp_unslash( $_POST['del_staff_id'] ) ) : 0;

	if ( wp_verify_nonce( $del_nonce, 'ahbn_delete_staff_action' ) && $del_id > 0 ) {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table_staff, array( 'id' => $del_id ), array( '%d' ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Staff record deleted.', 'awesome-hotel-booking' ) . '</p></div>';
	} else {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Unable to delete staff member.', 'awesome-hotel-booking' ) . '</p></div>';
	}
}