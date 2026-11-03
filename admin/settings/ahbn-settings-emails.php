<?php
/**
 * Emails Settings Sub-Tab View.
 *
 * Built with the Champagne Gold & Deep Navy enterprise UI system.
 * Handles automated notification dispatching, brand identity, and live HTML email previewing.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render and process the Email Branding & Notification Settings view.
 *
 * @return void
 */
function ahbn_settings_emails_view() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	// 1. Process Form Submission.
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_emails'] ) ) {
		$ahbn_nonce = isset( $_POST['ahbn_email_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_email_nonce'] ) ) : '';

		if ( empty( $ahbn_nonce ) || ! wp_verify_nonce( $ahbn_nonce, 'ahbn_save_email_settings' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please refresh and try again.', 'awesome-hotel-booking' ) . '</p></div>';
		} else {
			$raw_admin_email  = isset( $_POST['ahbn_admin_email'] ) ? sanitize_email( wp_unslash( $_POST['ahbn_admin_email'] ) ) : '';
			$raw_logo_url     = isset( $_POST['ahbn_email_logo'] ) ? esc_url_raw( wp_unslash( $_POST['ahbn_email_logo'] ) ) : '';
			$raw_accent_color = isset( $_POST['ahbn_email_accent_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['ahbn_email_accent_color'] ) ) : '#C5A880';
			$raw_primary_color= isset( $_POST['ahbn_email_primary_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['ahbn_email_primary_color'] ) ) : '#0A192F';
			$raw_footer_text  = isset( $_POST['ahbn_email_footer_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ahbn_email_footer_text'] ) ) : '';

			update_option( 'ahbn_admin_email', ! empty( $raw_admin_email ) ? $raw_admin_email : get_option( 'admin_email' ) );
			update_option( 'ahbn_email_logo', $raw_logo_url );
			update_option( 'ahbn_email_accent_color', ! empty( $raw_accent_color ) ? $raw_accent_color : '#C5A880' );
			update_option( 'ahbn_email_primary_color', ! empty( $raw_primary_color ) ? $raw_primary_color : '#0A192F' );
			update_option( 'ahbn_email_footer_text', $raw_footer_text );

			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Email notifications and branding updated successfully.', 'awesome-hotel-booking' ) . '</p></div>';
		}
	}

	// 2. Fetch Stored Values.
	$hotel_name    = get_option( 'ahbn_hotel_name', get_bloginfo( 'name' ) );
	$admin_email   = get_option( 'ahbn_admin_email', get_option( 'admin_email' ) );
	$logo_url      = get_option( 'ahbn_email_logo', '' );
	$accent_color  = get_option( 'ahbn_email_accent_color', '#C5A880' );
	$primary_color = get_option( 'ahbn_email_primary_color', '#0A192F' );
	$footer_text   = get_option( 'ahbn_email_footer_text', __( 'Thank you for choosing our luxury hospitality services. We look forward to hosting your stay.', 'awesome-hotel-booking' ) );
	?>

	<div class="ahbn-emails-settings-wrap">
		<?php wp_nonce_field( 'ahbn_save_email_settings', 'ahbn_email_nonce' ); ?>

		<div class="ahbn-emails-grid">
			
			<!-- Left Column: Settings Configuration -->
			<div class="ahbn-main-col">
				<div class="ahbn-panel">
					<div class="ahbn-panel-head">
						<span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
						<h2><?php esc_html_e( 'Automated Email Dispatcher & Branding', 'awesome-hotel-booking' ); ?></h2>
					</div>
					
					<div class="ahbn-panel-body">
						<div class="ahbn-field-group">
							<label for="ahbn_admin_email" class="ahbn-label"><?php esc_html_e( 'Front Desk / Concierge Notification Recipient', 'awesome-hotel-booking' ); ?></label>
							<input type="email" id="ahbn_admin_email" name="ahbn_admin_email" value="<?php echo esc_attr( $admin_email ); ?>" class="ahbn-input" required placeholder="concierge@hotel.com">
							<small class="ahbn-hint"><?php esc_html_e( 'New booking alerts and guest status changes will be dispatched to this address.', 'awesome-hotel-booking' ); ?></small>
						</div>

						<div class="ahbn-field-group">
							<label for="ahbn_logo_url" class="ahbn-label"><?php esc_html_e( 'Email Header Brand Logo URL', 'awesome-hotel-booking' ); ?></label>
							<div class="ahbn-upload-input-wrap">
								<input type="text" name="ahbn_email_logo" id="ahbn_logo_url" value="<?php echo esc_url( $logo_url ); ?>" class="ahbn-input" placeholder="https://">
								<button type="button" class="ahbn-btn-secondary" id="ahbn_upload_logo_btn">
									<span class="dashicons dashicons-upload" aria-hidden="true"></span>
									<?php esc_html_e( 'Media Library', 'awesome-hotel-booking' ); ?>
								</button>
							</div>
							<small class="ahbn-hint"><?php esc_html_e( 'Translucent PNG or SVG with contrasting light elements renders best on dark email headers.', 'awesome-hotel-booking' ); ?></small>
						</div>

						<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
							<div class="ahbn-field-group">
								<label for="ahbn_primary_picker" class="ahbn-label"><?php esc_html_e( 'Header & Body Accent', 'awesome-hotel-booking' ); ?></label>
								<div class="ahbn-color-input-wrap">
									<input type="color" name="ahbn_email_primary_color" id="ahbn_primary_picker" value="<?php echo esc_attr( $primary_color ); ?>" class="ahbn-color-picker">
									<input type="text" id="ahbn_primary_hex" value="<?php echo esc_attr( $primary_color ); ?>" class="ahbn-color-text" readonly>
								</div>
							</div>

							<div class="ahbn-field-group">
								<label for="ahbn_accent_picker" class="ahbn-label"><?php esc_html_e( 'Button & Gold Highlight Color', 'awesome-hotel-booking' ); ?></label>
								<div class="ahbn-color-input-wrap">
									<input type="color" name="ahbn_email_accent_color" id="ahbn_accent_picker" value="<?php echo esc_attr( $accent_color ); ?>" class="ahbn-color-picker">
									<input type="text" id="ahbn_accent_hex" value="<?php echo esc_attr( $accent_color ); ?>" class="ahbn-color-text" readonly>
								</div>
							</div>
						</div>

						<div class="ahbn-field-group">
							<label for="ahbn_footer_input" class="ahbn-label"><?php esc_html_e( 'Email Footer Signature & Legal Disclaimer', 'awesome-hotel-booking' ); ?></label>
							<textarea name="ahbn_email_footer_text" id="ahbn_footer_input" rows="3" class="ahbn-textarea" placeholder="<?php esc_attr_e( 'e.g. Grand Palace Hotel Ltd. All rights reserved.', 'awesome-hotel-booking' ); ?>"><?php echo esc_textarea( $footer_text ); ?></textarea>
						</div>
					</div>
				</div>

				<div class="ahbn-panel-actions">
					<button type="submit" name="ahbn_save_emails" class="ahbn-btn-save">
						<span class="dashicons dashicons-saved" aria-hidden="true"></span>
						<?php esc_html_e( 'Save Email Branding', 'awesome-hotel-booking' ); ?>
					</button>
				</div>
			</div>

			<!-- Right Column: Live Responsive Guest Email Preview -->
			<div class="ahbn-sidebar-col">
				<div class="ahbn-panel" style="position: sticky; top: 20px;">
					<div class="ahbn-panel-head">
						<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
						<h2><?php esc_html_e( 'Live Guest Confirmation Email Preview', 'awesome-hotel-booking' ); ?></h2>
					</div>
					
					<div class="ahbn-panel-body" style="background:#f1f5f9; padding: 20px;">
						<!-- Mock Email Client Wrapper -->
						<div class="ahbn-email-preview-card">
							
							<!-- Email Header -->
							<div class="ahbn-email-header-preview" id="email_preview_header" style="background-color: <?php echo esc_attr( $primary_color ); ?>;">
								<div id="email_preview_logo_wrap">
									<?php if ( ! empty( $logo_url ) ) : ?>
										<img src="<?php echo esc_url( $logo_url ); ?>" id="email_preview_logo" alt="<?php esc_attr_e( 'Hotel Logo', 'awesome-hotel-booking' ); ?>">
									<?php else : ?>
										<span id="email_preview_logo_text" style="color: #ffffff; font-family:'Playfair Display',serif; font-size:16px; font-weight:800; letter-spacing:0.04em;">
											<?php echo esc_html( strtoupper( $hotel_name ) ); ?>
										</span>
									<?php endif; ?>
								</div>
							</div>

							<!-- Email Body -->
							<div class="ahbn-email-body-preview">
								<h3 class="ahbn-email-heading"><?php esc_html_e( 'Reservation Confirmation', 'awesome-hotel-booking' ); ?></h3>
								
								<p class="ahbn-email-text">
									<?php esc_html_e( 'Dear Eleanor Vance,', 'awesome-hotel-booking' ); ?>
								</p>

								<p class="ahbn-email-text" style="margin-bottom: 16px;">
									<?php
									printf(
										/* translators: %s: Hotel brand name */
										esc_html__( 'We are delighted to confirm your upcoming stay at %s. Please review your stay folio details below.', 'awesome-hotel-booking' ),
										'<strong>' . esc_html( $hotel_name ) . '</strong>'
									);
									?>
								</p>

								<!-- Mock Itinerary Card -->
								<div class="ahbn-email-folio-box">
									<div class="folio-row">
										<span class="folio-lbl"><?php esc_html_e( 'Folio Code', 'awesome-hotel-booking' ); ?></span>
										<strong class="folio-val">AHBN-7492B</strong>
									</div>
									<div class="folio-row">
										<span class="folio-lbl"><?php esc_html_e( 'Assigned Suite', 'awesome-hotel-booking' ); ?></span>
										<strong class="folio-val" id="email_preview_suite_name"><?php esc_html_e( 'Grand Deluxe Ocean Suite', 'awesome-hotel-booking' ); ?></strong>
									</div>
									<div class="folio-row">
										<span class="folio-lbl"><?php esc_html_e( 'Duration', 'awesome-hotel-booking' ); ?></span>
										<span class="folio-val"><?php esc_html_e( '3 Nights (Oct 14 → Oct 17)', 'awesome-hotel-booking' ); ?></span>
									</div>
									<div class="folio-row" style="border-bottom:none; padding-bottom:0;">
										<span class="folio-lbl"><?php esc_html_e( 'Total Realized', 'awesome-hotel-booking' ); ?></span>
										<strong class="folio-val text-gold" id="email_preview_total" style="color: <?php echo esc_attr( $accent_color ); ?>;">$1,350.00</strong>
									</div>
								</div>

								<div style="text-align: center; margin: 20px 0 16px;">
									<button type="button" class="ahbn-email-mock-btn" id="email_preview_btn" style="background-color: <?php echo esc_attr( $accent_color ); ?>; color: <?php echo esc_attr( $primary_color ); ?>;">
										<?php esc_html_e( 'Manage Reservation Folio', 'awesome-hotel-booking' ); ?>
									</button>
								</div>

								<!-- Email Footer -->
								<div class="ahbn-email-footer-preview" id="email_preview_footer">
									<?php echo esc_html( $footer_text ); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>

	<style>
		.ahbn-emails-settings-wrap {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-emails-grid {
			display: grid;
			grid-template-columns: 3fr 2fr;
			gap: 20px;
			align-items: start;
		}

		.ahbn-panel {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			overflow: hidden;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-panel-head {
			padding: 16px 20px;
			background: #fafafa;
			border-bottom: 1px solid #f1f5f9;
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.ahbn-panel-head h2 {
			margin: 0;
			font-size: 13px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #0a192f;
		}

		.ahbn-panel-head .dashicons {
			color: #c5a880;
			font-size: 17px;
			width: 17px;
			height: 17px;
		}

		.ahbn-panel-body {
			padding: 24px;
			display: flex;
			flex-direction: column;
			gap: 16px;
		}

		.ahbn-field-group {
			display: flex;
			flex-direction: column;
			gap: 6px;
		}

		.ahbn-label {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #334155;
		}

		.ahbn-input,
		.ahbn-textarea {
			width: 100%;
			border: 1px solid #cbd5e1 !important;
			border-radius: 8px !important;
			padding: 8px 12px !important;
			font-size: 13px !important;
			background: #ffffff !important;
			color: #0f172a !important;
			box-shadow: none !important;
		}

		.ahbn-input:focus,
		.ahbn-textarea:focus {
			border-color: #0a192f !important;
			box-shadow: 0 0 0 2px rgba(10, 25, 47, 0.1) !important;
		}

		.ahbn-hint {
			font-size: 11px;
			color: #64748b;
		}

		.ahbn-upload-input-wrap {
			display: flex;
			gap: 8px;
			align-items: center;
		}

		.ahbn-btn-secondary {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 8px 14px;
			background: #ffffff;
			border: 1px solid #cbd5e1;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 600;
			color: #334155;
			cursor: pointer;
			white-space: nowrap;
			transition: all 0.2s ease;
		}

		.ahbn-btn-secondary:hover {
			border-color: #0a192f;
			color: #0a192f;
			background: #f8fafc;
		}

		/* Color Pickers */
		.ahbn-color-input-wrap {
			display: flex;
			align-items: center;
			border: 1px solid #cbd5e1;
			border-radius: 8px;
			padding: 4px 8px;
			background: #ffffff;
			gap: 8px;
		}

		.ahbn-color-picker {
			-webkit-appearance: none;
			border: none;
			width: 28px;
			height: 28px;
			border-radius: 6px;
			cursor: pointer;
			background: none;
			padding: 0;
		}

		.ahbn-color-picker::-webkit-color-swatch-wrapper { padding: 0; }
		.ahbn-color-picker::-webkit-color-swatch { border: 1px solid #e2e8f0; border-radius: 6px; }

		.ahbn-color-text {
			font-family: 'JetBrains Mono', monospace;
			font-size: 12px;
			font-weight: 700;
			border: none !important;
			outline: none !important;
			color: #334155 !important;
			background: none !important;
			padding: 0 !important;
			width: 80px;
		}

		/* Action Bar */
		.ahbn-panel-actions {
			display: flex;
			justify-content: flex-end;
			margin-top: 15px;
		}

		.ahbn-btn-save {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 11px 22px;
			background: #0a192f;
			border: 1px solid #0a192f;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 700;
			color: #ffffff;
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.ahbn-btn-save .dashicons {
			font-size: 17px;
			width: 17px;
			height: 17px;
			color: #c5a880;
		}

		.ahbn-btn-save:hover {
			background: #c5a880;
			border-color: #c5a880;
			color: #0a192f;
		}

		.ahbn-btn-save:hover .dashicons {
			color: #0a192f;
		}

		/* Live Email Preview Frame */
		.ahbn-email-preview-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			overflow: hidden;
			box-shadow: 0 4px 14px rgba(10, 25, 47, 0.08);
			max-width: 440px;
			margin: 0 auto;
		}

		.ahbn-email-header-preview {
			padding: 24px;
			text-align: center;
			transition: background-color 0.2s ease;
		}

		#email_preview_logo {
			max-height: 40px;
			max-width: 180px;
			object-fit: contain;
		}

		.ahbn-email-body-preview {
			padding: 24px;
		}

		.ahbn-email-heading {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 18px;
			font-weight: 800;
			color: #0a192f;
			margin: 0 0 14px;
			text-align: center;
		}

		.ahbn-email-text {
			font-size: 12px;
			color: #475569;
			line-height: 1.5;
			margin: 0 0 8px;
		}

		.ahbn-email-folio-box {
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 10px;
			padding: 12px 16px;
			margin: 16px 0;
			display: flex;
			flex-direction: column;
			gap: 8px;
		}

		.folio-row {
			display: flex;
			justify-content: space-between;
			align-items: center;
			font-size: 11px;
			padding-bottom: 6px;
			border-bottom: 1px solid #f1f5f9;
		}

		.folio-lbl {
			color: #64748b;
			font-weight: 600;
			text-transform: uppercase;
		}

		.folio-val {
			color: #0a192f;
			font-weight: 700;
		}

		.folio-val.text-gold {
			font-family: 'JetBrains Mono', monospace;
			font-size: 13px;
		}

		.ahbn-email-mock-btn {
			border: none;
			padding: 10px 20px;
			border-radius: 6px;
			font-size: 12px;
			font-weight: 800;
			letter-spacing: 0.02em;
			cursor: pointer;
			box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
			transition: all 0.2s ease;
		}

		.ahbn-email-footer-preview {
			margin-top: 20px;
			padding-top: 14px;
			border-top: 1px solid #e2e8f0;
			font-size: 11px;
			color: #94a3b8;
			text-align: center;
			line-height: 1.4;
		}

		@media (max-width: 1024px) {
			.ahbn-emails-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>

	<script>
	jQuery(document).ready(function($) {
		// 1. Live Color & Text Synchronizers
		function syncEmailPreview() {
			var primaryVal = $('#ahbn_primary_picker').val();
			var accentVal  = $('#ahbn_accent_picker').val();
			var footerVal  = $('#ahbn_footer_input').val();

			$('#ahbn_primary_hex').val(primaryVal);
			$('#ahbn_accent_hex').val(accentVal);

			$('#email_preview_header').css('background-color', primaryVal);
			$('#email_preview_btn').css({
				'background-color': accentVal,
				'color': primaryVal
			});
			$('#email_preview_total').css('color', accentVal);
			$('#email_preview_footer').text(footerVal);
		}

		$('#ahbn_primary_picker, #ahbn_accent_picker').on('input change', syncEmailPreview);
		$('#ahbn_footer_input').on('input keyup', syncEmailPreview);

		// 2. Logo Change & Live Preview
		$('#ahbn_logo_url').on('input change', function() {
			var url = $(this).val().trim();
			var $wrap = $('#email_preview_logo_wrap');

			if (url) {
				$wrap.html('<img src="' + encodeURI(url) + '" id="email_preview_logo" alt="<?php echo esc_js( __( 'Hotel Logo', 'awesome-hotel-booking' ) ); ?>">');
			} else {
				$wrap.html('<span id="email_preview_logo_text" style="color:#ffffff; font-family:\'Playfair Display\',serif; font-size:16px; font-weight:800; letter-spacing:0.04em;"><?php echo esc_js( strtoupper( $hotel_name ) ); ?></span>');
			}
		});

		// 3. Media Library Uploader
		var mediaFrame;
		$('#ahbn_upload_logo_btn').on('click', function(e) {
			e.preventDefault();
			if (mediaFrame) {
				mediaFrame.open();
				return;
			}
			mediaFrame = wp.media({
				title: '<?php echo esc_js( __( 'Select Email Header Logo', 'awesome-hotel-booking' ) ); ?>',
				button: { text: '<?php echo esc_js( __( 'Use this logo', 'awesome-hotel-booking' ) ); ?>' },
				multiple: false
			});
			mediaFrame.on('select', function() {
				var attachment = mediaFrame.state().get('selection').first().toJSON();
				$('#ahbn_logo_url').val(attachment.url).trigger('change');
			});
			mediaFrame.open();
		});
	});
	</script>
	<?php
}