<?php
/**
 * Emails Settings Sub-Tab View.
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
			$raw_accent_color = isset( $_POST['ahbn_email_accent_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['ahbn_email_accent_color'] ) ) : '#2271b1';
			$raw_footer_text  = isset( $_POST['ahbn_email_footer_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ahbn_email_footer_text'] ) ) : '';

			update_option( 'ahbn_admin_email', ! empty( $raw_admin_email ) ? $raw_admin_email : get_option( 'admin_email' ) );
			update_option( 'ahbn_email_logo', $raw_logo_url );
			update_option( 'ahbn_email_accent_color', ! empty( $raw_accent_color ) ? $raw_accent_color : '#2271b1' );
			update_option( 'ahbn_email_footer_text', $raw_footer_text );

			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Email branding updated successfully.', 'awesome-hotel-booking' ) . '</p></div>';
		}
	}

	// 2. Fetch Stored Values.
	$admin_email  = get_option( 'ahbn_admin_email', get_option( 'admin_email' ) );
	$logo_url     = get_option( 'ahbn_email_logo', '' );
	$accent_color = get_option( 'ahbn_email_accent_color', '#2271b1' );
	$footer_text  = get_option( 'ahbn_email_footer_text', __( 'Thank you for choosing our hospitality services.', 'awesome-hotel-booking' ) );
	?>

	<div class="ahbn-admin-wrapper">
		<?php wp_nonce_field( 'ahbn_save_email_settings', 'ahbn_email_nonce' ); ?>

		<div class="ahbn-admin-grid">
			
			<!-- Configuration Panel -->
			<div class="ahbn-admin-card">
				<div class="ahbn-admin-card-header">
					<span class="dashicons dashicons-email-alt" style="color:#2271b1; font-size: 22px;" aria-hidden="true"></span>
					<div>
						<h3 class="ahbn-admin-card-title"><?php esc_html_e( 'Hotel Email Branding', 'awesome-hotel-booking' ); ?></h3>
					</div>
				</div>
				
				<div class="ahbn-admin-field-group">
					<label for="ahbn_admin_email" class="ahbn-admin-label"><?php esc_html_e( 'Reception / Admin Email', 'awesome-hotel-booking' ); ?></label>
					<input type="email" id="ahbn_admin_email" name="ahbn_admin_email" value="<?php echo esc_attr( $admin_email ); ?>" class="ahbn-admin-input">
					<p class="description" style="color:#64748b; margin-top:4px;">
						<?php esc_html_e( 'Internal notifications for new room reservations will be dispatched to this address.', 'awesome-hotel-booking' ); ?>
					</p>
				</div>

				<div class="ahbn-admin-field-group">
					<label for="ahbn_logo_url" class="ahbn-admin-label"><?php esc_html_e( 'Hotel Logo URL', 'awesome-hotel-booking' ); ?></label>
					<div class="ahbn-admin-upload-wrap">
						<input type="text" name="ahbn_email_logo" id="ahbn_logo_url" value="<?php echo esc_url( $logo_url ); ?>" class="ahbn-admin-input">
						<button type="button" class="button button-secondary ahbn-admin-btn-upload" id="ahbn_upload_btn">
							<?php esc_html_e( 'Select Logo', 'awesome-hotel-booking' ); ?>
						</button>
					</div>
				</div>

				<div class="ahbn-admin-field-group">
					<label for="ahbn_accent_picker" class="ahbn-admin-label"><?php esc_html_e( 'Primary Brand Color', 'awesome-hotel-booking' ); ?></label>
					<input type="color" name="ahbn_email_accent_color" id="ahbn_accent_picker" value="<?php echo esc_attr( $accent_color ); ?>" style="width:100px; height:42px; cursor:pointer; border:1px solid #ccd0d4; padding:2px; border-radius:4px;">
				</div>

				<div class="ahbn-admin-field-group">
					<label for="ahbn_footer_input" class="ahbn-admin-label"><?php esc_html_e( 'Email Footer Signature', 'awesome-hotel-booking' ); ?></label>
					<textarea name="ahbn_email_footer_text" id="ahbn_footer_input" rows="4" class="ahbn-admin-textarea"><?php echo esc_textarea( $footer_text ); ?></textarea>
				</div>

				<div class="ahbn-admin-footer">
					<button type="submit" name="ahbn_save_emails" class="button button-primary ahbn-admin-btn-save">
						<?php esc_html_e( 'Update Email Branding', 'awesome-hotel-booking' ); ?>
					</button>
				</div>
			</div>

			<!-- Mock Email Live Preview -->
			<div class="ahbn-admin-preview-container">
				<div class="ahbn-admin-card">
					<div class="ahbn-admin-card-header">
						<span class="dashicons dashicons-visibility" style="color:#2271b1;" aria-hidden="true"></span>
						<h3 class="ahbn-admin-card-title"><?php esc_html_e( 'Guest View Preview', 'awesome-hotel-booking' ); ?></h3>
					</div>

					<div class="ahbn-admin-mock-email">
						<div class="ahbn-admin-mock-header" id="preview-header" style="background-color: <?php echo esc_attr( $accent_color ); ?>;">
							<div id="preview-logo-container">
								<?php if ( ! empty( $logo_url ) ) : ?>
									<img src="<?php echo esc_url( $logo_url ); ?>" id="preview-logo" style="max-height: 45px;" alt="<?php esc_attr_e( 'Hotel Logo', 'awesome-hotel-booking' ); ?>">
								<?php else : ?>
									<span id="preview-logo-placeholder" style="color:#fff; font-weight:700; opacity:0.8; font-size: 14px;">
										<?php esc_html_e( 'YOUR LOGO', 'awesome-hotel-booking' ); ?>
									</span>
								<?php endif; ?>
							</div>
						</div>
						<div class="ahbn-admin-mock-body">
							<p style="font-size:14px; color:#1d2327;">
								<strong><?php esc_html_e( 'Hello Valued Guest,', 'awesome-hotel-booking' ); ?></strong>
							</p>
							<p style="font-size:13px; color:#646970; line-height:1.5;">
								<?php
								printf(
									/* translators: %s: Highlighted room name */
									esc_html__( 'Your stay in our %s has been confirmed. We look forward to seeing you.', 'awesome-hotel-booking' ),
									'<strong>' . esc_html__( 'Deluxe Ocean Suite', 'awesome-hotel-booking' ) . '</strong>'
								);
								?>
							</p>
							<div class="ahbn-admin-mock-btn" id="preview-btn" style="background-color: <?php echo esc_attr( $accent_color ); ?>;">
								<?php esc_html_e( 'Reservation Details', 'awesome-hotel-booking' ); ?>
							</div>
							<div id="preview-footer" class="ahbn-admin-mock-footer"><?php echo esc_html( $footer_text ); ?></div>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>

	<script>
	jQuery(document).ready(function($){
		var frame;
		$('#ahbn_upload_btn').on('click', function(e){
			e.preventDefault();
			if (frame) {
				frame.open();
				return;
			}
			frame = wp.media({
				title: '<?php echo esc_js( __( 'Select Brand Logo', 'awesome-hotel-booking' ) ); ?>',
				button: {
					text: '<?php echo esc_js( __( 'Use Logo', 'awesome-hotel-booking' ) ); ?>'
				},
				multiple: false
			});
			frame.on('select', function(){
				var attachment = frame.state().get('selection').first().toJSON();
				$('#ahbn_logo_url').val(attachment.url).trigger('change');
			});
			frame.open();
		});

		$('#ahbn_accent_picker').on('input', function(){
			var color = $(this).val();
			$('#preview-header, #preview-btn').css('background-color', color);
		});

		$('#ahbn_footer_input').on('input keyup', function(){
			$('#preview-footer').text($(this).val());
		});

		$('#ahbn_logo_url').on('change keyup', function(){
			var url = $(this).val();
			var $container = $('#preview-logo-container').empty();

			if (url) {
				$('<img/>', {
					src: encodeURI(url),
					id: 'preview-logo',
					style: 'max-height: 45px;'
				}).appendTo($container);
			} else {
				$('<span/>', {
					id: 'preview-logo-placeholder',
					style: 'color:#fff; font-weight:700; opacity:0.8; font-size:14px;',
					text: '<?php echo esc_js( __( 'YOUR LOGO', 'awesome-hotel-booking' ) ); ?>'
				}).appendTo($container);
			}
		});
	});
	</script>
	<?php
}