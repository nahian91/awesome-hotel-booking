<?php
/**
 * Design, Typography, Color Palette & Customizer Engine.
 *
 * Provides granular branding control across:
 * - Brand color palette (Primary, Accent, Background, Surface, Text)
 * - Luxury & Modern Google Typography pairings
 * - Button radius, border styles, and container shadows
 * - Real-time Live Preview sandbox
 * - Direct dynamic CSS generation and frontend header injection
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default Design Tokens.
 *
 * @return array
 */
function ahbn_get_default_styling_options() {
	return array(
		'primary_color'    => '#0A192F',
		'accent_color'     => '#C5A880',
		'accent_hover'     => '#B39264',
		'surface_color'    => '#FFFFFF',
		'background_color' => '#F8FAFC',
		'text_primary'     => '#0F172A',
		'text_muted'       => '#64748B',
		'border_color'     => '#E2E8F0',
		'font_heading'     => 'Playfair Display',
		'font_body'        => 'Plus Jakarta Sans',
		'font_mono'        => 'JetBrains Mono',
		'border_radius'    => '12',
		'button_radius'    => '8',
		'box_shadow'       => 'subtle',
		'custom_css'       => '',
	);
}

/**
 * Retrieve Stored Design Tokens Merged with Defaults.
 *
 * @return array
 */
function ahbn_get_styling_options() {
	$defaults = ahbn_get_default_styling_options();
	$saved    = get_option( 'ahbn_styling_settings', array() );

	return wp_parse_args( $saved, $defaults );
}

/**
 * Render the Design & Styling Customizer Tab.
 *
 * @return void
 */
function ahbn_styling_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	// 1. Process Styling Form Submission.
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_styling'] ) ) {
		$nonce = isset( $_POST['ahbn_styling_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_styling_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'ahbn_save_styling_action' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please refresh the page.', 'awesome-hotel-booking' ) . '</p></div>';
		} else {
			$sanitized_settings = array(
				'primary_color'    => sanitize_hex_color( wp_unslash( $_POST['primary_color'] ?? '' ) ) ?: '#0A192F',
				'accent_color'     => sanitize_hex_color( wp_unslash( $_POST['accent_color'] ?? '' ) ) ?: '#C5A880',
				'accent_hover'     => sanitize_hex_color( wp_unslash( $_POST['accent_hover'] ?? '' ) ) ?: '#B39264',
				'surface_color'    => sanitize_hex_color( wp_unslash( $_POST['surface_color'] ?? '' ) ) ?: '#FFFFFF',
				'background_color' => sanitize_hex_color( wp_unslash( $_POST['background_color'] ?? '' ) ) ?: '#F8FAFC',
				'text_primary'     => sanitize_hex_color( wp_unslash( $_POST['text_primary'] ?? '' ) ) ?: '#0F172A',
				'text_muted'       => sanitize_hex_color( wp_unslash( $_POST['text_muted'] ?? '' ) ) ?: '#64748B',
				'border_color'     => sanitize_hex_color( wp_unslash( $_POST['border_color'] ?? '' ) ) ?: '#E2E8F0',
				'font_heading'     => sanitize_text_field( wp_unslash( $_POST['font_heading'] ?? 'Playfair Display' ) ),
				'font_body'        => sanitize_text_field( wp_unslash( $_POST['font_body'] ?? 'Plus Jakarta Sans' ) ),
				'font_mono'        => sanitize_text_field( wp_unslash( $_POST['font_mono'] ?? 'JetBrains Mono' ) ),
				'border_radius'    => absint( wp_unslash( $_POST['border_radius'] ?? 12 ) ),
				'button_radius'    => absint( wp_unslash( $_POST['button_radius'] ?? 8 ) ),
				'box_shadow'       => sanitize_key( wp_unslash( $_POST['box_shadow'] ?? 'subtle' ) ),
				'custom_css'       => wp_strip_all_tags( wp_unslash( $_POST['custom_css'] ?? '' ) ),
			);

			update_option( 'ahbn_styling_settings', $sanitized_settings );
			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Brand design system and visual style rules saved.', 'awesome-hotel-booking' ) . '</p></div>';
		}
	}

	$style = ahbn_get_styling_options();

	$heading_fonts = array(
		'Playfair Display' => 'Playfair Display (Luxury & Classic Serif)',
		'Cinzel'           => 'Cinzel (High-End Classical Serif)',
		'Cormorant Garamond' => 'Cormorant Garamond (Graceful Traditional)',
		'Plus Jakarta Sans'=> 'Plus Jakarta Sans (Modern Editorial Sans)',
		'Montserrat'       => 'Montserrat (Clean Geometric Sans)',
		'Inter'            => 'Inter (Neutral Functional Sans)',
	);

	$body_fonts = array(
		'Plus Jakarta Sans' => 'Plus Jakarta Sans (Ultra-Crisp Modern)',
		'Inter'             => 'Inter (Clean & Highly Legible)',
		'Open Sans'         => 'Open Sans (Balanced Humanist)',
		'Roboto'            => 'Roboto (Standard Sans)',
		'Lato'              => 'Lato (Friendly Humanist)',
	);
	?>

	<div class="ahbn-style-wrap">
		<!-- Header Toolbar -->
		<header class="ahbn-style-header">
			<div>
				<h2 class="ahbn-style-title"><?php esc_html_e( 'Design & Styling Customizer', 'awesome-hotel-booking' ); ?></h2>
				<p class="ahbn-style-subtitle">
					<?php esc_html_e( 'Customize your hotel booking engine colors, typography, layout geometry, and brand identity.', 'awesome-hotel-booking' ); ?>
				</p>
			</div>
			<div>
				<span class="ahbn-preview-badge">
					<span class="dashicons dashicons-art" aria-hidden="true"></span>
					<?php esc_html_e( 'Dynamic CSS Injection Active', 'awesome-hotel-booking' ); ?>
				</span>
			</div>
		</header>

		<form method="post" action="" id="ahbn_styling_form">
			<?php wp_nonce_field( 'ahbn_save_styling_action', 'ahbn_styling_nonce' ); ?>

			<div class="ahbn-style-grid">
				<!-- Left Column: Controls -->
				<div class="ahbn-main-col">
					
					<!-- Color Palette Panel -->
					<div class="ahbn-panel">
						<div class="ahbn-panel-head">
							<span class="dashicons dashicons-color-picker" aria-hidden="true"></span>
							<h2><?php esc_html_e( 'Brand Color Palette', 'awesome-hotel-booking' ); ?></h2>
						</div>
						<div class="ahbn-panel-body">
							<div class="ahbn-color-grid">
								<div class="ahbn-color-item">
									<label for="primary_color" class="ahbn-label"><?php esc_html_e( 'Primary Navy', 'awesome-hotel-booking' ); ?></label>
									<div class="ahbn-color-input-wrap">
										<input type="color" id="primary_color" name="primary_color" value="<?php echo esc_attr( $style['primary_color'] ); ?>" class="ahbn-color-picker">
										<input type="text" id="primary_color_hex" value="<?php echo esc_attr( $style['primary_color'] ); ?>" class="ahbn-color-text" readonly>
									</div>
								</div>

								<div class="ahbn-color-item">
									<label for="accent_color" class="ahbn-label"><?php esc_html_e( 'Champagne Gold (Accent)', 'awesome-hotel-booking' ); ?></label>
									<div class="ahbn-color-input-wrap">
										<input type="color" id="accent_color" name="accent_color" value="<?php echo esc_attr( $style['accent_color'] ); ?>" class="ahbn-color-picker">
										<input type="text" id="accent_color_hex" value="<?php echo esc_attr( $style['accent_color'] ); ?>" class="ahbn-color-text" readonly>
									</div>
								</div>

								<div class="ahbn-color-item">
									<label for="accent_hover" class="ahbn-label"><?php esc_html_e( 'Accent Hover State', 'awesome-hotel-booking' ); ?></label>
									<div class="ahbn-color-input-wrap">
										<input type="color" id="accent_hover" name="accent_hover" value="<?php echo esc_attr( $style['accent_hover'] ); ?>" class="ahbn-color-picker">
										<input type="text" id="accent_hover_hex" value="<?php echo esc_attr( $style['accent_hover'] ); ?>" class="ahbn-color-text" readonly>
									</div>
								</div>

								<div class="ahbn-color-item">
									<label for="background_color" class="ahbn-label"><?php esc_html_e( 'Canvas Background', 'awesome-hotel-booking' ); ?></label>
									<div class="ahbn-color-input-wrap">
										<input type="color" id="background_color" name="background_color" value="<?php echo esc_attr( $style['background_color'] ); ?>" class="ahbn-color-picker">
										<input type="text" id="background_color_hex" value="<?php echo esc_attr( $style['background_color'] ); ?>" class="ahbn-color-text" readonly>
									</div>
								</div>

								<div class="ahbn-color-item">
									<label for="surface_color" class="ahbn-label"><?php esc_html_e( 'Card / Surface Base', 'awesome-hotel-booking' ); ?></label>
									<div class="ahbn-color-input-wrap">
										<input type="color" id="surface_color" name="surface_color" value="<?php echo esc_attr( $style['surface_color'] ); ?>" class="ahbn-color-picker">
										<input type="text" id="surface_color_hex" value="<?php echo esc_attr( $style['surface_color'] ); ?>" class="ahbn-color-text" readonly>
									</div>
								</div>

								<div class="ahbn-color-item">
									<label for="border_color" class="ahbn-label"><?php esc_html_e( 'Component Border', 'awesome-hotel-booking' ); ?></label>
									<div class="ahbn-color-input-wrap">
										<input type="color" id="border_color" name="border_color" value="<?php echo esc_attr( $style['border_color'] ); ?>" class="ahbn-color-picker">
										<input type="text" id="border_color_hex" value="<?php echo esc_attr( $style['border_color'] ); ?>" class="ahbn-color-text" readonly>
									</div>
								</div>

								<div class="ahbn-color-item">
									<label for="text_primary" class="ahbn-label"><?php esc_html_e( 'Primary Typography', 'awesome-hotel-booking' ); ?></label>
									<div class="ahbn-color-input-wrap">
										<input type="color" id="text_primary" name="text_primary" value="<?php echo esc_attr( $style['text_primary'] ); ?>" class="ahbn-color-picker">
										<input type="text" id="text_primary_hex" value="<?php echo esc_attr( $style['text_primary'] ); ?>" class="ahbn-color-text" readonly>
									</div>
								</div>

								<div class="ahbn-color-item">
									<label for="text_muted" class="ahbn-label"><?php esc_html_e( 'Muted Typography', 'awesome-hotel-booking' ); ?></label>
									<div class="ahbn-color-input-wrap">
										<input type="color" id="text_muted" name="text_muted" value="<?php echo esc_attr( $style['text_muted'] ); ?>" class="ahbn-color-picker">
										<input type="text" id="text_muted_hex" value="<?php echo esc_attr( $style['text_muted'] ); ?>" class="ahbn-color-text" readonly>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Typography Pairings Panel -->
					<div class="ahbn-panel">
						<div class="ahbn-panel-head">
							<span class="dashicons dashicons-editor-textcolor" aria-hidden="true"></span>
							<h2><?php esc_html_e( 'Executive Typography Pairings', 'awesome-hotel-booking' ); ?></h2>
						</div>
						<div class="ahbn-panel-body">
							<div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
								<div class="ahbn-field-group">
									<label for="font_heading" class="ahbn-label"><?php esc_html_e( 'Display & Heading Font', 'awesome-hotel-booking' ); ?></label>
									<select name="font_heading" id="font_heading" class="ahbn-select">
										<?php foreach ( $heading_fonts as $k => $label ) : ?>
											<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $style['font_heading'], $k ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>

								<div class="ahbn-field-group">
									<label for="font_body" class="ahbn-label"><?php esc_html_e( 'Body & UI Font', 'awesome-hotel-booking' ); ?></label>
									<select name="font_body" id="font_body" class="ahbn-select">
										<?php foreach ( $body_fonts as $k => $label ) : ?>
											<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $style['font_body'], $k ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>
							</div>
						</div>
					</div>

					<!-- Geometry & Corner Radius -->
					<div class="ahbn-panel">
						<div class="ahbn-panel-head">
							<span class="dashicons dashicons-admin-appearance" aria-hidden="true"></span>
							<h2><?php esc_html_e( 'Geometry, Corner Radii & Shadows', 'awesome-hotel-booking' ); ?></h2>
						</div>
						<div class="ahbn-panel-body">
							<div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:16px;">
								<div class="ahbn-field-group">
									<label for="border_radius" class="ahbn-label">
										<?php esc_html_e( 'Card Radius (px)', 'awesome-hotel-booking' ); ?>
										<span id="radius_val_badge" style="color:#c5a880; font-family:'JetBrains Mono',monospace;"><?php echo esc_html( $style['border_radius'] ); ?>px</span>
									</label>
									<input type="range" min="0" max="28" step="2" id="border_radius" name="border_radius" value="<?php echo esc_attr( $style['border_radius'] ); ?>" class="ahbn-range-slider">
								</div>

								<div class="ahbn-field-group">
									<label for="button_radius" class="ahbn-label">
										<?php esc_html_e( 'Button Radius (px)', 'awesome-hotel-booking' ); ?>
										<span id="btn_radius_val_badge" style="color:#c5a880; font-family:'JetBrains Mono',monospace;"><?php echo esc_html( $style['button_radius'] ); ?>px</span>
									</label>
									<input type="range" min="0" max="24" step="2" id="button_radius" name="button_radius" value="<?php echo esc_attr( $style['button_radius'] ); ?>" class="ahbn-range-slider">
								</div>

								<div class="ahbn-field-group">
									<label for="box_shadow" class="ahbn-label"><?php esc_html_e( 'Elevation & Depth', 'awesome-hotel-booking' ); ?></label>
									<select name="box_shadow" id="box_shadow" class="ahbn-select">
										<option value="none" <?php selected( $style['box_shadow'], 'none' ); ?>><?php esc_html_e( 'Flat (No Shadow)', 'awesome-hotel-booking' ); ?></option>
										<option value="subtle" <?php selected( $style['box_shadow'], 'subtle' ); ?>><?php esc_html_e( 'Subtle Luxury', 'awesome-hotel-booking' ); ?></option>
										<option value="floating" <?php selected( $style['box_shadow'], 'floating' ); ?>><?php esc_html_e( 'Floating Card (High Depth)', 'awesome-hotel-booking' ); ?></option>
									</select>
								</div>
							</div>
						</div>
					</div>

					<!-- Custom CSS Override -->
					<div class="ahbn-panel">
						<div class="ahbn-panel-head">
							<span class="dashicons dashicons-editor-code" aria-hidden="true"></span>
							<h2><?php esc_html_e( 'Custom CSS Overrides', 'awesome-hotel-booking' ); ?></h2>
						</div>
						<div class="ahbn-panel-body">
							<textarea name="custom_css" id="custom_css" rows="4" class="ahbn-textarea" style="font-family:'JetBrains Mono',monospace; font-size:12px;" placeholder="<?php esc_attr_e( '/* Custom CSS rules injected directly to booking forms */', 'awesome-hotel-booking' ); ?>"><?php echo esc_textarea( $style['custom_css'] ); ?></textarea>
						</div>
					</div>

					<button type="submit" name="ahbn_save_styling" class="ahbn-btn-save">
						<?php esc_html_e( 'Save & Publish Styles', 'awesome-hotel-booking' ); ?>
					</button>
				</div>

				<!-- Right Column: Real-Time Component Sandbox Preview -->
				<div class="ahbn-sidebar-col">
					<div class="ahbn-panel" style="position: sticky; top: 20px;">
						<div class="ahbn-panel-head">
							<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
							<h2><?php esc_html_e( 'Live Interface Sandbox Preview', 'awesome-hotel-booking' ); ?></h2>
						</div>
						<div class="ahbn-panel-body" style="background:#f1f5f9;">
							
							<!-- Simulated Booking Card -->
							<div id="preview_card" class="sandbox-card">
								<div class="sandbox-room-img">
									<span class="sandbox-badge"><?php esc_html_e( 'Featured Suite', 'awesome-hotel-booking' ); ?></span>
								</div>

								<div class="sandbox-content">
									<h3 id="preview_heading" class="sandbox-heading"><?php esc_html_e( 'Grand Presidential Ocean Suite', 'awesome-hotel-booking' ); ?></h3>
									<p id="preview_body_text" class="sandbox-body"><?php esc_html_e( 'Panoramic ocean views, bespoke Italian marble bath, and private concierge.', 'awesome-hotel-booking' ); ?></p>

									<div class="sandbox-footer">
										<div>
											<span class="sandbox-price-lbl"><?php esc_html_e( 'From', 'awesome-hotel-booking' ); ?></span>
											<span id="preview_price" class="sandbox-price">$450.00 <small>/ night</small></span>
										</div>
										<button type="button" id="preview_btn" class="sandbox-btn">
											<?php esc_html_e( 'Reserve Suite', 'awesome-hotel-booking' ); ?>
										</button>
									</div>
								</div>
							</div>

							<!-- Simulated Search Bar Component -->
							<div id="preview_search_box" class="sandbox-search-bar" style="margin-top: 16px;">
								<span style="font-size:11px; font-weight:800; text-transform:uppercase; color:#64748b; margin-bottom:8px; display:block;">
									<?php esc_html_e( 'Search Engine Widget Preview', 'awesome-hotel-booking' ); ?>
								</span>
								<div style="display:flex; gap:8px;">
									<div class="sandbox-input-mock"><?php esc_html_e( 'Oct 12 → Oct 15', 'awesome-hotel-booking' ); ?></div>
									<button type="button" id="preview_mini_btn" class="sandbox-btn" style="padding: 6px 12px; font-size:11px;">
										<?php esc_html_e( 'Check', 'awesome-hotel-booking' ); ?>
									</button>
								</div>
							</div>

						</div>
					</div>
				</div>
			</div>
		</form>
	</div>

	<style>
		.ahbn-style-wrap {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-style-header {
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

		.ahbn-style-title {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 24px;
			font-weight: 700;
			color: #0a192f;
			margin: 0 0 4px;
		}

		.ahbn-style-subtitle {
			color: #64748b;
			margin: 0;
			font-size: 13px;
		}

		.ahbn-preview-badge {
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

		.ahbn-preview-badge .dashicons {
			color: #c5a880;
			font-size: 16px;
			width: 16px;
			height: 16px;
		}

		.ahbn-style-grid {
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
			margin-bottom: 20px;
		}

		.ahbn-panel:last-child {
			margin-bottom: 0;
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

		/* Color Grid Elements */
		.ahbn-color-grid {
			display: grid;
			grid-template-columns: repeat(2, 1fr);
			gap: 14px;
		}

		.ahbn-color-item {
			display: flex;
			flex-direction: column;
			gap: 6px;
		}

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

		.ahbn-select,
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

		.ahbn-select:focus,
		.ahbn-textarea:focus {
			border-color: #0a192f !important;
			box-shadow: 0 0 0 2px rgba(10, 25, 47, 0.1) !important;
		}

		.ahbn-range-slider {
			width: 100%;
			cursor: pointer;
		}

		.ahbn-btn-save {
			width: 100%;
			padding: 12px 20px;
			background: #0a192f;
			border: 1px solid #0a192f;
			border-radius: 8px;
			font-size: 14px;
			font-weight: 800;
			color: #ffffff;
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.ahbn-btn-save:hover {
			background: #c5a880;
			border-color: #c5a880;
			color: #0a192f;
		}

		/* Sandbox Component Classes */
		.sandbox-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			overflow: hidden;
			transition: all 0.2s ease;
		}

		.sandbox-room-img {
			height: 140px;
			background: linear-gradient(135deg, #07111e 0%, #1e293b 100%);
			position: relative;
			padding: 12px;
		}

		.sandbox-badge {
			display: inline-block;
			background: rgba(197, 168, 128, 0.2);
			border: 1px solid rgba(197, 168, 128, 0.4);
			color: #c5a880;
			font-size: 10px;
			font-weight: 800;
			text-transform: uppercase;
			padding: 3px 8px;
			border-radius: 99px;
			letter-spacing: 0.5px;
		}

		.sandbox-content {
			padding: 18px;
		}

		.sandbox-heading {
			margin: 0 0 6px;
			font-size: 17px;
			font-weight: 800;
			color: #0a192f;
			line-height: 1.3;
		}

		.sandbox-body {
			margin: 0 0 16px;
			font-size: 12px;
			line-height: 1.5;
			color: #64748b;
		}

		.sandbox-footer {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding-top: 14px;
			border-top: 1px solid #f1f5f9;
		}

		.sandbox-price-lbl {
			display: block;
			font-size: 9px;
			text-transform: uppercase;
			font-weight: 800;
			color: #94a3b8;
		}

		.sandbox-price {
			font-family: 'JetBrains Mono', monospace;
			font-size: 18px;
			font-weight: 800;
			color: #0a192f;
		}

		.sandbox-price small {
			font-size: 11px;
			color: #64748b;
			font-weight: 500;
		}

		.sandbox-btn {
			padding: 8px 16px;
			border: none;
			font-size: 12px;
			font-weight: 700;
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.sandbox-search-bar {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 14px;
		}

		.sandbox-input-mock {
			flex: 1;
			background: #f8fafc;
			border: 1px solid #cbd5e1;
			border-radius: 6px;
			padding: 6px 10px;
			font-size: 11px;
			color: #334155;
			font-weight: 600;
		}

		@media (max-width: 1024px) {
			.ahbn-style-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>

	<script>
	jQuery(document).ready(function($) {
		function refreshLiveSandbox() {
			const primaryColor = $('#primary_color').val();
			const accentColor  = $('#accent_color').val();
			const surfaceColor = $('#surface_color').val();
			const textPrimary  = $('#text_primary').val();
			const textMuted    = $('#text_muted').val();
			const borderColor  = $('#border_color').val();
			const fontHeading  = $('#font_heading').val();
			const fontBody     = $('#font_body').val();
			const cardRadius   = $('#border_radius').val() + 'px';
			const btnRadius    = $('#button_radius').val() + 'px';
			const shadowType   = $('#box_shadow').val();

			// Mirror hex displays.
			$('#primary_color_hex').val(primaryColor);
			$('#accent_color_hex').val(accentColor);
			$('#accent_hover_hex').val($('#accent_hover').val());
			$('#background_color_hex').val($('#background_color').val());
			$('#surface_color_hex').val(surfaceColor);
			$('#border_color_hex').val(borderColor);
			$('#text_primary_hex').val(textPrimary);
			$('#text_muted_hex').val(textMuted);

			$('#radius_val_badge').text(cardRadius);
			$('#btn_radius_val_badge').text(btnRadius);

			// Apply to Sandbox Card.
			let shadowCSS = 'none';
			if (shadowType === 'subtle') shadowCSS = '0 4px 14px rgba(10,25,47,0.06)';
			if (shadowType === 'floating') shadowCSS = '0 12px 28px rgba(10,25,47,0.12)';

			$('#preview_card, #preview_search_box').css({
				'background-color': surfaceColor,
				'border-color': borderColor,
				'border-radius': cardRadius,
				'box-shadow': shadowCSS
			});

			$('#preview_heading').css({
				'font-family': fontHeading + ', serif',
				'color': textPrimary
			});

			$('#preview_body_text').css({
				'font-family': fontBody + ', sans-serif',
				'color': textMuted
			});

			$('#preview_price').css('color', primaryColor);

			$('#preview_btn, #preview_mini_btn').css({
				'background-color': primaryColor,
				'color': '#FFFFFF',
				'border-radius': btnRadius
			});
		}

		$('input[type="color"], #border_radius, #button_radius, #box_shadow, #font_heading, #font_body').on('input change', refreshLiveSandbox);
		refreshLiveSandbox();
	});
	</script>
	<?php
}

/**
 * Public Front-End CSS Generator: Dynamic Stylesheet Injection.
 *
 * @return void
 */
function ahbn_inject_customizer_css() {
	$s = ahbn_get_styling_options();

	$heading_font = sanitize_text_field( $s['font_heading'] );
	$body_font    = sanitize_text_field( $s['font_body'] );

	// Preload Google Fonts.
	$fonts_to_load = array_unique( array( $heading_font, $body_font, 'JetBrains Mono' ) );
	$font_queries  = array();
	foreach ( $fonts_to_load as $font ) {
		$font_queries[] = 'family=' . str_replace( ' ', '+', $font ) . ':wght@400;500;600;700;800';
	}
	$google_url = 'https://fonts.googleapis.com/css2?' . implode( '&', $font_queries ) . '&display=swap';

	$shadow = 'none';
	if ( 'subtle' === $s['box_shadow'] ) {
		$shadow = '0 4px 14px rgba(10, 25, 47, 0.06)';
	} elseif ( 'floating' === $s['box_shadow'] ) {
		$shadow = '0 12px 28px rgba(10, 25, 47, 0.12)';
	}
	?>
	<!-- Awesome Hotel Booking Typography & Design System -->
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="<?php echo esc_url( $google_url ); ?>" rel="stylesheet">

	<style id="ahbn-dynamic-styles">
		:root {
			--ahbn-primary: <?php echo esc_attr( $s['primary_color'] ); ?>;
			--ahbn-accent: <?php echo esc_attr( $s['accent_color'] ); ?>;
			--ahbn-accent-hover: <?php echo esc_attr( $s['accent_hover'] ); ?>;
			--ahbn-surface: <?php echo esc_attr( $s['surface_color'] ); ?>;
			--ahbn-bg: <?php echo esc_attr( $s['background_color'] ); ?>;
			--ahbn-text-primary: <?php echo esc_attr( $s['text_primary'] ); ?>;
			--ahbn-text-muted: <?php echo esc_attr( $s['text_muted'] ); ?>;
			--ahbn-border: <?php echo esc_attr( $s['border_color'] ); ?>;
			--ahbn-font-heading: '<?php echo esc_attr( $heading_font ); ?>', Georgia, serif;
			--ahbn-font-body: '<?php echo esc_attr( $body_font ); ?>', -apple-system, sans-serif;
			--ahbn-font-mono: '<?php echo esc_attr( $s['font_mono'] ); ?>', monospace;
			--ahbn-card-radius: <?php echo esc_attr( (string) $s['border_radius'] ); ?>px;
			--ahbn-btn-radius: <?php echo esc_attr( (string) $s['button_radius'] ); ?>px;
			--ahbn-shadow: <?php echo esc_attr( $shadow ); ?>;
		}

		/* Core Styling Hooks */
		.ahbn-booking-form, .ahbn-card, .ahbn-room-card {
			background-color: var(--ahbn-surface) !important;
			border: 1px solid var(--ahbn-border) !important;
			border-radius: var(--ahbn-card-radius) !important;
			box-shadow: var(--ahbn-shadow) !important;
			color: var(--ahbn-text-primary) !important;
			font-family: var(--ahbn-font-body) !important;
		}

		.ahbn-room-title, .ahbn-section-heading {
			font-family: var(--ahbn-font-heading) !important;
			color: var(--ahbn-primary) !important;
		}

		.ahbn-btn, .ahbn-btn-primary, .ahbn-reserve-btn {
			background: var(--ahbn-primary) !important;
			color: #ffffff !important;
			border-radius: var(--ahbn-btn-radius) !important;
			font-family: var(--ahbn-font-body) !important;
			border: 1px solid var(--ahbn-primary) !important;
			transition: all 0.2s ease !important;
		}

		.ahbn-btn:hover, .ahbn-btn-primary:hover, .ahbn-reserve-btn:hover {
			background: var(--ahbn-accent) !important;
			border-color: var(--ahbn-accent) !important;
			color: var(--ahbn-primary) !important;
		}

		.ahbn-price-amount {
			color: var(--ahbn-primary) !important;
			font-family: var(--ahbn-font-mono) !important;
		}

		<?php if ( ! empty( $s['custom_css'] ) ) : ?>
			/* User Custom Overrides */
			<?php echo wp_strip_all_tags( $s['custom_css'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>
	</style>
	<?php
}
add_action( 'wp_head', 'ahbn_inject_customizer_css', 100 );