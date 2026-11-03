<?php
/**
 * Admin Navigation & Menu Architecture.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register top-level administration menu.
 *
 * @return void
 */
function ahbn_register_admin_menu() {
	add_menu_page(
		__( 'Hotel Booking', 'awesome-hotel-booking' ),
		__( 'Hotel Booking', 'awesome-hotel-booking' ),
		'manage_options',
		'awesome_hotel_booking',
		'ahbn_main_page',
		'dashicons-building',
		20
	);
}
add_action( 'admin_menu', 'ahbn_register_admin_menu' );

/**
 * Retrieve organized admin menu definitions with simple, direct names.
 *
 * @return array
 */
function ahbn_get_admin_menu_groups() {
	return array(
		'operations' => array(
			'title' => __( 'Operations', 'awesome-hotel-booking' ),
			'items' => array(
				'dashboard'    => array(
					'label' => __( 'Dashboard', 'awesome-hotel-booking' ),
					'icon'  => 'dashicons-performance',
				),
				'calendar'     => array(
					'label' => __( 'Calendar', 'awesome-hotel-booking' ),
					'icon'  => 'dashicons-calendar',
				),
				'bookings'     => array(
					'label' => __( 'Bookings', 'awesome-hotel-booking' ),
					'icon'  => 'dashicons-calendar-alt',
				),
				'housekeeping' => array(
					'label' => __( 'Housekeeping', 'awesome-hotel-booking' ),
					'icon'  => 'dashicons-forms',
				),
				'staff'        => array(
					'label' => __( 'Staff', 'awesome-hotel-booking' ),
					'icon'  => 'dashicons-id-alt',
				),
			),
		),
		'inventory'  => array(
			'title' => __( 'Inventory', 'awesome-hotel-booking' ),
			'items' => array(
				'rooms'          => array(
					'label' => __( 'Rooms', 'awesome-hotel-booking' ),
					'icon'  => 'dashicons-building',
				),
				'pricing'        => array(
					'label' => __( 'Pricing', 'awesome-hotel-booking' ),
					'icon'  => 'dashicons-money-alt',
				),
				'coupons'        => array(
					'label' => __( 'Coupons', 'awesome-hotel-booking' ),
					'icon'  => 'dashicons-tickets-alt',
				),
				'extra_services' => array(
					'label' => __( 'Services', 'awesome-hotel-booking' ),
					'icon'  => 'dashicons-coffee',
				),
			),
		),
		'finance'    => array(
			'title' => __( 'Finance & Guests', 'awesome-hotel-booking' ),
			'items' => array(
				'expense'   => array(
					'label' => __( 'Expenses', 'awesome-hotel-booking' ),
					'icon'  => 'dashicons-chart-pie',
				),
				'reports'   => array(
					'label' => __( 'Reports', 'awesome-hotel-booking' ),
					'icon'  => 'dashicons-chart-bar',
				),
				'customers' => array(
					'label' => __( 'Guests', 'awesome-hotel-booking' ),
					'icon'  => 'dashicons-groups',
				),
			),
		),
		'settings_group' => array(
			'title' => __( 'Settings', 'awesome-hotel-booking' ),
			'items' => array(
				'seo'       => array(
					'label' => __( 'SEO', 'awesome-hotel-booking' ),
					'icon'  => 'dashicons-search',
				),
				'styling'   => array(
					'label' => __( 'Design', 'awesome-hotel-booking' ),
					'icon'  => 'dashicons-art',
				),
				'settings'  => array(
					'label' => __( 'Settings', 'awesome-hotel-booking' ),
					'icon'  => 'dashicons-admin-generic',
				),
				'shortcode' => array(
					'label' => __( 'Shortcodes', 'awesome-hotel-booking' ),
					'icon'  => 'dashicons-editor-code',
				),
			),
		),
		'builders'   => array(
			'title' => __( 'Builders', 'awesome-hotel-booking' ),
			'items' => array(
				'elementor' => array(
					'label' => __( 'Elementor', 'awesome-hotel-booking' ),
					'icon'  => 'dashicons-welcome-widgets-menus',
				),
				'blocks'    => array(
					'label' => __( 'Blocks', 'awesome-hotel-booking' ),
					'icon'  => 'dashicons-block-default',
				),
			),
		),
	);
}

/**
 * Display the main admin layout and organized section router.
 *
 * @return void
 */
function ahbn_main_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$active_tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'dashboard';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$action      = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	$menu_groups = ahbn_get_admin_menu_groups();
	?>
	<style>
		.wrap.awesome-hotel-booking {
			margin: 20px 20px 0 0 !important;
			display: flex;
			gap: 24px;
			align-items: flex-start;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
			color: #0f172a;
		}

		.wrap.awesome-hotel-booking * {
			box-sizing: border-box;
		}

		/* Non-sticky, Natural Height Sidebar */
		.ahbn-sidebar-container {
			flex: 0 0 240px;
			width: 240px;
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 16px;
			box-shadow: 0 4px 20px -2px rgba(10, 20, 38, 0.05);
			overflow: visible;
			position: static !important;
			top: auto !important;
		}

		/* Brand Header */
		.ahbn-brand-header {
			padding: 20px 18px;
			background: linear-gradient(135deg, #07111e 0%, #0a192f 100%);
			border-bottom: 1px solid rgba(255, 255, 255, 0.08);
			border-radius: 15px 15px 0 0;
		}

		.ahbn-brand-header h2 {
			margin: 0;
			color: #ffffff;
			font-size: 15px;
			font-weight: 800;
			letter-spacing: -0.01em;
			display: flex;
			align-items: center;
			justify-content: space-between;
		}

		.ahbn-brand-title-left {
			display: inline-flex;
			align-items: center;
			gap: 8px;
		}

		.ahbn-brand-title-left .dashicons {
			color: #c5a880;
			font-size: 20px;
			width: 20px;
			height: 20px;
		}

		.ahbn-brand-header .version-tag {
			font-family: 'JetBrains Mono', ui-monospace, Menlo, Consolas, monospace;
			background: rgba(197, 168, 128, 0.16);
			color: #c5a880;
			border: 1px solid rgba(197, 168, 128, 0.3);
			padding: 2px 7px;
			border-radius: 99px;
			font-size: 10px;
			font-weight: 700;
			letter-spacing: 0.5px;
		}

		/* Normal Flow List Container */
		.ahbn-nav-scroll-wrapper {
			padding: 14px 10px 18px;
			max-height: none !important;
			overflow: visible !important;
		}

		/* Section Headings */
		.ahbn-menu-section-group {
			margin-bottom: 16px;
		}

		.ahbn-menu-section-group:last-child {
			margin-bottom: 0;
		}

		.ahbn-menu-group-heading {
			display: block;
			font-size: 10px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.08em;
			color: #94a3b8;
			padding: 4px 10px 6px;
		}

		/* Tab Items */
		.ahbn-left-tabs {
			list-style: none;
			margin: 0 !important;
			padding: 0 !important;
		}

		.ahbn-left-tabs li {
			margin: 0 0 2px 0 !important;
		}

		.ahbn-left-tabs li a {
			display: flex;
			align-items: center;
			gap: 10px;
			padding: 8px 12px;
			text-decoration: none;
			color: #64748b;
			font-size: 13px;
			font-weight: 600;
			border-radius: 8px;
			border: 1px solid transparent;
			transition: all 0.2s ease;
		}

		.ahbn-left-tabs li a .dashicons {
			font-size: 17px;
			width: 17px;
			height: 17px;
			color: #94a3b8;
			transition: color 0.2s ease;
		}

		.ahbn-nav-label {
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		/* Hover State */
		.ahbn-left-tabs li a:hover {
			color: #0a192f;
			background: #f8fafc;
			border-color: #e2e8f0;
			transform: translateX(2px);
		}

		.ahbn-left-tabs li a:hover .dashicons {
			color: #b39264;
		}

		/* Active State */
		.ahbn-left-tabs li a.active {
			background: #0a192f;
			color: #ffffff !important;
			font-weight: 700;
			border-color: #040914;
			box-shadow: 0 4px 12px rgba(10, 25, 47, 0.15);
		}

		.ahbn-left-tabs li a.active .dashicons {
			color: #c5a880 !important;
		}

		/* Right Content Canvas */
		.ahbn-right-box {
			flex: 1;
			min-width: 0;
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 16px;
			padding: 28px;
			box-shadow: 0 4px 20px -2px rgba(10, 20, 38, 0.05);
		}

		@media (max-width: 1024px) {
			.wrap.awesome-hotel-booking {
				flex-direction: column;
				margin-right: 15px !important;
			}
			.ahbn-sidebar-container {
				flex: 1 1 100%;
				width: 100%;
			}
		}

		@media print {
			.ahbn-sidebar-container {
				display: none !important;
			}
			.wrap.awesome-hotel-booking {
				margin: 0 !important;
				gap: 0 !important;
			}
			.ahbn-right-box {
				border: none !important;
				padding: 0 !important;
				box-shadow: none !important;
			}
		}
	</style>

	<div class="wrap awesome-hotel-booking">
		<?php if ( 'print' !== $action ) : ?>
			<div class="ahbn-sidebar-container">
				<div class="ahbn-brand-header">
					<h2>
						<span class="ahbn-brand-title-left">
							<span class="dashicons dashicons-building" aria-hidden="true"></span>
							<span><?php esc_html_e( 'Hotel Booking', 'awesome-hotel-booking' ); ?></span>
						</span>
						<span class="version-tag"><?php echo esc_html( 'v' . ( defined( 'AHBN_VERSION' ) ? AHBN_VERSION : '1.0.0' ) ); ?></span>
					</h2>
				</div>

				<div class="ahbn-nav-scroll-wrapper">
					<?php foreach ( $menu_groups as $group ) : ?>
						<div class="ahbn-menu-section-group">
							<span class="ahbn-menu-group-heading"><?php echo esc_html( $group['title'] ); ?></span>
							<ul class="ahbn-left-tabs">
								<?php
								foreach ( $group['items'] as $slug => $item ) :
									$is_active = ( $active_tab === $slug ) ? 'active' : '';
									$url       = add_query_arg(
										array(
											'page' => 'awesome_hotel_booking',
											'tab'  => $slug,
										),
										admin_url( 'admin.php' )
									);
									?>
									<li>
										<a class="<?php echo esc_attr( $is_active ); ?>" href="<?php echo esc_url( $url ); ?>">
											<span class="dashicons <?php echo esc_attr( $item['icon'] ); ?>" aria-hidden="true"></span>
											<span class="ahbn-nav-label"><?php echo esc_html( $item['label'] ); ?></span>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="ahbn-right-box">
			<?php
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['updated'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$ahbn_bid = isset( $_GET['booking_id'] ) ? absint( wp_unslash( $_GET['booking_id'] ) ) : 0;
				/* translators: %d: Booking ID */
				echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( esc_html__( 'Reservation #%d successfully updated.', 'awesome-hotel-booking' ), absint( $ahbn_bid ) ) . '</p></div>';
			}

			switch ( $active_tab ) {
				case 'dashboard':
					if ( function_exists( 'ahbn_dashboard_tab' ) ) {
						ahbn_dashboard_tab();
					}
					break;

				case 'calendar':
					if ( function_exists( 'ahbn_calendar_tab' ) ) {
						ahbn_calendar_tab();
					} else {
						echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Calendar module is ready to be initialized.', 'awesome-hotel-booking' ) . '</p></div>';
					}
					break;

				case 'bookings':
					if ( function_exists( 'ahbn_bookings_tab' ) ) {
						ahbn_bookings_tab();
					}
					break;

				case 'rooms':
					if ( function_exists( 'ahbn_rooms_tab' ) ) {
						ahbn_rooms_tab();
					}
					break;

				case 'housekeeping':
					if ( function_exists( 'ahbn_housekeeping_tab' ) ) {
						ahbn_housekeeping_tab();
					} else {
						echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Housekeeping module is ready to be initialized.', 'awesome-hotel-booking' ) . '</p></div>';
					}
					break;

				case 'pricing':
					if ( function_exists( 'ahbn_pricing_tab' ) ) {
						ahbn_pricing_tab();
					} else {
						echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Pricing module is ready to be initialized.', 'awesome-hotel-booking' ) . '</p></div>';
					}
					break;

				case 'coupons':
					if ( function_exists( 'ahbn_coupons_tab' ) ) {
						ahbn_coupons_tab();
					} else {
						echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Coupons module is ready to be initialized.', 'awesome-hotel-booking' ) . '</p></div>';
					}
					break;

				case 'extra_services':
					$addons_file = defined( 'AHBN_PATH' ) ? AHBN_PATH . 'admin/extra_service.php' : plugin_dir_path( __FILE__ ) . 'extra_service.php';
					if ( file_exists( $addons_file ) ) {
						require_once $addons_file;
					}

					if ( function_exists( 'ahbn_extra_service_tab' ) ) {
						ahbn_extra_service_tab();
					} elseif ( function_exists( 'ahbn_extra_services_tab' ) ) {
						ahbn_extra_services_tab();
					} else {
						echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Services module is ready to be initialized.', 'awesome-hotel-booking' ) . '</p></div>';
					}
					break;

				case 'staff':
					if ( function_exists( 'ahbn_staff_tab' ) ) {
						ahbn_staff_tab();
					} else {
						echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Staff module is ready to be initialized.', 'awesome-hotel-booking' ) . '</p></div>';
					}
					break;

				case 'expense':
					if ( function_exists( 'ahbn_expense_tab' ) ) {
						ahbn_expense_tab();
					}
					break;

				case 'reports':
					if ( function_exists( 'ahbn_reports_tab' ) ) {
						ahbn_reports_tab();
					}
					break;

				case 'customers':
					if ( function_exists( 'ahbn_customers_tab' ) ) {
						ahbn_customers_tab();
					}
					break;

				case 'seo':
					if ( function_exists( 'ahbn_seo_tab' ) ) {
						ahbn_seo_tab();
					} else {
						echo '<div class="notice notice-info inline"><p>' . esc_html__( 'SEO module is ready to be initialized.', 'awesome-hotel-booking' ) . '</p></div>';
					}
					break;

				case 'styling':
					if ( function_exists( 'ahbn_styling_tab' ) ) {
						ahbn_styling_tab();
					} else {
						echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Design module is ready to be initialized.', 'awesome-hotel-booking' ) . '</p></div>';
					}
					break;

				case 'settings':
					if ( function_exists( 'ahbn_settings_tab' ) ) {
						ahbn_settings_tab();
					}
					break;

				case 'shortcode':
					if ( function_exists( 'ahbn_shortcode_tab' ) ) {
						ahbn_shortcode_tab();
					}
					break;

				case 'elementor':
					$elementor_file = defined( 'AHBN_PATH' ) ? AHBN_PATH . 'admin/builders/elementor-settings.php' : plugin_dir_path( __FILE__ ) . 'builders/elementor-settings.php';
					if ( file_exists( $elementor_file ) ) {
						require_once $elementor_file;
					}

					if ( function_exists( 'ahbn_elementor_settings_tab' ) ) {
						ahbn_elementor_settings_tab();
					}
					break;

				case 'blocks':
					$blocks_file = defined( 'AHBN_PATH' ) ? AHBN_PATH . 'admin/builders/gutenberg-settings.php' : plugin_dir_path( __FILE__ ) . 'builders/gutenberg-settings.php';
					if ( file_exists( $blocks_file ) ) {
						require_once $blocks_file;
					}

					if ( function_exists( 'ahbn_gutenberg_settings_tab' ) ) {
						ahbn_gutenberg_settings_tab();
					}
					break;

				default:
					if ( function_exists( 'ahbn_dashboard_tab' ) ) {
						ahbn_dashboard_tab();
					}
					break;
			}
			?>
		</div>
	</div>
	<?php
}