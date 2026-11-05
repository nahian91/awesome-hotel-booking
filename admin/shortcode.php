<?php
/**
 * Shortcode Documentation & Library Tab View.
 *
 * Built with Champagne Gold & Deep Navy enterprise UI system.
 * Features: Multi-tiered cross-browser clipboard copy, visual toast confirmation,
 * button state feedback, zero top space, and no unwanted scrollbars.
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
				'title' => __( 'Header Auth & Profile Button', 'awesome-hotel-booking' ),
				'code'  => '[ahbn_header_auth]',
				'desc'  => __( 'Displays guest greeting, account balance, and avatar for logged-in users, or Login/Register triggers for visitors.', 'awesome-hotel-booking' ),
				'page'  => __( 'Site Header / Navigation Bar', 'awesome-hotel-booking' ),
			),
		),
		'Booking & Reservation Flow' => array(
			array(
				'title' => __( 'Live Search & Date Availability Bar', 'awesome-hotel-booking' ),
				'code'  => '[ahbn_search_bar]',
				'desc'  => __( 'Interactive check-in/out date picker, category dropdown, and guest counters with live SQL availability lookup.', 'awesome-hotel-booking' ),
				'page'  => __( 'Home Page / Hero Banner', 'awesome-hotel-booking' ),
			),
			array(
				'title' => __( 'All Rooms Inventory Grid', 'awesome-hotel-booking' ),
				'code'  => '[ahbn_all_rooms]',
				'desc'  => __( 'Displays published suites from wp_ahbn_rooms with JSON amenities, dynamic pricing, and occupancy badges.', 'awesome-hotel-booking' ),
				'page'  => __( 'Rooms / Suites Archive Page', 'awesome-hotel-booking' ),
			),
			array(
				'title' => __( 'Single Suite Showcase & Booking', 'awesome-hotel-booking' ),
				'code'  => '[ahbn_room_details id="1"]',
				'desc'  => __( 'Embeds a dedicated luxury suite profile with photo carousel and specifications. Replace "1" with target room ID.', 'awesome-hotel-booking' ),
				'page'  => __( 'Single Suite Page / Custom Landing Pages', 'awesome-hotel-booking' ),
			),
			array(
				'title' => __( 'Reservation Checkout & Payment Funnel', 'awesome-hotel-booking' ),
				'code'  => '[ahbn_checkout]',
				'desc'  => __( 'Step-by-step guest checkout: attaches paid add-on services, validates coupon promo codes, and confirms bookings.', 'awesome-hotel-booking' ),
				'page'  => __( 'Checkout Page', 'awesome-hotel-booking' ),
			),
			array(
				'title' => __( 'Confirmation & Invoice Folio', 'awesome-hotel-booking' ),
				'code'  => '[ahbn_thanks]',
				'desc'  => __( 'Displays booking confirmation code, check-in instructions, and printable breakdown receipt upon successful reservation.', 'awesome-hotel-booking' ),
				'page'  => __( 'Thank You / Confirmation Page', 'awesome-hotel-booking' ),
			),
		),
		'Guest CRM & Authentication' => array(
			array(
				'title' => __( 'AJAX Guest Login Portal', 'awesome-hotel-booking' ),
				'code'  => '[ahbn_login]',
				'desc'  => __( 'Instant AJAX customer login card that authenticates credentials and redirects guests into their portal.', 'awesome-hotel-booking' ),
				'page'  => __( 'Login Page', 'awesome-hotel-booking' ),
			),
			array(
				'title' => __( 'Guest Registration Portal', 'awesome-hotel-booking' ),
				'code'  => '[ahbn_register]',
				'desc'  => __( 'Member sign-up form with password verification, instant auto-login, and billing profile assignment.', 'awesome-hotel-booking' ),
				'page'  => __( 'Registration Page', 'awesome-hotel-booking' ),
			),
			array(
				'title' => __( 'Customer Dashboard & History', 'awesome-hotel-booking' ),
				'code'  => '[ahbn_account]',
				'desc'  => __( 'Self-service guest portal with stay history from wp_ahbn_bookings, profile details editor, and password reset tab.', 'awesome-hotel-booking' ),
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
					<?php esc_html_e( 'Deploy booking engines, inventory grids, customer dashboards, and header elements into any page or theme template.', 'awesome-hotel-booking' ); ?>
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
						esc_html__( '%d Production Shortcodes', 'awesome-hotel-booking' ),
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
									<code class="ahbn-code-text" id="code-<?php echo esc_attr( $sc_id ); ?>"><?php echo esc_html( $sc['code'] ); ?></code>
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

	<!-- Floating Toast Alert for Copy Confirmation -->
	<div id="ahbn-toast" class="ahbn-copy-toast" role="alert" aria-live="polite">
		<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
		<span><?php esc_html_e( 'Shortcode copied to clipboard!', 'awesome-hotel-booking' ); ?></span>
	</div>

	<style>
		/* Flush-top and prevent horizontal scrollbars */
		.ahbn-shortcode-wrap {
			margin-top: 0 !important;
			max-width: 1140px;
			margin-left: auto;
			margin-right: auto;
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
			box-sizing: border-box;
			overflow-x: hidden;
		}

		.ahbn-shortcode-wrap * {
			box-sizing: border-box;
		}

		.ahbn-shortcode-header {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			padding: 20px 24px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-wrap: wrap;
			gap: 14px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-shortcode-title {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 22px;
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
			font-size: 15px;
			width: 15px;
			height: 15px;
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
		}

		.ahbn-section-heading-wrap .dashicons {
			color: #c5a880;
			font-size: 16px;
			width: 16px;
			height: 16px;
		}

		.ahbn-section-title {
			font-size: 12px;
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
			border-radius: 12px;
			padding: 20px;
			display: flex;
			flex-direction: column;
			justify-content: space-between;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
			transition: all 0.2s ease;
		}

		.ahbn-shortcode-card:hover {
			border-color: #cbd5e1;
			box-shadow: 0 4px 14px rgba(10, 25, 47, 0.05);
			transform: translateY(-2px);
		}

		.ahbn-card-title {
			margin: 0 0 6px 0;
			font-size: 15px;
			font-weight: 700;
			color: #0a192f;
		}

		.ahbn-card-desc {
			margin: 0 0 16px 0;
			font-size: 12px;
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
			font-family: 'JetBrains Mono', monospace !important;
			font-size: 12px !important;
			font-weight: 700 !important;
			color: #0a192f !important;
			background: transparent !important;
			border: none !important;
			padding: 0 !important;
		}

		.ahbn-copy-action-btn {
			display: inline-flex;
			align-items: center;
			gap: 5px;
			background: #ffffff;
			border: 1px solid #cbd5e1;
			color: #334155;
			border-radius: 6px;
			padding: 4px 10px;
			font-size: 11px;
			font-weight: 700;
			cursor: pointer;
			transition: all 0.15s ease;
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
			bottom: 24px;
			right: 24px;
			background: #0a192f;
			color: #ffffff;
			border: 1px solid #c5a880;
			padding: 10px 18px;
			border-radius: 8px;
			font-size: 12px;
			font-weight: 700;
			z-index: 999999;
			box-shadow: 0 8px 20px rgba(10, 25, 47, 0.25);
			align-items: center;
			gap: 8px;
		}

		.ahbn-copy-toast .dashicons {
			color: #10b981;
			font-size: 16px;
			width: 16px;
			height: 16px;
		}

		@media (max-width: 600px) {
			.ahbn-shortcode-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>

	<script>
	jQuery(document).ready(function($) {
		$('.js-ahbn-copy').off('click').on('click', function(e) {
			e.preventDefault();

			var targetId = $(this).data('target');
			var $target  = $('#' + targetId);
			var $btn     = $(this);

			if (!$target.length) {
				return;
			}

			var textToCopy = $target.text().trim();

			// Async Clipboard API (Secure Contexts)
			if (navigator.clipboard && window.isSecureContext) {
				navigator.clipboard.writeText(textToCopy).then(function() {
					triggerCopyFeedback($btn);
				}).catch(function() {
					fallbackCopy(textToCopy, $btn);
				});
			} else {
				// Fallback for non-SSL/local domains
				fallbackCopy(textToCopy, $btn);
			}
		});

		function fallbackCopy(text, $btn) {
			var textArea = document.createElement('textarea');
			textArea.value = text;
			textArea.style.position = 'fixed';
			textArea.style.top = '-9999px';
			textArea.style.left = '-9999px';
			textArea.style.opacity = '0';
			textArea.setAttribute('readonly', '');

			document.body.appendChild(textArea);
			textArea.focus();
			textArea.select();

			try {
				var successful = document.execCommand('copy');
				if (successful) {
					triggerCopyFeedback($btn);
				}
			} catch (err) {
				// Silently fail
			}

			document.body.removeChild(textArea);
		}

		function triggerCopyFeedback($btn) {
			var $toast = $('#ahbn-toast');
			if ($toast.length) {
				$toast.stop(true, true).css('display', 'inline-flex').hide().fadeIn(150).delay(1800).fadeOut(250);
			}

			var $label = $btn.find('.ahbn-copy-btn-label');
			var originalText = $label.text();

			$btn.css({
				'background': '#059669',
				'border-color': '#059669',
				'color': '#ffffff'
			});
			$label.text('<?php echo esc_js( __( 'Copied!', 'awesome-hotel-booking' ) ); ?>');

			setTimeout(function() {
				$label.text(originalText);
				$btn.removeAttr('style');
			}, 1600);
		}
	});
	</script>
	<?php
}