<?php
/**
 * Gutenberg Native Blocks Switchboard View.
 *
 * Enable/Disable individual WordPress block editor components.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render Gutenberg Blocks Enable/Disable Manager.
 *
 * @return void
 */
function ahbn_gutenberg_settings_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	$blocks_registry = array(
		'room_card'       => array(
			'title'       => __( 'Single Suite Showcase Card', 'awesome-hotel-booking' ),
			'description' => __( 'Native block displaying suite photo, room rate, bed sizes, and direct booking trigger.', 'awesome-hotel-booking' ),
			'icon'        => 'dashicons-building',
			'tag'         => 'ahbn/room-card',
		),
		'booking_search'  => array(
			'title'       => __( 'Reservation Search & Filter Block', 'awesome-hotel-booking' ),
			'description' => __( 'Inline date pickers and guest count fields for modern block-theme landing pages.', 'awesome-hotel-booking' ),
			'icon'        => 'dashicons-search',
			'tag'         => 'ahbn/search-bar',
		),
		'checkout_embed'  => array(
			'title'       => __( 'Embedded Booking Checkout', 'awesome-hotel-booking' ),
			'description' => __( 'Full self-contained booking completion block with instant AJAX fee recalculations.', 'awesome-hotel-booking' ),
			'icon'        => 'dashicons-cart',
			'tag'         => 'ahbn/checkout',
		),
		'amenity_cloud'   => array(
			'title'       => __( 'Amenity Badge Cloud', 'awesome-hotel-booking' ),
			'description' => __( 'Gutenberg pills displaying vector icons for Wi-Fi, Balcony, Safe, AC, and Mini-bar.', 'awesome-hotel-booking' ),
			'icon'        => 'dashicons-star-filled',
			'tag'         => 'ahbn/amenities',
		),
		'extra_addons'    => array(
			'title'       => __( 'Add-on Service Upsell Carousel', 'awesome-hotel-booking' ),
			'description' => __( 'Interactive add-on cards for airport shuttles, luxury spa, and dining breakfast passes.', 'awesome-hotel-booking' ),
			'icon'        => 'dashicons-coffee',
			'tag'         => 'ahbn/extra-services',
		),
		'price_calculator'=> array(
			'title'       => __( 'Live Stay Cost Estimator', 'awesome-hotel-booking' ),
			'description' => __( 'Interactive slider enabling guests to calculate total night costs including VAT taxes.', 'awesome-hotel-booking' ),
			'icon'        => 'dashicons-calculator',
			'tag'         => 'ahbn/price-calculator',
		),
	);

	// Process Save.
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_blocks'] ) ) {
		check_admin_referer( 'ahbn_gutenberg_settings_nonce' );

		$submitted_blocks = isset( $_POST['blocks'] ) && is_array( $_POST['blocks'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['blocks'] ) ) : array();

		$saved_states = array();
		foreach ( array_keys( $blocks_registry ) as $key ) {
			$saved_states[ $key ] = in_array( $key, $submitted_blocks, true ) ? 'on' : 'off';
		}

		update_option( 'ahbn_gutenberg_blocks', $saved_states );
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Gutenberg block settings updated.', 'awesome-hotel-booking' ) . '</p></div>';
	}

	$stored_states = get_option( 'ahbn_gutenberg_blocks', array() );
	?>
	<div class="ahbn-builder-settings-wrap">
		
		<header class="ahbn-builder-header">
			<div>
				<h2 class="ahbn-builder-title"><?php esc_html_e( 'Gutenberg Blocks Switchboard', 'awesome-hotel-booking' ); ?></h2>
				<p class="ahbn-builder-subtitle">
					<?php esc_html_e( 'Toggle native WordPress block components available in the site editor and post canvas.', 'awesome-hotel-booking' ); ?>
				</p>
			</div>

			<div class="ahbn-builder-status-badge is-active">
				<span class="dashicons dashicons-block-default" aria-hidden="true"></span>
				<span><?php esc_html_e( 'Block Editor Ready', 'awesome-hotel-booking' ); ?></span>
			</div>
		</header>

		<form method="post" action="">
			<?php wp_nonce_field( 'ahbn_gutenberg_settings_nonce' ); ?>

			<div class="ahbn-bulk-actions-bar">
				<div class="ahbn-bulk-stats">
					<span class="ahbn-counter" id="active-blocks-count">0</span> <?php esc_html_e( 'of 6 Blocks Active', 'awesome-hotel-booking' ); ?>
				</div>
				<div class="ahbn-bulk-buttons">
					<button type="button" class="ahbn-btn-outline" id="btn-enable-all-blocks"><?php esc_html_e( 'Enable All', 'awesome-hotel-booking' ); ?></button>
					<button type="button" class="ahbn-btn-outline" id="btn-disable-all-blocks"><?php esc_html_e( 'Disable All', 'awesome-hotel-booking' ); ?></button>
				</div>
			</div>

			<div class="ahbn-switch-grid">
				<?php foreach ( $blocks_registry as $key => $data ) :
					$is_enabled = ! isset( $stored_states[ $key ] ) || 'on' === $stored_states[ $key ];
					?>
					<div class="ahbn-switch-card <?php echo $is_enabled ? 'is-active' : ''; ?>">
						<div class="ahbn-switch-top">
							<div class="ahbn-switch-icon-box">
								<span class="dashicons <?php echo esc_attr( $data['icon'] ); ?>" aria-hidden="true"></span>
							</div>
							<span class="ahbn-card-badge"><?php echo esc_html( $data['tag'] ); ?></span>
						</div>

						<div class="ahbn-switch-content">
							<h4 class="ahbn-switch-name"><?php echo esc_html( $data['title'] ); ?></h4>
							<p class="ahbn-switch-desc"><?php echo esc_html( $data['description'] ); ?></p>
						</div>

						<div class="ahbn-switch-foot">
							<span class="ahbn-state-label"><?php echo $is_enabled ? esc_html__( 'Active', 'awesome-hotel-booking' ) : esc_html__( 'Disabled', 'awesome-hotel-booking' ); ?></span>
							<label class="ahbn-modern-toggle">
								<input type="checkbox" name="blocks[]" value="<?php echo esc_attr( $key ); ?>" class="ahbn-block-checkbox" <?php checked( $is_enabled ); ?>>
								<span class="ahbn-toggle-knob"></span>
							</label>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="ahbn-builder-footer">
				<button type="submit" name="ahbn_save_blocks" class="ahbn-btn-save">
					<span class="dashicons dashicons-saved" aria-hidden="true"></span>
					<?php esc_html_e( 'Save Block Settings', 'awesome-hotel-booking' ); ?>
				</button>
			</div>
		</form>
	</div>

	<script>
	jQuery(document).ready(function($) {
		function updateBlockCounter() {
			var count = $('.ahbn-block-checkbox:checked').length;
			$('#active-blocks-count').text(count);
		}
		updateBlockCounter();

		$('.ahbn-block-checkbox').on('change', function() {
			var $card = $(this).closest('.ahbn-switch-card');
			var $lbl = $card.find('.ahbn-state-label');
			if ($(this).is(':checked')) {
				$card.addClass('is-active');
				$lbl.text('<?php echo esc_js( __( 'Active', 'awesome-hotel-booking' ) ); ?>');
			} else {
				$card.removeClass('is-active');
				$lbl.text('<?php echo esc_js( __( 'Disabled', 'awesome-hotel-booking' ) ); ?>');
			}
			updateBlockCounter();
		});

		$('#btn-enable-all-blocks').on('click', function(e) {
			e.preventDefault();
			$('.ahbn-block-checkbox').prop('checked', true).trigger('change');
		});

		$('#btn-disable-all-blocks').on('click', function(e) {
			e.preventDefault();
			$('.ahbn-block-checkbox').prop('checked', false).trigger('change');
		});
	});
	</script>
	<?php
}