<?php
/**
 * Hotel Staff & Shift Management Module.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 1. Process deletions before any HTML output.
require_once AHBN_PATH . 'admin/staff/staff-delete.php';

// 2. Load View Modules.
require_once AHBN_PATH . 'admin/staff/staff-tabs.php';
require_once AHBN_PATH . 'admin/staff/staff-add-edit.php';
require_once AHBN_PATH . 'admin/staff/staff-list.php';
require_once AHBN_PATH . 'admin/staff/staff-view.php';

/**
 * Controller callback for the Staff admin tab.
 *
 * @return void
 */
function ahbn_staff_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	// Handle staff record deletion if submitted.
	ahbn_handle_staff_deletion();

	// Render the primary wrapper view.
	ahbn_render_staff_view();
}