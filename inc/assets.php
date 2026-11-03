<?php
/**
 * Assets Enqueue Engine (Admin & Frontend).
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue administrative styles, scripts, and dependencies.
 *
 * @param string $hook Current admin page identifier.
 * @return void
 */
function ahbn_plugin_admin_scripts( $hook ) {
	if ( 'toplevel_page_awesome_hotel_booking' !== $hook ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_style( 'wp-color-picker' );

	// 1. Google Typography: Playfair Display + Plus Jakarta Sans + JetBrains Mono.
	wp_enqueue_style(
		'ahbn-google-fonts',
		'https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;700&family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);

	// 2. DataTables CDN Stylesheet.
	wp_enqueue_style(
		'ahbn-datatables-css',
		'https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css',
		array(),
		'1.13.7'
	);

	// 3. Main Admin Design System.
	wp_enqueue_style(
		'ahbn-admin-layout',
		AHBN_URL . 'assets/css/admin-style.css',
		array( 'ahbn-datatables-css' ),
		AHBN_VERSION
	);

	// Inject Live Customizer CSS Overrides if configured.
	$custom_css = get_option( 'ahbn_custom_css', '' );
	if ( ! empty( $custom_css ) ) {
		wp_add_inline_style( 'ahbn-admin-layout', wp_strip_all_tags( $custom_css ) );
	}

	// 4. DataTables CDN Library.
	wp_enqueue_script(
		'ahbn-datatables-js',
		'https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js',
		array( 'jquery' ),
		'1.13.7',
		true
	);

	// 5. Main Admin Script.
	wp_enqueue_script(
		'ahbn-admin-script',
		AHBN_URL . 'assets/js/admin-script.js',
		array( 'jquery', 'wp-color-picker', 'ahbn-datatables-js' ),
		AHBN_VERSION,
		true
	);

	wp_localize_script(
		'ahbn-admin-script',
		'ahbn_admin_obj',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'ahbn_admin_nonce' ),
			'currency' => get_option( 'ahbn_currency', '£' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'ahbn_plugin_admin_scripts' );

/**
 * Enqueue frontend visitor assets.
 *
 * @return void
 */
function ahbn_plugin_frontend_scripts() {
	wp_enqueue_style(
		'ahbn-frontend-style',
		AHBN_URL . 'assets/css/frontend-style.css',
		array(),
		AHBN_VERSION
	);

	$custom_css = get_option( 'ahbn_custom_css', '' );
	if ( ! empty( $custom_css ) ) {
		wp_add_inline_style( 'ahbn-frontend-style', wp_strip_all_tags( $custom_css ) );
	}

	wp_enqueue_script(
		'ahbn-frontend-script',
		AHBN_URL . 'assets/js/frontend-script.js',
		array( 'jquery' ),
		AHBN_VERSION,
		true
	);

	wp_localize_script(
		'ahbn-frontend-script',
		'ahbn_ajax_obj',
		array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'ahbn_frontend_nonce' ),
			'currency' => get_option( 'ahbn_currency', '£' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ahbn_plugin_frontend_scripts' );

/**
 * Output dynamic Schema.org JSON-LD Structured Data for Search Engines.
 *
 * @return void
 */
function ahbn_output_hotel_schema() {
	if ( is_admin() ) {
		return;
	}

	$hotel_name  = get_option( 'ahbn_hotel_name', get_bloginfo( 'name' ) );
	$hotel_desc  = get_option( 'ahbn_hotel_desc', get_bloginfo( 'description' ) );
	$schema_data = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Hotel',
		'name'        => esc_html( $hotel_name ),
		'description' => esc_html( $hotel_desc ),
		'url'         => esc_url( home_url( '/' ) ),
		'priceRange'  => get_option( 'ahbn_currency', '£' ) . '$$',
	);

	echo "\n<!-- Awesome Hotel Booking Schema -->\n";
	echo '<script type="application/ld+json">' . wp_json_encode( $schema_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . "</script>\n";
}
add_action( 'wp_head', 'ahbn_output_hotel_schema' );
