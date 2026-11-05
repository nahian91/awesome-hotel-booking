<?php
/**
 * Elementor Addon Integration & Widget Builder View.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Elementor builder settings tab view.
 *
 * @return void
 */
function ahbn_elementor_settings_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	$has_elementor = did_action( 'elementor/loaded' );
	?>
	<div class="ahbn-elementor-wrap">
		<div class="ahbn-coming-soon-card">
			<div class="ahbn-badge-pill">
				<span class="dashicons dashicons-welcome-widgets-menus" aria-hidden="true"></span>
				<span><?php esc_html_e( 'Elementor Builder Addon Pack', 'awesome-hotel-booking' ); ?></span>
			</div>

			<h2 class="ahbn-cs-title"><?php esc_html_e( 'Luxury Widgets Coming Soon', 'awesome-hotel-booking' ); ?></h2>
			
			<p class="ahbn-cs-desc">
				<?php esc_html_e( 'We are engineering native, high-conversion Elementor widgets for the Awesome Hotel Booking engine. Build bespoke hero search sections, single room booking cards, and live availability filters visually with drag-and-drop ease.', 'awesome-hotel-booking' ); ?>
			</p>

			<div class="ahbn-upcoming-grid">
				<div class="ahbn-widget-feature">
					<span class="dashicons dashicons-search" aria-hidden="true"></span>
					<div>
						<strong><?php esc_html_e( 'Hero Availability Search Bar', 'awesome-hotel-booking' ); ?></strong>
						<p><?php esc_html_e( 'Horizontal and vertical check-in/out datepickers with guest counters.', 'awesome-hotel-booking' ); ?></p>
					</div>
				</div>

				<div class="ahbn-widget-feature">
					<span class="dashicons dashicons-building" aria-hidden="true"></span>
					<div>
						<strong><?php esc_html_e( 'Suites Grid & Carousel', 'awesome-hotel-booking' ); ?></strong>
						<p><?php esc_html_e( 'Interactive room listings directly queried from your custom SQL table.', 'awesome-hotel-booking' ); ?></p>
					</div>
				</div>

				<div class="ahbn-widget-feature">
					<span class="dashicons dashicons-tag" aria-hidden="true"></span>
					<div>
						<strong><?php esc_html_e( 'Sticky Suite Booking Box', 'awesome-hotel-booking' ); ?></strong>
						<p><?php esc_html_e( 'Real-time nightly calculation, add-on upsells, and instant booking submission.', 'awesome-hotel-booking' ); ?></p>
					</div>
				</div>

				<div class="ahbn-widget-feature">
					<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
					<div>
						<strong><?php esc_html_e( 'Header Auth & Profile Bar', 'awesome-hotel-booking' ); ?></strong>
						<p><?php esc_html_e( 'Seamless member greetings, avatar badges, and login modal triggers.', 'awesome-hotel-booking' ); ?></p>
					</div>
				</div>
			</div>

			<div class="ahbn-status-callout <?php echo $has_elementor ? 'is-installed' : 'is-missing'; ?>">
				<span class="dashicons <?php echo $has_elementor ? 'dashicons-yes-alt' : 'dashicons-info-outline'; ?>" aria-hidden="true"></span>
				<span>
					<?php
					if ( $has_elementor ) {
						esc_html_e( 'Elementor core is detected and ready. Widgets will activate automatically upon update.', 'awesome-hotel-booking' );
					} else {
						esc_html_e( 'Elementor plugin is currently inactive. Install or activate Elementor to use these widgets upon release.', 'awesome-hotel-booking' );
					}
					?>
				</span>
			</div>
		</div>
	</div>

	<style>
		.ahbn-elementor-wrap {
			margin-top: 0 !important;
			max-width: 900px;
			margin-left: auto;
			margin-right: auto;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
			box-sizing: border-box;
		}

		.ahbn-elementor-wrap * {
			box-sizing: border-box;
		}

		.ahbn-coming-soon-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 16px;
			padding: 48px 40px;
			text-align: center;
			box-shadow: 0 4px 20px -2px rgba(10, 20, 38, 0.05);
		}

		.ahbn-badge-pill {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			font-family: 'JetBrains Mono', monospace;
			font-size: 11px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			background: #0a192f;
			color: #c5a880;
			padding: 6px 14px;
			border-radius: 99px;
			margin-bottom: 18px;
		}

		.ahbn-badge-pill .dashicons {
			font-size: 14px;
			width: 14px;
			height: 14px;
			color: #c5a880;
		}

		.ahbn-cs-title {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 28px;
			font-weight: 700;
			color: #0a192f;
			margin: 0 0 12px;
		}

		.ahbn-cs-desc {
			font-size: 14px;
			line-height: 1.6;
			color: #64748b;
			max-width: 660px;
			margin: 0 auto 36px;
		}

		.ahbn-upcoming-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 16px;
			text-align: left;
			margin-bottom: 32px;
		}

		.ahbn-widget-feature {
			display: flex;
			gap: 14px;
			align-items: flex-start;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 16px;
			transition: all 0.2s ease;
		}

		.ahbn-widget-feature:hover {
			border-color: #c5a880;
			background: #ffffff;
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(10, 25, 47, 0.05);
		}

		.ahbn-widget-feature .dashicons {
			font-size: 20px;
			width: 20px;
			height: 20px;
			color: #c5a880;
			flex-shrink: 0;
			margin-top: 2px;
		}

		.ahbn-widget-feature strong {
			display: block;
			font-size: 13px;
			color: #0a192f;
			margin-bottom: 4px;
		}

		.ahbn-widget-feature p {
			margin: 0;
			font-size: 12px;
			color: #64748b;
			line-height: 1.4;
		}

		.ahbn-status-callout {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			font-size: 12px;
			font-weight: 600;
			padding: 10px 20px;
			border-radius: 8px;
			max-width: 600px;
		}

		.ahbn-status-callout.is-installed {
			background: #ecfdf5;
			color: #065f46;
			border: 1px solid #a7f3d0;
		}

		.ahbn-status-callout.is-installed .dashicons {
			color: #10b981;
		}

		.ahbn-status-callout.is-missing {
			background: #fffbeb;
			color: #92400e;
			border: 1px solid #fde68a;
		}

		.ahbn-status-callout.is-missing .dashicons {
			color: #f59e0b;
		}

		@media (max-width: 680px) {
			.ahbn-coming-soon-card {
				padding: 30px 20px;
			}
			.ahbn-upcoming-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>
	<?php
}