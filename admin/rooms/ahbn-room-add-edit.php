<?php
/**
 * Room Editor (Add / Edit) View - Switch Toggles & Dynamic Room Number Mapping.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Room Add/Edit interface.
 *
 * @param int $edit_room_id Optional. Room database record ID to edit. Defaults to 0.
 * @return void
 */
function ahbn_add_edit_room_tab( $edit_room_id = 0 ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	global $wpdb;
	$table_rooms    = $wpdb->prefix . 'ahbn_rooms';
	$table_services = $wpdb->prefix . 'ahbn_services';

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$ahbn_active_id = absint( $edit_room_id );
	if ( 0 === $ahbn_active_id && isset( $_GET['room_id'] ) ) {
		$ahbn_active_id = absint( wp_unslash( $_GET['room_id'] ) );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$currency  = get_option( 'ahbn_currency', '£' );
	$page_slug = 'awesome_hotel_booking';
	$base_url  = admin_url( 'admin.php?page=' . rawurlencode( $page_slug ) . '&tab=rooms' );

	// 1. Fetch Room Inventory Mapping (Classification => Door Numbers)
	$default_inventory = array(
		array( 'name' => 'Standard Room', 'numbers' => '101, 102, 103' ),
		array( 'name' => 'Deluxe King Suite', 'numbers' => '201, 202, 203, 204' ),
		array( 'name' => 'Executive Suite', 'numbers' => '301, 302, 303' ),
		array( 'name' => 'Presidential Villa', 'numbers' => 'V01, V02' ),
	);
	$inventory_map = get_option( 'ahbn_room_inventory_map', $default_inventory );

	// Build relational dictionary for dynamic JS auto-population: { "Deluxe King Suite": ["201", "202", "203", "204"] }
	$room_types_dictionary = array();
	foreach ( $inventory_map as $item ) {
		if ( ! empty( $item['name'] ) ) {
			$nos_arr = array_filter( array_map( 'trim', explode( ',', isset( $item['numbers'] ) ? $item['numbers'] : '' ) ) );
			$room_types_dictionary[ $item['name'] ] = array_values( $nos_arr );
		}
	}

	// Fallback to legacy categories if empty
	if ( empty( $room_types_dictionary ) ) {
		$legacy_types = get_option( 'ahbn_global_types', array( 'Standard Room', 'Deluxe King Suite' ) );
		foreach ( $legacy_types as $lt ) {
			$room_types_dictionary[ $lt ] = array();
		}
	}

	$bedding_types = get_option( 'ahbn_bedding_types', array( '1 King Bed', '2 Queen Beds', '1 Queen Bed', '2 Twin Beds' ) );
	$room_views    = get_option( 'ahbn_room_views', array( 'Oceanfront Panoramic', 'Poolside Walkout', 'Tropical Garden View', 'City View' ) );

	$global_amenities = get_option(
		'ahbn_global_amenities',
		array(
			array( 'name' => 'High-Speed Wi-Fi' ),
			array( 'name' => 'Climate Control AC' ),
			array( 'name' => 'Artisanal Breakfast' ),
			array( 'name' => 'Mini Bar & Espresso Maker' ),
			array( 'name' => 'In-Room Safe Box' ),
			array( 'name' => '4K Ultra-HD Smart TV' ),
		)
	);

	// Fetch Paid Extra Services from wp_ahbn_services
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$db_services = $wpdb->get_results(
		"SELECT id, name, price, charge_type FROM {$table_services} WHERE status = 'publish' ORDER BY id ASC"
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	// Form Submission Processing
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_room_action'] ) ) {
		$nonce = isset( $_POST['ahbn_room_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_room_nonce'] ) ) : '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'ahbn_save_room' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security verification failed. Please try again.', 'awesome-hotel-booking' ) . '</p></div>';
		} else {
			$post_room_id    = isset( $_POST['ahbn_room_id'] ) ? absint( wp_unslash( $_POST['ahbn_room_id'] ) ) : 0;
			$room_name       = isset( $_POST['ahbn_room_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_room_name'] ) ) : '';
			$room_number     = isset( $_POST['ahbn_room_no'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_room_no'] ) ) : '';
			$room_type       = isset( $_POST['ahbn_room_type_select'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_room_type_select'] ) ) : 'Standard Room';
			$bed_type        = isset( $_POST['ahbn_bed_type'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_bed_type'] ) ) : '';
			$room_view       = isset( $_POST['ahbn_room_view'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_room_view'] ) ) : '';
			$room_size       = isset( $_POST['ahbn_room_size'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_room_size'] ) ) : '';
			$bathrooms       = isset( $_POST['ahbn_bathrooms'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_bathrooms'] ) ) : '1';
			$price_per_night = isset( $_POST['ahbn_room_price'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['ahbn_room_price'] ) ) : 0.00;
			$max_adults      = isset( $_POST['ahbn_max_adults'] ) ? absint( wp_unslash( $_POST['ahbn_max_adults'] ) ) : 2;
			$max_children    = isset( $_POST['ahbn_max_children'] ) ? absint( wp_unslash( $_POST['ahbn_max_children'] ) ) : 0;
			$description     = isset( $_POST['ahbn_room_desc'] ) ? wp_kses_post( wp_unslash( $_POST['ahbn_room_desc'] ) ) : '';
			$status          = isset( $_POST['ahbn_status'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_status'] ) ) : 'publish';
			$thumbnail_id    = isset( $_POST['ahbn_room_thumbnail_id'] ) ? absint( wp_unslash( $_POST['ahbn_room_thumbnail_id'] ) ) : 0;
			$raw_gallery     = isset( $_POST['ahbn_room_gallery'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_room_gallery'] ) ) : '';

			$amenities_post = isset( $_POST['room_amenities'] ) && is_array( $_POST['room_amenities'] ) ? array_values( array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['room_amenities'] ) ) ) ) : array();
			$paid_post      = isset( $_POST['room_paid_extras'] ) && is_array( $_POST['room_paid_extras'] ) ? array_values( array_filter( array_map( 'sanitize_text_field', wp_unslash( $_POST['room_paid_extras'] ) ) ) ) : array();

			$gallery_id_arr = array_filter( array_map( 'absint', explode( ',', $raw_gallery ) ) );
			$gallery_str    = implode( ',', $gallery_id_arr );

			if ( empty( $room_name ) ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please provide a room title.', 'awesome-hotel-booking' ) . '</p></div>';
			} else {
				$slug = sanitize_title( $room_name . '-' . ( ! empty( $room_number ) ? $room_number : wp_rand( 100, 999 ) ) );

				$room_data = array(
					'room_number'     => $room_number,
					'title'           => $room_name,
					'slug'            => $slug,
					'description'     => $description,
					'room_type'       => $room_type,
					'price_per_night' => $price_per_night,
					'max_adults'      => max( 1, $max_adults ),
					'max_children'    => $max_children,
					'thumbnail_id'    => $thumbnail_id,
					'gallery'         => $gallery_str,
					'amenities'       => wp_json_encode( $amenities_post ),
					'paid_extras'     => wp_json_encode( $paid_post ),
					'status'          => $status,
				);

				$format = array( '%s', '%s', '%s', '%s', '%s', '%f', '%d', '%d', '%d', '%s', '%s', '%s', '%s' );

				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( $post_room_id > 0 ) {
					$wpdb->update( $table_rooms, $room_data, array( 'id' => $post_room_id ), $format, array( '%d' ) );
					$ahbn_active_id = $post_room_id;
					echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Updated:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Suite record saved successfully!', 'awesome-hotel-booking' ) . '</p></div>';
				} else {
					$wpdb->insert( $table_rooms, $room_data, $format );
					$ahbn_active_id = (int) $wpdb->insert_id;
					echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Published:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'New suite registered to inventory!', 'awesome-hotel-booking' ) . '</p></div>';
				}
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

				update_option( 'ahbn_room_spec_bed_' . $ahbn_active_id, $bed_type );
				update_option( 'ahbn_room_spec_view_' . $ahbn_active_id, $room_view );
				update_option( 'ahbn_room_spec_size_' . $ahbn_active_id, $room_size );
				update_option( 'ahbn_room_spec_bath_' . $ahbn_active_id, $bathrooms );
			}
		}
	}

	// Fetch Data For Current Record
	$curr_title       = '';
	$curr_room_no     = '';
	$curr_room_type   = key( $room_types_dictionary );
	$curr_price       = '220.00';
	$curr_m_adults    = 2;
	$curr_m_children  = 0;
	$curr_description = '';
	$curr_status      = 'publish';
	$curr_thumb_id    = 0;
	$curr_gallery_ids = '';
	$curr_amenities   = array();
	$curr_paid        = array();

	$curr_bed_type  = '';
	$curr_room_view = '';
	$curr_room_size = '450 sq ft / 42 sqm';
	$curr_bathrooms = '1 Marble Bath';

	if ( $ahbn_active_id > 0 ) {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_rooms} WHERE id = %d", $ahbn_active_id ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $record ) {
			$curr_title       = $record->title;
			$curr_room_no     = $record->room_number;
			$curr_room_type   = ! empty( $record->room_type ) ? $record->room_type : $curr_room_type;
			$curr_price       = (float) $record->price_per_night;
			$curr_m_adults    = absint( $record->max_adults );
			$curr_m_children  = absint( $record->max_children );
			$curr_description = $record->description;
			$curr_status      = ! empty( $record->status ) ? $record->status : 'publish';
			$curr_thumb_id    = absint( $record->thumbnail_id );
			$curr_gallery_ids = (string) $record->gallery;

			$curr_amenities = (array) json_decode( (string) $record->amenities, true );
			$curr_paid      = (array) json_decode( (string) $record->paid_extras, true );

			$curr_bed_type  = get_option( 'ahbn_room_spec_bed_' . $ahbn_active_id, '' );
			$curr_room_view = get_option( 'ahbn_room_spec_view_' . $ahbn_active_id, '' );
			$curr_room_size = get_option( 'ahbn_room_spec_size_' . $ahbn_active_id, '' );
			$curr_bathrooms = get_option( 'ahbn_room_spec_bath_' . $ahbn_active_id, '' );
		}
	}

	$thumb_url   = $curr_thumb_id > 0 ? wp_get_attachment_image_url( $curr_thumb_id, 'large' ) : '';
	$cancel_link = add_query_arg( 'sub', 'all', $base_url );
	?>

	<div class="ahbn-editor-root">
		<form method="post" id="ahbn-room-form">
			<?php wp_nonce_field( 'ahbn_save_room', 'ahbn_room_nonce' ); ?>
			<input type="hidden" name="ahbn_save_room_action" value="1">
			<input type="hidden" name="ahbn_room_id" value="<?php echo esc_attr( (string) $ahbn_active_id ); ?>">

			<!-- 1. Header Command Bar -->
			<div class="ahbn-top-command-bar">
				<div>
					<div class="ahbn-folio-pill">
						<?php echo $ahbn_active_id ? sprintf( esc_html__( 'EDITING SUITE #%d', 'awesome-hotel-booking' ), absint( $ahbn_active_id ) ) : esc_html__( 'NEW SUITE REGISTRATION', 'awesome-hotel-booking' ); ?>
					</div>
					<h1 class="ahbn-head-title"><?php echo $ahbn_active_id ? esc_html__( 'Suite Configuration', 'awesome-hotel-booking' ) : esc_html__( 'Add New Luxury Suite', 'awesome-hotel-booking' ); ?></h1>
				</div>

				<div class="ahbn-command-btns">
					<a href="<?php echo esc_url( $cancel_link ); ?>" class="ahbn-btn-ghost">
						<?php esc_html_e( 'Cancel', 'awesome-hotel-booking' ); ?>
					</a>
					<button type="submit" class="ahbn-btn-primary-gold">
						<span class="dashicons dashicons-saved" aria-hidden="true"></span>
						<?php echo $ahbn_active_id ? esc_html__( 'Save Changes', 'awesome-hotel-booking' ) : esc_html__( 'Publish to Ledger', 'awesome-hotel-booking' ); ?>
					</button>
				</div>
			</div>

			<!-- 2. Suite Title Hero Field -->
			<div class="ahbn-hero-title-box">
				<label class="ahbn-micro-tag"><?php esc_html_e( 'Suite / Accommodation Title *', 'awesome-hotel-booking' ); ?></label>
				<input 
					type="text" 
					name="ahbn_room_name" 
					class="ahbn-hero-input" 
					value="<?php echo esc_attr( $curr_title ); ?>" 
					placeholder="<?php esc_attr_e( 'e.g. Royal Oceanfront Penthouse Suite', 'awesome-hotel-booking' ); ?>" 
					required
				>
			</div>

			<!-- 3. Full-Width Segmented Tab Navigation -->
			<div class="ahbn-fullwidth-container">
				<nav class="ahbn-segmented-tabs">
					<button type="button" class="ahbn-tab-btn is-active" data-tab="tab-specs">
						<span class="dashicons dashicons-admin-home"></span> <?php esc_html_e( 'Overview & Specs', 'awesome-hotel-booking' ); ?>
					</button>
					<button type="button" class="ahbn-tab-btn" data-tab="tab-pricing">
						<span class="dashicons dashicons-tag"></span> <?php esc_html_e( 'Pricing & Inventory', 'awesome-hotel-booking' ); ?>
					</button>
					<button type="button" class="ahbn-tab-btn" data-tab="tab-amenities">
						<span class="dashicons dashicons-star-filled"></span> <?php esc_html_e( 'Amenities & Extras', 'awesome-hotel-booking' ); ?>
					</button>
					<button type="button" class="ahbn-tab-btn" data-tab="tab-media">
						<span class="dashicons dashicons-format-image"></span> <?php esc_html_e( 'Media & Gallery', 'awesome-hotel-booking' ); ?>
					</button>
				</nav>

				<div class="ahbn-tab-card">
					
					<!-- TAB 1: OVERVIEW & SPECS -->
					<div id="tab-specs" class="ahbn-tab-panel is-active">
						<div class="ahbn-form-grid-2">
							<div class="ahbn-field">
								<label class="ahbn-field-lbl"><?php esc_html_e( 'Bedding Setup', 'awesome-hotel-booking' ); ?></label>
								<select name="ahbn_bed_type" class="ahbn-select">
									<option value=""><?php esc_html_e( 'Select Bedding', 'awesome-hotel-booking' ); ?></option>
									<?php foreach ( $bedding_types as $bed_opt ) : ?>
										<option value="<?php echo esc_attr( $bed_opt ); ?>" <?php selected( $curr_bed_type, $bed_opt ); ?>><?php echo esc_html( $bed_opt ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="ahbn-field">
								<label class="ahbn-field-lbl"><?php esc_html_e( 'View Orientation', 'awesome-hotel-booking' ); ?></label>
								<select name="ahbn_room_view" class="ahbn-select">
									<option value=""><?php esc_html_e( 'Select View', 'awesome-hotel-booking' ); ?></option>
									<?php foreach ( $room_views as $view_opt ) : ?>
										<option value="<?php echo esc_attr( $view_opt ); ?>" <?php selected( $curr_room_view, $view_opt ); ?>><?php echo esc_html( $view_opt ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="ahbn-form-grid-2" style="margin-top:18px;">
							<div class="ahbn-field">
								<label class="ahbn-field-lbl"><?php esc_html_e( 'Floor Area / Dimensions', 'awesome-hotel-booking' ); ?></label>
								<input type="text" name="ahbn_room_size" class="ahbn-input" value="<?php echo esc_attr( $curr_room_size ); ?>" placeholder="e.g. 520 sq ft / 48 sqm">
							</div>
							<div class="ahbn-field">
								<label class="ahbn-field-lbl"><?php esc_html_e( 'Bathrooms Configuration', 'awesome-hotel-booking' ); ?></label>
								<input type="text" name="ahbn_bathrooms" class="ahbn-input" value="<?php echo esc_attr( $curr_bathrooms ); ?>" placeholder="e.g. 1.5 Baths (Marble Soaking Tub)">
							</div>
						</div>

						<div class="ahbn-field" style="margin-top: 22px;">
							<label class="ahbn-field-lbl"><?php esc_html_e( 'Marketing Description & Highlights', 'awesome-hotel-booking' ); ?></label>
							<?php
							wp_editor(
								$curr_description,
								'ahbn_room_desc',
								array(
									'textarea_rows' => 12,
									'media_buttons' => true,
									'teeny'         => true,
									'quicktags'     => true,
								)
							);
							?>
						</div>
					</div>

					<!-- TAB 2: PRICING & INVENTORY (WITH DYNAMIC ROOM NUMBER SYNC) -->
					<div id="tab-pricing" class="ahbn-tab-panel">
						<div class="ahbn-form-grid-3">
							
							<!-- 1. Classification (Primary Trigger) -->
							<div class="ahbn-field">
								<label class="ahbn-field-lbl"><?php esc_html_e( '1. Suite Classification *', 'awesome-hotel-booking' ); ?></label>
								<select name="ahbn_room_type_select" id="ahbn_room_type_select" class="ahbn-select">
									<?php foreach ( array_keys( $room_types_dictionary ) as $type_opt ) : ?>
										<option value="<?php echo esc_attr( $type_opt ); ?>" <?php selected( $curr_room_type, $type_opt ); ?>>
											<?php echo esc_html( $type_opt ); ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<!-- 2. Dynamic Room Number Dropdown (Auto-updates on Classification change) -->
							<div class="ahbn-field">
								<div style="display:flex; justify-content:space-between; align-items:center;">
									<label class="ahbn-field-lbl" for="ahbn_room_no_select"><?php esc_html_e( '2. Assigned Door # *', 'awesome-hotel-booking' ); ?></label>
									<span id="ahbn_no_count_badge" class="ahbn-pill-badge">0 available</span>
								</div>
								
								<div class="ahbn-room-no-combo">
									<select id="ahbn_room_no_select" class="ahbn-select font-mono">
										<!-- Populated Dynamically via JavaScript -->
									</select>
									<!-- Actual field submitted to DB table -->
									<input 
										type="text" 
										name="ahbn_room_no" 
										id="ahbn_room_no_input" 
										class="ahbn-input font-mono" 
										value="<?php echo esc_attr( $curr_room_no ); ?>" 
										placeholder="e.g. 101"
										title="Selected Door Number"
										required
									>
								</div>
								<span class="ahbn-hint"><?php esc_html_e( 'Select from assigned inventory numbers or type a custom unit.', 'awesome-hotel-booking' ); ?></span>
							</div>

							<!-- 3. Nightly Rate -->
							<div class="ahbn-field">
								<label class="ahbn-field-lbl"><?php printf( esc_html__( '3. Nightly Base Rate (%s) *', 'awesome-hotel-booking' ), esc_html( $currency ) ); ?></label>
								<div class="ahbn-currency-field">
									<span class="ahbn-prefix"><?php echo esc_html( $currency ); ?></span>
									<input type="number" step="0.01" min="0" name="ahbn_room_price" class="ahbn-input font-mono" value="<?php echo esc_attr( (string) $curr_price ); ?>" required>
								</div>
							</div>
						</div>

						<div class="ahbn-form-grid-3" style="margin-top:22px;">
							<div class="ahbn-field">
								<label class="ahbn-field-lbl"><?php esc_html_e( 'Max Adults', 'awesome-hotel-booking' ); ?></label>
								<div class="ahbn-counter-box">
									<button type="button" class="ahbn-count-btn" data-act="minus">-</button>
									<input type="number" min="1" max="20" name="ahbn_max_adults" class="ahbn-count-val font-mono" value="<?php echo esc_attr( (string) $curr_m_adults ); ?>">
									<button type="button" class="ahbn-count-btn" data-act="plus">+</button>
								</div>
							</div>
							<div class="ahbn-field">
								<label class="ahbn-field-lbl"><?php esc_html_e( 'Max Children', 'awesome-hotel-booking' ); ?></label>
								<div class="ahbn-counter-box">
									<button type="button" class="ahbn-count-btn" data-act="minus">-</button>
									<input type="number" min="0" max="10" name="ahbn_max_children" class="ahbn-count-val font-mono" value="<?php echo esc_attr( (string) $curr_m_children ); ?>">
									<button type="button" class="ahbn-count-btn" data-act="plus">+</button>
								</div>
							</div>
							<div class="ahbn-field">
								<label class="ahbn-field-lbl"><?php esc_html_e( 'Inventory Status', 'awesome-hotel-booking' ); ?></label>
								<select name="ahbn_status" class="ahbn-select">
									<option value="publish" <?php selected( $curr_status, 'publish' ); ?>><?php esc_html_e( 'Published (Live & Bookable)', 'awesome-hotel-booking' ); ?></option>
									<option value="draft" <?php selected( $curr_status, 'draft' ); ?>><?php esc_html_e( 'Draft (Under Maintenance)', 'awesome-hotel-booking' ); ?></option>
								</select>
							</div>
						</div>
					</div>

					<!-- TAB 3: AMENITIES & EXTRAS (ALL CHECKBOXES AS LUXURY SWITCH TOGGLES) -->
					<div id="tab-amenities" class="ahbn-tab-panel">
						<span class="ahbn-section-subhead"><?php esc_html_e( 'Standard In-Suite Amenities (Complimentary)', 'awesome-hotel-booking' ); ?></span>
						<p class="ahbn-micro-desc"><?php esc_html_e( 'Toggle switches on to attach amenities to this suite.', 'awesome-hotel-booking' ); ?></p>
						
						<div class="ahbn-toggle-grid">
							<?php if ( ! empty( $global_amenities ) ) : ?>
								<?php foreach ( $global_amenities as $ahbn_a ) :
									$aname = isset( $ahbn_a['name'] ) ? $ahbn_a['name'] : '';
									if ( empty( $aname ) ) continue; ?>
									<div class="ahbn-switch-row">
										<span class="ahbn-switch-title"><?php echo esc_html( $aname ); ?></span>
										<label class="ahbn-switch">
											<input type="checkbox" name="room_amenities[]" value="<?php echo esc_attr( $aname ); ?>" <?php checked( in_array( $aname, $curr_amenities, true ) ); ?>>
											<span class="ahbn-switch-slider"></span>
										</label>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>

						<hr style="border:0; border-top:1px solid #f1f5f9; margin: 26px 0 22px;">

						<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px;">
							<div>
								<span class="ahbn-section-subhead"><?php esc_html_e( 'Paid Extra Add-ons (Checkout Upsells)', 'awesome-hotel-booking' ); ?></span>
								<p class="ahbn-micro-desc"><?php esc_html_e( 'Connected live from database table wp_ahbn_services.', 'awesome-hotel-booking' ); ?></p>
							</div>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=awesome_hotel_booking&tab=services' ) ); ?>" target="_blank" class="ahbn-manage-link">
								<?php esc_html_e( 'Manage Services Table', 'awesome-hotel-booking' ); ?> &rarr;
							</a>
						</div>

						<div class="ahbn-toggle-grid">
							<?php if ( ! empty( $db_services ) ) : ?>
								<?php foreach ( $db_services as $s_row ) :
									$cadence = ! empty( $s_row->charge_type ) ? str_replace( '_', ' / ', $s_row->charge_type ) : 'stay'; ?>
									<div class="ahbn-switch-row">
										<div class="ahbn-switch-meta">
											<span class="ahbn-switch-title"><?php echo esc_html( $s_row->name ); ?></span>
											<span class="ahbn-extra-price">
												+<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( (float) $s_row->price ) : $currency . number_format( (float) $s_row->price, 2 ) ); ?> 
												<small style="color:#64748b; font-size:10px;">(<?php echo esc_html( $cadence ); ?>)</small>
											</span>
										</div>
										<label class="ahbn-switch">
											<input type="checkbox" name="room_paid_extras[]" value="<?php echo esc_attr( $s_row->name ); ?>" <?php checked( in_array( $s_row->name, $curr_paid, true ) ); ?>>
											<span class="ahbn-switch-slider"></span>
										</label>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>

					<!-- TAB 4: MEDIA & GALLERY -->
					<div id="tab-media" class="ahbn-tab-panel">
						<input type="hidden" name="ahbn_room_thumbnail_id" id="ahbn_thumb_id" value="<?php echo esc_attr( (string) $curr_thumb_id ); ?>">
						<input type="hidden" name="ahbn_room_gallery" id="ahbn_gal_val" value="<?php echo esc_attr( $curr_gallery_ids ); ?>">

						<label class="ahbn-field-lbl"><?php esc_html_e( 'Hero Featured Photograph', 'awesome-hotel-booking' ); ?></label>
						<div class="ahbn-uploader-dropzone" id="ahbn_set_thumb">
							<?php if ( $curr_thumb_id > 0 && ! empty( $thumb_url ) ) : ?>
								<img src="<?php echo esc_url( $thumb_url ); ?>" id="ahbn_thumb_img" alt="">
								<span class="ahbn-dropzone-badge"><span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Change Image', 'awesome-hotel-booking' ); ?></span>
							<?php else : ?>
								<div class="ahbn-drop-prompt">
									<span class="dashicons dashicons-format-image"></span>
									<strong><?php esc_html_e( 'Click to browse media library', 'awesome-hotel-booking' ); ?></strong>
									<p><?php esc_html_e( 'Recommended resolution: 1600 × 1000px JPG/WebP', 'awesome-hotel-booking' ); ?></p>
								</div>
							<?php endif; ?>
						</div>

						<button type="button" id="ahbn_remove_thumb_btn" class="ahbn-btn-text-danger" style="<?php echo $curr_thumb_id > 0 ? 'display:inline-flex;' : 'display:none;'; ?>">
							<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Remove Featured Photo', 'awesome-hotel-booking' ); ?>
						</button>

						<hr style="border:0; border-top:1px solid #f1f5f9; margin: 26px 0 22px;">

						<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
							<label class="ahbn-field-lbl" style="margin:0;"><?php esc_html_e( 'Additional Gallery Carousel Photos', 'awesome-hotel-booking' ); ?></label>
							<button type="button" class="ahbn-btn-secondary-sm" id="ahbn_add_gal">
								<span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add Photos', 'awesome-hotel-booking' ); ?>
							</button>
						</div>

						<div class="ahbn-gallery-flex" id="ahbn_gal_preview">
							<?php 
							if ( ! empty( $curr_gallery_ids ) ) {
								$g_ids = array_filter( array_map( 'absint', explode( ',', $curr_gallery_ids ) ) );
								foreach ( $g_ids as $gid ) {
									$g_url = wp_get_attachment_thumb_url( $gid );
									if ( $g_url ) {
										printf(
											'<div class="ahbn-gal-thumb" data-id="%1$d">' .
												'<span class="ahbn-gal-del">&times;</span>' .
												'<img src="%2$s" alt="">' .
											'</div>',
											absint( $gid ),
											esc_url( $g_url )
										);
									}
								}
							}
							?>
						</div>
					</div>

				</div>

				<!-- Sticky Footer Action Bar -->
				<div class="ahbn-sticky-bottom-actions">
					<a href="<?php echo esc_url( $cancel_link ); ?>" class="ahbn-btn-ghost">
						<?php esc_html_e( 'Discard / Back', 'awesome-hotel-booking' ); ?>
					</a>
					<button type="submit" class="ahbn-btn-primary-gold">
						<span class="dashicons dashicons-saved" aria-hidden="true"></span>
						<?php echo $ahbn_active_id ? esc_html__( 'Save Changes', 'awesome-hotel-booking' ) : esc_html__( 'Publish Suite to Inventory', 'awesome-hotel-booking' ); ?>
					</button>
				</div>
			</div>
		</form>
	</div>

	<style>
		.ahbn-editor-root {
			--ahbn-navy: #0a192f;
			--ahbn-gold: #c5a880;
			--ahbn-gold-hover: #b39264;
			max-width: 1140px;
			margin: 20px auto 50px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
			box-sizing: border-box;
		}

		.ahbn-editor-root * { box-sizing: border-box; }

		/* Header Command Bar */
		.ahbn-top-command-bar {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 20px;
			flex-wrap: wrap;
			gap: 16px;
		}

		.ahbn-folio-pill {
			font-family: 'JetBrains Mono', monospace;
			font-size: 11px;
			font-weight: 800;
			color: var(--ahbn-gold);
			background: var(--ahbn-navy);
			padding: 4px 10px;
			border-radius: 6px;
			display: inline-block;
			margin-bottom: 4px;
		}

		.ahbn-head-title {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 26px;
			font-weight: 700;
			color: var(--ahbn-navy);
			margin: 0;
		}

		.ahbn-command-btns {
			display: flex;
			gap: 10px;
		}

		.ahbn-btn-ghost {
			padding: 10px 20px;
			border: 1px solid #cbd5e1;
			background: #ffffff;
			color: #475569;
			border-radius: 8px;
			font-weight: 700;
			font-size: 13px;
			text-decoration: none;
			transition: all 0.2s ease;
		}

		.ahbn-btn-ghost:hover { background: #f1f5f9; color: #0a192f; }

		.ahbn-btn-primary-gold {
			padding: 10px 24px;
			border: 1px solid var(--ahbn-navy);
			background: var(--ahbn-navy);
			color: #ffffff;
			border-radius: 8px;
			font-weight: 700;
			font-size: 13px;
			cursor: pointer;
			display: inline-flex;
			align-items: center;
			gap: 6px;
			transition: all 0.2s ease;
		}

		.ahbn-btn-primary-gold .dashicons { font-size: 16px; width: 16px; height: 16px; color: var(--ahbn-gold); }

		.ahbn-btn-primary-gold:hover {
			background: var(--ahbn-gold);
			border-color: var(--ahbn-gold);
			color: var(--ahbn-navy);
		}

		.ahbn-btn-primary-gold:hover .dashicons { color: var(--ahbn-navy); }

		/* Hero Title Field */
		.ahbn-hero-title-box {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 18px 24px;
			margin-bottom: 24px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-micro-tag {
			font-size: 11px;
			font-weight: 800;
			text-transform: uppercase;
			color: #64748b;
			letter-spacing: 0.05em;
			display: block;
			margin-bottom: 6px;
		}

		.ahbn-hero-input {
			width: 100% !important;
			border: none !important;
			outline: none !important;
			font-family: 'Playfair Display', Georgia, serif !important;
			font-size: 24px !important;
			font-weight: 700 !important;
			color: var(--ahbn-navy) !important;
			padding: 0 !important;
			box-shadow: none !important;
			background: transparent !important;
		}

		/* Segmented Tabs */
		.ahbn-segmented-tabs {
			display: flex;
			gap: 6px;
			background: #f1f5f9;
			padding: 6px;
			border-radius: 12px;
			margin-bottom: 16px;
			overflow-x: auto;
		}

		.ahbn-tab-btn {
			flex: 1;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 6px;
			border: none;
			background: transparent;
			padding: 11px 16px;
			font-size: 13px;
			font-weight: 700;
			color: #64748b;
			border-radius: 8px;
			cursor: pointer;
			white-space: nowrap;
			transition: all 0.2s ease;
		}

		.ahbn-tab-btn .dashicons { font-size: 16px; width: 16px; height: 16px; }

		.ahbn-tab-btn.is-active {
			background: #ffffff;
			color: var(--ahbn-navy);
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
		}

		.ahbn-tab-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			padding: 26px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-tab-panel { display: none; }
		.ahbn-tab-panel.is-active { display: block; }

		/* Form Inputs & Grids */
		.ahbn-form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
		.ahbn-form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 18px; }

		.ahbn-field { display: flex; flex-direction: column; gap: 6px; }
		.ahbn-field-lbl { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #334155; }
		.ahbn-hint { font-size: 11px; color: #64748b; margin-top: 2px; }
		.ahbn-section-subhead { font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color: var(--ahbn-navy); display: block; }
		.ahbn-micro-desc { font-size: 12px; color: #64748b; margin: 2px 0 14px; }

		.ahbn-pill-badge {
			font-family: 'JetBrains Mono', monospace;
			font-size: 10px;
			background: #eff6ff;
			color: #1e40af;
			padding: 2px 6px;
			border-radius: 4px;
			font-weight: 700;
		}

		.ahbn-room-no-combo {
			display: grid;
			grid-template-columns: 1.2fr 0.8fr;
			gap: 8px;
		}

		.ahbn-input,
		.ahbn-select {
			width: 100%;
			height: 40px !important;
			border: 1px solid #cbd5e1 !important;
			border-radius: 8px !important;
			padding: 0 12px !important;
			font-size: 13px !important;
			background: #ffffff !important;
			color: #0f172a !important;
			box-shadow: none !important;
		}

		.ahbn-input:focus,
		.ahbn-select:focus { border-color: var(--ahbn-navy) !important; box-shadow: 0 0 0 2px rgba(10, 25, 47, 0.1) !important; }

		.font-mono { font-family: 'JetBrains Mono', monospace !important; font-weight: 700; }

		.ahbn-currency-field {
			display: flex;
			align-items: center;
		}

		.ahbn-currency-field .ahbn-prefix {
			background: #f8fafc;
			border: 1px solid #cbd5e1;
			border-right: none;
			padding: 0 12px;
			height: 40px;
			display: flex;
			align-items: center;
			font-size: 14px;
			font-weight: 800;
			color: #475569;
			border-radius: 8px 0 0 8px;
		}

		.ahbn-currency-field .ahbn-input {
			border-radius: 0 8px 8px 0 !important;
			color: #059669 !important;
			font-size: 15px !important;
		}

		.ahbn-counter-box {
			display: flex;
			border: 1px solid #cbd5e1;
			border-radius: 8px;
			overflow: hidden;
			height: 40px;
		}

		.ahbn-count-btn {
			width: 40px;
			border: none;
			background: #f8fafc;
			font-size: 18px;
			font-weight: 700;
			cursor: pointer;
			color: #475569;
			transition: background 0.15s;
		}

		.ahbn-count-btn:hover { background: #e2e8f0; color: #0a192f; }

		.ahbn-count-val {
			flex: 1;
			text-align: center;
			border: none !important;
			box-shadow: none !important;
			font-size: 14px !important;
		}

		/* ========================================================
		   ENTERPRISE iOS / LUXURY TOGGLE SWITCHES
		   ======================================================== */
		.ahbn-toggle-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
			gap: 12px;
		}

		.ahbn-switch-row {
			display: flex;
			justify-content: space-between;
			align-items: center;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 10px;
			padding: 12px 16px;
			gap: 12px;
			transition: all 0.2s ease;
		}

		.ahbn-switch-row:hover {
			border-color: #cbd5e1;
			background: #ffffff;
			box-shadow: 0 2px 6px rgba(10, 25, 47, 0.03);
		}

		.ahbn-switch-title {
			font-size: 13px;
			font-weight: 700;
			color: #1e293b;
		}

		.ahbn-switch-meta {
			display: flex;
			flex-direction: column;
			gap: 2px;
		}

		.ahbn-extra-price {
			font-family: 'JetBrains Mono', monospace;
			font-size: 11px;
			font-weight: 800;
			color: #059669;
		}

		.ahbn-manage-link {
			font-size: 11px;
			font-weight: 700;
			color: var(--ahbn-gold);
			text-decoration: none;
		}

		.ahbn-manage-link:hover { text-decoration: underline; }

		/* Switch Anatomy */
		.ahbn-switch {
			position: relative;
			display: inline-block;
			width: 44px;
			height: 24px;
			flex-shrink: 0;
			margin: 0;
		}

		.ahbn-switch input {
			opacity: 0;
			width: 0;
			height: 0;
			position: absolute;
		}

		.ahbn-switch-slider {
			position: absolute;
			cursor: pointer;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background-color: #cbd5e1;
			transition: 0.25s cubic-bezier(0.16, 1, 0.3, 1);
			border-radius: 99px;
		}

		.ahbn-switch-slider:before {
			position: absolute;
			content: "";
			height: 18px;
			width: 18px;
			left: 3px;
			bottom: 3px;
			background-color: #ffffff;
			transition: 0.25s cubic-bezier(0.16, 1, 0.3, 1);
			border-radius: 50%;
			box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
		}

		.ahbn-switch input:checked + .ahbn-switch-slider {
			background-color: var(--ahbn-navy);
		}

		.ahbn-switch input:checked + .ahbn-switch-slider:before {
			transform: translateX(20px);
			background-color: var(--ahbn-gold);
		}

		/* Media Uploaders */
		.ahbn-uploader-dropzone {
			border: 2px dashed #cbd5e1;
			border-radius: 12px;
			background: #f8fafc;
			min-height: 200px;
			display: flex;
			align-items: center;
			justify-content: center;
			cursor: pointer;
			position: relative;
			overflow: hidden;
			transition: all 0.2s;
		}

		.ahbn-uploader-dropzone:hover { border-color: var(--ahbn-navy); background: #ffffff; }
		.ahbn-uploader-dropzone img { width: 100%; max-height: 300px; object-fit: cover; }

		.ahbn-drop-prompt { text-align: center; color: #64748b; padding: 20px; }
		.ahbn-drop-prompt .dashicons { font-size: 44px; width: 44px; height: 44px; color: #cbd5e1; margin-bottom: 6px; }
		.ahbn-drop-prompt strong { display: block; font-size: 14px; color: var(--ahbn-navy); }
		.ahbn-drop-prompt p { margin: 4px 0 0; font-size: 12px; color: #94a3b8; }

		.ahbn-dropzone-badge {
			position: absolute;
			bottom: 14px;
			right: 14px;
			background: rgba(10, 25, 47, 0.85);
			color: #ffffff;
			padding: 6px 12px;
			border-radius: 6px;
			font-size: 11px;
			font-weight: 700;
			backdrop-filter: blur(4px);
		}

		.ahbn-btn-text-danger {
			background: none;
			border: none;
			color: #e11d48;
			font-size: 11px;
			font-weight: 700;
			cursor: pointer;
			margin-top: 8px;
			padding: 0;
			display: inline-flex;
			align-items: center;
			gap: 4px;
		}

		.ahbn-btn-secondary-sm {
			border: 1px solid #cbd5e1;
			background: #ffffff;
			border-radius: 6px;
			padding: 5px 12px;
			font-size: 12px;
			font-weight: 700;
			color: #334155;
			cursor: pointer;
		}

		.ahbn-gallery-flex {
			display: flex;
			gap: 12px;
			flex-wrap: wrap;
		}

		.ahbn-gal-thumb {
			width: 90px;
			height: 90px;
			border-radius: 8px;
			overflow: hidden;
			position: relative;
			border: 1px solid #e2e8f0;
		}

		.ahbn-gal-thumb img { width: 100%; height: 100%; object-fit: cover; }

		.ahbn-gal-del {
			position: absolute;
			top: 3px;
			right: 3px;
			background: rgba(15, 23, 42, 0.8);
			color: #ffffff;
			width: 20px;
			height: 20px;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			font-size: 13px;
			cursor: pointer;
		}

		/* Bottom Sticky Action Bar */
		.ahbn-sticky-bottom-actions {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 16px 24px;
			margin-top: 20px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			box-shadow: 0 4px 14px rgba(10, 25, 47, 0.04);
		}

		@media (max-width: 850px) {
			.ahbn-form-grid-3,
			.ahbn-form-grid-2 {
				grid-template-columns: 1fr;
			}
			.ahbn-room-no-combo {
				grid-template-columns: 1fr;
			}
		}
	</style>

	<script>
	jQuery(document).ready(function($) {
		// 1. Tab Switching Logic
		$('.ahbn-tab-btn').on('click', function(e) {
			e.preventDefault();
			const target = $(this).data('tab');
			$('.ahbn-tab-btn').removeClass('is-active');
			$(this).addClass('is-active');
			$('.ahbn-tab-panel').removeClass('is-active');
			$('#' + target).addClass('is-active');
		});

		// 2. Dynamic Room Number Syncing on Classification Change
		const inventoryMap = <?php echo wp_json_encode( $room_types_dictionary ); ?>;
		const $typeSelect  = $('#ahbn_room_type_select');
		const $noSelect    = $('#ahbn_room_no_select');
		const $noInput     = $('#ahbn_room_no_input');
		const $badge       = $('#ahbn_no_count_badge');

		function updateRoomNumbersForType(selectedType, preserveValue) {
			const numbers = inventoryMap[selectedType] || [];
			$noSelect.empty();

			if (numbers.length > 0) {
				$badge.text(numbers.length + ' registered').css('background', '#eff6ff').css('color', '#1e40af');
				$noSelect.append($('<option/>', { value: '', text: '— Select Assigned Door # —' }));
				
				$.each(numbers, function(idx, no) {
					const $opt = $('<option/>', { value: no, text: 'Room ' + no });
					if (preserveValue && preserveValue === no) {
						$opt.prop('selected', true);
					}
					$noSelect.append($opt);
				});

				// Option for custom entry
				$noSelect.append($('<option/>', { value: '_custom_', text: '+ Type Custom Room #' }));
			} else {
				$badge.text('No door #s found').css('background', '#fff1f2').css('color', '#9f1239');
				$noSelect.append($('<option/>', { value: '_custom_', text: 'Type custom room number...' }));
			}

			// If current input value matches an available door number, select it
			if (preserveValue && numbers.indexOf(preserveValue) !== -1) {
				$noSelect.val(preserveValue);
			}
		}

		// Initial Run on Page Load
		const initialType = $typeSelect.val();
		const currentSavedNo = $noInput.val();
		updateRoomNumbersForType(initialType, currentSavedNo);

		// When Classification changes: update dropdown and auto-select first available number
		$typeSelect.on('change', function() {
			const selectedType = $(this).val();
			updateRoomNumbersForType(selectedType, false);
			
			const availableNos = inventoryMap[selectedType] || [];
			if (availableNos.length > 0) {
				$noSelect.val(availableNos[0]);
				$noInput.val(availableNos[0]);
			}
		});

		// When Room Number dropdown is changed: sync with input field
		$noSelect.on('change', function() {
			const val = $(this).val();
			if (val === '_custom_') {
				$noInput.val('').focus();
			} else if (val !== '') {
				$noInput.val(val);
			}
		});

		// 3. Counter Controls (+/- buttons)
		$('.ahbn-count-btn').on('click', function() {
			const act = $(this).data('act');
			const $input = $(this).siblings('.ahbn-count-val');
			let val = parseInt($input.val(), 10) || 0;
			const min = parseInt($input.attr('min'), 10) || 0;
			const max = parseInt($input.attr('max'), 10) || 20;

			if (act === 'plus' && val < max) {
				$input.val(val + 1);
			} else if (act === 'minus' && val > min) {
				$input.val(val - 1);
			}
		});

		// 4. Featured Image via WP Media API
		let thumbFrame;
		$('#ahbn_set_thumb').on('click', function(e) {
			e.preventDefault();
			if (thumbFrame) { thumbFrame.open(); return; }

			thumbFrame = wp.media({
				title: '<?php echo esc_js( __( 'Select Suite Hero Image', 'awesome-hotel-booking' ) ); ?>',
				button: { text: '<?php echo esc_js( __( 'Set Suite Image', 'awesome-hotel-booking' ) ); ?>' },
				multiple: false
			}).on('select', function() {
				const att = thumbFrame.state().get('selection').first().toJSON();
				$('#ahbn_thumb_id').val(att.id);
				$('#ahbn_set_thumb').html(
					'<img src="' + encodeURI(att.url) + '" id="ahbn_thumb_img" alt="">' +
					'<span class="ahbn-dropzone-badge"><span class="dashicons dashicons-update"></span> <?php echo esc_js( __( 'Change Image', 'awesome-hotel-booking' ) ); ?></span>'
				);
				$('#ahbn_remove_thumb_btn').show();
			});
			thumbFrame.open();
		});

		$('#ahbn_remove_thumb_btn').on('click', function(e) {
			e.preventDefault();
			$('#ahbn_thumb_id').val(0);
			$('#ahbn_set_thumb').html(
				'<div class="ahbn-drop-prompt"><span class="dashicons dashicons-format-image"></span><strong><?php echo esc_js( __( 'Click to browse media library', 'awesome-hotel-booking' ) ); ?></strong><p><?php echo esc_js( __( 'Recommended resolution: 1600 × 1000px JPG/WebP', 'awesome-hotel-booking' ) ); ?></p></div>'
			);
			$(this).hide();
		});

		// 5. Gallery Carousel Uploader
		let galFrame;
		$('#ahbn_add_gal').on('click', function(e) {
			e.preventDefault();
			if (galFrame) { galFrame.open(); return; }

			galFrame = wp.media({
				title: '<?php echo esc_js( __( 'Add Suite Gallery Images', 'awesome-hotel-booking' ) ); ?>',
				button: { text: '<?php echo esc_js( __( 'Add to Carousel', 'awesome-hotel-booking' ) ); ?>' },
				multiple: true
			}).on('select', function() {
				const selection = galFrame.state().get('selection');
				let ids = $('#ahbn_gal_val').val() ? $('#ahbn_gal_val').val().split(',') : [];

				selection.each(function(model) {
					const att = model.toJSON();
					const attId = att.id.toString();
					if ($.inArray(attId, ids) === -1) {
						ids.push(attId);
						const imgUrl = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
						$('#ahbn_gal_preview').append(
							$('<div/>', { class: 'ahbn-gal-thumb', 'data-id': attId })
								.append($('<span/>', { class: 'ahbn-gal-del', text: '×' }))
								.append($('<img/>', { src: imgUrl }))
						);
					}
				});
				$('#ahbn_gal_val').val(ids.join(','));
			});
			galFrame.open();
		});

		$(document).on('click', '.ahbn-gal-del', function(e) {
			e.preventDefault();
			const $item = $(this).closest('.ahbn-gal-thumb');
			const id    = $item.data('id').toString();
			const ids   = $('#ahbn_gal_val').val().split(',').filter(function(v) { return v !== id && v !== ''; });
			$('#ahbn_gal_val').val(ids.join(','));
			$item.fadeOut(150, function() { $(this).remove(); });
		});
	});
	</script>
	<?php
}