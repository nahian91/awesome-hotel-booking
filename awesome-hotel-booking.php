<?php
/**
 * Plugin Name:       Awesome Hotel Booking
 * Plugin URI:        https://devnahian.com
 * Description:       Enterprise Hotel Booking & Hospitality Management Engine built on high-performance custom relational database tables.
 * Version:           1.1
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
define( 'AHBN_VERSION', '1.1' );
define( 'AHBN_DB_VERSION', '1.1' );
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
		'^room-details/([^/]+)/?',
		'index.php?pagename=room-details&ahbn_room_name=$matches[1]',
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
# 5. Hide WP Admin Menu Toggle Routine
--------------------------------------------------------------*/
/**
 * Inject the styles and logic to collapse/hide the WP admin menu.
 *
 * @param string $hook_suffix Current admin page hook.
 * @return void
 */
function ahbn_toggle_wp_menu_script( $hook_suffix ) {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	if ( false === strpos( $page, 'awesome_hotel_booking' ) ) {
		return;
	}

	$is_hidden = (bool) get_option( 'ahbn_hide_wp_menu', 0 );
	?>
	<style id="ahbn-hide-wp-menu-styles">
		/* Header Action Bar inside Brand Card */
		.ahbn-brand-actions {
			display: inline-flex;
			align-items: center;
			gap: 6px;
		}

		/* Embedded Header Icon Button */
		.ahbn-toggle-wp-menu-btn {
			width: 24px;
			height: 24px;
			background: rgba(197, 168, 128, 0.12);
			color: #c5a880 !important;
			border: 1px solid rgba(197, 168, 128, 0.28);
			border-radius: 6px;
			cursor: pointer;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			padding: 0;
			transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
		}

		.ahbn-toggle-wp-menu-btn:hover {
			background: #c5a880;
			color: #0a192f !important;
			transform: scale(1.08);
		}

		.ahbn-toggle-wp-menu-btn .dashicons {
			font-size: 14px;
			width: 14px;
			height: 14px;
			line-height: 1;
		}

		/* When WordPress Menu is Hidden */
		body.ahbn-wp-menu-hidden #adminmenumain,
		body.ahbn-wp-menu-hidden #adminmenuback,
		body.ahbn-wp-menu-hidden #adminmenuwrap {
			display: none !important;
		}

		body.ahbn-wp-menu-hidden #wpcontent {
			margin-left: 0 !important;
			padding-left: 20px !important;
		}
	</style>

	<script type="text/javascript">
	(function($) {
		$(document).ready(function() {
			var isHidden = <?php echo $is_hidden ? 'true' : 'false'; ?>;
			if (isHidden) {
				$('body').addClass('ahbn-wp-menu-hidden');
				$('.ahbn-toggle-wp-menu-btn .dashicons')
					.removeClass('dashicons-arrow-left-alt2')
					.addClass('dashicons-menu-alt3');
				$('.ahbn-toggle-wp-menu-btn').attr('title', '<?php echo esc_js( __( 'Show WordPress Menu', 'awesome-hotel-booking' ) ); ?>');
			}

			$(document).on('click', '.ahbn-toggle-wp-menu-btn', function(e) {
				e.preventDefault();
				$('body').toggleClass('ahbn-wp-menu-hidden');
				var active = $('body').hasClass('ahbn-wp-menu-hidden') ? 1 : 0;

				var nextIcon  = active ? 'dashicons-menu-alt3' : 'dashicons-arrow-left-alt2';
				var nextTitle = active 
					? '<?php echo esc_js( __( 'Show WordPress Menu', 'awesome-hotel-booking' ) ); ?>' 
					: '<?php echo esc_js( __( 'Hide WordPress Menu', 'awesome-hotel-booking' ) ); ?>';

				$(this).attr('title', nextTitle)
					.attr('aria-label', nextTitle)
					.find('.dashicons')
					.removeClass('dashicons-menu-alt3 dashicons-arrow-left-alt2')
					.addClass(nextIcon);

				$.post(ajaxurl, {
					action: 'ahbn_save_wp_menu_toggle',
					status: active,
					security: '<?php echo esc_js( wp_create_nonce( 'ahbn_wp_menu_nonce' ) ); ?>'
				});
			});
		});
	})(jQuery);
	</script>
	<?php
}
add_action( 'admin_head', 'ahbn_toggle_wp_menu_script' );

/**
 * Handle AJAX request to store WP menu visibility preference.
 *
 * @return void
 */
function ahbn_save_wp_menu_toggle_preference() {
	check_ajax_referer( 'ahbn_wp_menu_nonce', 'security' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( esc_html__( 'Unauthorized', 'awesome-hotel-booking' ) );
	}

	$status = isset( $_POST['status'] ) ? absint( $_POST['status'] ) : 0;
	update_option( 'ahbn_hide_wp_menu', $status );
	wp_send_json_success( array( 'hidden' => $status ) );
}
add_action( 'wp_ajax_ahbn_save_wp_menu_toggle', 'ahbn_save_wp_menu_toggle_preference' );

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
	delete_option( 'ahbn_hide_wp_menu' );
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'ahbn_plugin_deactivate' );