<?php
/**
 * Global Room Types, Numbers, Amenities, Paid Extras & Property Configuration.
 *
 * Built with the Champagne Gold & Deep Navy enterprise UI system.
 * Manages taxonomy repeaters: Unified Room Classification + Room Numbers, SVG Amenities,
 * Paid Extras, Bedding Types, Room Views, and Operational Policies.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the SVG icon definitions used across the extras manager.
 *
 * @return array Associative array of raw SVG strings.
 */
function ahbn_get_svg_icons() {
	return array(
		'wifi'      => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><path d="M12 21l-8.2-10.3c.4-.4 4.8-3.7 8.2-3.7s7.8 3.3 8.2 3.7L12 21z"/></svg>',
		'ac'        => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><path d="M19 19H5V5h14v14zM3 3v18h18V3H3zm8 4h2v10h-2V7z"/></svg>',
		'breakfast' => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><path d="M11 9H9V2H7v7H5V2H3v7c0 2.1 1.6 3.8 3.7 4l-1.1 8h4.8l-1.1-8c2.1-.2 3.7-1.9 3.7-4V2h-2v7zm10-7v13h-3v7h-2v-7h-3V2c0 2 2 4 4 4s4-2 4-4z"/></svg>',
		'bar'       => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><path d="M21 5V3H3v2l8 9v5H6v2h12v-2h-5v-5l8-9zM7.4 5h9.2l-1.8 2H9.2L7.4 5z"/></svg>',
		'parking'   => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><path d="M13 3H6v18h4v-6h3c3.3 0 6-2.7 6-6s-2.7-6-6-6zm0 8h-3V7h3c1.1 0 2 .9 2 2s-.9 2-2 2z"/></svg>',
		'bed'       => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><path d="M20 10V7a2 2 0 00-2-2H6a2 2 0 00-2 2v3a2 2 0 00-2 2v5h1.3l.7 2h2l.7-2h10.6l.7 2h2l.7-2H22v-5a2 2 0 00-2-2zm-3-3v3H7V7h10z"/></svg>',
		'shuttle'   => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><path d="M18 11h-1.1c-.4-1.1-1.4-2-2.6-2-1.2 0-2.2.9-2.6 2h-1.4c-.4-1.1-1.4-2-2.6-2-1.2 0-2.2.9-2.6 2H4c-1.1 0-2 .9-2 2v4h2c0 1.7 1.3 3 3 3s3-1.3 3-3h4c0 1.7 1.3 3 3 3s3-1.3 3-3h2v-5c0-1.1-.9-2-2-2zm-11 7c-.6 0-1-.4-1-1s.4-1 1-1 1 .4 1 1-.4 1-1 1zm10 0c-.6 0-1-.4-1-1s.4-1 1-1 1 .4 1 1-.4 1-1 1z"/></svg>',
		'spa'       => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><path d="M12 2c-4 4-6 8-6 11 0 3.3 2.7 6 6 6s6-2.7 6-6c0-3-2-7-6-11zm0 15c-2.2 0-4-1.8-4-4 0-2 1.3-4.7 4-7.4 2.7 2.7 4 5.4 4 7.4 0 2.2-1.8 4-4 4z"/></svg>',
		'tv'        => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><path d="M21 3H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h5v2h8v-2h5c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 14H3V5h18v12z"/></svg>',
		'pool'      => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><path d="M2 19c2 0 2-1 4-1s2 1 4 1 2-1 4-1 2 1 4 1 2-1 4-1v2c-2 0-2 1-4 1s-2-1-4-1-2 1-4 1-2-1-4-1-2 1-4 1v-2zm0-4c2 0 2-1 4-1s2 1 4 1 2-1 4-1 2 1 4 1 2-1 4-1v2c-2 0-2 1-4 1s-2-1-4-1-2 1-4 1-2-1-4-1-2 1-4 1v-2zM18.5 3A2.5 2.5 0 0016 5.5v3.1l-2.6 2.6c-.4.4-.4 1 0 1.4.4.4 1 .4 1.4 0l2.7-2.7c.3-.3.5-.7.5-1.1V5.5c0-.3.2-.5.5-.5s.5.2.5.5v5.8c0 1.2-.5 2.4-1.4 3.2l-.7.7c-.4.4-.4 1 0 1.4.4.4 1 .4 1.4 0l.7-.7c1.3-1.2 2-2.9 2-4.6V5.5A2.5 2.5 0 0018.5 3z"/></svg>',
	);
}

/**
 * Safely render an SVG icon using allowed tags and attributes.
 *
 * @param string $icon_key Key corresponding to icon definition.
 * @return string Safe HTML SVG.
 */
function ahbn_render_svg_icon( $icon_key ) {
	$icons = ahbn_get_svg_icons();
	$svg   = isset( $icons[ $icon_key ] ) ? $icons[ $icon_key ] : $icons['wifi'];

	$allowed_tags = array(
		'svg'  => array(
			'viewbox' => true,
			'viewBox' => true,
			'xmlns'   => true,
			'width'   => true,
			'height'  => true,
			'fill'    => true,
		),
		'path' => array(
			'd'    => true,
			'fill' => true,
		),
	);

	return wp_kses( $svg, $allowed_tags );
}

/**
 * Render Extras, Policies, and Settings Manager.
 *
 * @return void
 */
function ahbn_rooms_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	$svg_lib  = ahbn_get_svg_icons();
	$currency = get_option( 'ahbn_currency', '£' );

	// 1. Save Logic.
	if ( isset( $_POST['ahbn_save_extras'] ) ) {
		check_admin_referer( 'ahbn_save_extras_action', 'ahbn_extras_nonce' );

		// 1.1 Combined Room Types and Numbers.
		$clean_rooms_map = array();
		$clean_types     = array();
		$clean_all_nos   = array();

		if ( isset( $_POST['room_inventory'] ) && is_array( $_POST['room_inventory'] ) ) {
			$raw_inventory = wp_unslash( $_POST['room_inventory'] );
			foreach ( $raw_inventory as $item ) {
				$name    = isset( $item['name'] ) ? sanitize_text_field( $item['name'] ) : '';
				$numbers = isset( $item['numbers'] ) ? sanitize_text_field( $item['numbers'] ) : '';

				if ( ! empty( $name ) ) {
					$nos_array = array_filter( array_map( 'trim', explode( ',', $numbers ) ) );
					$clean_str = implode( ', ', $nos_array );

					$clean_rooms_map[] = array(
						'name'    => $name,
						'numbers' => $clean_str,
					);

					$clean_types[] = $name;
					$clean_all_nos = array_merge( $clean_all_nos, $nos_array );
				}
			}
		}

		// 1.2 Taxonomies: Bedding Types and Room Views.
		$raw_beds   = isset( $_POST['bedding_types'] ) && is_array( $_POST['bedding_types'] ) ? wp_unslash( $_POST['bedding_types'] ) : array();
		$clean_beds = array_values( array_filter( array_map( 'sanitize_text_field', $raw_beds ) ) );

		$raw_views   = isset( $_POST['room_views'] ) && is_array( $_POST['room_views'] ) ? wp_unslash( $_POST['room_views'] ) : array();
		$clean_views = array_values( array_filter( array_map( 'sanitize_text_field', $raw_views ) ) );

		// 1.3 Property House Rules & Logistics.
		$checkin_time      = isset( $_POST['ahbn_checkin_time'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_checkin_time'] ) ) : '15:00';
		$checkout_time     = isset( $_POST['ahbn_checkout_time'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_checkout_time'] ) ) : '11:00';
		$min_booking_age   = isset( $_POST['ahbn_min_age'] ) ? absint( wp_unslash( $_POST['ahbn_min_age'] ) ) : 18;
		$deposit_type      = isset( $_POST['ahbn_deposit_type'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_deposit_type'] ) ) : 'full';
		$deposit_amount    = isset( $_POST['ahbn_deposit_amount'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['ahbn_deposit_amount'] ) ) : 0.00;
		$allow_pets        = isset( $_POST['ahbn_allow_pets'] ) ? 1 : 0;
		$smoking_allowed   = isset( $_POST['ahbn_smoking_allowed'] ) ? 1 : 0;
		$free_cancel_days  = isset( $_POST['ahbn_cancel_window'] ) ? absint( wp_unslash( $_POST['ahbn_cancel_window'] ) ) : 2;

		// 1.4 Amenities.
		$clean_amenities = array();
		if ( isset( $_POST['amenities'] ) && is_array( $_POST['amenities'] ) ) {
			$raw_amenities = wp_unslash( $_POST['amenities'] );
			foreach ( $raw_amenities as $ahbn_amenity ) {
				$name     = isset( $ahbn_amenity['name'] ) ? sanitize_text_field( $ahbn_amenity['name'] ) : '';
				$icon_key = isset( $ahbn_amenity['icon_key'] ) ? sanitize_key( $ahbn_amenity['icon_key'] ) : 'wifi';

				if ( ! empty( $name ) ) {
					$clean_amenities[] = array(
						'name'     => $name,
						'icon_key' => array_key_exists( $icon_key, $svg_lib ) ? $icon_key : 'wifi',
					);
				}
			}
		}

		// 1.5 Paid Extras.
		$clean_paid = array();
		if ( isset( $_POST['paid_extras'] ) && is_array( $_POST['paid_extras'] ) ) {
			$raw_paid = wp_unslash( $_POST['paid_extras'] );
			foreach ( $raw_paid as $ahbn_paid ) {
				$name     = isset( $ahbn_paid['name'] ) ? sanitize_text_field( $ahbn_paid['name'] ) : '';
				$price    = isset( $ahbn_paid['price'] ) ? (float) sanitize_text_field( $ahbn_paid['price'] ) : 0.00;
				$icon_key = isset( $ahbn_paid['icon_key'] ) ? sanitize_key( $ahbn_paid['icon_key'] ) : 'bed';

				if ( ! empty( $name ) ) {
					$clean_paid[] = array(
						'name'     => $name,
						'price'    => max( 0.00, $price ),
						'icon_key' => array_key_exists( $icon_key, $svg_lib ) ? $icon_key : 'bed',
					);
				}
			}
		}

		// Save options.
		update_option( 'ahbn_room_inventory_map', $clean_rooms_map );
		update_option( 'ahbn_global_types', ! empty( $clean_types ) ? array_unique( $clean_types ) : array( 'Standard Room', 'Deluxe Suite', 'Executive Suite' ) );
		update_option( 'ahbn_global_nos', ! empty( $clean_all_nos ) ? array_unique( $clean_all_nos ) : array( '101', '102', '103' ) );
		update_option( 'ahbn_bedding_types', ! empty( $clean_beds ) ? $clean_beds : array( '1 King Bed', '2 Queen Beds', '1 Queen Bed', '2 Twin Beds' ) );
		update_option( 'ahbn_room_views', ! empty( $clean_views ) ? $clean_views : array( 'Oceanfront', 'Poolside', 'Garden View', 'City View' ) );

		update_option( 'ahbn_checkin_time', $checkin_time );
		update_option( 'ahbn_checkout_time', $checkout_time );
		update_option( 'ahbn_min_age', $min_booking_age );
		update_option( 'ahbn_deposit_type', $deposit_type );
		update_option( 'ahbn_deposit_amount', $deposit_amount );
		update_option( 'ahbn_allow_pets', $allow_pets );
		update_option( 'ahbn_smoking_allowed', $smoking_allowed );
		update_option( 'ahbn_cancel_window', $free_cancel_days );

		update_option( 'ahbn_global_amenities', $clean_amenities );
		update_option( 'ahbn_global_paid_extras', $clean_paid );

		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Settings Saved:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'All hotel configurations and policies updated.', 'awesome-hotel-booking' ) . '</p></div>';
	}

	// 2. Fetch Stored Options.
	$default_inventory = array(
		array( 'name' => 'Standard Room', 'numbers' => '101, 102, 103' ),
		array( 'name' => 'Deluxe King Suite', 'numbers' => '201, 202, 203, 204' ),
		array( 'name' => 'Executive Suite', 'numbers' => '301, 302, 303' ),
		array( 'name' => 'Presidential Villa', 'numbers' => 'V01, V02' ),
	);
	$room_inventory = get_option( 'ahbn_room_inventory_map', $default_inventory );

	$bedding_types = get_option( 'ahbn_bedding_types', array( '1 King Bed', '2 Queen Beds', '1 Queen Bed', '2 Twin Beds', '1 King + 1 Sofa Bed' ) );
	$room_views    = get_option( 'ahbn_room_views', array( 'Oceanfront Panoramic', 'Poolside Walkout', 'Tropical Garden View', 'Executive Skyline' ) );

	$checkin_time     = get_option( 'ahbn_checkin_time', '15:00' );
	$checkout_time    = get_option( 'ahbn_checkout_time', '11:00' );
	$min_booking_age  = get_option( 'ahbn_min_age', 18 );
	$deposit_type     = get_option( 'ahbn_deposit_type', 'full' );
	$deposit_amount   = get_option( 'ahbn_deposit_amount', 0.00 );
	$allow_pets       = (int) get_option( 'ahbn_allow_pets', 0 );
	$smoking_allowed  = (int) get_option( 'ahbn_smoking_allowed', 0 );
	$free_cancel_days = (int) get_option( 'ahbn_cancel_window', 2 );

	$amen_def = array(
		array( 'name' => 'High-Speed Wi-Fi', 'icon_key' => 'wifi' ),
		array( 'name' => 'Climate Control AC', 'icon_key' => 'ac' ),
		array( 'name' => 'Artisanal Breakfast', 'icon_key' => 'breakfast' ),
		array( 'name' => 'Cocktail Mini Bar', 'icon_key' => 'bar' ),
		array( 'name' => 'Valet Parking', 'icon_key' => 'parking' ),
	);
	$amenities = get_option( 'ahbn_global_amenities', $amen_def );

	$paid_def = array(
		array( 'name' => 'Additional King Bed', 'price' => 25.00, 'icon_key' => 'bed' ),
		array( 'name' => 'Airport Chauffeur Transfer', 'price' => 60.00, 'icon_key' => 'shuttle' ),
		array( 'name' => 'Luxury Thermal Spa Pass', 'price' => 45.00, 'icon_key' => 'spa' ),
	);
	$paid_extras = get_option( 'ahbn_global_paid_extras', $paid_def );
	?>

	<div class="ahbn-rooms-settings-wrap">
		<form method="post" action="">
			<?php wp_nonce_field( 'ahbn_save_extras_action', 'ahbn_extras_nonce' ); ?>

			<!-- SECTION 1: Suite Types & Numbers -->
			<div class="ahbn-panel">
				<div class="ahbn-panel-head">
					<span class="dashicons dashicons-category" aria-hidden="true"></span>
					<h2><?php esc_html_e( '1. Suite Classifications & Assigned Door Numbers', 'awesome-hotel-booking' ); ?></h2>
				</div>
				<div class="ahbn-panel-body">
					<p class="ahbn-panel-desc">
						<?php esc_html_e( 'Define each room category and its physical door/unit numbers separated by commas (e.g. 101, 102, 103).', 'awesome-hotel-booking' ); ?>
					</p>

					<div class="repeater-container" id="ui-room-inventory">
						<?php if ( ! empty( $room_inventory ) && is_array( $room_inventory ) ) : ?>
							<?php foreach ( $room_inventory as $idx => $item ) : ?>
								<div class="ahbn-repeater-row ahbn-inventory-row">
									<span class="dashicons dashicons-menu ahbn-drag-handle" aria-hidden="true"></span>
									
									<div class="ahbn-field-cell ahbn-cell-name">
										<label class="ahbn-micro-label"><?php esc_html_e( 'Suite / Category Name', 'awesome-hotel-booking' ); ?></label>
										<input 
											type="text" 
											name="room_inventory[<?php echo absint( $idx ); ?>][name]" 
											value="<?php echo esc_attr( isset( $item['name'] ) ? $item['name'] : '' ); ?>" 
											class="ahbn-input" 
											placeholder="<?php esc_attr_e( 'e.g. Deluxe Ocean Suite', 'awesome-hotel-booking' ); ?>" 
											required
										>
									</div>

									<div class="ahbn-field-cell ahbn-cell-numbers">
										<label class="ahbn-micro-label"><?php esc_html_e( 'Assigned Door / Unit #s (Comma-separated)', 'awesome-hotel-booking' ); ?></label>
										<input 
											type="text" 
											name="room_inventory[<?php echo absint( $idx ); ?>][numbers]" 
											value="<?php echo esc_attr( isset( $item['numbers'] ) ? $item['numbers'] : '' ); ?>" 
											class="ahbn-input font-mono" 
											placeholder="<?php esc_attr_e( 'e.g. 101, 102, 103, 104', 'awesome-hotel-booking' ); ?>" 
											required
										>
									</div>

									<button type="button" class="btn-remove remove-row" aria-label="<?php esc_attr_e( 'Remove Category', 'awesome-hotel-booking' ); ?>" title="<?php esc_attr_e( 'Remove Row', 'awesome-hotel-booking' ); ?>">
										<span class="dashicons dashicons-trash" aria-hidden="true"></span>
									</button>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>

					<button type="button" class="btn-add-row" id="btn-add-inventory-row" style="max-width: 260px;">
						<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
						<?php esc_html_e( 'Add Room Classification', 'awesome-hotel-booking' ); ?>
					</button>
				</div>
			</div>

			<!-- SECTION 2: Bedding Configurations & Room Views -->
			<div class="ahbn-grid-two-panels" style="margin-top: 20px;">
				<!-- Bedding Types Repeater -->
				<div class="ahbn-panel">
					<div class="ahbn-panel-head">
						<span class="dashicons dashicons-admin-multisite" aria-hidden="true"></span>
						<h2><?php esc_html_e( '2. Bedding Arrangements', 'awesome-hotel-booking' ); ?></h2>
					</div>
					<div class="ahbn-panel-body">
						<p class="ahbn-panel-desc"><?php esc_html_e( 'Standard bedding selections for room editors and guest filters.', 'awesome-hotel-booking' ); ?></p>
						<div class="repeater-container" id="ui-beds">
							<?php foreach ( $bedding_types as $bed ) : ?>
								<div class="ahbn-repeater-row">
									<span class="dashicons dashicons-menu ahbn-drag-handle" aria-hidden="true"></span>
									<input type="text" name="bedding_types[]" value="<?php echo esc_attr( $bed ); ?>" class="ahbn-input" required>
									<button type="button" class="btn-remove remove-row"><span class="dashicons dashicons-trash"></span></button>
								</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="btn-add-row ahbn-btn-add-simple" data-target="ui-beds" data-field-name="bedding_types[]" data-placeholder="e.g. 1 King + 2 Bunk Beds">
							<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add Bedding Option', 'awesome-hotel-booking' ); ?>
						</button>
					</div>
				</div>

				<!-- Room Views Repeater -->
				<div class="ahbn-panel">
					<div class="ahbn-panel-head">
						<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
						<h2><?php esc_html_e( '3. View Orientations', 'awesome-hotel-booking' ); ?></h2>
					</div>
					<div class="ahbn-panel-body">
						<p class="ahbn-panel-desc"><?php esc_html_e( 'View perspectives showcased on suite profiles and SEO schemas.', 'awesome-hotel-booking' ); ?></p>
						<div class="repeater-container" id="ui-views">
							<?php foreach ( $room_views as $view ) : ?>
								<div class="ahbn-repeater-row">
									<span class="dashicons dashicons-menu ahbn-drag-handle" aria-hidden="true"></span>
									<input type="text" name="room_views[]" value="<?php echo esc_attr( $view ); ?>" class="ahbn-input" required>
									<button type="button" class="btn-remove remove-row"><span class="dashicons dashicons-trash"></span></button>
								</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="btn-add-row ahbn-btn-add-simple" data-target="ui-views" data-field-name="room_views[]" data-placeholder="e.g. Mountain Panorama">
							<span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e( 'Add View Option', 'awesome-hotel-booking' ); ?>
						</button>
					</div>
				</div>
			</div>

			<!-- SECTION 3: Operations, Timings & Booking Policies -->
			<div class="ahbn-panel" style="margin-top: 20px;">
				<div class="ahbn-panel-head">
					<span class="dashicons dashicons-clock" aria-hidden="true"></span>
					<h2><?php esc_html_e( '4. Check-in Logistics & Reservation Policies', 'awesome-hotel-booking' ); ?></h2>
				</div>
				<div class="ahbn-panel-body">
					<div class="ahbn-policy-grid">
						<div class="ahbn-field-cell">
							<label class="ahbn-label"><?php esc_html_e( 'Standard Check-In Time', 'awesome-hotel-booking' ); ?></label>
							<input type="time" name="ahbn_checkin_time" class="ahbn-input" value="<?php echo esc_attr( $checkin_time ); ?>">
						</div>
						<div class="ahbn-field-cell">
							<label class="ahbn-label"><?php esc_html_e( 'Standard Check-Out Time', 'awesome-hotel-booking' ); ?></label>
							<input type="time" name="ahbn_checkout_time" class="ahbn-input" value="<?php echo esc_attr( $checkout_time ); ?>">
						</div>
						<div class="ahbn-field-cell">
							<label class="ahbn-label"><?php esc_html_e( 'Minimum Guest Age', 'awesome-hotel-booking' ); ?></label>
							<input type="number" min="16" max="99" name="ahbn_min_age" class="ahbn-input" value="<?php echo esc_attr( (string) $min_booking_age ); ?>">
						</div>
						<div class="ahbn-field-cell">
							<label class="ahbn-label"><?php esc_html_e( 'Free Cancellation Window (Days Before Arrival)', 'awesome-hotel-booking' ); ?></label>
							<input type="number" min="0" max="60" name="ahbn_cancel_window" class="ahbn-input" value="<?php echo esc_attr( (string) $free_cancel_days ); ?>">
						</div>
					</div>

					<hr style="border: 0; border-top: 1px solid #f1f5f9; margin: 15px 0;">

					<div class="ahbn-policy-grid">
						<div class="ahbn-field-cell">
							<label class="ahbn-label"><?php esc_html_e( 'Booking Deposit Requirement', 'awesome-hotel-booking' ); ?></label>
							<select name="ahbn_deposit_type" class="ahbn-select" id="ahbn-deposit-selector">
								<option value="full" <?php selected( $deposit_type, 'full' ); ?>><?php esc_html_e( 'Full Payment Required at Booking (100%)', 'awesome-hotel-booking' ); ?></option>
								<option value="percentage" <?php selected( $deposit_type, 'percentage' ); ?>><?php esc_html_e( 'Percentage Deposit (%)', 'awesome-hotel-booking' ); ?></option>
								<option value="fixed" <?php selected( $deposit_type, 'fixed' ); ?>><?php esc_html_e( 'Fixed Amount Deposit', 'awesome-hotel-booking' ); ?></option>
								<option value="arrival" <?php selected( $deposit_type, 'arrival' ); ?>><?php esc_html_e( 'Pay in Full on Arrival (0% Deposit)', 'awesome-hotel-booking' ); ?></option>
							</select>
						</div>
						<div class="ahbn-field-cell" id="ahbn-deposit-val-wrap" style="<?php echo ( 'full' === $deposit_type || 'arrival' === $deposit_type ) ? 'display:none;' : ''; ?>">
							<label class="ahbn-label"><?php esc_html_e( 'Deposit Value (Fixed / %)', 'awesome-hotel-booking' ); ?></label>
							<input type="number" step="0.01" min="0" name="ahbn_deposit_amount" class="ahbn-input font-mono" value="<?php echo esc_attr( (string) $deposit_amount ); ?>">
						</div>
					</div>

					<div class="ahbn-policy-toggles">
						<label class="ahbn-checkbox-label">
							<input type="checkbox" name="ahbn_allow_pets" value="1" <?php checked( $allow_pets, 1 ); ?>>
							<span><?php esc_html_e( 'Pet Friendly Property (Allows Dogs/Cats)', 'awesome-hotel-booking' ); ?></span>
						</label>
						<label class="ahbn-checkbox-label">
							<input type="checkbox" name="ahbn_smoking_allowed" value="1" <?php checked( $smoking_allowed, 1 ); ?>>
							<span><?php esc_html_e( 'Designated Smoking Balconies Allowed', 'awesome-hotel-booking' ); ?></span>
						</label>
					</div>
				</div>
			</div>

			<!-- SECTION 4: In-Suite Amenities -->
			<div class="ahbn-panel" style="margin-top: 20px;">
				<div class="ahbn-panel-head">
					<span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
					<h2><?php esc_html_e( '5. Global In-Suite Amenities (Complimentary)', 'awesome-hotel-booking' ); ?></h2>
				</div>
				<div class="ahbn-panel-body">
					<p class="ahbn-panel-desc"><?php esc_html_e( 'Features displayed with custom vector icons on room cards and details pages.', 'awesome-hotel-booking' ); ?></p>
					
					<div class="repeater-container" id="ui-amenities">
						<?php if ( ! empty( $amenities ) && is_array( $amenities ) ) : ?>
							<?php foreach ( $amenities as $idx => $a ) : ?>
								<?php $icon_key = isset( $a['icon_key'] ) ? $a['icon_key'] : 'wifi'; ?>
								<div class="ahbn-repeater-row ahbn-amenity-row">
									<div class="ahbn-svg-preview-box">
										<?php echo ahbn_render_svg_icon( $icon_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</div>
									<select name="amenities[<?php echo absint( $idx ); ?>][icon_key]" class="ahbn-select ahbn-icon-sel">
										<?php foreach ( $svg_lib as $key => $svg ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $icon_key, $key ); ?>>
												<?php echo esc_html( ucfirst( $key ) ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<input 
										type="text" 
										name="amenities[<?php echo absint( $idx ); ?>][name]" 
										value="<?php echo esc_attr( isset( $a['name'] ) ? $a['name'] : '' ); ?>" 
										class="ahbn-input" 
										placeholder="<?php esc_attr_e( 'Amenity Name (e.g. High-Speed Wi-Fi)', 'awesome-hotel-booking' ); ?>"
										required
									>
									<button type="button" class="btn-remove remove-row" aria-label="<?php esc_attr_e( 'Remove Amenity', 'awesome-hotel-booking' ); ?>" title="<?php esc_attr_e( 'Remove Row', 'awesome-hotel-booking' ); ?>">
										<span class="dashicons dashicons-trash" aria-hidden="true"></span>
									</button>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>

					<button type="button" class="btn-add-row" id="btn-add-amenity" style="max-width: 240px;">
						<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
						<?php esc_html_e( 'Add Amenity Item', 'awesome-hotel-booking' ); ?>
					</button>
				</div>
			</div>

			<!-- SECTION 5: Paid Extras -->
			<div class="ahbn-panel" style="margin-top: 20px;">
				<div class="ahbn-panel-head">
					<span class="dashicons dashicons-cart" aria-hidden="true"></span>
					<h2><?php esc_html_e( '6. Attachable Paid Extras (Checkout Upsells)', 'awesome-hotel-booking' ); ?></h2>
				</div>
				<div class="ahbn-panel-body">
					<p class="ahbn-panel-desc"><?php esc_html_e( 'Premium add-ons selectable during online booking reservation checkout.', 'awesome-hotel-booking' ); ?></p>
					
					<div class="repeater-container" id="ui-paid">
						<?php if ( ! empty( $paid_extras ) && is_array( $paid_extras ) ) : ?>
							<?php foreach ( $paid_extras as $idx => $e ) : ?>
								<?php
								$icon_key  = isset( $e['icon_key'] ) ? $e['icon_key'] : 'bed';
								$price_val = isset( $e['price'] ) ? (float) $e['price'] : 0.00;
								?>
								<div class="ahbn-repeater-row ahbn-amenity-row">
									<div class="ahbn-svg-preview-box">
										<?php echo ahbn_render_svg_icon( $icon_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</div>
									<select name="paid_extras[<?php echo absint( $idx ); ?>][icon_key]" class="ahbn-select ahbn-icon-sel">
										<?php foreach ( $svg_lib as $key => $svg ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $icon_key, $key ); ?>>
												<?php echo esc_html( ucfirst( $key ) ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<input 
										type="text" 
										name="paid_extras[<?php echo absint( $idx ); ?>][name]" 
										value="<?php echo esc_attr( isset( $e['name'] ) ? $e['name'] : '' ); ?>" 
										class="ahbn-input" 
										placeholder="<?php esc_attr_e( 'Extra Service Name (e.g. Airport Transfer)', 'awesome-hotel-booking' ); ?>"
										required
									>
									<div class="ahbn-price-input-wrap">
										<span class="ahbn-currency-addon"><?php echo esc_html( $currency ); ?></span>
										<input 
											type="number" 
											step="0.01" 
											min="0" 
											name="paid_extras[<?php echo absint( $idx ); ?>][price]" 
											value="<?php echo esc_attr( (string) $price_val ); ?>" 
											class="ahbn-input font-mono" 
											placeholder="0.00"
											required
										>
									</div>
									<button type="button" class="btn-remove remove-row" aria-label="<?php esc_attr_e( 'Remove Extra', 'awesome-hotel-booking' ); ?>" title="<?php esc_attr_e( 'Remove Row', 'awesome-hotel-booking' ); ?>">
										<span class="dashicons dashicons-trash" aria-hidden="true"></span>
									</button>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>

					<button type="button" class="btn-add-row" id="btn-add-paid" style="max-width: 260px;">
						<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
						<?php esc_html_e( 'Add Paid Extra Service', 'awesome-hotel-booking' ); ?>
					</button>
				</div>
			</div>

			<!-- Form Actions -->
			<div class="ahbn-panel-actions" style="margin-top: 25px;">
				<button type="submit" name="ahbn_save_extras" class="ahbn-btn-save">
					<span class="dashicons dashicons-saved" aria-hidden="true"></span>
					<?php esc_html_e( 'Save All Hotel Configurations', 'awesome-hotel-booking' ); ?>
				</button>
			</div>
		</form>
	</div>

	<style>
		.ahbn-rooms-settings-wrap {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-grid-two-panels {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 20px;
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
			padding: 22px;
			display: flex;
			flex-direction: column;
			gap: 14px;
		}

		.ahbn-panel-desc {
			margin: 0;
			font-size: 13px;
			color: #64748b;
			line-height: 1.5;
		}

		.repeater-container {
			display: flex;
			flex-direction: column;
			gap: 10px;
		}

		.ahbn-repeater-row {
			display: flex;
			align-items: center;
			gap: 10px;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 10px;
			padding: 10px 14px;
			transition: all 0.2s ease;
		}

		.ahbn-repeater-row:hover {
			border-color: #cbd5e1;
			background: #ffffff;
		}

		.ahbn-inventory-row {
			align-items: flex-end;
		}

		.ahbn-field-cell {
			display: flex;
			flex-direction: column;
			gap: 4px;
		}

		.ahbn-cell-name {
			flex: 1.2;
		}

		.ahbn-cell-numbers {
			flex: 1.8;
		}

		.ahbn-micro-label {
			font-size: 10px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #64748b;
		}

		.ahbn-label {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #334155;
		}

		.ahbn-drag-handle {
			color: #cbd5e1;
			cursor: grab;
			font-size: 16px;
			width: 16px;
			height: 16px;
			flex-shrink: 0;
			margin-bottom: 6px;
		}

		.ahbn-input,
		.ahbn-select {
			height: 38px !important;
			border: 1px solid #cbd5e1 !important;
			border-radius: 8px !important;
			padding: 0 12px !important;
			font-size: 13px !important;
			background: #ffffff !important;
			color: #0f172a !important;
			box-shadow: none !important;
		}

		.ahbn-input:focus,
		.ahbn-select:focus {
			border-color: #0a192f !important;
			box-shadow: 0 0 0 2px rgba(10, 25, 47, 0.1) !important;
		}

		.font-mono {
			font-family: 'JetBrains Mono', monospace !important;
			font-weight: 600;
		}

		/* Policy Grids */
		.ahbn-policy-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
			gap: 16px;
		}

		.ahbn-policy-toggles {
			display: flex;
			gap: 30px;
			flex-wrap: wrap;
			margin-top: 10px;
		}

		.ahbn-checkbox-label {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			font-size: 13px;
			font-weight: 600;
			color: #334155;
			cursor: pointer;
		}

		.ahbn-checkbox-label input {
			width: 17px;
			height: 17px;
			accent-color: #0a192f;
			cursor: pointer;
		}

		/* Amenity & Extra Rows */
		.ahbn-amenity-row {
			align-items: center;
		}

		.ahbn-amenity-row .ahbn-input {
			flex: 1;
		}

		.ahbn-amenity-row .ahbn-icon-sel {
			width: 130px;
			flex-shrink: 0;
		}

		.ahbn-svg-preview-box {
			width: 38px;
			height: 38px;
			border-radius: 8px;
			background: #ffffff;
			border: 1px solid #e2e8f0;
			display: flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
			color: #0a192f;
			padding: 8px;
			box-sizing: border-box;
		}

		.ahbn-svg-preview-box svg {
			width: 100%;
			height: 100%;
			fill: currentColor;
		}

		.ahbn-price-input-wrap {
			display: flex;
			align-items: center;
			width: 130px;
			flex-shrink: 0;
		}

		.ahbn-currency-addon {
			background: #f1f5f9;
			border: 1px solid #cbd5e1;
			border-right: none;
			border-radius: 8px 0 0 8px;
			padding: 0 10px;
			height: 38px;
			display: flex;
			align-items: center;
			font-size: 13px;
			font-weight: 700;
			color: #475569;
		}

		.ahbn-price-input-wrap .ahbn-input {
			border-radius: 0 8px 8px 0 !important;
		}

		.btn-remove {
			width: 38px;
			height: 38px;
			border: 1px solid #fecdd3;
			background: #fff1f2;
			border-radius: 8px;
			color: #e11d48;
			cursor: pointer;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			flex-shrink: 0;
			transition: all 0.15s ease;
		}

		.btn-remove:hover {
			background: #e11d48;
			border-color: #e11d48;
			color: #ffffff;
		}

		.btn-remove .dashicons {
			font-size: 16px;
			width: 16px;
			height: 16px;
		}

		.btn-add-row {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 6px;
			background: #ffffff;
			border: 1px dashed #cbd5e1;
			border-radius: 8px;
			padding: 9px 16px;
			font-size: 13px;
			font-weight: 700;
			color: #0a192f;
			cursor: pointer;
			transition: all 0.2s ease;
			margin-top: 4px;
		}

		.btn-add-row .dashicons {
			font-size: 15px;
			width: 15px;
			height: 15px;
			color: #c5a880;
		}

		.btn-add-row:hover {
			border-color: #0a192f;
			background: #f8fafc;
			color: #0a192f;
		}

		.ahbn-panel-actions {
			display: flex;
			justify-content: flex-end;
		}

		.ahbn-btn-save {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 12px 28px;
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

		@media (max-width: 900px) {
			.ahbn-grid-two-panels {
				grid-template-columns: 1fr;
			}
			.ahbn-inventory-row {
				flex-direction: column;
				align-items: stretch;
			}
			.ahbn-drag-handle {
				display: none;
			}
			.ahbn-inventory-row .btn-remove {
				align-self: flex-end;
			}
		}
	</style>

	<script>
	jQuery(document).ready(function($) {
		const icons = <?php echo wp_json_encode( $svg_lib ); ?>;
		const iconKeys = Object.keys(icons);
		const alertText = '<?php echo esc_js( __( 'At least one item is required in this list.', 'awesome-hotel-booking' ) ); ?>';

		function buildIconSelect(fieldName, selectedKey) {
			const $select = $('<select/>', { name: fieldName, class: 'ahbn-select ahbn-icon-sel' });
			$.each(iconKeys, function(index, key) {
				const label = key.charAt(0).toUpperCase() + key.slice(1);
				const $opt = $('<option/>', { value: key, text: label });
				if (key === selectedKey) {
					$opt.attr('selected', 'selected');
				}
				$select.append($opt);
			});
			return $select;
		}

		// 1. Add Room Inventory Row.
		$('#btn-add-inventory-row').on('click', function(e) {
			e.preventDefault();
			const i = new Date().getTime();

			const $row = $('<div/>', { class: 'ahbn-repeater-row ahbn-inventory-row' })
				.append($('<span/>', { class: 'dashicons dashicons-menu ahbn-drag-handle', 'aria-hidden': 'true' }))
				.append(
					$('<div/>', { class: 'ahbn-field-cell ahbn-cell-name' })
						.append($('<label/>', { class: 'ahbn-micro-label', text: '<?php echo esc_js( __( 'Suite / Category Name', 'awesome-hotel-booking' ) ); ?>' }))
						.append($('<input/>', {
							type: 'text',
							name: 'room_inventory[' + i + '][name]',
							class: 'ahbn-input',
							placeholder: '<?php echo esc_js( __( 'e.g. Deluxe Garden Villa', 'awesome-hotel-booking' ) ); ?>',
							required: true
						}))
				)
				.append(
					$('<div/>', { class: 'ahbn-field-cell ahbn-cell-numbers' })
						.append($('<label/>', { class: 'ahbn-micro-label', text: '<?php echo esc_js( __( 'Assigned Door / Unit #s (Comma-separated)', 'awesome-hotel-booking' ) ); ?>' }))
						.append($('<input/>', {
							type: 'text',
							name: 'room_inventory[' + i + '][numbers]',
							class: 'ahbn-input font-mono',
							placeholder: '<?php echo esc_js( __( 'e.g. 401, 402, 403', 'awesome-hotel-booking' ) ); ?>',
							required: true
						}))
				)
				.append(
					$('<button/>', {
						type: 'button',
						class: 'btn-remove remove-row',
						'aria-label': '<?php echo esc_js( __( 'Remove Row', 'awesome-hotel-booking' ) ); ?>'
					}).append($('<span/>', { class: 'dashicons dashicons-trash', 'aria-hidden': 'true' }))
				);

			$('#ui-room-inventory').append($row);
			$row.find('.ahbn-cell-name input').focus();
		});

		// 2. Add Simple Repeater Row (Beds / Views).
		$('.ahbn-btn-add-simple').on('click', function(e) {
			e.preventDefault();
			const target      = $(this).data('target');
			const fieldName   = $(this).data('field-name');
			const placeholder = $(this).data('placeholder') || '';

			const $row = $('<div/>', { class: 'ahbn-repeater-row' })
				.append($('<span/>', { class: 'dashicons dashicons-menu ahbn-drag-handle', 'aria-hidden': 'true' }))
				.append($('<input/>', {
					type: 'text',
					name: fieldName,
					class: 'ahbn-input',
					placeholder: placeholder,
					required: true
				}))
				.append($('<button/>', { type: 'button', class: 'btn-remove remove-row' }).append($('<span/>', { class: 'dashicons dashicons-trash' })));

			$('#' + target).append($row);
			$row.find('input').focus();
		});

		// 3. Add Amenity Row.
		$('#btn-add-amenity').on('click', function(e) {
			e.preventDefault();
			const i = new Date().getTime();
			const defaultIcon = 'wifi';

			const $row = $('<div/>', { class: 'ahbn-repeater-row ahbn-amenity-row' })
				.append($('<div/>', { class: 'ahbn-svg-preview-box', html: icons[defaultIcon] }))
				.append(buildIconSelect('amenities[' + i + '][icon_key]', defaultIcon))
				.append($('<input/>', {
					type: 'text',
					name: 'amenities[' + i + '][name]',
					class: 'ahbn-input',
					placeholder: '<?php echo esc_js( __( 'Amenity Name (e.g. 4K Smart TV)', 'awesome-hotel-booking' ) ); ?>',
					required: true
				}))
				.append($('<button/>', { type: 'button', class: 'btn-remove remove-row' }).append($('<span/>', { class: 'dashicons dashicons-trash' })));

			$('#ui-amenities').append($row);
			$row.find('input[type="text"]').focus();
		});

		// 4. Add Paid Extra Row.
		$('#btn-add-paid').on('click', function(e) {
			e.preventDefault();
			const i = new Date().getTime();
			const defaultIcon = 'bed';
			const currSymbol = '<?php echo esc_js( $currency ); ?>';

			const $priceWrap = $('<div/>', { class: 'ahbn-price-input-wrap' })
				.append($('<span/>', { class: 'ahbn-currency-addon', text: currSymbol }))
				.append($('<input/>', {
					type: 'number',
					step: '0.01',
					min: '0',
					name: 'paid_extras[' + i + '][price]',
					class: 'ahbn-input font-mono',
					placeholder: '0.00',
					required: true
				}));

			const $row = $('<div/>', { class: 'ahbn-repeater-row ahbn-amenity-row' })
				.append($('<div/>', { class: 'ahbn-svg-preview-box', html: icons[defaultIcon] }))
				.append(buildIconSelect('paid_extras[' + i + '][icon_key]', defaultIcon))
				.append($('<input/>', {
					type: 'text',
					name: 'paid_extras[' + i + '][name]',
					class: 'ahbn-input',
					placeholder: '<?php echo esc_js( __( 'Extra Service Name (e.g. Airport Transfer)', 'awesome-hotel-booking' ) ); ?>',
					required: true
				}))
				.append($priceWrap)
				.append($('<button/>', { type: 'button', class: 'btn-remove remove-row' }).append($('<span/>', { class: 'dashicons dashicons-trash' })));

			$('#ui-paid').append($row);
			$row.find('input[type="text"]').focus();
		});

		// 5. Dynamic SVG Preview.
		$(document).on('change', '.ahbn-icon-sel', function() {
			const selKey = $(this).val();
			if (icons[selKey]) {
				$(this).siblings('.ahbn-svg-preview-box').html(icons[selKey]);
			}
		});

		// 6. Deposit Selector Toggle.
		$('#ahbn-deposit-selector').on('change', function() {
			const val = $(this).val();
			if (val === 'percentage' || val === 'fixed') {
				$('#ahbn-deposit-val-wrap').slideDown(150);
			} else {
				$('#ahbn-deposit-val-wrap').slideUp(150);
			}
		});

		// 7. Remove Row.
		$(document).on('click', '.remove-row', function(e) {
			e.preventDefault();
			const $container = $(this).closest('.repeater-container');
			if ($container.find('.ahbn-repeater-row').length > 1) {
				$(this).closest('.ahbn-repeater-row').fadeOut(150, function() {
					$(this).remove();
				});
			} else {
				alert(alertText);
			}
		});
	});
	</script>
	<?php
}