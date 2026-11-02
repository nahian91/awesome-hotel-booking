<?php
/**
 * Expense Settings Configuration.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render Expense Settings view and handle repeater configuration updates.
 *
 * @return void
 */
function ahbn_expense_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	// 1. Handle Save Logic.
	if ( isset( $_POST['ahbn_save_expense_settings'] ) ) {
		check_admin_referer( 'ahbn_expense_settings_nonce' );

		$raw_categories  = isset( $_POST['categories'] ) && is_array( $_POST['categories'] ) ? wp_unslash( $_POST['categories'] ) : array();
		$raw_items       = isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : array();
		$raw_departments = isset( $_POST['departments'] ) && is_array( $_POST['departments'] ) ? wp_unslash( $_POST['departments'] ) : array();

		// Sanitize, strip empty strings, and reset indices.
		$clean_categories  = array_values( array_filter( array_map( 'sanitize_text_field', $raw_categories ) ) );
		$clean_items       = array_values( array_filter( array_map( 'sanitize_text_field', $raw_items ) ) );
		$clean_departments = array_values( array_filter( array_map( 'sanitize_text_field', $raw_departments ) ) );

		$settings = array(
			'categories'  => ! empty( $clean_categories ) ? $clean_categories : array( 'Maintenance', 'Utilities', 'Housekeeping' ),
			'items'       => ! empty( $clean_items ) ? $clean_items : array( 'Electricity Bill', 'Cleaning Supplies', 'Plumbing Repair' ),
			'departments' => ! empty( $clean_departments ) ? $clean_departments : array( 'Front Desk', 'Kitchen', 'Management' ),
		);

		update_option( 'ahbn_expense_config', $settings );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'awesome-hotel-booking' ) . '</p></div>';
	}

	// 2. Fetch Stored Configuration (with Fallback Defaults).
	$default_config = array(
		'categories'  => array( 'Maintenance', 'Utilities', 'Housekeeping' ),
		'items'       => array( 'Electricity Bill', 'Cleaning Supplies', 'Plumbing Repair' ),
		'departments' => array( 'Front Desk', 'Kitchen', 'Management' ),
	);
	$config         = get_option( 'ahbn_expense_config', $default_config );

	$categories  = isset( $config['categories'] ) && is_array( $config['categories'] ) ? $config['categories'] : $default_config['categories'];
	$items       = isset( $config['items'] ) && is_array( $config['items'] ) ? $config['items'] : $default_config['items'];
	$departments = isset( $config['departments'] ) && is_array( $config['departments'] ) ? $config['departments'] : $default_config['departments'];
	?>

	<form method="post" action="">
		<?php wp_nonce_field( 'ahbn_expense_settings_nonce' ); ?>

		<div class="ahbn-settings-grid">
			
			<!-- Categories Repeater Card -->
			<div class="ahbn-repeater-card">
				<h3>
					<span class="dashicons dashicons-category" aria-hidden="true"></span>
					<?php esc_html_e( 'Expense Categories', 'awesome-hotel-booking' ); ?>
				</h3>
				<div class="repeater-container" id="cat-repeater">
					<?php foreach ( $categories as $val ) : ?>
						<div class="ahbn-repeater-row">
							<input 
								type="text" 
								name="categories[]" 
								value="<?php echo esc_attr( $val ); ?>" 
								placeholder="<?php esc_attr_e( 'e.g. Utilities', 'awesome-hotel-booking' ); ?>"
								aria-label="<?php esc_attr_e( 'Category Name', 'awesome-hotel-booking' ); ?>"
							>
							<button type="button" class="btn-remove remove-row" aria-label="<?php esc_attr_e( 'Remove Category', 'awesome-hotel-booking' ); ?>">
								<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
							</button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="btn-add-row" data-target="cat-repeater" data-name="categories[]" data-placeholder="<?php esc_attr_e( 'e.g. Utilities', 'awesome-hotel-booking' ); ?>">
					+ <?php esc_html_e( 'Add Category', 'awesome-hotel-booking' ); ?>
				</button>
			</div>

			<!-- Items Repeater Card -->
			<div class="ahbn-repeater-card">
				<h3>
					<span class="dashicons dashicons-cart" aria-hidden="true"></span>
					<?php esc_html_e( 'Expense Items', 'awesome-hotel-booking' ); ?>
				</h3>
				<div class="repeater-container" id="item-repeater">
					<?php foreach ( $items as $val ) : ?>
						<div class="ahbn-repeater-row">
							<input 
								type="text" 
								name="items[]" 
								value="<?php echo esc_attr( $val ); ?>" 
								placeholder="<?php esc_attr_e( 'e.g. WiFi Bill', 'awesome-hotel-booking' ); ?>"
								aria-label="<?php esc_attr_e( 'Item Name', 'awesome-hotel-booking' ); ?>"
							>
							<button type="button" class="btn-remove remove-row" aria-label="<?php esc_attr_e( 'Remove Item', 'awesome-hotel-booking' ); ?>">
								<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
							</button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="btn-add-row" data-target="item-repeater" data-name="items[]" data-placeholder="<?php esc_attr_e( 'e.g. WiFi Bill', 'awesome-hotel-booking' ); ?>">
					+ <?php esc_html_e( 'Add Item', 'awesome-hotel-booking' ); ?>
				</button>
			</div>

			<!-- Department Repeater Card -->
			<div class="ahbn-repeater-card">
				<h3>
					<span class="dashicons dashicons-groups" aria-hidden="true"></span>
					<?php esc_html_e( 'Department Assignment', 'awesome-hotel-booking' ); ?>
				</h3>
				<div class="repeater-container" id="dept-repeater">
					<?php foreach ( $departments as $val ) : ?>
						<div class="ahbn-repeater-row">
							<input 
								type="text" 
								name="departments[]" 
								value="<?php echo esc_attr( $val ); ?>" 
								placeholder="<?php esc_attr_e( 'e.g. Housekeeping', 'awesome-hotel-booking' ); ?>"
								aria-label="<?php esc_attr_e( 'Department Name', 'awesome-hotel-booking' ); ?>"
							>
							<button type="button" class="btn-remove remove-row" aria-label="<?php esc_attr_e( 'Remove Department', 'awesome-hotel-booking' ); ?>">
								<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
							</button>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="btn-add-row" data-target="dept-repeater" data-name="departments[]" data-placeholder="<?php esc_attr_e( 'e.g. Housekeeping', 'awesome-hotel-booking' ); ?>">
					+ <?php esc_html_e( 'Add Department', 'awesome-hotel-booking' ); ?>
				</button>
			</div>

		</div>

		<div class="settings-footer">
			<button type="submit" name="ahbn_save_expense_settings" class="button button-primary" style="background:#0A1A38; border:none; height:45px; padding:0 30px; border-radius:8px; font-weight:700;">
				<?php esc_html_e( 'Save Expense Configuration', 'awesome-hotel-booking' ); ?>
			</button>
		</div>
	</form>

	<script>
	jQuery(document).ready(function($) {
		var defaultPlaceholder = '<?php echo esc_js( __( 'Type here...', 'awesome-hotel-booking' ) ); ?>';
		var alertText          = '<?php echo esc_js( __( 'At least one item is required in this list.', 'awesome-hotel-booking' ) ); ?>';

		// Add Row via safe DOM construction.
		$('.btn-add-row').on('click', function(e) {
			e.preventDefault();
			var target      = $(this).data('target');
			var name        = $(this).data('name');
			var placeholder = $(this).data('placeholder') || defaultPlaceholder;

			var $row = $('<div/>', { class: 'ahbn-repeater-row' })
				.append($('<input/>', {
					type: 'text',
					name: name,
					value: '',
					placeholder: placeholder
				}))
				.append(
					$('<button/>', {
						type: 'button',
						class: 'btn-remove remove-row',
						'aria-label': '<?php echo esc_js( __( 'Remove Row', 'awesome-hotel-booking' ) ); ?>'
					}).append($('<span/>', { class: 'dashicons dashicons-no-alt', 'aria-hidden': 'true' }))
				);

			$('#' + target).append($row);
		});

		// Remove Row (Delegated Event).
		$(document).on('click', '.remove-row', function(e) {
			e.preventDefault();
			var $container = $(this).closest('.repeater-container');
			if ($container.find('.ahbn-repeater-row').length > 1) {
				$(this).closest('.ahbn-repeater-row').remove();
			} else {
				alert(alertText);
			}
		});
	});
	</script>
	<?php
}