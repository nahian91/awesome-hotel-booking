<?php
/**
 * Settings Tab Controller & Navigation.
 *
 * Handles display, sub-tab routing, and styling for Admin Configuration.
 * Styled with Champagne Gold & Deep Navy enterprise UI system.
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
	<div class="ahbn-settings-wrap">
		
		<header class="ahbn-settings-header">
			<div>
				<h2 class="ahbn-settings-title"><?php esc_html_e( 'System Configuration & Settings', 'awesome-hotel-booking' ); ?></h2>
				<p class="ahbn-settings-subtitle">
					<?php
					printf(
						/* translators: %s: Current default currency symbol, e.g., £ */
						esc_html__( 'Manage reservation policies, booking checkout gateways, and automated guest email templates (Active Currency: %s).', 'awesome-hotel-booking' ),
						esc_html( $current_currency )
					);
					?>
				</p>
			</div>
		</header>

		<!-- Sub-Tabs Navigation Bar -->
		<div class="ahbn-subtabs-nav-bar">
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

				$is_active = ( $active_sub === $key );
				?>
				<a href="<?php echo esc_url( $url ); ?>" class="ahbn-subtab-link <?php echo $is_active ? 'is-active' : ''; ?>">
					<span class="dashicons <?php echo esc_attr( $data['icon'] ); ?>" aria-hidden="true"></span>
					<span><?php echo esc_html( $data['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>

		<!-- Main Settings Form & View Router -->
		<main class="ahbn-settings-card">
			<form method="post" action="">
				<?php
				wp_nonce_field( 'ahbn_save_settings', 'ahbn_settings_nonce' );

				switch ( $active_sub ) {
					case 'payments':
						if ( function_exists( 'ahbn_settings_payments_view' ) ) {
							ahbn_settings_payments_view();
						} else {
							echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Payment settings view is not defined. Create admin/settings/payments.php to manage gateway keys.', 'awesome-hotel-booking' ) . '</p></div>';
						}
						break;

					case 'emails':
						if ( function_exists( 'ahbn_settings_emails_view' ) ) {
							ahbn_settings_emails_view();
						} else {
							echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Email settings view is not defined. Create admin/settings/emails.php to manage email notifications.', 'awesome-hotel-booking' ) . '</p></div>';
						}
						break;

					case 'general':
					default:
						if ( function_exists( 'ahbn_settings_general_view' ) ) {
							ahbn_settings_general_view();
						} else {
							echo '<div class="ahbn-empty-notice">';
							echo '<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>';
							echo '<h3>' . esc_html__( 'General Settings View', 'awesome-hotel-booking' ) . '</h3>';
							echo '<p>' . esc_html__( 'Please define ahbn_settings_general_view() in admin/settings/general.php to manage property info, taxes, and checkout options.', 'awesome-hotel-booking' ) . '</p>';
							echo '</div>';
						}
						break;
				}
				?>
			</form>
		</main>
		
	</div>

	<style>
		.ahbn-settings-wrap {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-settings-header {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 16px;
			padding: 22px 28px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 16px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-settings-title {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 24px;
			font-weight: 700;
			color: #0a192f;
			margin: 0 0 4px;
		}

		.ahbn-settings-subtitle {
			color: #64748b;
			margin: 0;
			font-size: 13px;
		}

		/* Sub-tabs Nav Bar */
		.ahbn-subtabs-nav-bar {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			padding: 5px;
			border-radius: 12px;
			width: fit-content;
		}

		.ahbn-subtab-link {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			padding: 8px 16px;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 700;
			color: #64748b;
			text-decoration: none;
			border: 1px solid transparent;
			transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
			cursor: pointer;
		}

		.ahbn-subtab-link .dashicons {
			font-size: 16px;
			width: 16px;
			height: 16px;
			color: #94a3b8;
			transition: color 0.2s ease;
		}

		.ahbn-subtab-link:hover {
			color: #0a192f;
			background: #ffffff;
			border-color: #e2e8f0;
		}

		.ahbn-subtab-link:hover .dashicons {
			color: #c5a880;
		}

		.ahbn-subtab-link.is-active {
			background: #0a192f;
			color: #ffffff !important;
			border-color: #040914;
			box-shadow: 0 4px 12px rgba(10, 25, 47, 0.15);
		}

		.ahbn-subtab-link.is-active .dashicons {
			color: #c5a880 !important;
		}

		/* Content Card */
		.ahbn-settings-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
			padding: 28px;
		}

		.ahbn-empty-notice {
			text-align: center;
			padding: 40px 20px;
			color: #64748b;
		}

		.ahbn-empty-notice .dashicons {
			font-size: 42px;
			width: 42px;
			height: 42px;
			color: #cbd5e1;
			margin-bottom: 8px;
		}

		.ahbn-empty-notice h3 {
			font-size: 17px;
			color: #0a192f;
			margin: 0 0 6px;
		}

		.ahbn-empty-notice p {
			margin: 0;
			font-size: 13px;
		}
	</style>
	<?php
}