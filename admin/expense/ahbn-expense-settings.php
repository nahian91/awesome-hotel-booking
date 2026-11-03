<?php
/**
 * Expense Settings Configuration.
 *
 * Built with the Champagne Gold & Deep Navy enterprise UI system.
 * Manages dynamic repeater lists for Categories, Items, and Departments.
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
			'categories'  => ! empty( $clean_categories ) ? $clean_categories : array( 'Maintenance', 'Utilities & Energy', 'Housekeeping Supplies', 'Food & Beverage', 'General' ),
			'items'       => ! empty( $clean_items ) ? $clean_items : array( 'Electricity Bill', 'Cleaning Linens', 'Plumbing Repair', 'WiFi & Telephony' ),
			'departments' => ! empty( $clean_departments ) ? $clean_departments : array( 'Operations', 'Front Desk', 'Housekeeping', 'Food & Beverage', 'Management' ),
		);

		update_option( 'ahbn_expense_config', $settings );
		echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Expense categories and department taxonomy saved.', 'awesome-hotel-booking' ) . '</p></div>';
	}

	// 2. Fetch Stored Configuration (with Fallback Defaults).
	$default_config = array(
		'categories'  => array( 'Maintenance', 'Utilities & Energy', 'Housekeeping Supplies', 'Food & Beverage', 'General' ),
		'items'       => array( 'Electricity Bill', 'Cleaning Linens', 'Plumbing Repair', 'WiFi & Telephony' ),
		'departments' => array( 'Operations', 'Front Desk', 'Housekeeping', 'Food & Beverage', 'Management' ),
	);
	$config         = get_option( 'ahbn_expense_config', $default_config );

	$categories  = isset( $config['categories'] ) && is_array( $config['categories'] ) ? $config['categories'] : $default_config['categories'];
	$items       = isset( $config['items'] ) && is_array( $config['items'] ) ? $config['items'] : $default_config['items'];
	$departments = isset( $config['departments'] ) && is_array( $config['departments'] ) ? $config['departments'] : $default_config['departments'];
	?>

	<div class="ahbn-expense-settings-wrap">
		<form method="post" action="">
			<?php wp_nonce_field( 'ahbn_expense_settings_nonce' ); ?>

			<div class="ahbn-settings-grid">
				
				<!-- Card 1: Categories Repeater -->
				<div class="ahbn-panel">
					<div class="ahbn-panel-head">
						<span class="dashicons dashicons-category" aria-hidden="true"></span>
						<h2><?php esc_html_e( 'Expense Categories', 'awesome-hotel-booking' ); ?></h2>
					</div>
					<div class="ahbn-panel-body">
						<p class="ahbn-panel-desc"><?php esc_html_e( 'Taxonomy used to organize expense types across accounts and reports.', 'awesome-hotel-booking' ); ?></p>
						<div class="repeater-container" id="cat-repeater">
							<?php foreach ( $categories as $val ) : ?>
								<div class="ahbn-repeater-row">
									<span class="dashicons dashicons-menu ahbn-drag-handle" aria-hidden="true"></span>
									<input 
										type="text" 
										name="categories[]" 
										class="ahbn-input"
										value="<?php echo esc_attr( $val ); ?>" 
										placeholder="<?php esc_attr_e( 'e.g. Utilities', 'awesome-hotel-booking' ); ?>"
										aria-label="<?php esc_attr_e( 'Category Name', 'awesome-hotel-booking' ); ?>"
										required
									>
									<button type="button" class="btn-remove remove-row" aria-label="<?php esc_attr_e( 'Remove Category', 'awesome-hotel-booking' ); ?>" title="<?php esc_attr_e( 'Remove Row', 'awesome-hotel-booking' ); ?>">
										<span class="dashicons dashicons-trash" aria-hidden="true"></span>
									</button>
								</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="btn-add-row" data-target="cat-repeater" data-name="categories[]" data-placeholder="<?php esc_attr_e( 'e.g. Marketing & Advertising', 'awesome-hotel-booking' ); ?>">
							<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
							<?php esc_html_e( 'Add Category', 'awesome-hotel-booking' ); ?>
						</button>
					</div>
				</div>

				<!-- Card 2: Items Repeater -->
				<div class="ahbn-panel">
					<div class="ahbn-panel-head">
						<span class="dashicons dashicons-cart" aria-hidden="true"></span>
						<h2><?php esc_html_e( 'Standard Expense Items', 'awesome-hotel-booking' ); ?></h2>
					</div>
					<div class="ahbn-panel-body">
						<p class="ahbn-panel-desc"><?php esc_html_e( 'Frequent line item labels suggested when logging new receipts.', 'awesome-hotel-booking' ); ?></p>
						<div class="repeater-container" id="item-repeater">
							<?php foreach ( $items as $val ) : ?>
								<div class="ahbn-repeater-row">
									<span class="dashicons dashicons-menu ahbn-drag-handle" aria-hidden="true"></span>
									<input 
										type="text" 
										name="items[]" 
										class="ahbn-input"
										value="<?php echo esc_attr( $val ); ?>" 
										placeholder="<?php esc_attr_e( 'e.g. WiFi Bill', 'awesome-hotel-booking' ); ?>"
										aria-label="<?php esc_attr_e( 'Item Name', 'awesome-hotel-booking' ); ?>"
										required
									>
									<button type="button" class="btn-remove remove-row" aria-label="<?php esc_attr_e( 'Remove Item', 'awesome-hotel-booking' ); ?>" title="<?php esc_attr_e( 'Remove Row', 'awesome-hotel-booking' ); ?>">
										<span class="dashicons dashicons-trash" aria-hidden="true"></span>
									</button>
								</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="btn-add-row" data-target="item-repeater" data-name="items[]" data-placeholder="<?php esc_attr_e( 'e.g. Laundry Detergent Refill', 'awesome-hotel-booking' ); ?>">
							<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
							<?php esc_html_e( 'Add Item', 'awesome-hotel-booking' ); ?>
						</button>
					</div>
				</div>

				<!-- Card 3: Department Repeater -->
				<div class="ahbn-panel">
					<div class="ahbn-panel-head">
						<span class="dashicons dashicons-groups" aria-hidden="true"></span>
						<h2><?php esc_html_e( 'Department Allocation', 'awesome-hotel-booking' ); ?></h2>
					</div>
					<div class="ahbn-panel-body">
						<p class="ahbn-panel-desc"><?php esc_html_e( 'Internal hotel cost centers for tracking departmental budgets.', 'awesome-hotel-booking' ); ?></p>
						<div class="repeater-container" id="dept-repeater">
							<?php foreach ( $departments as $val ) : ?>
								<div class="ahbn-repeater-row">
									<span class="dashicons dashicons-menu ahbn-drag-handle" aria-hidden="true"></span>
									<input 
										type="text" 
										name="departments[]" 
										class="ahbn-input"
										value="<?php echo esc_attr( $val ); ?>" 
										placeholder="<?php esc_attr_e( 'e.g. Housekeeping', 'awesome-hotel-booking' ); ?>"
										aria-label="<?php esc_attr_e( 'Department Name', 'awesome-hotel-booking' ); ?>"
										required
									>
									<button type="button" class="btn-remove remove-row" aria-label="<?php esc_attr_e( 'Remove Department', 'awesome-hotel-booking' ); ?>" title="<?php esc_attr_e( 'Remove Row', 'awesome-hotel-booking' ); ?>">
										<span class="dashicons dashicons-trash" aria-hidden="true"></span>
									</button>
								</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="btn-add-row" data-target="dept-repeater" data-name="departments[]" data-placeholder="<?php esc_attr_e( 'e.g. Facilities & Security', 'awesome-hotel-booking' ); ?>">
							<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
							<?php esc_html_e( 'Add Department', 'awesome-hotel-booking' ); ?>
						</button>
					</div>
				</div>

			</div>

			<div class="ahbn-panel-actions">
				<button type="submit" name="ahbn_save_expense_settings" class="ahbn-btn-save">
					<span class="dashicons dashicons-saved" aria-hidden="true"></span>
					<?php esc_html_e( 'Save Expense Configuration', 'awesome-hotel-booking' ); ?>
				</button>
			</div>
		</form>
	</div>

	<style>
		.ahbn-expense-settings-wrap {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-settings-grid {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 20px;
			align-items: start;
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
			padding: 20px;
			display: flex;
			flex-direction: column;
			gap: 14px;
		}

		.ahbn-panel-desc {
			margin: 0;
			font-size: 12px;
			color: #64748b;
			line-height: 1.4;
		}

		.repeater-container {
			display: flex;
			flex-direction: column;
			gap: 8px;
		}

		.ahbn-repeater-row {
			display: flex;
			align-items: center;
			gap: 8px;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 8px;
			padding: 6px 10px;
			transition: all 0.2s ease;
		}

		.ahbn-repeater-row:hover {
			border-color: #cbd5e1;
			background: #ffffff;
		}

		.ahbn-drag-handle {
			color: #cbd5e1;
			cursor: grab;
			font-size: 16px;
			width: 16px;
			height: 16px;
			flex-shrink: 0;
		}

		.ahbn-repeater-row .ahbn-input {
			flex: 1;
			height: 34px !important;
			border: 1px solid #cbd5e1 !important;
			border-radius: 6px !important;
			padding: 0 10px !important;
			font-size: 13px !important;
			background: #ffffff !important;
			color: #0f172a !important;
			box-shadow: none !important;
		}

		.ahbn-repeater-row .ahbn-input:focus {
			border-color: #0a192f !important;
			box-shadow: 0 0 0 2px rgba(10, 25, 47, 0.1) !important;
		}

		.btn-remove {
			width: 30px;
			height: 30px;
			border: 1px solid #fecdd3;
			background: #fff1f2;
			border-radius: 6px;
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
			font-size: 15px;
			width: 15px;
			height: 15px;
		}

		.btn-add-row {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 6px;
			background: #ffffff;
			border: 1px dashed #cbd5e1;
			border-radius: 8px;
			padding: 8px 14px;
			font-size: 12px;
			font-weight: 700;
			color: #0a192f;
			cursor: pointer;
			transition: all 0.2s ease;
			width: 100%;
			margin-top: 4px;
		}

		.btn-add-row .dashicons {
			font-size: 14px;
			width: 14px;
			height: 14px;
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

		@media (max-width: 1200px) {
			.ahbn-settings-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>

	<script>
	jQuery(document).ready(function($) {
		const defaultPlaceholder = '<?php echo esc_js( __( 'Type entry name...', 'awesome-hotel-booking' ) ); ?>';
		const alertText          = '<?php echo esc_js( __( 'At least one item is required in this list.', 'awesome-hotel-booking' ) ); ?>';

		// Add Row via safe DOM construction.
		$('.btn-add-row').on('click', function(e) {
			e.preventDefault();
			const target      = $(this).data('target');
			const name        = $(this).data('name');
			const placeholder = $(this).data('placeholder') || defaultPlaceholder;

			const $row = $('<div/>', { class: 'ahbn-repeater-row' })
				.append($('<span/>', { class: 'dashicons dashicons-menu ahbn-drag-handle', 'aria-hidden': 'true' }))
				.append($('<input/>', {
					type: 'text',
					name: name,
					class: 'ahbn-input',
					value: '',
					placeholder: placeholder,
					required: true
				}))
				.append(
					$('<button/>', {
						type: 'button',
						class: 'btn-remove remove-row',
						'aria-label': '<?php echo esc_js( __( 'Remove Row', 'awesome-hotel-booking' ) ); ?>',
						title: '<?php echo esc_js( __( 'Remove Row', 'awesome-hotel-booking' ) ); ?>'
					}).append($('<span/>', { class: 'dashicons dashicons-trash', 'aria-hidden': 'true' }))
				);

			$('#' + target).append($row);
			$row.find('input').focus();
		});

		// Remove Row (Delegated Event).
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