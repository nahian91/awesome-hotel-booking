<?php
/**
 * Expense Add / Edit Interface.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render Expense Add/Edit form and process submissions.
 *
 * @param int $edit_id Optional. Expense post ID to edit. Defaults to 0.
 * @return void
 */
function ahbn_expense_add_edit( $edit_id = 0 ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	$page_slug = 'awesome_hotel_booking';
	$currency  = get_option( 'ahbn_currency', '£' );

	// Fetch dynamic categories/departments from stored configuration.
	$default_config = array(
		'categories'  => array( 'Maintenance', 'Utilities', 'Housekeeping' ),
		'departments' => array( 'Front Desk', 'Kitchen', 'Management' ),
	);
	$config         = get_option( 'ahbn_expense_config', $default_config );

	// Resolve the active edit ID from function argument or sanitized query string.
	$ahbn_active_edit_id = absint( $edit_id );
	if ( 0 === $ahbn_active_edit_id && isset( $_GET['expense_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ahbn_active_edit_id = absint( wp_unslash( $_GET['expense_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} elseif ( 0 === $ahbn_active_edit_id && isset( $_GET['edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ahbn_active_edit_id = absint( wp_unslash( $_GET['edit'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	// 1. Data Processing (Save / Update Logic).
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_expense_action'] ) ) {

		$nonce = isset( $_POST['ahbn_expense_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_expense_nonce'] ) ) : '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'ahbn_save_expense' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security verification failed. Please try again.', 'awesome-hotel-booking' ) . '</p></div>';
		} else {
			$ahbn_expense_title = isset( $_POST['expense_title'] ) ? sanitize_text_field( wp_unslash( $_POST['expense_title'] ) ) : '';
			$ahbn_edit_id_post  = isset( $_POST['ahbn_edit_id'] ) ? absint( wp_unslash( $_POST['ahbn_edit_id'] ) ) : 0;

			$ahbn_expense_meta = array(
				'amount'      => isset( $_POST['expense_amount'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['expense_amount'] ) ) : 0.00,
				'category'    => isset( $_POST['expense_category'] ) ? sanitize_text_field( wp_unslash( $_POST['expense_category'] ) ) : '',
				'department'  => isset( $_POST['expense_dept'] ) ? sanitize_text_field( wp_unslash( $_POST['expense_dept'] ) ) : '',
				'date'        => isset( $_POST['expense_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expense_date'] ) ) : current_time( 'Y-m-d' ),
				'description' => isset( $_POST['expense_desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['expense_desc'] ) ) : '',
				'receipt_id'  => isset( $_POST['ahbn_receipt_id'] ) ? absint( wp_unslash( $_POST['ahbn_receipt_id'] ) ) : 0,
			);

			if ( empty( $ahbn_expense_title ) ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please provide an expense title or reference.', 'awesome-hotel-booking' ) . '</p></div>';
			} elseif ( $ahbn_edit_id_post > 0 ) {
				// Verify user can edit this specific post.
				if ( ! current_user_can( 'edit_post', $ahbn_edit_id_post ) ) {
					wp_die( esc_html__( 'You do not have permission to edit this record.', 'awesome-hotel-booking' ) );
				}

				wp_update_post(
					array(
						'ID'         => $ahbn_edit_id_post,
						'post_title' => $ahbn_expense_title,
					)
				);

				update_post_meta( $ahbn_edit_id_post, '_ahbn_expense_data', $ahbn_expense_meta );
				$ahbn_active_edit_id = $ahbn_edit_id_post;

				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Expense updated successfully!', 'awesome-hotel-booking' ) . '</p></div>';
			} else {
				$new_expense_id = wp_insert_post(
					array(
						'post_title'  => $ahbn_expense_title,
						'post_type'   => 'ahbn_expense',
						'post_status' => 'publish',
					)
				);

				if ( $new_expense_id && ! is_wp_error( $new_expense_id ) ) {
					update_post_meta( $new_expense_id, '_ahbn_expense_data', $ahbn_expense_meta );
					$ahbn_active_edit_id = $new_expense_id;

					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'New expense recorded successfully!', 'awesome-hotel-booking' ) . '</p></div>';
				}
			}
		}
	}

	// 2. Load Existing Data for Form Display.
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
		$post = get_post( $ahbn_active_edit_id );
		if ( $post && 'ahbn_expense' === $post->post_type ) {
			$curr_title = $post->post_title;
			$saved_meta = get_post_meta( $ahbn_active_edit_id, '_ahbn_expense_data', true );
			if ( is_array( $saved_meta ) ) {
				$curr_meta = array_merge( $curr_meta, $saved_meta );
			}
		}
	}

	$cancel_url = admin_url( 'admin.php?page=' . rawurlencode( $page_slug ) . '&tab=expense&sub=all' );
	?>

	<div class="ahbn-form-wrap" style="margin-top:20px;">
		<div class="ahbn-form-card" style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:30px; max-width:800px; box-shadow:0 4px 20px rgba(0,0,0,0.05);">
			
			<form method="post" action="">
				<?php wp_nonce_field( 'ahbn_save_expense', 'ahbn_expense_nonce' ); ?>
				<input type="hidden" name="ahbn_save_expense_action" value="1">
				<input type="hidden" name="ahbn_edit_id" value="<?php echo esc_attr( (string) $ahbn_active_edit_id ); ?>">

				<div class="ahbn-form-grid" style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">
					
					<div class="ahbn-form-group" style="grid-column: span 2;">
						<label for="expense_title" style="display:block; font-weight:700; color:#0A1A38; margin-bottom:8px;">
							<?php esc_html_e( 'Expense Title / Reference', 'awesome-hotel-booking' ); ?>
						</label>
						<input 
							type="text" 
							id="expense_title" 
							name="expense_title" 
							class="widefat" 
							value="<?php echo esc_attr( $curr_title ); ?>" 
							placeholder="<?php esc_attr_e( 'e.g. Electricity Bill Jan', 'awesome-hotel-booking' ); ?>" 
							required 
							style="height:40px; border-radius:8px;"
						>
					</div>

					<div class="ahbn-form-group">
						<label for="expense_amount" style="display:block; font-weight:700; color:#0A1A38; margin-bottom:8px;">
							<?php
							printf(
								/* translators: %s: Currency symbol */
								esc_html__( 'Amount (%s)', 'awesome-hotel-booking' ),
								esc_html( $currency )
							);
							?>
						</label>
						<input 
							type="number" 
							step="0.01" 
							min="0" 
							id="expense_amount" 
							name="expense_amount" 
							class="widefat" 
							value="<?php echo esc_attr( (string) $curr_meta['amount'] ); ?>" 
							required 
							style="height:40px; border-radius:8px;"
						>
					</div>

					<div class="ahbn-form-group">
						<label for="expense_date" style="display:block; font-weight:700; color:#0A1A38; margin-bottom:8px;">
							<?php esc_html_e( 'Date', 'awesome-hotel-booking' ); ?>
						</label>
						<input 
							type="date" 
							id="expense_date" 
							name="expense_date" 
							class="widefat" 
							value="<?php echo esc_attr( $curr_meta['date'] ); ?>" 
							style="height:40px; border-radius:8px;"
						>
					</div>

					<div class="ahbn-form-group">
						<label for="expense_category" style="display:block; font-weight:700; color:#0A1A38; margin-bottom:8px;">
							<?php esc_html_e( 'Category', 'awesome-hotel-booking' ); ?>
						</label>
						<select id="expense_category" name="expense_category" class="widefat" style="height:40px; border-radius:8px;">
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

					<div class="ahbn-form-group">
						<label for="expense_dept" style="display:block; font-weight:700; color:#0A1A38; margin-bottom:8px;">
							<?php esc_html_e( 'Department', 'awesome-hotel-booking' ); ?>
						</label>
						<select id="expense_dept" name="expense_dept" class="widefat" style="height:40px; border-radius:8px;">
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

					<div class="ahbn-form-group" style="grid-column: span 2;">
						<label for="expense_desc" style="display:block; font-weight:700; color:#0A1A38; margin-bottom:8px;">
							<?php esc_html_e( 'Description / Internal Notes', 'awesome-hotel-booking' ); ?>
						</label>
						<textarea id="expense_desc" name="expense_desc" rows="3" class="widefat" style="border-radius:8px;"><?php echo esc_textarea( $curr_meta['description'] ); ?></textarea>
					</div>

					<div class="ahbn-form-group" style="grid-column: span 2;">
						<label class="ahbn-label" style="display:block; font-weight:700; color:#0A1A38; margin-bottom:8px;">
							<?php esc_html_e( 'Receipt Attachment', 'awesome-hotel-booking' ); ?>
						</label>
						<input type="hidden" name="ahbn_receipt_id" id="ahbn_receipt_id" value="<?php echo esc_attr( (string) $curr_meta['receipt_id'] ); ?>">
						
						<div id="ahbn_upload_trigger" style="border:2px dashed #d1d5db; padding:20px; text-align:center; cursor:pointer; border-radius:8px; background:#fafafa;">
							<?php if ( ! empty( $curr_meta['receipt_id'] ) ) : ?>
								<?php echo wp_get_attachment_image( $curr_meta['receipt_id'], array( 100, 100 ), false, array( 'style' => 'max-width:100px; height:auto; display:block; margin:0 auto;' ) ); ?>
								<p style="margin:8px 0 0; color:#4b5563; font-size:13px;"><?php esc_html_e( 'Click to change receipt image', 'awesome-hotel-booking' ); ?></p>
							<?php else : ?>
								<span class="dashicons dashicons-cloud-upload" style="font-size:32px; height:32px; width:32px; color:#9ca3af;" aria-hidden="true"></span>
								<p style="margin:8px 0 0; color:#4b5563; font-size:13px;"><?php esc_html_e( 'Click to upload receipt photo', 'awesome-hotel-booking' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<div style="margin-top:30px; display:flex; justify-content:space-between; align-items:center;">
					<a href="<?php echo esc_url( $cancel_url ); ?>" class="button">
						<?php esc_html_e( 'Cancel', 'awesome-hotel-booking' ); ?>
					</a>
					<button type="submit" class="button button-primary" style="background:#0A1A38; border:none; padding:0 30px; height:42px; border-radius:8px; font-weight:600;">
						<?php
						echo $ahbn_active_edit_id
							? esc_html__( 'Update Record', 'awesome-hotel-booking' )
							: esc_html__( 'Save Expense', 'awesome-hotel-booking' );
						?>
					</button>
				</div>
			</form>
		</div>
	</div>

	<script>
	jQuery(document).ready(function($){
		$('#ahbn_upload_trigger').on('click', function(e) {
			e.preventDefault();
			var frame = wp.media({
				title: '<?php echo esc_js( __( 'Select Receipt', 'awesome-hotel-booking' ) ); ?>',
				multiple: false
			});
			frame.on('select', function() {
				var attachment = frame.state().get('selection').first().toJSON();
				$('#ahbn_receipt_id').val(attachment.id);
				$('#ahbn_upload_trigger').html(
					'<img src="' + encodeURI(attachment.url) + '" style="max-width:100px; height:auto; display:block; margin:0 auto; border-radius:6px;">' +
					'<p style="margin:8px 0 0; color:#4b5563; font-size:13px;"><?php echo esc_js( __( 'Receipt Selected. Click to change.', 'awesome-hotel-booking' ) ); ?></p>'
				);
			}).open();
		});
	});
	</script>
	<?php
}