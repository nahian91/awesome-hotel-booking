<?php
/**
 * Expense Add / Edit Interface.
 *
 * Rewritten for custom SQL database tables:
 * {$wpdb->prefix}ahbn_expenses
 * Styled with the Champagne Gold & Deep Navy enterprise UI system.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render Expense Add/Edit form and process submissions.
 *
 * @param int $edit_id Optional. Expense record ID to edit. Defaults to 0.
 * @return void
 */
function ahbn_expense_add_edit( $edit_id = 0 ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	global $wpdb;
	$table_expenses = $wpdb->prefix . 'ahbn_expenses';

	$page_slug = 'awesome_hotel_booking';
	$base_url  = admin_url( 'admin.php?page=' . rawurlencode( $page_slug ) . '&tab=expense' );
	$currency  = get_option( 'ahbn_currency', '£' );

	// Fetch dynamic categories/departments from stored configuration.
	$default_config = array(
		'categories'  => array( 'Maintenance', 'Housekeeping Supplies', 'Utilities & Energy', 'Food & Beverage', 'Marketing & Ads', 'Software & Licenses', 'Salaries & Wages', 'General' ),
		'departments' => array( 'Operations', 'Front Desk', 'Housekeeping', 'Facilities & Engineering', 'Management', 'Food & Beverage' ),
	);
	$config         = get_option( 'ahbn_expense_config', $default_config );

	// Resolve the active edit ID from function argument or sanitized query string.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$ahbn_active_edit_id = absint( $edit_id );
	if ( 0 === $ahbn_active_edit_id && isset( $_GET['expense_id'] ) ) {
		$ahbn_active_edit_id = absint( wp_unslash( $_GET['expense_id'] ) );
	} elseif ( 0 === $ahbn_active_edit_id && isset( $_GET['edit'] ) ) {
		$ahbn_active_edit_id = absint( wp_unslash( $_GET['edit'] ) );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	// 1. Data Processing (Save / Update Logic with Direct SQL).
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_expense_action'] ) ) {

		$nonce = isset( $_POST['ahbn_expense_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_expense_nonce'] ) ) : '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'ahbn_save_expense' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security verification failed. Please try again.', 'awesome-hotel-booking' ) . '</p></div>';
		} else {
			$ahbn_expense_title = isset( $_POST['expense_title'] ) ? sanitize_text_field( wp_unslash( $_POST['expense_title'] ) ) : '';
			$ahbn_edit_id_post  = isset( $_POST['ahbn_edit_id'] ) ? absint( wp_unslash( $_POST['ahbn_edit_id'] ) ) : 0;
			$amount             = isset( $_POST['expense_amount'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['expense_amount'] ) ) : 0.00;
			$category           = isset( $_POST['expense_category'] ) ? sanitize_text_field( wp_unslash( $_POST['expense_category'] ) ) : 'General';
			$department         = isset( $_POST['expense_dept'] ) ? sanitize_text_field( wp_unslash( $_POST['expense_dept'] ) ) : 'Operations';
			$expense_date       = isset( $_POST['expense_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expense_date'] ) ) : current_time( 'Y-m-d' );
			$description        = isset( $_POST['expense_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['expense_desc'] ) ) : '';
			$receipt_id         = isset( $_POST['ahbn_receipt_id'] ) ? absint( wp_unslash( $_POST['ahbn_receipt_id'] ) ) : 0;

			if ( empty( $ahbn_expense_title ) || $amount <= 0 ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please provide an expense title and an amount greater than zero.', 'awesome-hotel-booking' ) . '</p></div>';
			} else {
				$expense_data = array(
					'title'        => $ahbn_expense_title,
					'amount'       => $amount,
					'category'     => ! empty( $category ) ? $category : 'General',
					'department'   => ! empty( $department ) ? $department : 'Operations',
					'expense_date' => $expense_date,
					'description'  => $description,
					'receipt_id'   => $receipt_id,
					'created_by'   => get_current_user_id(),
				);

				$format = array( '%s', '%f', '%s', '%s', '%s', '%s', '%d', '%d' );

				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( $ahbn_edit_id_post > 0 ) {
					$wpdb->update(
						$table_expenses,
						$expense_data,
						array( 'id' => $ahbn_edit_id_post ),
						$format,
						array( '%d' )
					);
					$ahbn_active_edit_id = $ahbn_edit_id_post;

					echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Expense record updated successfully!', 'awesome-hotel-booking' ) . '</p></div>';
				} else {
					$wpdb->insert( $table_expenses, $expense_data, $format );
					$ahbn_active_edit_id = (int) $wpdb->insert_id;

					echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'New expense record added to ledger!', 'awesome-hotel-booking' ) . '</p></div>';
				}
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
		}
	}

	// 2. Load Existing Data for Form Display from custom SQL table.
	$curr_title = '';
	$curr_meta  = array(
		'amount'      => '',
		'category'    => '',
		'department'  => '',
		'date'        => current_time( 'Y-m-d' ),
		'description' => '',
		'receipt_id'  => 0,
	);

	if ( $ahbn_active_edit_id > 0 ) {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$record = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table_expenses} WHERE id = %d", $ahbn_active_edit_id )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $record ) {
			$curr_title = $record->title;
			$curr_meta  = array(
				'amount'      => (float) $record->amount,
				'category'    => $record->category,
				'department'  => $record->department,
				'date'        => $record->expense_date,
				'description' => $record->description,
				'receipt_id'  => (int) $record->receipt_id,
			);
		}
	}

	$cancel_url = add_query_arg( 'sub', 'all', $base_url );
	?>

	<div class="ahbn-panel" style="max-width: 760px; margin: 0 auto; width: 100%;">
		<div class="ahbn-panel-head">
			<span class="dashicons dashicons-money-alt" aria-hidden="true"></span>
			<h2>
				<?php
				echo $ahbn_active_edit_id
					? sprintf( /* translators: %d: Expense Record ID */ esc_html__( 'Edit Expense Record #%d', 'awesome-hotel-booking' ), absint( $ahbn_active_edit_id ) )
					: esc_html__( 'Record Operational Hotel Expense', 'awesome-hotel-booking' );
				?>
			</h2>
		</div>

		<form method="post" action="">
			<?php wp_nonce_field( 'ahbn_save_expense', 'ahbn_expense_nonce' ); ?>
			<input type="hidden" name="ahbn_save_expense_action" value="1">
			<input type="hidden" name="ahbn_edit_id" value="<?php echo esc_attr( (string) $ahbn_active_edit_id ); ?>">

			<div class="ahbn-panel-body">
				<div class="ahbn-field-group">
					<label for="expense_title" class="ahbn-label">
						<?php esc_html_e( 'Expense Title / Payee Reference *', 'awesome-hotel-booking' ); ?>
					</label>
					<input 
						type="text" 
						id="expense_title" 
						name="expense_title" 
						class="ahbn-input" 
						value="<?php echo esc_attr( $curr_title ); ?>" 
						placeholder="<?php esc_attr_e( 'e.g. Linens & Towels restocking, AC servicing Suite 201', 'awesome-hotel-booking' ); ?>" 
						required 
					>
				</div>

				<div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
					<div class="ahbn-field-group">
						<label for="expense_amount" class="ahbn-label">
							<?php
							printf(
								/* translators: %s: Currency symbol */
								esc_html__( 'Disbursed Amount (%s) *', 'awesome-hotel-booking' ),
								esc_html( $currency )
							);
							?>
						</label>
						<input 
							type="number" 
							step="0.01" 
							min="0.01" 
							id="expense_amount" 
							name="expense_amount" 
							class="ahbn-input" 
							value="<?php echo esc_attr( (string) $curr_meta['amount'] ); ?>" 
							placeholder="0.00" 
							required 
							style="font-family:'JetBrains Mono', monospace; font-weight:700;"
						>
					</div>

					<div class="ahbn-field-group">
						<label for="expense_date" class="ahbn-label">
							<?php esc_html_e( 'Disbursement Date *', 'awesome-hotel-booking' ); ?>
						</label>
						<input 
							type="date" 
							id="expense_date" 
							name="expense_date" 
							class="ahbn-input" 
							value="<?php echo esc_attr( $curr_meta['date'] ); ?>" 
							required
						>
					</div>
				</div>

				<div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
					<div class="ahbn-field-group">
						<label for="expense_category" class="ahbn-label">
							<?php esc_html_e( 'Expense Category', 'awesome-hotel-booking' ); ?>
						</label>
						<select id="expense_category" name="expense_category" class="ahbn-select">
							<option value=""><?php esc_html_e( '— Select Category —', 'awesome-hotel-booking' ); ?></option>
							<?php if ( ! empty( $config['categories'] ) && is_array( $config['categories'] ) ) : ?>
								<?php foreach ( $config['categories'] as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $curr_meta['category'], $cat ); ?>>
										<?php echo esc_html( $cat ); ?>
									</option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
					</div>

					<div class="ahbn-field-group">
						<label for="expense_dept" class="ahbn-label">
							<?php esc_html_e( 'Charging Department', 'awesome-hotel-booking' ); ?>
						</label>
						<select id="expense_dept" name="expense_dept" class="ahbn-select">
							<option value=""><?php esc_html_e( '— Select Department —', 'awesome-hotel-booking' ); ?></option>
							<?php if ( ! empty( $config['departments'] ) && is_array( $config['departments'] ) ) : ?>
								<?php foreach ( $config['departments'] as $dept ) : ?>
									<option value="<?php echo esc_attr( $dept ); ?>" <?php selected( $curr_meta['department'], $dept ); ?>>
										<?php echo esc_html( $dept ); ?>
									</option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
					</div>
				</div>

				<div class="ahbn-field-group">
					<label for="expense_desc" class="ahbn-label">
						<?php esc_html_e( 'Internal Description / Ledger Notes', 'awesome-hotel-booking' ); ?>
					</label>
					<textarea id="expense_desc" name="expense_desc" rows="3" class="ahbn-textarea" placeholder="<?php esc_attr_e( 'Invoice reference numbers, contractor vendor names, purchase order codes...', 'awesome-hotel-booking' ); ?>"><?php echo esc_textarea( $curr_meta['description'] ); ?></textarea>
				</div>

				<!-- Receipt Voucher Attachment -->
				<div class="ahbn-field-group">
					<label class="ahbn-label">
						<?php esc_html_e( 'Receipt / Bill Voucher Attachment', 'awesome-hotel-booking' ); ?>
					</label>
					<input type="hidden" name="ahbn_receipt_id" id="ahbn_receipt_id" value="<?php echo esc_attr( (string) $curr_meta['receipt_id'] ); ?>">
					
					<div id="ahbn_upload_trigger" class="ahbn-receipt-uploader">
						<?php if ( ! empty( $curr_meta['receipt_id'] ) ) : ?>
							<?php echo wp_get_attachment_image( $curr_meta['receipt_id'], array( 120, 120 ), false, array( 'class' => 'ahbn-receipt-thumb' ) ); ?>
							<p class="ahbn-receipt-text"><?php esc_html_e( 'Click to change attached receipt photo', 'awesome-hotel-booking' ); ?></p>
						<?php else : ?>
							<span class="dashicons dashicons-cloud-upload" aria-hidden="true"></span>
							<p class="ahbn-receipt-text"><?php esc_html_e( 'Click to browse or drop receipt invoice image', 'awesome-hotel-booking' ); ?></p>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $curr_meta['receipt_id'] ) ) : ?>
						<button type="button" class="ahbn-btn-remove-receipt" id="ahbn_remove_receipt_btn">
							<span class="dashicons dashicons-trash" aria-hidden="true"></span>
							<?php esc_html_e( 'Remove Receipt Attachment', 'awesome-hotel-booking' ); ?>
						</button>
					<?php endif; ?>
				</div>
			</div>

			<div class="ahbn-panel-footer">
				<a href="<?php echo esc_url( $cancel_url ); ?>" class="ahbn-btn-cancel">
					<?php esc_html_e( 'Cancel', 'awesome-hotel-booking' ); ?>
				</a>
				<button type="submit" class="ahbn-btn-save">
					<span class="dashicons dashicons-saved" aria-hidden="true"></span>
					<?php
					echo $ahbn_active_edit_id
						? esc_html__( 'Update Expense Record', 'awesome-hotel-booking' )
						: esc_html__( 'Save Expense to Ledger', 'awesome-hotel-booking' );
					?>
				</button>
			</div>
		</form>
	</div>

	<style>
		.ahbn-receipt-uploader {
			border: 2px dashed #cbd5e1;
			padding: 24px 20px;
			text-align: center;
			cursor: pointer;
			border-radius: 10px;
			background: #f8fafc;
			transition: all 0.2s ease;
		}

		.ahbn-receipt-uploader:hover {
			border-color: #0a192f;
			background: #ffffff;
		}

		.ahbn-receipt-uploader .dashicons {
			font-size: 34px;
			height: 34px;
			width: 34px;
			color: #94a3b8;
		}

		.ahbn-receipt-thumb {
			max-width: 120px;
			height: auto;
			display: block;
			margin: 0 auto;
			border-radius: 8px;
			border: 1px solid #e2e8f0;
			box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
		}

		.ahbn-receipt-text {
			margin: 8px 0 0;
			color: #64748b;
			font-size: 12px;
			font-weight: 600;
		}

		.ahbn-btn-remove-receipt {
			display: inline-flex;
			align-items: center;
			gap: 4px;
			background: none;
			border: none;
			color: #ef4444;
			font-size: 11px;
			font-weight: 700;
			cursor: pointer;
			margin-top: 6px;
			padding: 0;
		}

		.ahbn-btn-remove-receipt:hover {
			text-decoration: underline;
		}

		.ahbn-btn-remove-receipt .dashicons {
			font-size: 14px;
			width: 14px;
			height: 14px;
		}
	</style>

	<script>
	jQuery(document).ready(function($) {
		let receiptFrame;

		$('#ahbn_upload_trigger').on('click', function(e) {
			e.preventDefault();
			if (receiptFrame) {
				receiptFrame.open();
				return;
			}
			receiptFrame = wp.media({
				title: '<?php echo esc_js( __( 'Select Receipt or Invoice Document', 'awesome-hotel-booking' ) ); ?>',
				button: { text: '<?php echo esc_js( __( 'Attach Receipt', 'awesome-hotel-booking' ) ); ?>' },
				multiple: false
			});

			receiptFrame.on('select', function() {
				const attachment = receiptFrame.state().get('selection').first().toJSON();
				$('#ahbn_receipt_id').val(attachment.id);
				$('#ahbn_upload_trigger').html(
					'<img src="' + encodeURI(attachment.url) + '" class="ahbn-receipt-thumb">' +
					'<p class="ahbn-receipt-text"><?php echo esc_js( __( 'Receipt Selected. Click to change.', 'awesome-hotel-booking' ) ); ?></p>'
				);
			});
			receiptFrame.open();
		});

		$(document).on('click', '#ahbn_remove_receipt_btn', function(e) {
			e.preventDefault();
			$('#ahbn_receipt_id').val(0);
			$('#ahbn_upload_trigger').html(
				'<span class="dashicons dashicons-cloud-upload" aria-hidden="true"></span>' +
				'<p class="ahbn-receipt-text"><?php echo esc_js( __( 'Click to browse or drop receipt invoice image', 'awesome-hotel-booking' ) ); ?></p>'
			);
			$(this).remove();
		});
	});
	</script>
	<?php
}