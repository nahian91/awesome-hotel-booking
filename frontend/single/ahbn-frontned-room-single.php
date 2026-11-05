<?php
/**
 * [ahbn_room_details] Single Room Details & AJAX Reservation Engine.
 *
 * Query engine connected to custom relational database tables:
 * {$wpdb->prefix}ahbn_rooms, {$wpdb->prefix}ahbn_bookings, and {$wpdb->prefix}ahbn_services.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*--------------------------------------------------------------
# 1. Rewrite Rules & Query Variables
--------------------------------------------------------------*/

function ahbn_room_details_rewrite_rules() {
	add_rewrite_rule(
		'^room-details/([^/]+)/?',
		'index.php?pagename=room-details&ahbn_room_name=$matches[1]',
		'top'
	);
}
add_action( 'init', 'ahbn_room_details_rewrite_rules' );

function ahbn_room_details_query_vars( $vars ) {
	$vars[] = 'ahbn_room_name';
	return $vars;
}
add_filter( 'query_vars', 'ahbn_room_details_query_vars' );

/*--------------------------------------------------------------
# 2. AJAX Booking Submission Handler
--------------------------------------------------------------*/

function ahbn_handle_booking_submission() {
	$nonce = isset( $_POST['security'] ) ? sanitize_key( wp_unslash( $_POST['security'] ) ) : '';
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'ahbn_booking_nonce' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Security verification failed. Please refresh the page.', 'awesome-hotel-booking' ) ) );
	}

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Please log in to complete your reservation.', 'awesome-hotel-booking' ) ) );
	}

	global $wpdb;
	$table_rooms    = $wpdb->prefix . 'ahbn_rooms';
	$table_bookings = $wpdb->prefix . 'ahbn_bookings';

	$room_id         = isset( $_POST['room_id'] ) ? absint( wp_unslash( $_POST['room_id'] ) ) : 0;
	$full_name       = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
	$email           = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$checkin         = isset( $_POST['checkin'] ) ? sanitize_text_field( wp_unslash( $_POST['checkin'] ) ) : '';
	$checkout        = isset( $_POST['checkout'] ) ? sanitize_text_field( wp_unslash( $_POST['checkout'] ) ) : '';
	$adults          = isset( $_POST['adults'] ) ? max( 1, absint( wp_unslash( $_POST['adults'] ) ) ) : 1;
	$children        = isset( $_POST['children'] ) ? absint( wp_unslash( $_POST['children'] ) ) : 0;
	$selected_extras = isset( $_POST['extras'] ) && is_array( $_POST['extras'] ) ? array_map( 'absint', wp_unslash( $_POST['extras'] ) ) : array();

	if ( empty( $full_name ) || empty( $email ) || ! $room_id ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Please complete all required fields.', 'awesome-hotel-booking' ) ) );
	}

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$room = $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$table_rooms} WHERE id = %d", $room_id )
	);

	if ( ! $room ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid suite selection.', 'awesome-hotel-booking' ) ) );
	}

	$time_in  = strtotime( $checkin );
	$time_out = strtotime( $checkout );

	if ( ! $time_in || ! $time_out || $time_out <= $time_in ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Please select a valid check-out date after check-in.', 'awesome-hotel-booking' ) ) );
	}

	$nights    = max( 1, (int) round( ( $time_out - $time_in ) / DAY_IN_SECONDS ) );
	$room_rate = (float) $room->price_per_night;
	$total_amt = $nights * $room_rate;

	// Calculate Paid Extras Cost
	if ( ! empty( $selected_extras ) ) {
		$table_services = $wpdb->prefix . 'ahbn_services';
		$placeholders   = implode( ',', array_fill( 0, count( $selected_extras ), '%d' ) );
		$extras_items   = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table_services} WHERE id IN ({$placeholders})", $selected_extras )
		);

		foreach ( $extras_items as $ex ) {
			$ex_price = (float) $ex->price;
			if ( 'per_night' === $ex->charge_type ) {
				$total_amt += ( $ex_price * $nights );
			} elseif ( 'per_person' === $ex->charge_type ) {
				$total_amt += ( $ex_price * ( $adults + $children ) );
			} elseif ( 'per_night_per_person' === $ex->charge_type ) {
				$total_amt += ( $ex_price * $nights * ( $adults + $children ) );
			} else {
				$total_amt += $ex_price; // per_stay
			}
		}
	}
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	$booking_code = 'AHBN-' . strtoupper( wp_generate_password( 6, false ) );

	$booking_data = array(
		'booking_code'   => $booking_code,
		'room_id'        => $room_id,
		'guest_name'     => $full_name,
		'guest_email'    => $email,
		'checkin_date'   => $checkin,
		'checkout_date'  => $checkout,
		'nights'         => $nights,
		'adults'         => $adults,
		'children'       => $children,
		'total_amount'   => $total_amt,
		'payment_status' => 'unpaid',
		'status'         => 'confirmed',
	);

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$inserted = $wpdb->insert(
		$table_bookings,
		$booking_data,
		array( '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%f', '%s', '%s' )
	);

	if ( false !== $inserted ) {
		$result_id = $wpdb->insert_id;
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$thanks_pid   = (int) get_option( 'ahbn_thanks_page' );
		$redirect_url = $thanks_pid ? add_query_arg( 'booking_id', $result_id, get_permalink( $thanks_pid ) ) : '';

		wp_send_json_success(
			array(
				'booking_id' => $result_id,
				'redirect'   => $redirect_url,
				'message'    => esc_html__( 'Reservation confirmed! Folio code: ' . $booking_code, 'awesome-hotel-booking' ),
			)
		);
	} else {
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		wp_send_json_error( array( 'message' => esc_html__( 'Database error: Could not record reservation.', 'awesome-hotel-booking' ) ) );
	}
}
add_action( 'wp_ajax_ahbn_save_booking', 'ahbn_handle_booking_submission' );
add_action( 'wp_ajax_nopriv_ahbn_save_booking', 'ahbn_handle_booking_submission' );

/*--------------------------------------------------------------
# 3. [ahbn_room_details] Shortcode Implementation
--------------------------------------------------------------*/

function ahbn_room_details_shortcode_callback( $atts = array() ) {
	global $wpdb;
	$table_rooms    = $wpdb->prefix . 'ahbn_rooms';
	$table_bookings = $wpdb->prefix . 'ahbn_bookings';
	$table_services = $wpdb->prefix . 'ahbn_services';

	$atts = shortcode_atts(
		array(
			'id'   => 0,
			'slug' => '',
		),
		$atts,
		'ahbn_room_details'
	);

	$room_slug = sanitize_title( get_query_var( 'ahbn_room_name' ) );
	if ( empty( $room_slug ) && ! empty( $atts['slug'] ) ) {
		$room_slug = sanitize_title( $atts['slug'] );
	}
	$room_id = absint( $atts['id'] );

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( ! empty( $room_slug ) ) {
		$room = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_rooms} WHERE slug = %s", $room_slug ) );
	} elseif ( $room_id > 0 ) {
		$room = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_rooms} WHERE id = %d", $room_id ) );
	} else {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$get_id = isset( $_GET['room_id'] ) ? absint( wp_unslash( $_GET['room_id'] ) ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$room = $get_id > 0 ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_rooms} WHERE id = %d", $get_id ) ) : null;
	}

	// Fetch Booked Dates for this room to block calendar availability
	$booked_dates = array();
	if ( $room ) {
		$raw_bookings = $wpdb->get_results(
			$wpdb->prepare( "SELECT checkin_date, checkout_date FROM {$table_bookings} WHERE room_id = %d AND status != 'cancelled'", $room->id )
		);
		foreach ( $raw_bookings as $b ) {
			$period = new DatePeriod(
				new DateTime( $b->checkin_date ),
				new DateInterval( 'P1D' ),
				( new DateTime( $b->checkout_date ) )->modify( '+1 day' )
			);
			foreach ( $period as $date ) {
				$booked_dates[] = $date->format( 'Y-m-d' );
			}
		}
	}

	// Fetch Paid Extra Add-ons
	$paid_extras = $wpdb->get_results( "SELECT * FROM {$table_services} WHERE status = 'publish' ORDER BY name ASC" );
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	if ( ! $room ) {
		return '<div class="ahbn-empty-state" style="text-align:center; padding:80px 20px; font-family:\'Plus Jakarta Sans\',sans-serif;">
					<h2 style="font-size:24px; color:#0a192f; font-weight:800;">' . esc_html__( 'Suite Not Found', 'awesome-hotel-booking' ) . '</h2>
					<p style="color:#64748b; font-size:14px; margin-top:8px;">' . esc_html__( 'Please return to our room listings to explore available suites.', 'awesome-hotel-booking' ) . '</p>
					<a href="' . esc_url( home_url( '/rooms/' ) ) . '" class="ahbn-btn-back" style="margin-top:16px; display:inline-block; padding:10px 22px; background:#0a192f; color:#fff; text-decoration:none; border-radius:8px; font-weight:700;">' . esc_html__( 'Browse Suites', 'awesome-hotel-booking' ) . '</a>
				</div>';
	}

	$rid              = (int) $room->id;
	$title            = $room->title;
	$room_number      = $room->room_number;
	$room_type        = ! empty( $room->room_type ) ? $room->room_type : __( 'Suite', 'awesome-hotel-booking' );
	$price            = (float) $room->price_per_night;
	$description      = ! empty( $room->description ) ? $room->description : '';
	$max_adults       = (int) $room->max_adults;
	$max_children     = (int) $room->max_children;
	$bedding_setup    = ! empty( $room->bedding_setup ) ? $room->bedding_setup : __( 'King Size Luxury Bed', 'awesome-hotel-booking' );
	$view_orientation = ! empty( $room->view_orientation ) ? $room->view_orientation : __( 'Panoramic City & Ocean View', 'awesome-hotel-booking' );
	$floor_area       = ! empty( $room->room_size ) ? $room->room_size : '45 m² / 485 sq.ft';
	$bathrooms_conf   = ! empty( $room->bathrooms ) ? $room->bathrooms : __( '1 En-suite Marble Bathroom with Rain Shower', 'awesome-hotel-booking' );

	// Amenities Parsing
	$amenities = array();
	if ( ! empty( $room->amenities ) ) {
		$decoded = json_decode( $room->amenities, true );
		$amenities = is_array( $decoded ) ? $decoded : array_filter( array_map( 'trim', explode( ',', $room->amenities ) ) );
	}

	// Gallery Image Resolution
	$gallery_ids = array();
	if ( ! empty( $room->gallery_ids ) ) {
		$decoded_gallery = json_decode( $room->gallery_ids, true );
		$gallery_ids = is_array( $decoded_gallery ) ? array_map( 'absint', $decoded_gallery ) : array_filter( array_map( 'absint', explode( ',', $room->gallery_ids ) ) );
	}
	if ( ! empty( $room->thumbnail_id ) ) {
		array_unshift( $gallery_ids, absint( $room->thumbnail_id ) );
	}
	$gallery_ids = array_unique( array_filter( $gallery_ids ) );

	$current_user = wp_get_current_user();
	$user_name    = is_user_logged_in() ? $current_user->display_name : '';
	$user_email   = is_user_logged_in() ? $current_user->user_email : '';

	ob_start();
	?>
	<div class="ahbn-details-container">
		<!-- Featured Image & Gallery Hero -->
		<div class="ahbn-details-hero">
			<div class="ahbn-main-slider" id="ahbnMainSlider">
				<?php if ( ! empty( $gallery_ids ) ) : ?>
					<?php foreach ( $gallery_ids as $index => $img_id ) : ?>
						<div class="ahbn-slide <?php echo 0 === $index ? 'active' : ''; ?>">
							<?php echo wp_get_attachment_image( $img_id, 'full', false, array( 'class' => 'ahbn-hero-img' ) ); ?>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div class="ahbn-slide active" style="background:#0a192f; display:flex; align-items:center; justify-content:center;">
						<span class="dashicons dashicons-building" style="font-size:64px; color:#c5a880; width:64px; height:64px;" aria-hidden="true"></span>
					</div>
				<?php endif; ?>

				<div class="ahbn-hero-overlay">
					<?php if ( ! empty( $room_number ) ) : ?>
						<span class="ahbn-gold-tag"><?php printf( esc_html__( 'Suite %s', 'awesome-hotel-booking' ), esc_html( $room_number ) ); ?></span>
					<?php endif; ?>
					<h1 class="ahbn-dynamic-title"><?php echo esc_html( $title ); ?></h1>
				</div>

				<?php if ( count( $gallery_ids ) > 1 ) : ?>
					<button type="button" class="ahbn-nav-btn prev" id="ahbnSlidePrev" aria-label="<?php esc_attr_e( 'Previous Image', 'awesome-hotel-booking' ); ?>">&#10094;</button>
					<button type="button" class="ahbn-nav-btn next" id="ahbnSlideNext" aria-label="<?php esc_attr_e( 'Next Image', 'awesome-hotel-booking' ); ?>">&#10095;</button>
				<?php endif; ?>
			</div>
		</div>

		<div class="ahbn-details-layout">
			<div class="ahbn-main-column">
				<!-- Classification & Architectural Badges -->
				<div class="ahbn-specs-bar">
					<div class="ahbn-spec-badge">
						<span class="dashicons dashicons-tag" aria-hidden="true"></span>
						<span><?php echo esc_html( $room_type ); ?></span>
					</div>
					<div class="ahbn-spec-badge">
						<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
						<span><?php printf( esc_html__( 'Capacity: %1$d Adults, %2$d Children', 'awesome-hotel-booking' ), absint( $max_adults ), absint( $max_children ) ); ?></span>
					</div>
				</div>

				<!-- Suite Specifications Table (Bedding, View, Floor Area, Bathrooms) -->
				<div class="ahbn-features-matrix">
					<div class="ahbn-matrix-item">
						<span class="dashicons dashicons-hotel" aria-hidden="true"></span>
						<div>
							<span class="ahbn-matrix-label"><?php esc_html_e( 'Bedding Setup', 'awesome-hotel-booking' ); ?></span>
							<strong class="ahbn-matrix-val"><?php echo esc_html( $bedding_setup ); ?></strong>
						</div>
					</div>
					<div class="ahbn-matrix-item">
						<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
						<div>
							<span class="ahbn-matrix-label"><?php esc_html_e( 'View Orientation', 'awesome-hotel-booking' ); ?></span>
							<strong class="ahbn-matrix-val"><?php echo esc_html( $view_orientation ); ?></strong>
						</div>
					</div>
					<div class="ahbn-matrix-item">
						<span class="dashicons dashicons-image-rotate" aria-hidden="true"></span>
						<div>
							<span class="ahbn-matrix-label"><?php esc_html_e( 'Floor Area / Dimensions', 'awesome-hotel-booking' ); ?></span>
							<strong class="ahbn-matrix-val"><?php echo esc_html( $floor_area ); ?></strong>
						</div>
					</div>
					<div class="ahbn-matrix-item">
						<span class="dashicons dashicons-admin-home" aria-hidden="true"></span>
						<div>
							<span class="ahbn-matrix-label"><?php esc_html_e( 'Bathrooms Configuration', 'awesome-hotel-booking' ); ?></span>
							<strong class="ahbn-matrix-val"><?php echo esc_html( $bathrooms_conf ); ?></strong>
						</div>
					</div>
				</div>

				<!-- Overview Content -->
				<section class="ahbn-content-block">
					<h3 class="ahbn-section-title"><?php esc_html_e( 'Suite Overview', 'awesome-hotel-booking' ); ?></h3>
					<div class="ahbn-description">
						<?php echo ! empty( $description ) ? wp_kses_post( wpautop( $description ) ) : '<p>' . esc_html__( 'Experience unmatched comfort and style in this luxury suite, equipped with state-of-the-art hospitality conveniences.', 'awesome-hotel-booking' ) . '</p>'; ?>
					</div>
				</section>

				<!-- Standard In-Suite Amenities -->
				<?php if ( ! empty( $amenities ) ) : ?>
					<section class="ahbn-content-block" style="margin-top:35px;">
						<h3 class="ahbn-section-title"><?php esc_html_e( 'Standard In-Suite Amenities (Complimentary)', 'awesome-hotel-booking' ); ?></h3>
						<div class="ahbn-details-amenities">
							<?php foreach ( $amenities as $item ) :
								if ( ! is_string( $item ) || '' === trim( $item ) ) {
									continue;
								}
								$label = str_replace( array( '-', '_' ), ' ', ucfirst( $item ) );
								?>
								<div class="ahbn-spec-item">
									<svg viewBox="0 0 24 24" fill="none" stroke="#c5a880" stroke-width="2.5" width="18" height="18" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>
									<span><?php echo esc_html( $label ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>

				<!-- Paid Extra Add-ons Checkout Upsells -->
				<?php if ( ! empty( $paid_extras ) ) : ?>
					<section class="ahbn-content-block" style="margin-top:35px;">
						<h3 class="ahbn-section-title"><?php esc_html_e( 'Paid Extra Add-ons (Checkout Upsells)', 'awesome-hotel-booking' ); ?></h3>
						<div class="ahbn-extras-grid">
							<?php foreach ( $paid_extras as $extra ) : ?>
								<label class="ahbn-extra-card">
									<input type="checkbox" name="extras[]" value="<?php echo esc_attr( $extra->id ); ?>" form="ahbnBookingForm" class="ahbn-extra-checkbox" data-price="<?php echo esc_attr( $extra->price ); ?>" data-charge="<?php echo esc_attr( $extra->charge_type ); ?>">
									<div class="ahbn-extra-info">
										<strong><?php echo esc_html( $extra->name ); ?></strong>
										<span><?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $extra->price ) : '$' . number_format_i18n( $extra->price, 2 ) ); ?> (<?php echo esc_html( str_replace( '_', ' ', $extra->charge_type ) ); ?>)</span>
									</div>
								</label>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>
			</div>

			<div class="ahbn-sidebar">
				<div class="ahbn-booking-card">
					<div class="ahbn-price-tag">
						<span class="ahbn-amt" id="ahbnBasePrice" data-rate="<?php echo esc_attr( (string) $price ); ?>">
							<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $price ) : '$' . number_format_i18n( $price, 2 ) ); ?>
						</span>
						<span class="ahbn-unit"><?php esc_html_e( '/ night', 'awesome-hotel-booking' ); ?></span>
					</div>

					<form id="ahbnBookingForm">
						<input type="hidden" name="security" value="<?php echo esc_attr( wp_create_nonce( 'ahbn_booking_nonce' ) ); ?>">
						<input type="hidden" name="room_id" value="<?php echo esc_attr( (string) $rid ); ?>">

						<!-- Live Interactive Availability Calendar Preview Bar -->
						<div class="ahbn-input-group">
							<label><?php esc_html_e( 'Live Availability Calendar', 'awesome-hotel-booking' ); ?></label>
							<div class="ahbn-mini-calendar-hint">
								<span class="ahbn-dot available"></span> <?php esc_html_e( 'Available Dates', 'awesome-hotel-booking' ); ?>
								<span class="ahbn-dot booked" style="margin-left:12px;"></span> <?php esc_html_e( 'Already Reserved', 'awesome-hotel-booking' ); ?>
							</div>
						</div>

						<div class="ahbn-form-grid">
							<div class="ahbn-input-group">
								<label for="ahbn_checkin"><?php esc_html_e( 'Check-in', 'awesome-hotel-booking' ); ?></label>
								<input type="date" name="checkin" id="ahbn_checkin" required min="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
							</div>
							<div class="ahbn-input-group">
								<label for="ahbn_checkout"><?php esc_html_e( 'Check-out', 'awesome-hotel-booking' ); ?></label>
								<input type="date" name="checkout" id="ahbn_checkout" required>
							</div>
						</div>

						<div class="ahbn-form-grid" style="margin-top:10px;">
							<div class="ahbn-input-group">
								<label for="ahbn_adults"><?php esc_html_e( 'Adults', 'awesome-hotel-booking' ); ?></label>
								<select name="adults" id="ahbn_adults" class="ahbn-select-field">
									<option value="1">1</option>
									<option value="2" selected>2</option>
									<option value="3">3</option>
									<option value="4">4+</option>
								</select>
							</div>
							<div class="ahbn-input-group">
								<label for="ahbn_children"><?php esc_html_e( 'Children', 'awesome-hotel-booking' ); ?></label>
								<select name="children" id="ahbn_children" class="ahbn-select-field">
									<option value="0" selected>0</option>
									<option value="1">1</option>
									<option value="2">2</option>
								</select>
							</div>
						</div>

						<div class="ahbn-input-group">
							<label for="ahbn_full_name"><?php esc_html_e( 'Full Name', 'awesome-hotel-booking' ); ?></label>
							<input type="text" name="full_name" id="ahbn_full_name" value="<?php echo esc_attr( $user_name ); ?>" required autocomplete="name">
						</div>

						<div class="ahbn-input-group">
							<label for="ahbn_email"><?php esc_html_e( 'Email Address', 'awesome-hotel-booking' ); ?></label>
							<input type="email" name="email" id="ahbn_email" value="<?php echo esc_attr( $user_email ); ?>" required autocomplete="email">
						</div>

						<div class="ahbn-price-summary-box">
							<div class="ahbn-summary-row">
								<span><?php esc_html_e( 'Stay Duration', 'awesome-hotel-booking' ); ?></span>
								<span id="stayNights"><?php esc_html_e( '0 Nights', 'awesome-hotel-booking' ); ?></span>
							</div>
							<div class="ahbn-summary-row total">
								<span><?php esc_html_e( 'Estimated Total', 'awesome-hotel-booking' ); ?></span>
								<span id="totalCost">
									<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $price ) : '$' . number_format_i18n( $price, 2 ) ); ?>
								</span>
							</div>
						</div>

						<button type="submit" class="ahbn-book-now-btn" id="ahbnSubmitBooking">
							<?php esc_html_e( 'Complete Reservation', 'awesome-hotel-booking' ); ?>
						</button>

						<div id="ahbnBookingMsg" style="display:none; margin-top:15px; padding:12px; border-radius:8px; font-size:13px; text-align:center;" role="alert"></div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<style>
		.ahbn-details-container { max-width: 1240px; margin: 40px auto; padding: 0 20px; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; box-sizing: border-box; }
		.ahbn-details-hero { height: 480px; border-radius: 20px; overflow: hidden; position: relative; background: #0a192f; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
		.ahbn-main-slider { position: relative; width: 100%; height: 100%; }
		.ahbn-slide { position: absolute; inset:0; opacity:0; transition: opacity 0.8s ease; }
		.ahbn-slide.active { opacity:1; }
		.ahbn-hero-img { width: 100%; height:100%; object-fit: cover; filter: brightness(0.7); display: block; }
		.ahbn-hero-overlay { position: absolute; bottom: 40px; left: 40px; color: #fff; z-index: 5; }
		.ahbn-dynamic-title { font-size: 38px; margin: 8px 0 0; font-weight: 800; color:#fff; text-shadow: 2px 2px 10px rgba(0,0,0,0.4); line-height: 1.2; font-family: 'Playfair Display', Georgia, serif; }
		.ahbn-gold-tag { color: #c5a880; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; font-size: 13px; font-family: 'JetBrains Mono', monospace; }
		.ahbn-nav-btn { position: absolute; top:50%; transform: translateY(-50%); background: rgba(10,25,47,0.7); border:none; color:#fff; width:44px; height:44px; border-radius:50%; cursor:pointer; z-index: 10; font-size: 18px; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
		.ahbn-nav-btn:hover { background: #c5a880; color:#0a192f; }
		.ahbn-nav-btn.prev { left: 20px; }
		.ahbn-nav-btn.next { right: 20px; }
		.ahbn-specs-bar { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
		.ahbn-spec-badge { display: inline-flex; align-items: center; gap: 6px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 700; color: #334155; }
		.ahbn-spec-badge .dashicons { font-size: 16px; width: 16px; height: 16px; color: #c5a880; }
		
		/* Features Matrix Grid */
		.ahbn-features-matrix { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin-bottom: 30px; }
		.ahbn-matrix-item { display: flex; align-items: flex-start; gap: 12px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; box-shadow: 0 1px 3px rgba(15,23,42,0.03); }
		.ahbn-matrix-item .dashicons { font-size: 22px; width: 22px; height: 22px; color: #c5a880; margin-top: 2px; }
		.ahbn-matrix-label { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 700; }
		.ahbn-matrix-val { display: block; font-size: 13px; color: #0a192f; font-weight: 700; margin-top: 2px; }

		.ahbn-details-layout { display: grid; grid-template-columns: 1.6fr 1fr; gap: 40px; margin-top: 20px; }
		.ahbn-section-title { border-bottom: 2px solid #c5a880; display: inline-block; padding-bottom: 5px; margin: 0 0 20px; font-size: 20px; color: #0a192f; font-weight: 800; font-family: 'Playfair Display', Georgia, serif; }
		.ahbn-booking-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(10,25,47,0.06); position: sticky; top: 20px; }
		.ahbn-amt { font-size: 30px; font-weight: 800; color: #0a192f; font-family: 'JetBrains Mono', monospace; }
		.ahbn-unit { font-size: 14px; color: #64748b; font-weight: 600; }
		.ahbn-price-tag { margin-bottom: 20px; }
		.ahbn-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
		.ahbn-input-group { margin-bottom: 16px; }
		.ahbn-input-group label { display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
		.ahbn-input-group input, .ahbn-select-field { width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box; background: #fff; color: #0f172a; }
		.ahbn-input-group input:focus, .ahbn-select-field:focus { border-color: #0a192f; outline: none; box-shadow: 0 0 0 3px rgba(10,25,47,0.1); }
		
		/* Extras Checkboxes */
		.ahbn-extras-grid { display: flex; flex-direction: column; gap: 10px; }
		.ahbn-extra-card { display: flex; align-items: center; gap: 12px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 16px; border-radius: 10px; cursor: pointer; transition: 0.2s; }
		.ahbn-extra-card:hover { border-color: #c5a880; }
		.ahbn-extra-checkbox { width: 18px; height: 18px; accent-color: #0a192f; cursor: pointer; }
		.ahbn-extra-info { display: flex; justify-content: space-between; width: 100%; font-size: 13px; }
		.ahbn-extra-info strong { color: #0a192f; font-weight: 700; }
		.ahbn-extra-info span { color: #64748b; font-family: 'JetBrains Mono', monospace; font-size: 12px; }

		/* Mini Availability Calendar Hint */
		.ahbn-mini-calendar-hint { font-size: 11px; color: #64748b; display: flex; align-items: center; margin-bottom: 8px; }
		.ahbn-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 4px; }
		.ahbn-dot.available { background: #10b981; }
		.ahbn-dot.booked { background: #ef4444; }

		.ahbn-price-summary-box { background: #0a192f; color: #fff; padding: 20px; border-radius: 14px; margin: 20px 0; }
		.ahbn-summary-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px; }
		.ahbn-summary-row.total { border-top: 1px solid rgba(197,168,128,0.3); padding-top: 12px; margin-top: 10px; font-weight: 800; font-size: 18px; color: #c5a880; font-family: 'JetBrains Mono', monospace; }
		.ahbn-book-now-btn { width: 100%; background: #0a192f; color: #fff; border: 1px solid #0a192f; padding: 16px; border-radius: 10px; font-weight: 800; cursor: pointer; transition: 0.3s; font-size: 15px; }
		.ahbn-book-now-btn:hover { background: #c5a880; border-color:#c5a880; color:#0a192f; transform: translateY(-1px); }
		.ahbn-details-amenities { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
		.ahbn-spec-item { display: flex; align-items: center; gap: 10px; font-size: 13px; background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #f1f5f9; color: #334155; }
		@media (max-width: 850px) { .ahbn-details-layout { grid-template-columns: 1fr; } .ahbn-details-hero { height: 350px; } .ahbn-features-matrix { grid-template-columns: 1fr; } }
	</style>

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Blocked dates array from backend
		const bookedDates = <?php echo wp_json_encode( $booked_dates ); ?>;
		const checkinEl   = document.getElementById('ahbn_checkin');
		const checkoutEl  = document.getElementById('ahbn_checkout');

		if (checkinEl) {
			checkinEl.addEventListener('input', function() {
				if (bookedDates.includes(this.value)) {
					alert('<?php echo esc_js( __( 'Selected check-in date is already booked. Please choose another date.', 'awesome-hotel-booking' ) ); ?>');
					this.value = '';
				}
			});
		}
		if (checkoutEl) {
			checkoutEl.addEventListener('input', function() {
				if (bookedDates.includes(this.value)) {
					alert('<?php echo esc_js( __( 'Selected check-out date conflicts with an existing reservation.', 'awesome-hotel-booking' ) ); ?>');
					this.value = '';
				}
			});
		}

		// Slider Navigation
		const slides = document.querySelectorAll('.ahbn-slide');
		let currentSlide = 0;
		function showSlide(index) {
			if (!slides.length) return;
			slides.forEach(s => s.classList.remove('active'));
			currentSlide = (index + slides.length) % slides.length;
			slides[currentSlide].classList.add('active');
		}
		const btnPrev = document.getElementById('ahbnSlidePrev');
		const btnNext = document.getElementById('ahbnSlideNext');
		if (btnPrev) btnPrev.addEventListener('click', () => showSlide(currentSlide - 1));
		if (btnNext) btnNext.addEventListener('click', () => showSlide(currentSlide + 1));

		// Live Calculation with Extras
		const pricePerNight = parseFloat(document.getElementById('ahbnBasePrice').dataset.rate) || 0;
		const stayNightsEl  = document.getElementById('stayNights');
		const totalCostEl   = document.getElementById('totalCost');
		const extraCheckboxes = document.querySelectorAll('.ahbn-extra-checkbox');
		const adultsEl      = document.getElementById('ahbn_adults');
		const childrenEl    = document.getElementById('ahbn_children');

		function calculateDuration() {
			if (!checkinEl.value || !checkoutEl.value) return;

			const start = new Date(checkinEl.value);
			const end   = new Date(checkoutEl.value);
			const diffTime = end.getTime() - start.getTime();
			const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

			if (diffDays > 0) {
				let baseTotal = diffDays * pricePerNight;
				const adults = parseInt(adultsEl.value) || 1;
				const children = parseInt(childrenEl.value) || 0;
				const guestsCount = adults + children;

				extraCheckboxes.forEach(box => {
					if (box.checked) {
						const exPrice = parseFloat(box.dataset.price) || 0;
						const chargeType = box.dataset.charge;
						if (chargeType === 'per_night') {
							baseTotal += (exPrice * diffDays);
						} else if (chargeType === 'per_person') {
							baseTotal += (exPrice * guestsCount);
						} else if (chargeType === 'per_night_per_person') {
							baseTotal += (exPrice * diffDays * guestsCount);
						} else {
							baseTotal += exPrice; // per_stay
						}
					}
				});

				stayNightsEl.innerText = diffDays + ' <?php echo esc_js( __( 'Nights', 'awesome-hotel-booking' ) ); ?>';
				totalCostEl.innerText  = baseTotal.toFixed(2);
			} else {
				stayNightsEl.innerText = '0 <?php echo esc_js( __( 'Nights', 'awesome-hotel-booking' ) ); ?>';
			}
		}

		if (checkinEl && checkoutEl) {
			checkinEl.addEventListener('change', function() {
				const minEnd = new Date(this.value);
				minEnd.setDate(minEnd.getDate() + 1);
				checkoutEl.min = minEnd.toISOString().split('T')[0];
				if (checkoutEl.value && checkoutEl.value <= this.value) {
					checkoutEl.value = checkoutEl.min;
				}
				calculateDuration();
			});
			checkoutEl.addEventListener('change', calculateDuration);
		}

		extraCheckboxes.forEach(cb => cb.addEventListener('change', calculateDuration));
		if (adultsEl) adultsEl.addEventListener('change', calculateDuration);
		if (childrenEl) childrenEl.addEventListener('change', calculateDuration);

		// AJAX Booking Submission
		const form = document.getElementById('ahbnBookingForm');
		const msgEl = document.getElementById('ahbnBookingMsg');
		const submitBtn = document.getElementById('ahbnSubmitBooking');

		if (form) {
			form.addEventListener('submit', function(e) {
				e.preventDefault();

				<?php if ( ! is_user_logged_in() ) : ?>
					msgEl.style.display = 'block';
					msgEl.style.background = '#fff1f2';
					msgEl.style.color = '#9f1239';
					msgEl.innerHTML = '<?php echo esc_js( __( 'Please log in to make a reservation.', 'awesome-hotel-booking' ) ); ?>';
					return;
				<?php endif; ?>

				const origText = submitBtn.innerText;
				submitBtn.innerText = '<?php echo esc_js( __( 'Processing...', 'awesome-hotel-booking' ) ); ?>';
				submitBtn.disabled = true;

				const formData = new FormData(form);
				formData.append('action', 'ahbn_save_booking');

				fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
					method: 'POST',
					body: formData
				})
				.then(r => r.json())
				.then(data => {
					msgEl.style.display = 'block';
					if (data.success) {
						msgEl.style.background = '#ecfdf5';
						msgEl.style.color = '#065f46';
						msgEl.innerText = data.data.message || '<?php echo esc_js( __( 'Reservation confirmed!', 'awesome-hotel-booking' ) ); ?>';
						if (data.data.redirect) {
							setTimeout(() => { window.location.href = data.data.redirect; }, 1200);
						}
					} else {
						msgEl.style.background = '#fff1f2';
						msgEl.style.color = '#9f1239';
						msgEl.innerText = data.data.message || '<?php echo esc_js( __( 'Reservation failed.', 'awesome-hotel-booking' ) ); ?>';
						submitBtn.disabled = false;
						submitBtn.innerText = origText;
					}
				})
				.catch(() => {
					msgEl.style.display = 'block';
					msgEl.style.background = '#fff1f2';
					msgEl.style.color = '#9f1239';
					msgEl.innerText = '<?php echo esc_js( __( 'Connection error. Please try again.', 'awesome-hotel-booking' ) ); ?>';
					submitBtn.disabled = false;
					submitBtn.innerText = origText;
				});
			});
		}
	});
	</script>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ahbn_room_details', 'ahbn_room_details_shortcode_callback' );