<?php
/**
 * Global Room Types, Numbers, Amenities, and Paid Extras Configuration.
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
		'wifi'      => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M12 21l-8.2-10.3c.4-.4 4.8-3.7 8.2-3.7s7.8 3.3 8.2 3.7L12 21z"/></svg>',
		'ac'        => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M19 19H5V5h14v14zM3 3v18h18V3H3zm8 4h2v10h-2V7z"/></svg>',
		'breakfast' => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M11 9H9V2H7v7H5V2H3v7c0 2.1 1.6 3.8 3.7 4l-1.1 8h4.8l-1.1-8c2.1-.2 3.7-1.9 3.7-4V2h-2v7zm10-7v13h-3v7h-2v-7h-3V2c0 2 2 4 4 4s4-2 4-4z"/></svg>',
		'bar'       => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M21 5V3H3v2l8 9v5H6v2h12v-2h-5v-5l8-9zM7.4 5h9.2l-1.8 2H9.2L7.4 5z"/></svg>',
		'parking'   => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M13 3H6v18h4v-6h3c3.3 0 6-2.7 6-6s-2.7-6-6-6zm0 8h-3V7h3c1.1 0 2 .9 2 2s-.9 2-2 2z"/></svg>',
		'bed'       => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 10V7a2 2 0 00-2-2H6a2 2 0 00-2 2v3a2 2 0 00-2 2v5h1.3l.7 2h2l.7-2h10.6l.7 2h2l.7-2H22v-5a2 2 0 00-2-2zm-3-3v3H7V7h10z"/></svg>',
		'shuttle'   => '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 11h-1.1c-.4-1.1-1.4-2-2.6-2-1.2 0-2.2.9-2.6 2h-1.4c-.4-1.1-1.4-2-2.6-2-1.2 0-2.2.9-2.6 2H4c-1.1 0-2 .9-2 2v4h2c0 1.7 1.3 3 3 3s3-1.3 3-3h4c0 1.7 1.3 3 3 3s3-1.3 3-3h2v-5c0-1.1-.9-2-2-2zm-11 7c-.6 0-1-.4-1-1s.4-1 1-1 1 .4 1 1-.4 1-1 1zm10 0c-.6 0-1-.4-1-1s.4-1 1-1 1 .4 1 1-.4 1-1 1z"/></svg>',
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
	$svg   = isset( $icons[ $icon_key ] ) ? $icons[ $icon_key ] : '';

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
 * Render Extras and Settings Manager.
 *
 * @return void
 */
function ahbn_rooms_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	$svg_lib = ahbn_get_svg_icons();

	// 1. Save Logic.
	if ( isset( $_POST['ahbn_save_extras'] ) ) {
		check_admin_referer( 'ahbn_save_extras_action', 'ahbn_extras_nonce' );

		// Room Types.
		$raw_types   = isset( $_POST['room_types'] ) && is_array( $_POST['room_types'] ) ? wp_unslash( $_POST['room_types'] ) : array();
		$clean_types = array_values( array_filter( array_map( 'sanitize_text_field', $raw_types ) ) );

		// Room Numbers.
		$raw_nos   = isset( $_POST['room_nos'] ) && is_array( $_POST['room_nos'] ) ? wp_unslash( $_POST['room_nos'] ) : array();
		$clean_nos = array_values( array_filter( array_map( 'sanitize_text_field', $raw_nos ) ) );

		// Amenities.
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

		// Paid Extras.
		$clean_paid = array();
		if ( isset( $_POST['paid_extras'] ) && is_array( $_POST['paid_extras'] ) ) {
			$raw_paid = wp_unslash( $_POST['paid_extras'] );
			foreach ( $raw_paid as $ahbn_paid ) {
				$name     = isset( $ahbn_paid['name'] ) ? sanitize_text_field( $ahbn_paid['name'] ) : '';
				$price    = isset( $ahbn_paid['price'] ) ? sanitize_text_field( $ahbn_paid['price'] ) : '0';
				$icon_key = isset( $ahbn_paid['icon_key'] ) ? sanitize_key( $ahbn_paid['icon_key'] ) : 'bed';

				if ( ! empty( $name ) ) {
					$clean_paid[] = array(
						'name'     => $name,
						'price'    => $price,
						'icon_key' => array_key_exists( $icon_key, $svg_lib ) ? $icon_key : 'bed',
					);
				}
			}
		}

		update_option( 'ahbn_global_types', $clean_types );
		update_option( 'ahbn_global_nos', $clean_nos );
		update_option( 'ahbn_global_amenities', $clean_amenities );
		update_option( 'ahbn_global_paid_extras', $clean_paid );

		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Settings Saved:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'All features updated.', 'awesome-hotel-booking' ) . '</p></div>';
	}

	// 2. Fetch Stored Options.
	$room_types = get_option( 'ahbn_global_types', array( 'Standard', 'Double', 'Suite' ) );
	$room_nos   = get_option( 'ahbn_global_nos', array( '101', '102', '103' ) );

	$amen_def = array(
		array( 'name' => 'WiFi', 'icon_key' => 'wifi' ),
		array( 'name' => 'AC', 'icon_key' => 'ac' ),
		array( 'name' => 'Breakfast', 'icon_key' => 'breakfast' ),
		array( 'name' => 'Mini Bar', 'icon_key' => 'bar' ),
		array( 'name' => 'Parking', 'icon_key' => 'parking' ),
	);
	$amenities = get_option( 'ahbn_global_amenities', $amen_def );

	$paid_def = array(
		array( 'name' => 'Extra Bed', 'price' => '10', 'icon_key' => 'bed' ),
		array( 'name' => 'Airport Pick Up', 'price' => '5', 'icon_key' => 'shuttle' ),
	);
	$paid_extras = get_option( 'ahbn_global_paid_extras', $paid_def );
	?>

	<div class="ahbn-wrap">
		<form method="post" action="">
			<?php wp_nonce_field( 'ahbn_save_extras_action', 'ahbn_extras_nonce' ); ?>

			<div class="ahbn-grid-2">
				<div class="ahbn-card">
					<div class="ahbn-card-title">
						<span class="dashicons dashicons-category" aria-hidden="true"></span> 
						<?php esc_html_e( '1. Room Types', 'awesome-hotel-booking' ); ?>
					</div>
					<div id="ui-types">
						<?php if ( ! empty( $room_types ) && is_array( $room_types ) ) : ?>
							<?php foreach ( $room_types as $t ) : ?>
								<div class="ahbn-row">
									<input type="text" name="room_types[]" value="<?php echo esc_attr( $t ); ?>" class="ahbn-input">
									<span class="dashicons dashicons-no-alt ahbn-remove" role="button" aria-label="<?php esc_attr_e( 'Remove', 'awesome-hotel-booking' ); ?>"></span>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<button type="button" class="button ahbn-btn-add-simple" data-target="ui-types" data-field-name="room_types[]">
						+ <?php esc_html_e( 'Add Type', 'awesome-hotel-booking' ); ?>
					</button>
				</div>

				<div class="ahbn-card">
					<div class="ahbn-card-title">
						<span class="dashicons dashicons-id" aria-hidden="true"></span> 
						<?php esc_html_e( '2. Room Numbers', 'awesome-hotel-booking' ); ?>
					</div>
					<div id="ui-nos">
						<?php if ( ! empty( $room_nos ) && is_array( $room_nos ) ) : ?>
							<?php foreach ( $room_nos as $n ) : ?>
								<div class="ahbn-row">
									<input type="text" name="room_nos[]" value="<?php echo esc_attr( $n ); ?>" class="ahbn-input">
									<span class="dashicons dashicons-no-alt ahbn-remove" role="button" aria-label="<?php esc_attr_e( 'Remove', 'awesome-hotel-booking' ); ?>"></span>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
					<button type="button" class="button ahbn-btn-add-simple" data-target="ui-nos" data-field-name="room_nos[]">
						+ <?php esc_html_e( 'Add Number', 'awesome-hotel-booking' ); ?>
					</button>
				</div>
			</div>

			<div class="ahbn-card">
				<div class="ahbn-card-title">
					<span class="dashicons dashicons-star-filled" aria-hidden="true"></span> 
					<?php esc_html_e( '3. Amenities', 'awesome-hotel-booking' ); ?>
				</div>
				<div id="ui-amenities">
					<?php if ( ! empty( $amenities ) && is_array( $amenities ) ) : ?>
						<?php foreach ( $amenities as $idx => $a ) : ?>
							<?php $icon_key = isset( $a['icon_key'] ) ? $a['icon_key'] : 'wifi'; ?>
							<div class="ahbn-row">
								<div class="ahbn-icon-preview">
									<?php echo ahbn_render_svg_icon( $icon_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
								<select name="amenities[<?php echo absint( $idx ); ?>][icon_key]" class="ahbn-input ahbn-icon-sel" style="flex:0.2;">
									<?php foreach ( $svg_lib as $key => $svg ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $icon_key, $key ); ?>>
											<?php echo esc_html( ucfirst( $key ) ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<input type="text" name="amenities[<?php echo absint( $idx ); ?>][name]" value="<?php echo esc_attr( isset( $a['name'] ) ? $a['name'] : '' ); ?>" class="ahbn-input" placeholder="<?php esc_attr_e( 'Amenity Name', 'awesome-hotel-booking' ); ?>">
								<span class="dashicons dashicons-no-alt ahbn-remove" role="button" aria-label="<?php esc_attr_e( 'Remove', 'awesome-hotel-booking' ); ?>"></span>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
				<button type="button" class="button" id="btn-add-amenity">
					+ <?php esc_html_e( 'Add Amenity Item', 'awesome-hotel-booking' ); ?>
				</button>
			</div>

			<div class="ahbn-card">
				<div class="ahbn-card-title">
					<span class="dashicons dashicons-cart" aria-hidden="true"></span> 
					<?php esc_html_e( '4. Paid Extras', 'awesome-hotel-booking' ); ?>
				</div>
				<div id="ui-paid">
					<?php if ( ! empty( $paid_extras ) && is_array( $paid_extras ) ) : ?>
						<?php foreach ( $paid_extras as $idx => $e ) : ?>
							<?php $icon_key = isset( $e['icon_key'] ) ? $e['icon_key'] : 'bed'; ?>
							<div class="ahbn-row">
								<div class="ahbn-icon-preview">
									<?php echo ahbn_render_svg_icon( $icon_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</div>
								<select name="paid_extras[<?php echo absint( $idx ); ?>][icon_key]" class="ahbn-input ahbn-icon-sel" style="flex:0.2;">
									<?php foreach ( $svg_lib as $key => $svg ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $icon_key, $key ); ?>>
											<?php echo esc_html( ucfirst( $key ) ); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<input type="text" name="paid_extras[<?php echo absint( $idx ); ?>][name]" value="<?php echo esc_attr( isset( $e['name'] ) ? $e['name'] : '' ); ?>" class="ahbn-input" placeholder="<?php esc_attr_e( 'Extra Service Name', 'awesome-hotel-booking' ); ?>">
								<input type="text" name="paid_extras[<?php echo absint( $idx ); ?>][price]" value="<?php echo esc_attr( isset( $e['price'] ) ? $e['price'] : '' ); ?>" class="ahbn-input ahbn-price-field" placeholder="<?php esc_attr_e( 'Price', 'awesome-hotel-booking' ); ?>">
								<span class="dashicons dashicons-no-alt ahbn-remove" role="button" aria-label="<?php esc_attr_e( 'Remove', 'awesome-hotel-booking' ); ?>"></span>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
				<button type="button" class="button" id="btn-add-paid">
					+ <?php esc_html_e( 'Add Paid Extra Service', 'awesome-hotel-booking' ); ?>
				</button>
			</div>

			<div class="ahbn-footer">
				<button type="submit" name="ahbn_save_extras" class="button button-primary ahbn-submit">
					<?php esc_html_e( 'Update Hotel Registry', 'awesome-hotel-booking' ); ?>
				</button>
			</div>
		</form>
	</div>

	<script>
	jQuery(document).ready(function($) {
		var icons = <?php echo wp_json_encode( $svg_lib ); ?>;
		var iconKeys = Object.keys(icons);

		function buildIconSelect(fieldName) {
			var $select = $('<select/>', { name: fieldName, class: 'ahbn-input ahbn-icon-sel', style: 'flex:0.2;' });
			$.each(iconKeys, function(index, key) {
				var label = key.charAt(0).toUpperCase() + key.slice(1);
				$select.append($('<option/>', { value: key, text: label }));
			});
			return $select;
		}

		// Simple Row Addition (Types & Numbers).
		$('.ahbn-btn-add-simple').on('click', function(e) {
			e.preventDefault();
			var target = $(this).data('target');
			var fieldName = $(this).data('field-name');

			var $row = $('<div/>', { class: 'ahbn-row' })
				.append($('<input/>', { type: 'text', name: fieldName, class: 'ahbn-input' }))
				.append($('<span/>', { class: 'dashicons dashicons-no-alt ahbn-remove', role: 'button' }));

			$('#' + target).append($row);
		});

		// Dynamic Icon Preview Change.
		$(document).on('change', '.ahbn-icon-sel', function() {
			var selKey = $(this).val();
			if (icons[selKey]) {
				$(this).siblings('.ahbn-icon-preview').html(icons[selKey]);
			}
		});

		// Remove Row.
		$(document).on('click', '.ahbn-remove', function() {
			$(this).closest('.ahbn-row').remove();
		});

		// Add Amenity Item.
		$('#btn-add-amenity').on('click', function(e) {
			e.preventDefault();
			var i = new Date().getTime();
			var defaultIcon = 'wifi';

			var $row = $('<div/>', { class: 'ahbn-row' })
				.append($('<div/>', { class: 'ahbn-icon-preview', html: icons[defaultIcon] }))
				.append(buildIconSelect('amenities[' + i + '][icon_key]'))
				.append($('<input/>', {
					type: 'text',
					name: 'amenities[' + i + '][name]',
					class: 'ahbn-input',
					placeholder: '<?php echo esc_js( __( 'Amenity Name', 'awesome-hotel-booking' ) ); ?>'
				}))
				.append($('<span/>', { class: 'dashicons dashicons-no-alt ahbn-remove', role: 'button' }));

			$('#ui-amenities').append($row);
		});

		// Add Paid Extra Item.
		$('#btn-add-paid').on('click', function(e) {
			e.preventDefault();
			var i = new Date().getTime();
			var defaultIcon = 'bed';

			var $row = $('<div/>', { class: 'ahbn-row' })
				.append($('<div/>', { class: 'ahbn-icon-preview', html: icons[defaultIcon] }))
				.append(buildIconSelect('paid_extras[' + i + '][icon_key]'))
				.append($('<input/>', {
					type: 'text',
					name: 'paid_extras[' + i + '][name]',
					class: 'ahbn-input',
					placeholder: '<?php echo esc_js( __( 'Extra Service Name', 'awesome-hotel-booking' ) ); ?>'
				}))
				.append($('<input/>', {
					type: 'text',
					name: 'paid_extras[' + i + '][price]',
					class: 'ahbn-input ahbn-price-field',
					placeholder: '<?php echo esc_js( __( 'Price', 'awesome-hotel-booking' ) ); ?>'
				}))
				.append($('<span/>', { class: 'dashicons dashicons-no-alt ahbn-remove', role: 'button' }));

			$('#ui-paid').append($row);
		});
	});
	</script>
	<?php
}