<?php
/**
 * Coupon Deletion Action Handler.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check and process coupon delete requests.
 *
 * @return void
 */
function ahbn_handle_coupon_deletion() {
	if ( 'POST' !== ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
		return;
	}

	if ( ! isset( $_POST['ahbn_delete_coupon'] ) ) {
		return;
	}

	global $wpdb;
	$table_coupons = $wpdb->prefix . 'ahbn_coupons';

	$del_nonce = isset( $_POST['ahbn_del_coupon_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_del_coupon_nonce'] ) ) : '';
	$del_id    = isset( $_POST['del_coupon_id'] ) ? absint( wp_unslash( $_POST['del_coupon_id'] ) ) : 0;

	if ( wp_verify_nonce( $del_nonce, 'ahbn_delete_coupon_action' ) && $del_id > 0 ) {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $table_coupons, array( 'id' => $del_id ), array( '%d' ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Coupon removed successfully.', 'awesome-hotel-booking' ) . '</p></div>';
	} else {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Unable to delete coupon.', 'awesome-hotel-booking' ) . '</p></div>';
	}
}