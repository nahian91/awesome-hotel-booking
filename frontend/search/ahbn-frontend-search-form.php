<?php
/**
 * [ahbn_search_form] Standalone Room Search Bar for Homepage & Hero Sections.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the hotel room search bar shortcode.
 *
 * @return string Rendered HTML.
 */
function ahbn_search_form_shortcode_callback() {
	// 1. Pull Available Room Types & Configuration.
	$room_types = get_option( 'ahbn_global_types', array( 'Standard', 'Double', 'Suite' ) );

	// 2. Resolve Target Results Page.
	$rooms_page_id    = (int) get_option( 'ahbn_rooms_page_id' );
	$results_page_url = $rooms_page_id ? get_permalink( $rooms_page_id ) : home_url( '/rooms/' );

	$today    = current_time( 'Y-m-d' );
	$tomorrow = wp_date( 'Y-m-d', strtotime( '+1 day', current_time( 'timestamp' ) ) );

	ob_start();
	?>
	<div class="ahbn-search-wrapper">
		<form action="<?php echo esc_url( $results_page_url ); ?>" method="GET" class="ahbn-search-form">
			
			<div class="ahbn-search-main">
				<!-- Room Type / Category -->
				<div class="ahbn-search-field">
					<label for="ahbn_room_type">
						<span class="dashicons dashicons-category" aria-hidden="true"></span> 
						<?php esc_html_e( 'Room Type', 'awesome-hotel-booking' ); ?>
					</label>
					<select name="room_type" id="ahbn_room_type" class="ahbn-search-select">
						<option value=""><?php esc_html_e( 'All Room Types', 'awesome-hotel-booking' ); ?></option>
						<?php if ( ! empty( $room_types ) && is_array( $room_types ) ) : ?>
							<?php foreach ( $room_types as $type ) : ?>
								<option value="<?php echo esc_attr( $type ); ?>">
									<?php echo esc_html( $type ); ?>
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>

				<!-- Check-in Date -->
				<div class="ahbn-search-field">
					<label for="ahbn_checkin">
						<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span> 
						<?php esc_html_e( 'Check-In', 'awesome-hotel-booking' ); ?>
					</label>
					<input 
						type="date" 
						name="checkin" 
						id="ahbn_checkin" 
						class="ahbn-search-input" 
						value="<?php echo esc_attr( $today ); ?>" 
						min="<?php echo esc_attr( $today ); ?>" 
						required
					>
				</div>

				<!-- Check-out Date -->
				<div class="ahbn-search-field">
					<label for="ahbn_checkout">
						<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span> 
						<?php esc_html_e( 'Check-Out', 'awesome-hotel-booking' ); ?>
					</label>
					<input 
						type="date" 
						name="checkout" 
						id="ahbn_checkout" 
						class="ahbn-search-input" 
						value="<?php echo esc_attr( $tomorrow ); ?>" 
						min="<?php echo esc_attr( $tomorrow ); ?>" 
						required
					>
				</div>

				<!-- Adults Count -->
				<div class="ahbn-search-field ahbn-search-field-compact">
					<label for="ahbn_adults">
						<span class="dashicons dashicons-admin-users" aria-hidden="true"></span> 
						<?php esc_html_e( 'Adults', 'awesome-hotel-booking' ); ?>
					</label>
					<select name="adults" id="ahbn_adults" class="ahbn-search-select">
						<option value="1">1</option>
						<option value="2" selected>2</option>
						<option value="3">3</option>
						<option value="4">4+</option>
					</select>
				</div>

				<!-- Children Count -->
				<div class="ahbn-search-field ahbn-search-field-compact">
					<label for="ahbn_children">
						<span class="dashicons dashicons-groups" aria-hidden="true"></span> 
						<?php esc_html_e( 'Children', 'awesome-hotel-booking' ); ?>
					</label>
					<select name="children" id="ahbn_children" class="ahbn-search-select">
						<option value="0" selected>0</option>
						<option value="1">1</option>
						<option value="2">2</option>
						<option value="3">3+</option>
					</select>
				</div>

				<!-- Submit Button -->
				<button type="submit" class="ahbn-search-submit ahbn-btn-primary">
					<span><?php esc_html_e( 'Check Availability', 'awesome-hotel-booking' ); ?></span>
					<span class="dashicons dashicons-search" aria-hidden="true"></span>
				</button>
			</div>

		</form>
	</div>

	<style>
		.ahbn-search-wrapper {
			max-width: 1100px;
			margin: 20px auto;
			padding: 0 15px;
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
		}
		.ahbn-search-main {
			display: flex;
			flex-wrap: wrap;
			gap: 12px;
			align-items: flex-end;
			background: #ffffff;
			padding: 20px 24px;
			border-radius: 14px;
			box-shadow: 0 15px 35px rgba(10, 26, 56, 0.08);
			border: 1px solid #e2e8f0;
		}
		.ahbn-search-field {
			flex: 1 1 160px;
			display: flex;
			flex-direction: column;
		}
		.ahbn-search-field-compact {
			flex: 0 1 90px;
		}
		.ahbn-search-field label {
			font-size: 12px;
			font-weight: 700;
			color: #0A1A38;
			text-transform: uppercase;
			letter-spacing: 0.5px;
			margin-bottom: 6px;
			display: flex;
			align-items: center;
			gap: 4px;
		}
		.ahbn-search-field label .dashicons {
			font-size: 16px;
			width: 16px;
			height: 16px;
			color: #D9B061;
		}
		.ahbn-search-select,
		.ahbn-search-input {
			width: 100%;
			height: 44px;
			padding: 8px 12px;
			border: 1px solid #cbd5e1;
			border-radius: 8px;
			background: #f8fafc;
			color: #1e293b;
			font-size: 14px;
			font-weight: 500;
			box-sizing: border-box;
			transition: border-color 0.2s ease, box-shadow 0.2s ease;
		}
		.ahbn-search-select:focus,
		.ahbn-search-input:focus {
			border-color: #D9B061;
			outline: none;
			box-shadow: 0 0 0 3px rgba(217, 176, 97, 0.15);
			background: #ffffff;
		}
		.ahbn-search-submit {
			height: 44px;
			padding: 0 24px;
			background: #0A1A38;
			color: #ffffff;
			border: none;
			border-radius: 8px;
			font-weight: 700;
			font-size: 14px;
			cursor: pointer;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 8px;
			transition: background 0.2s ease, transform 0.1s ease;
		}
		.ahbn-search-submit:hover {
			background: #D9B061;
			color: #0A1A38;
			transform: translateY(-1px);
		}
		@media (max-width: 768px) {
			.ahbn-search-main {
				flex-direction: column;
				align-items: stretch;
			}
			.ahbn-search-field-compact {
				flex: 1 1 auto;
			}
			.ahbn-search-submit {
				width: 100%;
			}
		}
	</style>

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		const checkinInput  = document.getElementById('ahbn_checkin');
		const checkoutInput = document.getElementById('ahbn_checkout');

		if (checkinInput && checkoutInput) {
			checkinInput.addEventListener('change', function() {
				const checkinDate = new Date(this.value);
				if (!isNaN(checkinDate.getTime())) {
					checkinDate.setDate(checkinDate.getDate() + 1);
					const minCheckout = checkinDate.toISOString().split('T')[0];
					checkoutInput.min = minCheckout;
					if (checkoutInput.value && checkoutInput.value <= this.value) {
						checkoutInput.value = minCheckout;
					}
				}
			});
		}
	});
	</script>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ahbn_search_form', 'ahbn_search_form_shortcode_callback' );