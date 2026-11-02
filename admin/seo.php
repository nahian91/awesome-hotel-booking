<?php
/**
 * SEO, Open Graph & Schema.org Rich Snippets Architecture.
 *
 * Built on high-performance custom relational database tables:
 * {$wpdb->prefix}ahbn_seo & {$wpdb->prefix}ahbn_rooms.
 * Features: SERP Preview, Social Open Graph, JSON-LD Schema Generator, and Front-End Injection.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the SEO & Schema Markup Tab in the Admin Panel.
 *
 * @return void
 */
function ahbn_seo_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	global $wpdb;
	$table_seo   = $wpdb->prefix . 'ahbn_seo';
	$table_rooms = $wpdb->prefix . 'ahbn_rooms';

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$selected_entity_type = isset( $_GET['entity_type'] ) ? sanitize_key( wp_unslash( $_GET['entity_type'] ) ) : 'global';
	$selected_entity_id   = isset( $_GET['entity_id'] ) ? absint( wp_unslash( $_GET['entity_id'] ) ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	// 1. Process SEO Metadata Form Submission.
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_seo'] ) ) {
		$nonce = isset( $_POST['ahbn_seo_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_seo_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'ahbn_save_seo_action' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed. Please refresh the page.', 'awesome-hotel-booking' ) . '</p></div>';
		} else {
			$entity_type      = isset( $_POST['entity_type'] ) ? sanitize_key( wp_unslash( $_POST['entity_type'] ) ) : 'global';
			$entity_id        = isset( $_POST['entity_id'] ) ? absint( wp_unslash( $_POST['entity_id'] ) ) : 0;
			$meta_title       = isset( $_POST['meta_title'] ) ? sanitize_text_field( wp_unslash( $_POST['meta_title'] ) ) : '';
			$meta_description = isset( $_POST['meta_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['meta_description'] ) ) : '';
			$schema_type      = isset( $_POST['schema_type'] ) ? sanitize_text_field( wp_unslash( $_POST['schema_type'] ) ) : 'Hotel';
			$og_image_id      = isset( $_POST['og_image_id'] ) ? absint( wp_unslash( $_POST['og_image_id'] ) ) : 0;
			$canonical_url    = isset( $_POST['canonical_url'] ) ? esc_url_raw( wp_unslash( $_POST['canonical_url'] ) ) : '';

			$seo_data = array(
				'entity_type'      => $entity_type,
				'entity_id'        => $entity_id,
				'meta_title'       => $meta_title,
				'meta_description' => $meta_description,
				'schema_type'      => $schema_type,
				'og_image_id'      => $og_image_id,
				'canonical_url'    => $canonical_url,
			);

			$format = array( '%s', '%d', '%s', '%s', '%s', '%d', '%s' );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$existing_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table_seo} WHERE entity_type = %s AND entity_id = %d",
					$entity_type,
					$entity_id
				)
			);

			if ( $existing_id ) {
				$wpdb->update(
					$table_seo,
					$seo_data,
					array( 'id' => $existing_id ),
					$format,
					array( '%d' )
				);
			} else {
				$wpdb->insert( $table_seo, $seo_data, $format );
			}
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			$selected_entity_type = $entity_type;
			$selected_entity_id   = $entity_id;

			echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'SEO metadata & Schema configuration saved.', 'awesome-hotel-booking' ) . '</p></div>';
		}
	}

	// 2. Fetch Inventory Suites for Target Entity Selection.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$rooms = $wpdb->get_results( "SELECT id, room_number, title, room_type FROM {$table_rooms} WHERE status = 'publish' ORDER BY room_number ASC" );

	// 3. Retrieve Current Entity Record.
	$current_seo = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table_seo} WHERE entity_type = %s AND entity_id = %d",
			$selected_entity_type,
			$selected_entity_id
		)
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	$hotel_name       = get_option( 'ahbn_hotel_name', get_bloginfo( 'name' ) );
	$site_desc        = get_bloginfo( 'description' );
	$default_title    = 'global' === $selected_entity_type ? $hotel_name . ' | Luxury Accommodation & Suite Reservations' : '';
	$meta_title_val   = $current_seo ? $current_seo->meta_title : $default_title;
	$meta_desc_val    = $current_seo ? $current_seo->meta_description : $site_desc;
	$schema_type_val  = $current_seo ? $current_seo->schema_type : ( 'global' === $selected_entity_type ? 'Hotel' : 'HotelRoom' );
	$canonical_val    = $current_seo ? $current_seo->canonical_url : home_url();
	$og_image_id_val  = $current_seo ? (int) $current_seo->og_image_id : 0;
	$og_image_url_val = $og_image_id_val > 0 ? wp_get_attachment_image_url( $og_image_id_val, 'large' ) : '';

	$base_seo_url = admin_url( 'admin.php?page=awesome_hotel_booking&tab=seo' );
	?>

	<div class="ahbn-seo-wrap">
		<!-- Header Toolbar -->
		<header class="ahbn-seo-header">
			<div>
				<h2 class="ahbn-seo-title"><?php esc_html_e( 'SEO & Rich Snippets Architecture', 'awesome-hotel-booking' ); ?></h2>
				<p class="ahbn-seo-subtitle">
					<?php esc_html_e( 'Configure search engine indexing, Google SERP previews, Open Graph share cards, and JSON-LD schema structures.', 'awesome-hotel-booking' ); ?>
				</p>
			</div>
			<div>
				<span class="ahbn-schema-badge">
					<span class="dashicons dashicons-google" aria-hidden="true"></span>
					<?php esc_html_e( 'Schema.org JSON-LD Enabled', 'awesome-hotel-booking' ); ?>
				</span>
			</div>
		</header>

		<!-- Entity Target Selector -->
		<div class="ahbn-seo-selector-card">
			<label for="ahbn_entity_selector" class="ahbn-label"><?php esc_html_e( 'Select Target Entity to Optimize:', 'awesome-hotel-booking' ); ?></label>
			<select id="ahbn_entity_selector" class="ahbn-select" onchange="window.location.href=this.value;">
				<option value="<?php echo esc_url( add_query_arg( array( 'entity_type' => 'global', 'entity_id' => 0 ), $base_seo_url ) ); ?>" <?php selected( 'global' === $selected_entity_type ); ?>>
					<?php esc_html_e( '★ Global Hotel Entity (Homepage / Entire Property)', 'awesome-hotel-booking' ); ?>
				</option>
				<optgroup label="<?php esc_attr_e( 'Individual Rooms & Suites', 'awesome-hotel-booking' ); ?>">
					<?php if ( ! empty( $rooms ) ) : ?>
						<?php foreach ( $rooms as $rm ) :
							$rm_url = add_query_arg( array( 'entity_type' => 'room', 'entity_id' => $rm->id ), $base_seo_url );
							?>
							<option value="<?php echo esc_url( $rm_url ); ?>" <?php selected( 'room' === $selected_entity_type && $selected_entity_id === (int) $rm->id ); ?>>
								#<?php echo esc_html( $rm->room_number . ' - ' . $rm->title . ' (' . $rm->room_type . ')' ); ?>
							</option>
						<?php endforeach; ?>
					<?php endif; ?>
				</optgroup>
			</select>
		</div>

		<form method="post" action="">
			<?php wp_nonce_field( 'ahbn_save_seo_action', 'ahbn_seo_nonce' ); ?>
			<input type="hidden" name="entity_type" value="<?php echo esc_attr( $selected_entity_type ); ?>">
			<input type="hidden" name="entity_id" value="<?php echo esc_attr( (string) $selected_entity_id ); ?>">

			<div class="ahbn-seo-grid">
				<!-- Left Column: Form Controls -->
				<div class="ahbn-main-col">
					<!-- SERP Metadata -->
					<div class="ahbn-panel">
						<div class="ahbn-panel-head">
							<span class="dashicons dashicons-search" aria-hidden="true"></span>
							<h2><?php esc_html_e( 'Search Engine Representation (Meta Tags)', 'awesome-hotel-booking' ); ?></h2>
						</div>
						<div class="ahbn-panel-body">
							<div class="ahbn-field-group">
								<div class="ahbn-label-flex">
									<label for="meta_title" class="ahbn-label"><?php esc_html_e( 'Meta Title (Title Tag) *', 'awesome-hotel-booking' ); ?></label>
									<span class="ahbn-char-counter" id="title_counter">0 / 60</span>
								</div>
								<input type="text" name="meta_title" id="meta_title" class="ahbn-input" required maxlength="70" value="<?php echo esc_attr( $meta_title_val ); ?>" placeholder="<?php esc_attr_e( 'e.g. Grand Palace Hotel | Luxury Ocean Suites', 'awesome-hotel-booking' ); ?>">
							</div>

							<div class="ahbn-field-group">
								<div class="ahbn-label-flex">
									<label for="meta_description" class="ahbn-label"><?php esc_html_e( 'Meta Description (Snippet Snippet) *', 'awesome-hotel-booking' ); ?></label>
									<span class="ahbn-char-counter" id="desc_counter">0 / 160</span>
								</div>
								<textarea name="meta_description" id="meta_description" rows="3" class="ahbn-textarea" required maxlength="175" placeholder="<?php esc_attr_e( 'Write an engaging synopsis summarizing room amenities, central locations, and booking rates...', 'awesome-hotel-booking' ); ?>"><?php echo esc_textarea( $meta_desc_val ); ?></textarea>
							</div>

							<div class="ahbn-field-group">
								<label for="canonical_url" class="ahbn-label"><?php esc_html_e( 'Canonical URL Override', 'awesome-hotel-booking' ); ?></label>
								<input type="url" name="canonical_url" id="canonical_url" class="ahbn-input" value="<?php echo esc_attr( $canonical_val ); ?>" placeholder="https://">
								<small class="ahbn-field-hint"><?php esc_html_e( 'Leave empty to automatically resolve canonical links.', 'awesome-hotel-booking' ); ?></small>
							</div>
						</div>
					</div>

					<!-- Social Share / Open Graph -->
					<div class="ahbn-panel">
						<div class="ahbn-panel-head">
							<span class="dashicons dashicons-share" aria-hidden="true"></span>
							<h2><?php esc_html_e( 'Open Graph & Social Media Share Banner', 'awesome-hotel-booking' ); ?></h2>
						</div>
						<div class="ahbn-panel-body">
							<div class="ahbn-field-group">
								<label class="ahbn-label"><?php esc_html_e( 'Social Card Image (1200 × 630px Recommended)', 'awesome-hotel-booking' ); ?></label>
								<input type="hidden" name="og_image_id" id="ahbn_og_image_id" value="<?php echo esc_attr( (string) $og_image_id_val ); ?>">
								
								<div class="ahbn-og-preview-box">
									<img id="ahbn_og_preview_img" src="<?php echo esc_url( $og_image_url_val ); ?>" alt="<?php esc_attr_e( 'OG Preview', 'awesome-hotel-booking' ); ?>" style="<?php echo empty( $og_image_url_val ) ? 'display:none;' : 'display:block;'; ?>">
									<div id="ahbn_og_placeholder" style="<?php echo empty( $og_image_url_val ) ? 'display:flex;' : 'display:none;'; ?>">
										<span class="dashicons dashicons-format-image" aria-hidden="true"></span>
										<span><?php esc_html_e( 'No social card image selected', 'awesome-hotel-booking' ); ?></span>
									</div>
								</div>

								<div style="display:flex; gap:10px; margin-top:10px;">
									<button type="button" class="ahbn-btn-secondary" id="ahbn_upload_og_btn">
										<span class="dashicons dashicons-upload" aria-hidden="true"></span>
										<?php esc_html_e( 'Select Image', 'awesome-hotel-booking' ); ?>
									</button>
									<button type="button" class="ahbn-btn-secondary is-remove" id="ahbn_remove_og_btn" style="<?php echo empty( $og_image_url_val ) ? 'display:none;' : 'display:inline-flex;'; ?>">
										<span class="dashicons dashicons-trash" aria-hidden="true"></span>
										<?php esc_html_e( 'Remove', 'awesome-hotel-booking' ); ?>
									</button>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Right Column: Previews & Structured Data -->
				<div class="ahbn-sidebar-col">
					<!-- Live Google SERP Simulation -->
					<div class="ahbn-panel">
						<div class="ahbn-panel-head">
							<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
							<h2><?php esc_html_e( 'Live Search Snippet Preview', 'awesome-hotel-booking' ); ?></h2>
						</div>
						<div class="ahbn-panel-body" style="background:#ffffff;">
							<div class="ahbn-serp-box">
								<div class="ahbn-serp-site-flex">
									<div class="ahbn-serp-favicon">
										<span class="dashicons dashicons-building" aria-hidden="true"></span>
									</div>
									<div class="ahbn-serp-url-stack">
										<span class="ahbn-serp-brand"><?php echo esc_html( $hotel_name ); ?></span>
										<span class="ahbn-serp-url" id="serp_url"><?php echo esc_html( home_url( '/' . ( 'room' === $selected_entity_type ? 'rooms/' : '' ) ) ); ?></span>
									</div>
								</div>
								<h3 class="ahbn-serp-title" id="serp_title"><?php echo esc_html( ! empty( $meta_title_val ) ? $meta_title_val : $default_title ); ?></h3>
								<p class="ahbn-serp-desc" id="serp_desc"><?php echo esc_html( ! empty( $meta_desc_val ) ? $meta_desc_val : $site_desc ); ?></p>
							</div>
						</div>
					</div>

					<!-- Schema.org Configuration -->
					<div class="ahbn-panel">
						<div class="ahbn-panel-head">
							<span class="dashicons dashicons-media-code" aria-hidden="true"></span>
							<h2><?php esc_html_e( 'Schema.org JSON-LD Type', 'awesome-hotel-booking' ); ?></h2>
						</div>
						<div class="ahbn-panel-body">
							<div class="ahbn-field-group">
								<label for="schema_type" class="ahbn-label"><?php esc_html_e( 'Structural Vocabulary Type', 'awesome-hotel-booking' ); ?></label>
								<select name="schema_type" id="schema_type" class="ahbn-select">
									<?php if ( 'global' === $selected_entity_type ) : ?>
										<option value="Hotel" <?php selected( 'Hotel' === $schema_type_val ); ?>><?php esc_html_e( 'Hotel (Standard Property)', 'awesome-hotel-booking' ); ?></option>
										<option value="Resort" <?php selected( 'Resort' === $schema_type_val ); ?>><?php esc_html_e( 'Resort (Destination Complex)', 'awesome-hotel-booking' ); ?></option>
										<option value="BedAndBreakfast" <?php selected( 'BedAndBreakfast' === $schema_type_val ); ?>><?php esc_html_e( 'Bed & Breakfast (B&B)', 'awesome-hotel-booking' ); ?></option>
										<option value="Hostel" <?php selected( 'Hostel' === $schema_type_val ); ?>><?php esc_html_e( 'Hostel', 'awesome-hotel-booking' ); ?></option>
									<?php else : ?>
										<option value="HotelRoom" <?php selected( 'HotelRoom' === $schema_type_val ); ?>><?php esc_html_e( 'HotelRoom (Guest Suite/Room)', 'awesome-hotel-booking' ); ?></option>
										<option value="Suite" <?php selected( 'Suite' === $schema_type_val ); ?>><?php esc_html_e( 'Suite (Multi-room unit)', 'awesome-hotel-booking' ); ?></option>
									<?php endif; ?>
								</select>
								<small class="ahbn-field-hint"><?php esc_html_e( 'Injects clean JSON-LD into the page <head> for Google Rich Results.', 'awesome-hotel-booking' ); ?></small>
							</div>

							<button type="submit" name="ahbn_save_seo" class="ahbn-btn-save">
								<?php esc_html_e( 'Save SEO Settings', 'awesome-hotel-booking' ); ?>
							</button>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>

	<style>
		.ahbn-seo-wrap {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-seo-header {
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

		.ahbn-seo-title {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 24px;
			font-weight: 700;
			color: #0a192f;
			margin: 0 0 4px;
		}

		.ahbn-seo-subtitle {
			color: #64748b;
			margin: 0;
			font-size: 13px;
		}

		.ahbn-schema-badge {
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

		.ahbn-schema-badge .dashicons {
			color: #c5a880;
			font-size: 16px;
			width: 16px;
			height: 16px;
		}

		.ahbn-seo-selector-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 16px 20px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-seo-grid {
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

		.ahbn-field-group {
			display: flex;
			flex-direction: column;
			gap: 6px;
		}

		.ahbn-label-flex {
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.ahbn-label {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #334155;
		}

		.ahbn-char-counter {
			font-family: 'JetBrains Mono', monospace;
			font-size: 11px;
			font-weight: 700;
			color: #94a3b8;
		}

		.ahbn-char-counter.is-warn {
			color: #d97706;
		}

		.ahbn-char-counter.is-over {
			color: #e11d48;
		}

		.ahbn-input,
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

		.ahbn-input:focus,
		.ahbn-select:focus,
		.ahbn-textarea:focus {
			border-color: #0a192f !important;
			box-shadow: 0 0 0 2px rgba(10, 25, 47, 0.1) !important;
		}

		.ahbn-field-hint {
			font-size: 11px;
			color: #94a3b8;
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
			transition: all 0.2s ease;
		}

		.ahbn-btn-secondary:hover {
			border-color: #0a192f;
			color: #0a192f;
			background: #f8fafc;
		}

		.ahbn-btn-secondary.is-remove:hover {
			border-color: #e11d48;
			color: #e11d48;
			background: #fff1f2;
		}

		.ahbn-btn-save {
			width: 100%;
			padding: 10px 20px;
			background: #0a192f;
			border: 1px solid #0a192f;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 700;
			color: #ffffff;
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.ahbn-btn-save:hover {
			background: #c5a880;
			border-color: #c5a880;
			color: #0a192f;
		}

		/* Open Graph Image Area */
		.ahbn-og-preview-box {
			width: 100%;
			height: 180px;
			background: #f8fafc;
			border: 1px dashed #cbd5e1;
			border-radius: 10px;
			overflow: hidden;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.ahbn-og-preview-box img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		#ahbn_og_placeholder {
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 6px;
			color: #94a3b8;
			font-size: 12px;
		}

		#ahbn_og_placeholder .dashicons {
			font-size: 32px;
			width: 32px;
			height: 32px;
		}

		/* SERP Simulation Card */
		.ahbn-serp-box {
			font-family: Arial, sans-serif;
			padding: 16px;
			border: 1px solid #e2e8f0;
			border-radius: 10px;
			background: #ffffff;
		}

		.ahbn-serp-site-flex {
			display: flex;
			align-items: center;
			gap: 10px;
			margin-bottom: 6px;
		}

		.ahbn-serp-favicon {
			width: 26px;
			height: 26px;
			border-radius: 50%;
			background: #f1f5f9;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.ahbn-serp-favicon .dashicons {
			font-size: 15px;
			width: 15px;
			height: 15px;
			color: #0a192f;
		}

		.ahbn-serp-url-stack {
			line-height: 1.2;
		}

		.ahbn-serp-brand {
			display: block;
			font-size: 12px;
			color: #202124;
		}

		.ahbn-serp-url {
			display: block;
			font-size: 11px;
			color: #4d5156;
		}

		.ahbn-serp-title {
			margin: 0 0 4px 0;
			font-size: 18px;
			font-weight: 400;
			color: #1a0dab;
			line-height: 1.3;
			cursor: pointer;
			text-decoration: none;
		}

		.ahbn-serp-title:hover {
			text-decoration: underline;
		}

		.ahbn-serp-desc {
			margin: 0;
			font-size: 13px;
			line-height: 1.5;
			color: #4d5156;
		}

		@media (max-width: 1024px) {
			.ahbn-seo-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>

	<script>
	jQuery(document).ready(function($) {
		const $titleInput = $('#meta_title');
		const $descInput  = $('#meta_description');
		const $titleCount = $('#title_counter');
		const $descCount  = $('#desc_counter');
		const $serpTitle  = $('#serp_title');
		const $serpDesc   = $('#serp_desc');

		function updateCounters() {
			const tLen = $titleInput.val().length;
			const dLen = $descInput.val().length;

			$titleCount.text(tLen + ' / 60');
			$descCount.text(dLen + ' / 160');

			$titleCount.toggleClass('is-warn', tLen > 55 && tLen <= 65).toggleClass('is-over', tLen > 65);
			$descCount.toggleClass('is-warn', dLen > 150 && dLen <= 165).toggleClass('is-over', dLen > 165);

			$serpTitle.text($titleInput.val() || '<?php echo esc_js( $default_title ); ?>');
			$serpDesc.text($descInput.val() || '<?php echo esc_js( $site_desc ); ?>');
		}

		$titleInput.on('input', updateCounters);
		$descInput.on('input', updateCounters);
		updateCounters();

		// WP Media Uploader for OG Image.
		let mediaFrame;
		$('#ahbn_upload_og_btn').on('click', function(e) {
			e.preventDefault();
			if (mediaFrame) {
				mediaFrame.open();
				return;
			}
			mediaFrame = wp.media({
				title: '<?php echo esc_js( __( 'Select or Upload Social Card Image', 'awesome-hotel-booking' ) ); ?>',
				button: { text: '<?php echo esc_js( __( 'Use this image', 'awesome-hotel-booking' ) ); ?>' },
				multiple: false
			});

			mediaFrame.on('select', function() {
				const attachment = mediaFrame.state().get('selection').first().toJSON();
				$('#ahbn_og_image_id').val(attachment.id);
				$('#ahbn_og_preview_img').attr('src', attachment.url).show();
				$('#ahbn_og_placeholder').hide();
				$('#ahbn_remove_og_btn').css('display', 'inline-flex');
			});
			mediaFrame.open();
		});

		$('#ahbn_remove_og_btn').on('click', function(e) {
			e.preventDefault();
			$('#ahbn_og_image_id').val(0);
			$('#ahbn_og_preview_img').attr('src', '').hide();
			$('#ahbn_og_placeholder').show();
			$(this).hide();
		});
	});
	</script>
	<?php
}

/**
 * Public Front-End Injection: Meta Tags, Open Graph, and JSON-LD Structured Data.
 *
 * @return void
 */
function ahbn_render_frontend_seo_head() {
	global $wpdb;
	$table_seo   = $wpdb->prefix . 'ahbn_seo';
	$table_rooms = $wpdb->prefix . 'ahbn_rooms';

	$entity_type = 'global';
	$entity_id   = 0;

	// Check if viewing a custom room.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['room_id'] ) && absint( $_GET['room_id'] ) > 0 ) {
		$entity_type = 'room';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$entity_id   = absint( $_GET['room_id'] );
	}

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$seo_record = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table_seo} WHERE entity_type = %s AND entity_id = %d",
			$entity_type,
			$entity_id
		)
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	$hotel_name = get_option( 'ahbn_hotel_name', get_bloginfo( 'name' ) );
	$title      = $seo_record && ! empty( $seo_record->meta_title ) ? $seo_record->meta_title : $hotel_name;
	$desc       = $seo_record && ! empty( $seo_record->meta_description ) ? $seo_record->meta_description : get_bloginfo( 'description' );
	$canonical  = $seo_record && ! empty( $seo_record->canonical_url ) ? $seo_record->canonical_url : ( is_singular() ? get_permalink() : home_url() );
	$og_img     = $seo_record && $seo_record->og_image_id > 0 ? wp_get_attachment_image_url( $seo_record->og_image_id, 'large' ) : '';

	echo "\n<!-- Awesome Hotel Booking SEO Engine -->\n";
	if ( ! empty( $desc ) ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";

	// Open Graph Tags
	echo '<meta property="og:site_name" content="' . esc_attr( $hotel_name ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	if ( ! empty( $desc ) ) {
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	echo '<meta property="og:url" content="' . esc_url( $canonical ) . '">' . "\n";
	echo '<meta property="og:type" content="' . ( 'room' === $entity_type ? 'hotel.room' : 'website' ) . '">' . "\n";
	if ( ! empty( $og_img ) ) {
		echo '<meta property="og:image" content="' . esc_url( $og_img ) . '">' . "\n";
	}

	// Schema.org JSON-LD Generation
	$schema_type = $seo_record && ! empty( $seo_record->schema_type ) ? $seo_record->schema_type : ( 'room' === $entity_type ? 'HotelRoom' : 'Hotel' );

	$schema_data = array(
		'@context'    => 'https://schema.org',
		'@type'       => $schema_type,
		'name'        => $title,
		'description' => $desc,
		'url'         => $canonical,
	);

	if ( ! empty( $og_img ) ) {
		$schema_data['image'] = $og_img;
	}

	if ( 'global' === $entity_type ) {
		$schema_data['telephone'] = get_option( 'ahbn_hotel_phone', '' );
		$schema_data['email']     = get_option( 'ahbn_hotel_email', get_bloginfo( 'admin_email' ) );
		$schema_data['address']   = array(
			'@type'          => 'PostalAddress',
			'streetAddress' => get_option( 'ahbn_hotel_address', '' ),
		);
	} elseif ( 'room' === $entity_type && $entity_id > 0 ) {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$room_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_rooms} WHERE id = %d", $entity_id ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $room_row ) {
			$schema_data['bed']           = $room_row->room_type;
			$schema_data['occupancy']     = array(
				'@type'    => 'QuantitativeValue',
				'maxValue' => absint( $room_row->max_adults ) + absint( $room_row->max_children ),
			);
			$schema_data['offers']        = array(
				'@type'         => 'Offer',
				'price'         => (float) $room_row->price_per_night,
				'priceCurrency' => get_option( 'ahbn_currency', 'USD' ),
				'availability'  => 'https://schema.org/InStock',
			);
		}
	}

	echo '<script type="application/ld+json">' . wp_json_encode( $schema_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . "</script>\n";
	echo "<!-- /Awesome Hotel Booking SEO Engine -->\n\n";
}
add_action( 'wp_head', 'ahbn_render_frontend_seo_head', 1 );