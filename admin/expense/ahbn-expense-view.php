<?php
/**
 * Room / Category Type Add & Edit Interface.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render taxonomy term add/edit screen with custom image meta.
 *
 * @return void
 */
function ahbn_vehicle_type_add_edit() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	$taxonomy = taxonomy_exists( 'ahbn_room_type' ) ? 'ahbn_room_type' : ( taxonomy_exists( 'room_type' ) ? 'room_type' : 'vehicle_type' );
	$item_id  = isset( $_GET['item'] ) ? absint( wp_unslash( $_GET['item'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$term   = $item_id ? get_term( $item_id, $taxonomy ) : null;
	$img_id = ( $term && ! is_wp_error( $term ) ) ? (int) get_term_meta( $item_id, 'ahbn_type_image', true ) : 0;

	// 1. Handle Form Submission.
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_type'] ) ) {
		check_admin_referer( 'ahbn_type_nonce' );

		$name   = isset( $_POST['type_name'] ) ? sanitize_text_field( wp_unslash( $_POST['type_name'] ) ) : '';
		$img_id = isset( $_POST['type_image_id'] ) ? absint( wp_unslash( $_POST['type_image_id'] ) ) : 0;

		if ( empty( $name ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Category name cannot be empty.', 'awesome-hotel-booking' ) . '</p></div>';
		} elseif ( $item_id > 0 ) {
			// Update Existing Term.
			$update_result = wp_update_term(
				$item_id,
				$taxonomy,
				array(
					'name' => $name,
				)
			);

			if ( ! is_wp_error( $update_result ) ) {
				update_term_meta( $item_id, 'ahbn_type_image', $img_id );
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Category updated successfully.', 'awesome-hotel-booking' ) . '</p></div>';
				$term = get_term( $item_id, $taxonomy );
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $update_result->get_error_message() ) . '</p></div>';
			}
		} else {
			// Create New Term.
			$new_term = wp_insert_term( $name, $taxonomy );

			if ( ! is_wp_error( $new_term ) && isset( $new_term['term_id'] ) ) {
				$item_id = absint( $new_term['term_id'] );
				update_term_meta( $item_id, 'ahbn_type_image', $img_id );

				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'New category created successfully.', 'awesome-hotel-booking' ) . '</p></div>';
				$term = get_term( $item_id, $taxonomy );
			} else {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $new_term->get_error_message() ) . '</p></div>';
			}
		}
	}

	$cancel_url = admin_url( 'admin.php?page=awesome_hotel_booking&tab=rooms&sub=extras' );
	?>

	<div class="ahbn-type-editor">
		<h1 class="wp-heading-inline">
			<?php
			echo $item_id
				? esc_html__( 'Edit Category / Type', 'awesome-hotel-booking' )
				: esc_html__( 'Add New Category / Type', 'awesome-hotel-booking' );
			?>
		</h1>

		<form method="post" action="" style="margin-top: 20px;">
			<?php wp_nonce_field( 'ahbn_type_nonce' ); ?>
			<div class="ahbn-card" style="background:#fff; border:1px solid #ccd0d4; padding:25px; border-radius:8px; max-width: 800px; box-shadow:0 1px 3px rgba(0,0,0,0.04);">
				
				<div class="ahbn-form-group" style="margin-bottom: 20px;">
					<label for="type_name" style="display:block; font-weight:bold; margin-bottom:8px;">
						<?php esc_html_e( 'Category Name', 'awesome-hotel-booking' ); ?>
					</label>
					<input 
						type="text" 
						id="type_name"
						name="type_name" 
						value="<?php echo ( $term && ! is_wp_error( $term ) ) ? esc_attr( $term->name ) : ''; ?>" 
						class="regular-text widefat" 
						placeholder="<?php esc_attr_e( 'e.g. Deluxe Sea View Suite, Executive Double', 'awesome-hotel-booking' ); ?>" 
						required
						style="height:40px; border-radius:6px;"
					>
					<p class="description" style="margin-top:6px; color:#64748b;">
						<?php esc_html_e( 'This name will appear in booking filters and room search listings.', 'awesome-hotel-booking' ); ?>
					</p>
				</div>

				<div class="ahbn-form-group" style="margin-bottom: 25px;">
					<label style="display:block; font-weight:bold; margin-bottom:8px;">
						<?php esc_html_e( 'Category Cover Image', 'awesome-hotel-booking' ); ?>
					</label>
					<div id="ahbn-image-preview" style="margin-bottom:12px;">
						<?php
						if ( $img_id > 0 ) {
							echo wp_get_attachment_image( $img_id, array( 150, 150 ), false, array( 'style' => 'border-radius:6px; border:1px solid #ddd; object-fit:cover;' ) );
						}
						?>
					</div>
					<input type="hidden" name="type_image_id" id="ahbn-type-image-id" value="<?php echo esc_attr( (string) $img_id ); ?>">
					<button type="button" class="button button-secondary" id="ahbn-upload-btn">
						<?php esc_html_e( 'Select Image', 'awesome-hotel-booking' ); ?>
					</button>
					<button 
						type="button" 
						class="button ahbn-remove-btn <?php echo ( 0 === $img_id ) ? 'hidden' : ''; ?>" 
						style="color:#d63638; border-color:#d63638;"
					>
						<?php esc_html_e( 'Remove', 'awesome-hotel-booking' ); ?>
					</button>
				</div>

				<hr style="border:0; border-top:1px solid #f1f5f9; margin:25px 0;">

				<div class="ahbn-form-actions" style="display:flex; gap:10px; align-items:center;">
					<button type="submit" name="ahbn_save_type" class="button button-primary" style="background:#0A1A38; border:none; height:40px; padding:0 24px; border-radius:6px; font-weight:600;">
						<?php esc_html_e( 'Save Category', 'awesome-hotel-booking' ); ?>
					</button>
					<a href="<?php echo esc_url( $cancel_url ); ?>" class="button" style="height:40px; line-height:38px;">
						<?php esc_html_e( 'Cancel', 'awesome-hotel-booking' ); ?>
					</a>
				</div>
			</div>
		</form>
	</div>

	<script>
	jQuery(document).ready(function($){
		var frame;
		$('#ahbn-upload-btn').on('click', function(e){
			e.preventDefault();
			if (frame) {
				frame.open();
				return;
			}
			frame = wp.media({
				title: '<?php echo esc_js( __( 'Select Category Image', 'awesome-hotel-booking' ) ); ?>',
				button: {
					text: '<?php echo esc_js( __( 'Use this image', 'awesome-hotel-booking' ) ); ?>'
				},
				multiple: false
			});
			frame.on('select', function(){
				var attachment = frame.state().get('selection').first().toJSON();
				$('#ahbn-image-preview').html('<img src="' + encodeURI(attachment.url) + '" style="max-width:150px; height:auto; border-radius:6px; border:1px solid #ddd;">');
				$('#ahbn-type-image-id').val(attachment.id);
				$('.ahbn-remove-btn').removeClass('hidden');
			});
			frame.open();
		});

		$('.ahbn-remove-btn').on('click', function(e){
			e.preventDefault();
			$('#ahbn-image-preview').empty();
			$('#ahbn-type-image-id').val('0');
			$(this).addClass('hidden');
		});
	});
	</script>
	<?php
}