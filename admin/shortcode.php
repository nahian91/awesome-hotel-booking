<?php
/**
 * Shortcode Documentation & Library Tab View.
 *
 * Built with Champagne Gold & Deep Navy enterprise UI system.
 * Features: One-click clipboard copy, placement guidance, and organized shortcode groups.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Shortcode Library tab.
 *
 * @return void
 */
function ahbn_shortcode_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	$sections = array(
		'Header & Navigation' => array(
			array(
				'title' => __( 'Header Auth Button', 'awesome-hotel-booking' ),
				'code'  => '[ahbn_header_auth]',
				'desc'  => __( 'Displays guest greeting and avatar for logged-in users, or Login/Register buttons for visitors.', 'awesome-hotel-booking' ),
				'page'  => __( 'Site Header / Navigation Bar', 'awesome-hotel-booking' ),
			),
		),
		'Booking Flow'        => array(
			array(
				'title' => __( 'Search & Reservation Form', 'awesome-hotel-booking' ),
				'code'  => '[ahbn_search_form]',
				'desc'  => __( 'Booking search form for check-in/out dates and guest counts. Ideal for homepage hero sections.', 'awesome-hotel-booking' ),
				'page'  => __( 'Home Page / Hero Section', 'awesome-hotel-booking' ),
			),
			array(
				'title' => __( 'All Rooms Directory', 'awesome-hotel-booking' ),
				'code'  => '[ahbn_all_rooms]',
				'desc'  => __( 'Displays full room inventory in an interactive responsive grid with category filters.', 'awesome-hotel-booking' ),
				'page'  => __( 'Rooms / Inventory Page', 'awesome-hotel-booking' ),
			),
			array(
				'title' => __( 'Booking Confirmation', 'awesome-hotel-booking' ),
				'code'  => '[ahbn_thanks]',
				'desc'  => __( 'Displays reservation confirmation and order summary details after a successful checkout.', 'awesome-hotel-booking' ),
				'page'  => __( 'Thank You / Confirmation Page', 'awesome-hotel-booking' ),
			),
		),
		'User & Authentication' => array(
			array(
				'title' => __( 'Guest Login Form', 'awesome-hotel-booking' ),
				'code'  => '[ahbn_login]',
				'desc'  => __( 'AJAX-powered guest login form that automatically redirects to the customer account dashboard.', 'awesome-hotel-booking' ),
				'page'  => __( 'Login Page', 'awesome-hotel-booking' ),
			),
			array(
				'title' => __( 'Guest Registration', 'awesome-hotel-booking' ),
				'code'  => '[ahbn_register]',
				'desc'  => __( 'Registration form with telephone and contact fields that auto-authenticates the guest.', 'awesome-hotel-booking' ),
				'page'  => __( 'Registration Page', 'awesome-hotel-booking' ),
			),
			array(
				'title' => __( 'Guest Account Dashboard', 'awesome-hotel-booking' ),
				'code'  => '[ahbn_account]',
				'desc'  => __( 'Central hub where customers manage personal profile details and review past room stays.', 'awesome-hotel-booking' ),
				'page'  => __( 'My Account Page', 'awesome-hotel-booking' ),
			),
		),
	);
	?>

	<div class="ahbn-shortcode-wrap">
		<!-- Header Toolbar -->
		<header class="ahbn-shortcode-header">
			<div>
				<h2 class="ahbn-shortcode-title"><?php esc_html_e( 'Shortcode Library & Deployment', 'awesome-hotel-booking' ); ?></h2>
				<p class="ahbn-shortcode-subtitle">
					<?php esc_html_e( 'Deploy booking forms, room galleries, guest dashboards, and header elements anywhere on your site.', 'awesome-hotel-booking' ); ?>
				</p>
			</div>
			<div>
				<span class="ahbn-code-counter-badge">
					<span class="dashicons dashicons-editor-code" aria-hidden="true"></span>
					<?php
					$total_shortcodes = 0;
					foreach ( $sections as $s_items ) {
						$total_shortcodes += count( $s_items );
					}
					printf(
						/* translators: %d: number of shortcodes */
						esc_html__( '%d Production Ready Shortcodes', 'awesome-hotel-booking' ),
						absint( $total_shortcodes )
					);
					?>
				</span>
			</div>
		</header>

		<!-- Shortcodes Container by Sections -->
		<?php foreach ( $sections as $section_name => $items ) : ?>
			<div class="ahbn-shortcode-section">
				<div class="ahbn-section-heading-wrap">
					<span class="dashicons dashicons-category" aria-hidden="true"></span>
					<h3 class="ahbn-section-title"><?php echo esc_html( $section_name ); ?></h3>
				</div>

				<div class="ahbn-shortcode-grid">
					<?php
					foreach ( $items as $sc ) :
						$sc_id = sanitize_title( $sc['title'] );
						?>
						<div class="ahbn-shortcode-card">
							<div class="ahbn-card-top">
								<h4 class="ahbn-card-title"><?php echo esc_html( $sc['title'] ); ?></h4>
								<p class="ahbn-card-desc"><?php echo esc_html( $sc['desc'] ); ?></p>
							</div>

							<div class="ahbn-card-bottom">
								<div class="ahbn-code-display-box">
									<code class="ahbn-code-text" id="code-<?php echo esc_attr( $sc_id ); ?>">
										<?php echo esc_html( $sc['code'] ); ?>
									</code>
									<button 
										type="button" 
										class="ahbn-copy-action-btn js-ahbn-copy" 
										data-target="code-<?php echo esc_attr( $sc_id ); ?>" 
										title="<?php esc_attr_e( 'Copy to Clipboard', 'awesome-hotel-booking' ); ?>"
										aria-label="<?php esc_attr_e( 'Copy shortcode to clipboard', 'awesome-hotel-booking' ); ?>"
									>
										<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
										<span class="ahbn-copy-btn-label"><?php esc_html_e( 'Copy', 'awesome-hotel-booking' ); ?></span>
									</button>
								</div>

								<div class="ahbn-placement-box">
									<span class="dashicons dashicons-location-alt" aria-hidden="true"></span>
									<span class="placement-label"><?php esc_html_e( 'Suggested Placement:', 'awesome-hotel-booking' ); ?></span>
									<strong class="placement-value"><?php echo esc_html( $sc['page'] ); ?></strong>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<!-- Toast Alert for Copy Confirmation -->
	<div id="ahbn-toast" class="ahbn-copy-toast" role="alert" aria-live="polite">
		<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
		<span><?php esc_html_e( 'Shortcode copied to clipboard!', 'awesome-hotel-booking' ); ?></span>
	</div>

	<style>
		.ahbn-shortcode-wrap {
			display: flex;
			flex-direction: column;
			gap: 24px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-shortcode-header {
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

		.ahbn-shortcode-title {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 24px;
			font-weight: 700;
			color: #0a192f;
			margin: 0 0 4px;
		}

		.ahbn-shortcode-subtitle {
			color: #64748b;
			margin: 0;
			font-size: 13px;
		}

		.ahbn-code-counter-badge {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			font-family: 'JetBrains Mono', monospace;
			font-size: 11px;
			font-weight: 700;
			background: #f8fafc;
			border: 1px solid #cbd5e1;
			padding: 6px 12px;
			border-radius: 99px;
			color: #0a192f;
		}

		.ahbn-code-counter-badge .dashicons {
			color: #c5a880;
			font-size: 16px;
			width: 16px;
			height: 16px;
		}

		.ahbn-shortcode-section {
			display: flex;
			flex-direction: column;
			gap: 12px;
		}

		.ahbn-section-heading-wrap {
			display: flex;
			align-items: center;
			gap: 8px;
			margin-bottom: 2px;
		}

		.ahbn-section-heading-wrap .dashicons {
			color: #c5a880;
			font-size: 17px;
			width: 17px;
			height: 17px;
		}

		.ahbn-section-title {
			font-size: 13px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.06em;
			color: #0a192f;
			margin: 0;
		}

		.ahbn-shortcode-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
			gap: 16px;
		}

		.ahbn-shortcode-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			padding: 22px;
			display: flex;
			flex-direction: column;
			justify-content: space-between;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
			transition: all 0.2s ease;
		}

		.ahbn-shortcode-card:hover {
			border-color: #cbd5e1;
			box-shadow: 0 6px 18px rgba(10, 25, 47, 0.06);
			transform: translateY(-2px);
		}

		.ahbn-card-title {
			margin: 0 0 6px 0;
			font-size: 16px;
			font-weight: 700;
			color: #0a192f;
		}

		.ahbn-card-desc {
			margin: 0 0 18px 0;
			font-size: 13px;
			line-height: 1.5;
			color: #64748b;
		}

		.ahbn-code-display-box {
			display: flex;
			align-items: center;
			justify-content: space-between;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 8px;
			padding: 8px 12px;
			margin-bottom: 12px;
			transition: border-color 0.2s ease;
		}

		.ahbn-code-display-box:hover {
			border-color: #c5a880;
		}

		.ahbn-code-text {
			font-family: 'JetBrains Mono', monospace;
			font-size: 13px;
			font-weight: 700;
			color: #0a192f;
			background: transparent !important;
			border: none !important;
			padding: 0 !important;
		}

		.ahbn-copy-action-btn {
			display: inline-flex;
			align-items: center;
			gap: 4px;
			background: #ffffff;
			border: 1px solid #cbd5e1;
			color: #334155;
			border-radius: 6px;
			padding: 4px 10px;
			font-size: 11px;
			font-weight: 700;
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.ahbn-copy-action-btn .dashicons {
			font-size: 13px;
			width: 13px;
			height: 13px;
		}

		.ahbn-copy-action-btn:hover {
			background: #0a192f;
			border-color: #0a192f;
			color: #ffffff;
		}

		.ahbn-placement-box {
			display: flex;
			align-items: center;
			gap: 6px;
			font-size: 11px;
			color: #64748b;
			padding-top: 10px;
			border-top: 1px solid #f1f5f9;
		}

		.ahbn-placement-box .dashicons {
			color: #c5a880;
			font-size: 14px;
			width: 14px;
			height: 14px;
		}

		.placement-label {
			text-transform: uppercase;
			font-weight: 700;
			font-size: 10px;
			letter-spacing: 0.04em;
		}

		.placement-value {
			color: #0a192f;
		}

		/* Toast Notification */
		.ahbn-copy-toast {
			display: none;
			position: fixed;
			bottom: 28px;
			right: 28px;
			background: #0a192f;
			color: #ffffff;
			border: 1px solid #c5a880;
			padding: 12px 20px;
			border-radius: 10px;
			font-size: 13px;
			font-weight: 700;
			z-index: 999999;
			box-shadow: 0 10px 25px rgba(10, 25, 47, 0.25);
			align-items: center;
			gap: 8px;
		}

		.ahbn-copy-toast .dashicons {
			color: #10b981;
			font-size: 18px;
			width: 18px;
			height: 18px;
		}

		@media (max-width: 600px) {
			.ahbn-shortcode-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>

	<script>
	jQuery(document).ready(function($) {
		$('.js-ahbn-copy').on('click', function(e) {
			e.preventDefault();
			const targetId = $(this).data('target');
			const $target = $('#' + targetId);
			if (!$target.length) {
				return;
			}

			const text = $target.text().trim();

			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(text).then(showToast);
			} else {
				// Fallback for non-HTTPS local development environments.
				const $temp = $('<textarea>');
				$('body').append($temp);
				$temp.val(text).select();
				document.execCommand('copy');
				$temp.remove();
				showToast();
			}

			function showToast() {
				const $toast = $('#ahbn-toast');
				$toast.css('display', 'inline-flex').hide().fadeIn(150).delay(2200).fadeOut(250);
			}
		});
	});
	</script>
	<?php
}