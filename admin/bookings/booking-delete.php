<?php
/**
 * Delete Booking Handler.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$table_bookings = $wpdb->prefix . 'ahbn_bookings';

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$booking_id = isset( $_GET['booking_id'] ) ? absint( wp_unslash( $_GET['booking_id'] ) ) : 0;
$nonce      = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended

if ( $booking_id > 0 ) {
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'ahbn_booking_action' ) ) {
		wp_die( esc_html__( 'Security check failed. Please return to the bookings list and try again.', 'awesome-hotel-booking' ) );
	}

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$deleted = $wpdb->delete(
		$table_bookings,
		array( 'id' => $booking_id ),
		array( '%d' )
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	if ( false !== $deleted ) {
		do_action( 'ahbn_after_booking_deleted', $booking_id );

		echo '<div class="notice notice-warning is-dismissible"><p>' . sprintf(
			/* translators: %d: The ID of the deleted booking */
			esc_html__( 'Reservation #%d has been deleted.', 'awesome-hotel-booking' ),
			absint( $booking_id )
		) . '</p></div>';
	}
}