<?php
/**
 * Master Bookings Loader & Modular Dispatcher.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Bookings Tab callback for the admin menu.
 *
 * @return void
 */
function ahbn_bookings_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	// 1. Intercept state-changing delete requests before rendering.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

	if ( 'delete' === $action ) {
		$delete_file = AHBN_PATH . 'admin/bookings/booking-delete.php';
		if ( file_exists( $delete_file ) ) {
			require_once $delete_file;
		}
	}

	// 2. Load the sub-router view controller.
	$tab_file = AHBN_PATH . 'admin/bookings/booking-tab.php';
	if ( file_exists( $tab_file ) ) {
		require_once $tab_file;
	}
}