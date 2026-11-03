<?php
/**
 * Elementor Widgets Switchboard View.
 *
 * Enable/Disable individual Elementor widgets to save memory and boost page speed.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render Elementor Widgets Enable/Disable Manager.
 *
 * @return void
 */
function ahbn_elementor_settings_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	$widgets_registry = array(
		'room_grid'       => array(
			'title'       => __( 'Room Showcase Grid', 'awesome-hotel-booking' ),
			'description' => __( 'Display hotel suites in responsive 2, 3, or 4 column grids with filters and price badges.', 'awesome-hotel-booking' ),
			'icon'        => 'dashicons-grid-view',
			'badge'       => 'Core',
		),
		'room_slider'     => array(
			'title'       => __( 'Suite Carousel & Slider', 'awesome-hotel-booking' ),
			'description' => __( 'Smooth luxury touch-enabled slider highlighting premium suites and amenities.', 'awesome-hotel-booking' ),
			'icon'        => 'dashicons-images-alt2',
			'badge'       => 'Luxury',
		),
		'booking_bar'     => array(
			'title'       => __( 'Horizontal Search & Booking Bar', 'awesome-hotel-booking' ),
			'description' => __( 'Header/Hero date range picker with guest counters and instant live availability search.', 'awesome-hotel-booking' ),
			'icon'        => 'dashicons-search',
			'badge'       => 'Conversion',
		),
		'checkout_form'   => array(
			'title'       => __( 'Express Checkout Form', 'awesome-hotel-booking' ),
			'description' => __( 'Complete reservation checkout with gateway selectors and live price breakdown.', 'awesome-hotel-booking' ),
			'icon'        => 'dashicons-cart',
			'badge'       => 'Checkout',
		),
		'pricing_table'   => array(
			'title'       => __( 'Seasonal Rates & Pricing Table', 'awesome-hotel-booking' ),
			'description' => __( 'Comparison cards for room categories, inclusions, and minimum stay rules.', 'awesome-hotel-booking' ),
			'icon'        => 'dashicons-money-alt',
			'badge'       => 'Yield',
		),
		'amenities_show'  => array(
			'title'       => __( 'Amenities & Perks Matrix', 'awesome-hotel-booking' ),
			'description' => __( 'Interactive feature list with Champagne Gold icons for in-suite amenities.', 'awesome-hotel-booking' ),
			'icon'        => 'dashicons-star-filled',
			'badge'       => 'Features',
		),
		'reviews_slider'  => array(
			'title'       => __( 'Guest Testimonials & Reviews', 'awesome-hotel-booking' ),
			'description' => __( 'Showcase verified guest ratings, quotes, and TripAdvisor scores.', 'awesome-hotel-booking' ),
			'icon'        => 'dashicons-testimonial',
			'badge'       => 'Social Proof',
		),
		'staff_roster'    => array(
			'title'       => __( 'Concierge & Leadership Team', 'awesome-hotel-booking' ),
			'description' => __( 'Meet-the-team card layouts displaying hotel management and staff profiles.', 'awesome-hotel-booking' ),
			'icon'        => 'dashicons-id-alt',
			'badge'       => 'Team',
		),
	);

	// Process Save.
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_elementor'] ) ) {
		check_admin_referer( 'ahbn_elementor_settings_nonce' );

		$submitted_widgets = isset( $_POST['widgets'] ) && is_array( $_POST['widgets'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['widgets'] ) ) : array();

		$saved_states = array();
		foreach ( array_keys( $widgets_registry ) as $key ) {
			$saved_states[ $key ] = in_array( $key, $submitted_widgets, true ) ? 'on' : 'off';
		}

		update_option( 'ahbn_elementor_widgets', $saved_states );
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Elementor widgets configuration updated.', 'awesome-hotel-booking' ) . '</p></div>';
	}

	$stored_states = get_option( 'ahbn_elementor_widgets', array() );
	$is_elementor_active = did_action( 'elementor/loaded' );
	?>
	<div class="ahbn-builder-settings-wrap">
		
		<header class="ahbn-builder-header">
			<div>
				<h2 class="ahbn-builder-title"><?php esc_html_e( 'Elementor Widgets Switchboard', 'awesome-hotel-booking' ); ?></h2>
				<p class="ahbn-builder-subtitle">
					<?php esc_html_e( 'Activate or deactivate individual Elementor widgets to eliminate unnecessary scripts and optimize render speeds.', 'awesome-hotel-booking' ); ?>
				</p>
			</div>

			<div class="ahbn-builder-status-badge <?php echo $is_elementor_active ? 'is-active' : 'is-missing'; ?>">
				<span class="dashicons <?php echo $is_elementor_active ? 'dashicons-yes' : 'dashicons-warning'; ?>" aria-hidden="true"></span>
				<span><?php echo $is_elementor_active ? esc_html__( 'Elementor Installed & Active', 'awesome-hotel-booking' ) : esc_html__( 'Elementor Not Detected', 'awesome-hotel-booking' ); ?></span>
			</div>
		</header>

		<form method="post" action="">
			<?php wp_nonce_field( 'ahbn_elementor_settings_nonce' ); ?>

			<div class="ahbn-bulk-actions-bar">
				<div class="ahbn-bulk-stats">
					<span class="ahbn-counter" id="active-widget-count">0</span> <?php esc_html_e( 'of 8 Widgets Enabled', 'awesome-hotel-booking' ); ?>
				</div>
				<div class="ahbn-bulk-buttons">
					<button type="button" class="ahbn-btn-outline" id="btn-enable-all-widgets"><?php esc_html_e( 'Enable All', 'awesome-hotel-booking' ); ?></button>
					<button type="button" class="ahbn-btn-outline" id="btn-disable-all-widgets"><?php esc_html_e( 'Disable All', 'awesome-hotel-booking' ); ?></button>
				</div>
			</div>

			<div class="ahbn-switch-grid">
				<?php foreach ( $widgets_registry as $key => $data ) :
					$is_enabled = ! isset( $stored_states[ $key ] ) || 'on' === $stored_states[ $key ];
					?>
					<div class="ahbn-switch-card <?php echo $is_enabled ? 'is-active' : ''; ?>">
						<div class="ahbn-switch-top">
							<div class="ahbn-switch-icon-box">
								<span class="dashicons <?php echo esc_attr( $data['icon'] ); ?>" aria-hidden="true"></span>
							</div>
							<span class="ahbn-card-badge"><?php echo esc_html( $data['badge'] ); ?></span>
						</div>

						<div class="ahbn-switch-content">
							<h4 class="ahbn-switch-name"><?php echo esc_html( $data['title'] ); ?></h4>
							<p class="ahbn-switch-desc"><?php echo esc_html( $data['description'] ); ?></p>
						</div>

						<div class="ahbn-switch-foot">
							<span class="ahbn-state-label"><?php echo $is_enabled ? esc_html__( 'Active', 'awesome-hotel-booking' ) : esc_html__( 'Disabled', 'awesome-hotel-booking' ); ?></span>
							<label class="ahbn-modern-toggle">
								<input type="checkbox" name="widgets[]" value="<?php echo esc_attr( $key ); ?>" class="ahbn-widget-checkbox" <?php checked( $is_enabled ); ?>>
								<span class="ahbn-toggle-knob"></span>
							</label>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="ahbn-builder-footer">
				<button type="submit" name="ahbn_save_elementor" class="ahbn-btn-save">
					<span class="dashicons dashicons-saved" aria-hidden="true"></span>
					<?php esc_html_e( 'Save Widget Settings', 'awesome-hotel-booking' ); ?>
				</button>
			</div>
		</form>
	</div>

	<style>
		.ahbn-builder-settings-wrap {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-builder-header {
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

		.ahbn-builder-title {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 24px;
			font-weight: 700;
			color: #0a192f;
			margin: 0 0 4px;
		}

		.ahbn-builder-subtitle {
			color: #64748b;
			margin: 0;
			font-size: 13px;
		}

		.ahbn-builder-status-badge {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 6px 12px;
			border-radius: 99px;
			font-size: 11px;
			font-weight: 700;
		}

		.ahbn-builder-status-badge.is-active {
			background: #ecfdf5;
			color: #065f46;
			border: 1px solid #a7f3d0;
		}

		.ahbn-builder-status-badge.is-missing {
			background: #fffbeb;
			color: #92400e;
			border: 1px solid #fde68a;
		}

		.ahbn-bulk-actions-bar {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 12px 18px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-bulk-stats {
			font-size: 13px;
			font-weight: 600;
			color: #475569;
		}

		.ahbn-counter {
			font-family: 'JetBrains Mono', monospace;
			font-weight: 800;
			color: #0a192f;
		}

		.ahbn-bulk-buttons {
			display: flex;
			gap: 8px;
		}

		.ahbn-btn-outline {
			background: #ffffff;
			border: 1px solid #cbd5e1;
			border-radius: 6px;
			padding: 6px 14px;
			font-size: 12px;
			font-weight: 700;
			color: #334155;
			cursor: pointer;
			transition: all 0.15s ease;
		}

		.ahbn-btn-outline:hover {
			border-color: #0a192f;
			color: #0a192f;
			background: #f8fafc;
		}

		/* Grid & Cards */
		.ahbn-switch-grid {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
			gap: 16px;
		}

		.ahbn-switch-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			padding: 20px;
			display: flex;
			flex-direction: column;
			justify-content: space-between;
			transition: all 0.2s ease;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-switch-card:hover {
			border-color: #cbd5e1;
			transform: translateY(-2px);
			box-shadow: 0 6px 16px rgba(10, 25, 47, 0.06);
		}

		.ahbn-switch-card.is-active {
			border-color: #cbd5e1;
		}

		.ahbn-switch-top {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 14px;
		}

		.ahbn-switch-icon-box {
			width: 42px;
			height: 42px;
			border-radius: 10px;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #0a192f;
			transition: all 0.2s ease;
		}

		.ahbn-switch-icon-box .dashicons {
			font-size: 20px;
			width: 20px;
			height: 20px;
			color: #64748b;
		}

		.ahbn-switch-card.is-active .ahbn-switch-icon-box {
			background: #0a192f;
			border-color: #0a192f;
		}

		.ahbn-switch-card.is-active .ahbn-switch-icon-box .dashicons {
			color: #c5a880;
		}

		.ahbn-card-badge {
			font-family: 'JetBrains Mono', monospace;
			font-size: 10px;
			font-weight: 800;
			text-transform: uppercase;
			color: #64748b;
			background: #f1f5f9;
			padding: 3px 8px;
			border-radius: 4px;
		}

		.ahbn-switch-name {
			font-size: 14px;
			font-weight: 700;
			color: #0a192f;
			margin: 0 0 6px;
		}

		.ahbn-switch-desc {
			font-size: 12px;
			line-height: 1.5;
			color: #64748b;
			margin: 0;
			min-height: 36px;
		}

		.ahbn-switch-foot {
			margin-top: 18px;
			padding-top: 14px;
			border-top: 1px solid #f1f5f9;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.ahbn-state-label {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #94a3b8;
		}

		.ahbn-switch-card.is-active .ahbn-state-label {
			color: #059669;
		}

		/* Modern Gold Toggle */
		.ahbn-modern-toggle {
			position: relative;
			display: inline-block;
			width: 44px;
			height: 24px;
			flex-shrink: 0;
		}

		.ahbn-modern-toggle input {
			opacity: 0;
			width: 0;
			height: 0;
		}

		.ahbn-toggle-knob {
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

		.ahbn-toggle-knob:before {
			position: absolute;
			content: "";
			height: 18px;
			width: 18px;
			left: 3px;
			bottom: 3px;
			background-color: white;
			transition: 0.25s cubic-bezier(0.16, 1, 0.3, 1);
			border-radius: 50%;
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
		}

		.ahbn-modern-toggle input:checked + .ahbn-toggle-knob {
			background-color: #0a192f;
		}

		.ahbn-modern-toggle input:checked + .ahbn-toggle-knob:before {
			transform: translateX(20px);
			background-color: #c5a880;
		}

		.ahbn-builder-footer {
			display: flex;
			justify-content: flex-end;
			margin-top: 5px;
		}

		.ahbn-btn-save {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 11px 24px;
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
	</style>

	<script>
	jQuery(document).ready(function($) {
		function updateWidgetCounter() {
			var count = $('.ahbn-widget-checkbox:checked').length;
			$('#active-widget-count').text(count);
		}
		updateWidgetCounter();

		$('.ahbn-widget-checkbox').on('change', function() {
			var $card = $(this).closest('.ahbn-switch-card');
			var $lbl = $card.find('.ahbn-state-label');
			if ($(this).is(':checked')) {
				$card.addClass('is-active');
				$lbl.text('<?php echo esc_js( __( 'Active', 'awesome-hotel-booking' ) ); ?>');
			} else {
				$card.removeClass('is-active');
				$lbl.text('<?php echo esc_js( __( 'Disabled', 'awesome-hotel-booking' ) ); ?>');
			}
			updateWidgetCounter();
		});

		$('#btn-enable-all-widgets').on('click', function(e) {
			e.preventDefault();
			$('.ahbn-widget-checkbox').prop('checked', true).trigger('change');
		});

		$('#btn-disable-all-widgets').on('click', function(e) {
			e.preventDefault();
			$('.ahbn-widget-checkbox').prop('checked', false).trigger('change');
		});
	});
	</script>
	<?php
}