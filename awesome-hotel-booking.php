<?php
/**
 * Plugin Name:       Awesome Hotel Booking
 * Plugin URI:        https://devnahian.com
 * Description:       Enterprise Hotel Booking & Hospitality Management Engine built on high-performance custom relational database tables.
 * Version:           2.5.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Abdullah Nahian
 * Author URI:        https://devnahian.com
 * Text Domain:       awesome-hotel-booking
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define Plugin Constants.
 */
define( 'AHBN_VERSION', '2.5.0' );
define( 'AHBN_DB_VERSION', '2.5.0' );
define( 'AHBN_PATH', plugin_dir_path( __FILE__ ) );
define( 'AHBN_URL', plugin_dir_url( __FILE__ ) );

/*--------------------------------------------------------------
# 1. Modular Core Architecture Includes
--------------------------------------------------------------*/
require_once AHBN_PATH . 'inc/db.php';
require_once AHBN_PATH . 'inc/menu.php';
require_once AHBN_PATH . 'inc/assets.php';

/*--------------------------------------------------------------
# 2. Global Utilities & Price Formatter
--------------------------------------------------------------*/
if ( ! function_exists( 'ahbn_format_price' ) ) {
	/**
	 * Format currency according to plugin settings.
	 *
	 * @param float|int|string $amount Numeric price.
	 * @return string Formatted price string.
	 */
	function ahbn_format_price( $amount ) {
		$currency     = get_option( 'ahbn_currency', '£' );
		$currency_pos = get_option( 'ahbn_currency_pos', 'left' );
		$clean_amount = (float) preg_replace( '/[^\d.]/', '', (string) $amount );
		$formatted    = number_format_i18n( $clean_amount, 2 );

		return ( 'left' === $currency_pos ) ? $currency . $formatted : $formatted . $currency;
	}
}

/*--------------------------------------------------------------
# 3. Rewrite Rules & Query Variables
--------------------------------------------------------------*/
/**
 * Register clean rewrite rules for hotel room details.
 *
 * @return void
 */
function ahbn_core_rewrites() {
	add_rewrite_rule(
		'^ahbn-room-details/([^/]+)/?',
		'index.php?pagename=ahbn-room-details&ahbn_room_name=$matches[1]',
		'top'
	);
}
add_action( 'init', 'ahbn_core_rewrites' );

/**
 * Register public query variables.
 *
 * @param array $vars Public query variables.
 * @return array
 */
function ahbn_core_query_vars( $vars ) {
	$vars[] = 'ahbn_room_name';
	return $vars;
}
add_filter( 'query_vars', 'ahbn_core_query_vars' );

/*--------------------------------------------------------------
# 4. Load Component Submodules
--------------------------------------------------------------*/
$ahbn_includes = array(
	'admin/dashboard.php',
	'admin/calendar.php',
	'admin/rooms.php',
	'admin/bookings.php',
	'admin/housekeeping.php',
	'admin/pricing.php',
	'admin/coupons.php',
	'admin/extra-services.php',
	'admin/staff.php',
	'admin/expense.php',
	'admin/report.php',
	'admin/customers.php',
	'admin/seo.php',
	'admin/styling.php',
	'admin/settings.php',
	'admin/shortcode.php',
	'frontend/frontend.php',
);

foreach ( $ahbn_includes as $ahbn_file ) {
	$ahbn_file_path = AHBN_PATH . $ahbn_file;
	if ( file_exists( $ahbn_file_path ) ) {
		require_once $ahbn_file_path;
	}
}

/*--------------------------------------------------------------
# 5. Activation & Setup Routines
--------------------------------------------------------------*/
/**
 * Setup default pages with shortcodes.
 *
 * @return void
 */
function ahbn_setup_pages() {
	$pages = array(
		'ahbn_all_rooms_page'    => array(
			'title' => __( 'All Rooms', 'awesome-hotel-booking' ),
			'slug'  => 'rooms',
			'short' => '[ahbn_all_rooms]',
		),
		'ahbn_single_room_page'  => array(
			'title' => __( 'Room Details', 'awesome-hotel-booking' ),
			'slug'  => 'room-details',
			'short' => '[ahbn_room_details]',
		),
		'ahbn_account_page'      => array(
			'title' => __( 'Account', 'awesome-hotel-booking' ),
			'slug'  => 'ahbn-account',
			'short' => '[ahbn_account]',
		),
		'ahbn_thanks_page'       => array(
			'title' => __( 'Thank You', 'awesome-hotel-booking' ),
			'slug'  => 'ahbn-thanks',
			'short' => '[ahbn_thanks]',
		),
		'ahbn_login_page'        => array(
			'title' => __( 'Login', 'awesome-hotel-booking' ),
			'slug'  => 'ahbn-login',
			'short' => '[ahbn_login]',
		),
		'ahbn_registration_page' => array(
			'title' => __( 'Registration', 'awesome-hotel-booking' ),
			'slug'  => 'ahbn-registration',
			'short' => '[ahbn_register]',
		),
	);

	$author_id = get_current_user_id();

	foreach ( $pages as $option_key => $data ) {
		$existing_page = get_page_by_path( $data['slug'] );

		if ( ! $existing_page ) {
			$new_page_id = wp_insert_post(
				array(
					'post_title'   => $data['title'],
					'post_name'    => $data['slug'],
					'post_content' => $data['short'],
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_author'  => $author_id,
				)
			);

			if ( ! is_wp_error( $new_page_id ) ) {
				update_option( $option_key, $new_page_id );
			}
		} else {
			if ( 'trash' === $existing_page->post_status ) {
				wp_untrash_post( $existing_page->ID );
			}
			update_option( $option_key, $existing_page->ID );
		}
	}
}

/**
 * Plugin activation routine.
 *
 * @return void
 */
function ahbn_plugin_activate() {
	if ( function_exists( 'ahbn_install_database_schema' ) ) {
		ahbn_install_database_schema();
	}
	ahbn_setup_pages();
	ahbn_core_rewrites();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ahbn_plugin_activate' );

/**
 * Plugin deactivation routine.
 *
 * @return void
 */
function ahbn_plugin_deactivate() {
	delete_transient( 'ahbn_dashboard_stats' );
	delete_transient( 'ahbn_all_rooms_list' );
	delete_transient( 'ahbn_rooms_active_count' );
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'ahbn_plugin_deactivate' );