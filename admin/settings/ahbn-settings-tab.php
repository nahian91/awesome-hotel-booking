<?php
/**
 * Settings Tab Controller & Navigation.
 *
 * Handles the display and routing for the Admin Configuration pages.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 1. Auto-load Sub-Settings Views if present in admin/settings/ directory.
$ahbn_settings_files = array(
	AHBN_PATH . 'admin/settings/general.php',
	AHBN_PATH . 'admin/settings/payments.php',
	AHBN_PATH . 'admin/settings/emails.php',
);

foreach ( $ahbn_settings_files as $ahbn_file ) {
	if ( file_exists( $ahbn_file ) ) {
		require_once $ahbn_file;
	}
}

/**
 * Render the Settings tab and dispatch to sub-tabs.
 *
 * @return void
 */
function ahbn_settings_tab() {
	// 1. SECURITY: Access Control.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	// 2. Define Sub-Tabs.
	$sub_tabs = array(
		'general'  => array(
			'label' => __( 'General', 'awesome-hotel-booking' ),
			'icon'  => 'dashicons-admin-generic',
		),
		'payments' => array(
			'label' => __( 'Payments', 'awesome-hotel-booking' ),
			'icon'  => 'dashicons-cart',
		),
		'emails'   => array(
			'label' => __( 'Emails', 'awesome-hotel-booking' ),
			'icon'  => 'dashicons-email-alt',
		),
	);

	// 3. Capture Active Sub-Tab and Page.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$requested_sub = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : '';
	$active_sub    = ( ! empty( $requested_sub ) && array_key_exists( $requested_sub, $sub_tabs ) ) ? $requested_sub : 'general';

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$requested_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	$current_page   = ! empty( $requested_page ) ? $requested_page : 'awesome_hotel_booking';

	$current_currency = get_option( 'ahbn_currency', '£' );
	?>
	<div class="ahbn-admin-container">
		
		<header class="ahbn-header">
			<div class="ahbn-header-text">
				<h1><?php esc_html_e( 'Plugin Configuration', 'awesome-hotel-booking' ); ?></h1>
				<p>
					<?php
					printf(
						/* translators: %s: Current default currency symbol, e.g., £ */
						esc_html__( 'Configure your hotel reservation rules, tax rates, and currency settings (Current: %s).', 'awesome-hotel-booking' ),
						esc_html( $current_currency )
					);
					?>
				</p>
			</div>
		</header>

		<!-- Sub-Navigation -->
		<nav class="ahbn-nav-wrapper">
			<?php
			foreach ( $sub_tabs as $key => $data ) :
				$url = add_query_arg(
					array(
						'page' => $current_page,
						'tab'  => 'settings',
						'sub'  => $key,
					),
					admin_url( 'admin.php' )
				);

				$active_class = ( $active_sub === $key ) ? ' is-active' : '';
				?>
				<a href="<?php echo esc_url( $url ); ?>" class="ahbn-nav-item<?php echo esc_attr( $active_class ); ?>">
					<span class="dashicons <?php echo esc_attr( $data['icon'] ); ?>" aria-hidden="true"></span>
					<span class="ahbn-nav-label"><?php echo esc_html( $data['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</nav>

		<!-- Main Settings Form & View Router -->
		<main class="ahbn-content-card">
			<form method="post" action="">
				<?php
				wp_nonce_field( 'ahbn_save_settings', 'ahbn_settings_nonce' );

				switch ( $active_sub ) {
					case 'payments':
						if ( function_exists( 'ahbn_settings_payments_view' ) ) {
							ahbn_settings_payments_view();
						} else {
							echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Payment settings view is not defined.', 'awesome-hotel-booking' ) . '</p></div>';
						}
						break;

					case 'emails':
						if ( function_exists( 'ahbn_settings_emails_view' ) ) {
							ahbn_settings_emails_view();
						} else {
							echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Email settings view is not defined.', 'awesome-hotel-booking' ) . '</p></div>';
						}
						break;

					case 'general':
					default:
						if ( function_exists( 'ahbn_settings_general_view' ) ) {
							ahbn_settings_general_view();
						} else {
							echo '<h3>' . esc_html__( 'General Settings', 'awesome-hotel-booking' ) . '</h3>';
							echo '<p>' . esc_html__( 'Please define ahbn_settings_general_view() to display fields here.', 'awesome-hotel-booking' ) . '</p>';
						}
						break;
				}
				?>
			</form>
		</main>
		
	</div> 
	<?php
}