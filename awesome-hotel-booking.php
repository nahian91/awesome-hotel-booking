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
# 5. Icon-Only Full-Screen Canvas & Toggle Routine
--------------------------------------------------------------*/
/**
 * Inject Icon-Only Full-Screen Toggle on AHBN Admin Pages.
 *
 * @param string $hook_suffix Current admin page hook.
 * @return void
 */
function ahbn_admin_fullscreen_canvas( $hook_suffix ) {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( false === strpos( $page, 'awesome_hotel_booking' ) ) {
		return;
	}

	$is_fullscreen = (bool) get_option( 'ahbn_admin_fullscreen', 0 );
	?>
	<style id="ahbn-fullscreen-styles">
		/* Icon-Only Floating Toggle Button */
		.ahbn-fs-icon-btn {
			position: fixed;
			top: 38px;
			right: 25px;
			z-index: 999999;
			width: 36px;
			height: 36px;
			background: #0a192f;
			color: #c5a880 !important;
			border: 1px solid #c5a880;
			border-radius: 8px;
			cursor: pointer;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			padding: 0;
			box-shadow: 0 4px 12px rgba(10, 25, 47, 0.25);
			transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
		}
		.ahbn-fs-icon-btn:hover {
			background: #c5a880;
			color: #0a192f !important;
			transform: scale(1.05);
		}
		.ahbn-fs-icon-btn .dashicons {
			font-size: 18px;
			width: 18px;
			height: 18px;
			line-height: 1;
		}

		/* Fullscreen Mode Overrides */
		body.ahbn-fullscreen-mode #adminmenumain,
		body.ahbn-fullscreen-mode #wpadminbar,
		body.ahbn-fullscreen-mode #wpfooter {
			display: none !important;
		}
		body.ahbn-fullscreen-mode #wpcontent,
		body.ahbn-fullscreen-mode #wpbody-content {
			margin-left: 0 !important;
			padding: 24px 32px !important;
			width: 100vw !important;
			min-height: 100vh !important;
			box-sizing: border-box !important;
		}
		body.ahbn-fullscreen-mode html.wp-toolbar {
			padding-top: 0 !important;
		}
		body.ahbn-fullscreen-mode .ahbn-fs-icon-btn {
			top: 18px;
			right: 25px;
		}
	</style>

	<script type="text/javascript">
	(function($) {
		$(document).ready(function() {
			const isFs = <?php echo $is_fullscreen ? 'true' : 'false'; ?>;
			if (isFs) {
				$('body').addClass('ahbn-fullscreen-mode');
			}

			const isNowFs = $('body').hasClass('ahbn-fullscreen-mode');
			const iconClass = isNowFs ? 'dashicons-fullscreen-exit-alt' : 'dashicons-fullscreen-alt';
			const tooltipText = isNowFs 
				? '<?php echo esc_js( __( 'Exit Fullscreen', 'awesome-hotel-booking' ) ); ?>' 
				: '<?php echo esc_js( __( 'Enter Fullscreen', 'awesome-hotel-booking' ) ); ?>';

			const $btn = $('<button/>', {
				type: 'button',
				class: 'ahbn-fs-icon-btn',
				title: tooltipText,
				'aria-label': tooltipText,
				html: '<span class="dashicons ' + iconClass + '" aria-hidden="true"></span>'
			}).appendTo('body');

			$btn.on('click', function(e) {
				e.preventDefault();
				$('body').toggleClass('ahbn-fullscreen-mode');
				const active = $('body').hasClass('ahbn-fullscreen-mode') ? 1 : 0;

				const nextIcon = active ? 'dashicons-fullscreen-exit-alt' : 'dashicons-fullscreen-alt';
				const nextTooltip = active 
					? '<?php echo esc_js( __( 'Exit Fullscreen', 'awesome-hotel-booking' ) ); ?>' 
					: '<?php echo esc_js( __( 'Enter Fullscreen', 'awesome-hotel-booking' ) ); ?>';

				$btn.attr('title', nextTooltip)
					.attr('aria-label', nextTooltip)
					.html('<span class="dashicons ' + nextIcon + '" aria-hidden="true"></span>');

				// Persist preference via AJAX
				$.post(ajaxurl, {
					action: 'ahbn_save_fullscreen_pref',
					status: active,
					security: '<?php echo esc_js( wp_create_nonce( 'ahbn_fs_nonce' ) ); ?>'
				});
			});
		});
	})(jQuery);
	</script>
	<?php
}
add_action( 'admin_head', 'ahbn_admin_fullscreen_canvas' );

/**
 * Handle AJAX request to store full-screen preference.
 *
 * @return void
 */
function ahbn_save_fullscreen_preference() {
	check_ajax_referer( 'ahbn_fs_nonce', 'security' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( esc_html__( 'Unauthorized', 'awesome-hotel-booking' ) );
	}

	$status = isset( $_POST['status'] ) ? absint( $_POST['status'] ) : 0;
	update_option( 'ahbn_admin_fullscreen', $status );
	wp_send_json_success( array( 'fullscreen' => $status ) );
}
add_action( 'wp_ajax_ahbn_save_fullscreen_pref', 'ahbn_save_fullscreen_preference' );

/*--------------------------------------------------------------
# 6. Activation & Setup Routines
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