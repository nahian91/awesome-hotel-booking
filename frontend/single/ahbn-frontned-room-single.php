<?php
/**
 * [ahbn_room_details] Single Room Details & AJAX Reservation Engine.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*--------------------------------------------------------------
# 1. Rewrite Rules & Query Variables
--------------------------------------------------------------*/

/**
 * Register rewrite rules for custom room detail endpoints.
 *
 * @return void
 */
function ahbn_room_details_rewrite_rules() {
	add_rewrite_rule(
		'^ahbn-room-details/([^/]+)/?',
		'index.php?pagename=ahbn-room-details&ahbn_room_name=$matches[1]',
		'top'
	);
}
add_action( 'init', 'ahbn_room_details_rewrite_rules' );

/**
 * Register public query variables.
 *
 * @param array $vars Public query vars.
 * @return array Modified query vars.
 */
function ahbn_room_details_query_vars( $vars ) {
	$vars[] = 'ahbn_room_name';
	return $vars;
}
add_filter( 'query_vars', 'ahbn_room_details_query_vars' );

/*--------------------------------------------------------------
# 2. AJAX Booking Submission Handler
--------------------------------------------------------------*/

/**
 * Handle booking submission from room details modal/form.
 *
 * @return void
 */
function ahbn_handle_booking_submission() {
	// 1. Security Check: Nonce Verification.
	$nonce = isset( $_POST['security'] ) ? sanitize_key( wp_unslash( $_POST['security'] ) ) : '';
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'ahbn_booking_nonce' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Security verification failed. Please refresh the page.', 'awesome-hotel-booking' ) ) );
	}

	// 2. Authentication Check.
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Login session expired. Please sign in to reserve this suite.', 'awesome-hotel-booking' ) ) );
	}

	// 3. User Authorization Check.
	if ( ! current_user_can( 'read' ) ) {
		wp_send_json_error( array( 'message' => esc_html__( 'You do not have permission to perform this action.', 'awesome-hotel-booking' ) ) );
	}

	// 4. Booking ID for Modifications (CVE-2025-14352 Prevention).
	$booking_id = isset( $_POST['booking_id'] ) ? absint( wp_unslash( $_POST['booking_id'] ) ) : 0;

	if ( $booking_id > 0 ) {
		$existing_post = get_post( $booking_id );

		if ( ! $existing_post || 'ahbn_booking' !== $existing_post->post_type ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Booking record not found.', 'awesome-hotel-booking' ) ) );
		}

		if ( (int) $existing_post->post_author !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized: You cannot modify this reservation.', 'awesome-hotel-booking' ) ) );
		}
	}

	// 5. Input Sanitization & Validation.
	$ahbn_room_id   = isset( $_POST['room_id'] ) ? absint( wp_unslash( $_POST['room_id'] ) ) : 0;
	$ahbn_full_name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
	$ahbn_email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$ahbn_checkin   = isset( $_POST['checkin'] ) ? sanitize_text_field( wp_unslash( $_POST['checkin'] ) ) : '';
	$ahbn_checkout  = isset( $_POST['checkout'] ) ? sanitize_text_field( wp_unslash( $_POST['checkout'] ) ) : '';
	$ahbn_adults    = isset( $_POST['adults'] ) ? max( 1, absint( wp_unslash( $_POST['adults'] ) ) ) : 1;
	$ahbn_children  = isset( $_POST['children'] ) ? absint( wp_unslash( $_POST['children'] ) ) : 0;
	$ahbn_payment   = isset( $_POST['payment_method'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_method'] ) ) : 'Pay on Arrival';

	if ( empty( $ahbn_full_name ) || empty( $ahbn_email ) || ! $ahbn_room_id ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Please complete all required fields.', 'awesome-hotel-booking' ) ) );
	}

	$room_post = get_post( $ahbn_room_id );
	if ( ! $room_post || 'ahbn_room' !== $room_post->post_type ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid room selection.', 'awesome-hotel-booking' ) ) );
	}

	// Date Validation & Nights Calculation.
	$time_in  = strtotime( $ahbn_checkin );
	$time_out = strtotime( $ahbn_checkout );

	if ( ! $time_in || ! $time_out || $time_out <= $time_in ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid check-in or check-out dates selected.', 'awesome-hotel-booking' ) ) );
	}

	$nights = max( 1, (int) round( ( $time_out - $time_in ) / DAY_IN_SECONDS ) );

	// Server-Side Price Calculation (Prevents Client-Side Tampering).
	$raw_room_price    = get_post_meta( $ahbn_room_id, 'ahbn_room_price', true );
	$room_rate         = (float) preg_replace( '/[^\d.]/', '', (string) $raw_room_price );
	$calculated_total  = $nights * $room_rate;

	// 6. Save or Update Booking Post.
	$post_data = array(
		'post_type'   => 'ahbn_booking',
		'post_title'  => sprintf(
			/* translators: 1: Guest full name, 2: Room title */
			esc_html__( 'Booking: %1$s - %2$s', 'awesome-hotel-booking' ),
			$ahbn_full_name,
			get_the_title( $ahbn_room_id )
		),
		'post_status' => 'publish',
	);

	if ( $booking_id > 0 ) {
		$post_data['ID'] = $booking_id;
		$result_id       = wp_update_post( $post_data );
	} else {
		$post_data['post_author'] = get_current_user_id();
		$result_id                = wp_insert_post( $post_data );
	}

	if ( ! is_wp_error( $result_id ) && $result_id > 0 ) {
		update_post_meta( $result_id, 'ahbn_book_customer', $ahbn_full_name );
		update_post_meta( $result_id, 'ahbn_book_email', $ahbn_email );
		update_post_meta( $result_id, 'ahbn_book_room_id', $ahbn_room_id );
		update_post_meta( $result_id, 'ahbn_book_checkin', $ahbn_checkin );
		update_post_meta( $result_id, 'ahbn_book_checkout', $ahbn_checkout );
		update_post_meta( $result_id, 'ahbn_book_guests', sprintf( '%1$d Adults, %2$d Children', $ahbn_adults, $ahbn_children ) );
		update_post_meta( $result_id, 'ahbn_book_payment', $ahbn_payment );
		update_post_meta( $result_id, 'ahbn_book_total', number_format( $calculated_total, 2, '.', '' ) );

		if ( ! $booking_id ) {
			update_post_meta( $result_id, 'status', 'pending' );
		}

		// Clear user booking caches.
		delete_transient( 'ahbn_user_bookings_' . get_current_user_id() );
		delete_transient( 'ahbn_booking_count_' . $ahbn_room_id );

		// Target URL for Confirmation.
		$thanks_pid  = (int) get_option( 'ahbn_thanks_page' );
		$redirect_url = $thanks_pid ? add_query_arg( 'booking_id', $result_id, get_permalink( $thanks_pid ) ) : '';

		wp_send_json_success(
			array(
				'booking_id' => $result_id,
				'redirect'   => $redirect_url,
				'message'    => esc_html__( 'Reservation confirmed successfully!', 'awesome-hotel-booking' ),
			)
		);
	} else {
		wp_send_json_error( array( 'message' => esc_html__( 'System error: Could not save reservation.', 'awesome-hotel-booking' ) ) );
	}
}
add_action( 'wp_ajax_ahbn_save_booking', 'ahbn_handle_booking_submission' );
add_action( 'wp_ajax_nopriv_ahbn_save_booking', 'ahbn_handle_booking_submission' );

/*--------------------------------------------------------------
# 3. [ahbn_room_details] Shortcode Implementation
--------------------------------------------------------------*/

/**
 * Render single room details and booking interface.
 *
 * @return string Rendered HTML.
 */
function ahbn_room_details_shortcode_callback() {
	global $post;
	$ahbn_room = null;

	$room_slug = sanitize_title( get_query_var( 'ahbn_room_name' ) );
	if ( ! empty( $room_slug ) ) {
		$ahbn_room = get_page_by_path( $room_slug, OBJECT, 'ahbn_room' );
	}

	if ( ! $ahbn_room && is_singular( 'ahbn_room' ) ) {
		$ahbn_room = $post;
	}

	if ( ! $ahbn_room || 'ahbn_room' !== $ahbn_room->post_type ) {
		return '<div class="ahbn-empty-state" style="text-align:center; padding:80px 20px; font-family:\'Inter\',sans-serif;">
					<h2 style="font-size:24px; color:#0A1A38; font-weight:800;">' . esc_html__( 'Suite Not Found', 'awesome-hotel-booking' ) . '</h2>
					<p style="color:#64748b; font-size:14px; margin-top:8px;">' . esc_html__( 'Please return to our room listings to explore available suites.', 'awesome-hotel-booking' ) . '</p>
					<a href="' . esc_url( home_url( '/rooms/' ) ) . '" class="button button-primary" style="margin-top:16px;">' . esc_html__( 'Browse Suites', 'awesome-hotel-booking' ) . '</a>
				</div>';
	}

	$rid       = $ahbn_room->ID;
	$raw_price = get_post_meta( $rid, 'ahbn_room_price', true );
	$price     = (float) preg_replace( '/[^\d.]/', '', (string) $raw_price );
	$rno       = (string) get_post_meta( $rid, 'ahbn_room_no', true );
	$amen      = (array) get_post_meta( $rid, 'ahbn_room_amenities', true );
	$gallery   = (string) get_post_meta( $rid, 'ahbn_room_gallery', true );

	$current_user = wp_get_current_user();
	$user_name    = is_user_logged_in() ? $current_user->display_name : '';
	$user_email   = is_user_logged_in() ? $current_user->user_email : '';

	ob_start();
	?>
	<div class="ahbn-details-container">
		<div class="ahbn-details-hero">
			<div class="ahbn-main-slider" id="ahbnMainSlider">
				<?php
				$gallery_ids = array_filter( array_map( 'absint', explode( ',', $gallery ) ) );
				if ( has_post_thumbnail( $rid ) ) {
					array_unshift( $gallery_ids, get_post_thumbnail_id( $rid ) );
				}
				$gallery_ids = array_unique( $gallery_ids );

				if ( ! empty( $gallery_ids ) ) :
					foreach ( $gallery_ids as $index => $img_id ) :
						?>
						<div class="ahbn-slide <?php echo 0 === $index ? 'active' : ''; ?>">
							<?php echo wp_get_attachment_image( $img_id, 'full', false, array( 'class' => 'ahbn-hero-img' ) ); ?>
						</div>
						<?php
					endforeach;
				else :
					?>
					<div class="ahbn-slide active" style="background:#0f172a; display:flex; align-items:center; justify-content:center;">
						<span class="dashicons dashicons-format-image" style="font-size:64px; color:#334155;" aria-hidden="true"></span>
					</div>
				<?php endif; ?>

				<div class="ahbn-hero-overlay">
					<?php if ( ! empty( $rno ) ) : ?>
						<span class="ahbn-gold-tag">
							<?php
							printf(
								/* translators: %s: Room/Suite number */
								esc_html__( 'Suite %s', 'awesome-hotel-booking' ),
								esc_html( $rno )
							);
							?>
						</span>
					<?php endif; ?>
					<h1 class="ahbn-dynamic-title"><?php echo esc_html( $ahbn_room->post_title ); ?></h1>
				</div>

				<?php if ( count( $gallery_ids ) > 1 ) : ?>
					<button type="button" class="ahbn-nav-btn prev" id="ahbnSlidePrev" aria-label="<?php esc_attr_e( 'Previous Image', 'awesome-hotel-booking' ); ?>">&#10094;</button>
					<button type="button" class="ahbn-nav-btn next" id="ahbnSlideNext" aria-label="<?php esc_attr_e( 'Next Image', 'awesome-hotel-booking' ); ?>">&#10095;</button>
				<?php endif; ?>
			</div>
		</div>

		<div class="ahbn-details-layout">
			<div class="ahbn-main-column">
				<section class="ahbn-content-block">
					<h3 class="ahbn-section-title"><?php esc_html_e( 'Suite Overview', 'awesome-hotel-booking' ); ?></h3>
					<div class="ahbn-description">
						<?php echo wp_kses_post( wpautop( $ahbn_room->post_content ) ); ?>
					</div>
				</section>

				<?php if ( ! empty( $amen ) ) : ?>
					<section class="ahbn-content-block" style="margin-top:40px;">
						<h3 class="ahbn-section-title"><?php esc_html_e( 'Featured Amenities', 'awesome-hotel-booking' ); ?></h3>
						<div class="ahbn-details-amenities">
							<?php
							foreach ( $amen as $item ) :
								if ( ! is_string( $item ) || '' === trim( $item ) ) {
									continue;
								}
								$label = str_replace( array( '-', '_' ), ' ', ucfirst( $item ) );
								?>
								<div class="ahbn-spec-item">
									<svg viewBox="0 0 24 24" fill="none" stroke="#bf9441" stroke-width="2.5" width="18" height="18" aria-hidden="true"><polyline points="20 6 9 17 4 12"></polyline></svg>
									<span><?php echo esc_html( $label ); ?></span>
								</div>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>
			</div>

			<div class="ahbn-sidebar">
				<div class="ahbn-booking-card">
					<div class="ahbn-price-tag">
						<span class="ahbn-amt" id="ahbnBasePrice" data-rate="<?php echo esc_attr( (string) $price ); ?>">
							<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $price ) : (string) number_format_i18n( $price, 2 ) ); ?>
						</span>
						<span class="ahbn-unit"><?php esc_html_e( '/ night', 'awesome-hotel-booking' ); ?></span>
					</div>

					<form id="ahbnBookingForm">
						<input type="hidden" name="security" value="<?php echo esc_attr( wp_create_nonce( 'ahbn_booking_nonce' ) ); ?>">
						<input type="hidden" name="room_id" value="<?php echo esc_attr( (string) $rid ); ?>">

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
									<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $price ) : (string) number_format_i18n( $price, 2 ) ); ?>
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
		.ahbn-details-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #1e293b; }
		.ahbn-details-hero { height: 480px; border-radius: 20px; overflow: hidden; position: relative; background: #000; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
		.ahbn-main-slider { position: relative; width: 100%; height: 100%; }
		.ahbn-slide { position: absolute; inset:0; opacity:0; transition: opacity 0.8s ease; }
		.ahbn-slide.active { opacity:1; }
		.ahbn-hero-img { width: 100%; height:100%; object-fit: cover; filter: brightness(0.7); display: block; }
		.ahbn-hero-overlay { position: absolute; bottom: 40px; left: 40px; color: #fff; z-index: 5; }
		.ahbn-dynamic-title { font-size: 38px; margin: 8px 0 0; font-weight: 800; color:#fff; text-shadow: 2px 2px 10px rgba(0,0,0,0.3); line-height: 1.2; }
		.ahbn-gold-tag { color: #bf9441; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; font-size: 13px; }
		.ahbn-nav-btn { position: absolute; top:50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); border:none; color:#fff; width:44px; height:44px; border-radius:50%; cursor:pointer; z-index: 10; font-size: 18px; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
		.ahbn-nav-btn:hover { background: #bf9441; }
		.ahbn-nav-btn.prev { left: 20px; }
		.ahbn-nav-btn.next { right: 20px; }
		.ahbn-details-layout { display: grid; grid-template-columns: 1.6fr 1fr; gap: 40px; margin-top: 40px; }
		.ahbn-section-title { border-bottom: 2px solid #bf9441; display: inline-block; padding-bottom: 5px; margin: 0 0 20px; font-size: 22px; color: #0A1A38; font-weight: 800; }
		.ahbn-booking-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); position: sticky; top: 20px; }
		.ahbn-amt { font-size: 30px; font-weight: 800; color: #0f172a; }
		.ahbn-unit { font-size: 14px; color: #64748b; font-weight: 500; }
		.ahbn-price-tag { margin-bottom: 20px; }
		.ahbn-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
		.ahbn-input-group { margin-bottom: 16px; }
		.ahbn-input-group label { display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
		.ahbn-input-group input, .ahbn-select-field { width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; box-sizing: border-box; background: #fff; }
		.ahbn-input-group input:focus, .ahbn-select-field:focus { border-color: #bf9441; outline: none; box-shadow: 0 0 0 3px rgba(191,148,65,0.15); }
		.ahbn-price-summary-box { background: #0f172a; color: #fff; padding: 20px; border-radius: 14px; margin: 20px 0; }
		.ahbn-summary-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px; }
		.ahbn-summary-row.total { border-top: 1px solid #334155; padding-top: 12px; margin-top: 10px; font-weight: 800; font-size: 17px; color: #bf9441; }
		.ahbn-book-now-btn { width: 100%; background: #bf9441; color: #fff; border: none; padding: 16px; border-radius: 10px; font-weight: 800; cursor: pointer; transition: 0.3s; font-size: 15px; }
		.ahbn-book-now-btn:hover { background: #0f172a; transform: translateY(-1px); }
		.ahbn-details-amenities { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
		.ahbn-spec-item { display: flex; align-items: center; gap: 10px; font-size: 13px; background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #f1f5f9; color: #334155; }
		@media (max-width: 850px) { .ahbn-details-layout { grid-template-columns: 1fr; } .ahbn-details-hero { height: 350px; } }
	</style>

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Slider Navigation.
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

		// Live Calculation.
		const pricePerNight = parseFloat(document.getElementById('ahbnBasePrice').dataset.rate) || 0;
		const checkinEl     = document.getElementById('ahbn_checkin');
		const checkoutEl    = document.getElementById('ahbn_checkout');
		const stayNightsEl  = document.getElementById('stayNights');
		const totalCostEl   = document.getElementById('totalCost');

		function calculateDuration() {
			if (!checkinEl.value || !checkoutEl.value) return;

			const start = new Date(checkinEl.value);
			const end   = new Date(checkoutEl.value);
			const diffTime = end.getTime() - start.getTime();
			const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

			if (diffDays > 0) {
				const total = (diffDays * pricePerNight).toFixed(2);
				stayNightsEl.innerText = diffDays + ' <?php echo esc_js( __( 'Nights', 'awesome-hotel-booking' ) ); ?>';
				totalCostEl.innerText  = total;
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

		// AJAX Booking Submission.
		const form = document.getElementById('ahbnBookingForm');
		const msgEl = document.getElementById('ahbnBookingMsg');
		const submitBtn = document.getElementById('ahbnSubmitBooking');

		if (form) {
			form.addEventListener('submit', function(e) {
				e.preventDefault();

				<?php if ( ! is_user_logged_in() ) : ?>
					msgEl.style.display = 'block';
					msgEl.style.background = '#fef2f2';
					msgEl.style.color = '#991b1b';
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
						msgEl.style.background = '#f0fdf4';
						msgEl.style.color = '#166534';
						msgEl.innerText = data.data.message || '<?php echo esc_js( __( 'Reservation confirmed!', 'awesome-hotel-booking' ) ); ?>';
						if (data.data.redirect) {
							setTimeout(() => { window.location.href = data.data.redirect; }, 1200);
						}
					} else {
						msgEl.style.background = '#fef2f2';
						msgEl.style.color = '#991b1b';
						msgEl.innerText = data.data.message || '<?php echo esc_js( __( 'Reservation failed.', 'awesome-hotel-booking' ) ); ?>';
						submitBtn.disabled = false;
						submitBtn.innerText = origText;
					}
				})
				.catch(() => {
					msgEl.style.display = 'block';
					msgEl.style.background = '#fef2f2';
					msgEl.style.color = '#991b1b';
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