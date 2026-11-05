<?php
/**
 * Promotional Codes & Discount Coupons Management Module.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 1. Process deletions before any HTML output.
require_once AHBN_PATH . 'admin/coupons/coupon-delete.php';

// 2. Load View Modules.
require_once AHBN_PATH . 'admin/coupons/coupon-tabs.php';
require_once AHBN_PATH . 'admin/coupons/coupon-add-edit.php';
require_once AHBN_PATH . 'admin/coupons/coupon-list.php';
require_once AHBN_PATH . 'admin/coupons/coupon-view.php';

/**
 * Controller callback for the Coupons admin tab.
 *
 * @return void
 */
function ahbn_coupons_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	// Handle coupon deletions first if triggered.
	ahbn_handle_coupon_deletion();

	// Render the primary wrapper template.
	ahbn_render_coupon_view();
}