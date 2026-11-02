<?php
/**
 * Room Editor (Add / Edit) View.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Room Add/Edit interface and handle form submissions.
 *
 * @param int $edit_room_id Optional. Room post ID to edit. Defaults to 0.
 * @return void
 */
function ahbn_add_edit_room_tab( $edit_room_id = 0 ) {
	$ahbn_edit_room_id = absint( $edit_room_id );
	$ahbn_room         = $ahbn_edit_room_id ? get_post( $ahbn_edit_room_id ) : null;

	// 1. Fetch Global Settings.
	$ahbn_global_types     = get_option( 'ahbn_global_types', array() );
	$ahbn_global_room_nos  = get_option( 'ahbn_global_nos', array() );
	$ahbn_global_amenities = get_option( 'ahbn_global_amenities', array() );
	$ahbn_global_paid      = get_option( 'ahbn_global_paid_extras', array() );

	// 2. Form Submission Handling.
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_room_action'] ) ) {

		$ahbn_nonce = isset( $_POST['ahbn_room_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_room_nonce'] ) ) : '';

		if ( empty( $ahbn_nonce ) || ! wp_verify_nonce( $ahbn_nonce, 'ahbn_save_room' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security verification failed. Please try again.', 'awesome-hotel-booking' ) . '</p></div>';
		} elseif ( ! current_user_can( 'manage_options' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Unauthorized: You do not have permission to edit rooms.', 'awesome-hotel-booking' ) . '</p></div>';
		} else {
			$ahbn_room_name     = isset( $_POST['ahbn_room_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_room_name'] ) ) : '';
			$ahbn_room_desc     = isset( $_POST['ahbn_room_desc'] ) ? wp_kses_post( wp_unslash( $_POST['ahbn_room_desc'] ) ) : '';
			$ahbn_room_price    = isset( $_POST['ahbn_room_price'] ) ? (float) sanitize_text_field( wp_unslash( $_POST['ahbn_room_price'] ) ) : 0.00;
			$ahbn_room_no       = isset( $_POST['ahbn_room_no'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_room_no'] ) ) : '';
			$ahbn_room_type     = isset( $_POST['ahbn_room_type_select'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_room_type_select'] ) ) : '';
			$ahbn_raw_gallery   = isset( $_POST['ahbn_room_gallery'] ) ? sanitize_text_field( wp_unslash( $_POST['ahbn_room_gallery'] ) ) : '';
			$ahbn_thumb_id      = isset( $_POST['ahbn_room_thumbnail_id'] ) ? absint( wp_unslash( $_POST['ahbn_room_thumbnail_id'] ) ) : 0;
			$ahbn_adults_val    = isset( $_POST['ahbn_max_adults'] ) ? absint( wp_unslash( $_POST['ahbn_max_adults'] ) ) : 2;
			$ahbn_children_val  = isset( $_POST['ahbn_max_children'] ) ? absint( wp_unslash( $_POST['ahbn_max_children'] ) ) : 0;

			// Sanitize comma-separated attachment IDs.
			$gallery_id_arr  = array_filter( array_map( 'absint', explode( ',', $ahbn_raw_gallery ) ) );
			$ahbn_room_gal   = implode( ',', $gallery_id_arr );

			if ( empty( $ahbn_room_name ) ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Room title is required.', 'awesome-hotel-booking' ) . '</p></div>';
			} else {
				$ahbn_post_args = array(
					'post_type'    => 'ahbn_room',
					'post_title'   => $ahbn_room_name,
					'post_content' => $ahbn_room_desc,
					'post_status'  => 'publish',
				);

				if ( $ahbn_edit_room_id > 0 ) {
					$ahbn_post_args['ID'] = $ahbn_edit_room_id;
					$ahbn_saved_id        = wp_update_post( $ahbn_post_args );
				} else {
					$ahbn_saved_id = wp_insert_post( $ahbn_post_args );
				}

				if ( $ahbn_saved_id && ! is_wp_error( $ahbn_saved_id ) ) {
					update_post_meta( $ahbn_saved_id, 'ahbn_room_price', $ahbn_room_price );
					update_post_meta( $ahbn_saved_id, 'ahbn_room_no', $ahbn_room_no );
					update_post_meta( $ahbn_saved_id, 'ahbn_room_type_select', $ahbn_room_type );
					update_post_meta( $ahbn_saved_id, 'ahbn_room_gallery', $ahbn_room_gal );
					update_post_meta( $ahbn_saved_id, 'ahbn_max_adults', $ahbn_adults_val );
					update_post_meta( $ahbn_saved_id, 'ahbn_max_children', $ahbn_children_val );

					if ( $ahbn_thumb_id > 0 ) {
						set_post_thumbnail( $ahbn_saved_id, $ahbn_thumb_id );
					} else {
						delete_post_thumbnail( $ahbn_saved_id );
					}

					// Amenities and Paid Extras.
					$ahbn_amenities_post = isset( $_POST['room_amenities'] ) && is_array( $_POST['room_amenities'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['room_amenities'] ) ) : array();
					$ahbn_paid_post      = isset( $_POST['room_paid_extras'] ) && is_array( $_POST['room_paid_extras'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['room_paid_extras'] ) ) : array();

					update_post_meta( $ahbn_saved_id, 'ahbn_room_amenities', $ahbn_amenities_post );
					update_post_meta( $ahbn_saved_id, 'ahbn_room_paid_extras', $ahbn_paid_post );

					printf(
						'<div class="notice notice-success is-dismissible"><p><strong>%s</strong> %s</p></div>',
						esc_html__( 'Success:', 'awesome-hotel-booking' ),
						/* translators: %s: The name of the room. */
						sprintf( esc_html__( 'Room "%s" saved successfully.', 'awesome-hotel-booking' ), esc_html( $ahbn_room_name ) )
					);

					$ahbn_edit_room_id = $ahbn_saved_id;
					$ahbn_room         = get_post( $ahbn_edit_room_id );
				}
			}
		}
	}

	// 3. Prepare Form Values.
	$ahbn_price       = $ahbn_edit_room_id ? get_post_meta( $ahbn_edit_room_id, 'ahbn_room_price', true ) : '';
	$ahbn_room_no     = $ahbn_edit_room_id ? get_post_meta( $ahbn_edit_room_id, 'ahbn_room_no', true ) : '';
	$ahbn_room_type   = $ahbn_edit_room_id ? get_post_meta( $ahbn_edit_room_id, 'ahbn_room_type_select', true ) : '';
	$ahbn_s_amenities = $ahbn_edit_room_id ? (array) get_post_meta( $ahbn_edit_room_id, 'ahbn_room_amenities', true ) : array();
	$ahbn_s_paid      = $ahbn_edit_room_id ? (array) get_post_meta( $ahbn_edit_room_id, 'ahbn_room_paid_extras', true ) : array();
	$ahbn_gallery_ids = $ahbn_edit_room_id ? (string) get_post_meta( $ahbn_edit_room_id, 'ahbn_room_gallery', true ) : '';
	$ahbn_thumb_id    = $ahbn_edit_room_id ? get_post_thumbnail_id( $ahbn_edit_room_id ) : 0;
	$ahbn_m_adults    = $ahbn_edit_room_id ? get_post_meta( $ahbn_edit_room_id, 'ahbn_max_adults', true ) : 2;
	$ahbn_m_children  = $ahbn_edit_room_id ? get_post_meta( $ahbn_edit_room_id, 'ahbn_max_children', true ) : 0;
	?>

	<div class="ahbn-editor-wrap">
		<form method="post" id="ahbn-room-form">
			<?php wp_nonce_field( 'ahbn_save_room', 'ahbn_room_nonce' ); ?>
			<input type="hidden" name="ahbn_save_room_action" value="1">

			<input 
				type="text" 
				name="ahbn_room_name" 
				class="ahbn-title-field" 
				value="<?php echo esc_attr( $ahbn_room ? $ahbn_room->post_title : '' ); ?>" 
				placeholder="<?php esc_attr_e( 'Enter Room Name (e.g. Deluxe Sea View Suite)', 'awesome-hotel-booking' ); ?>" 
				required
			>

			<div class="ahbn-grid">
				<div class="ahbn-left">
					<div class="ahbn-panel">
						<div class="ahbn-panel-header">
							<span class="dashicons dashicons-text" aria-hidden="true"></span> 
							<?php esc_html_e( 'Description', 'awesome-hotel-booking' ); ?>
						</div>
						<div class="ahbn-panel-body">
							<?php
							wp_editor(
								$ahbn_room ? $ahbn_room->post_content : '',
								'ahbn_room_desc',
								array(
									'textarea_rows' => 10,
									'media_buttons' => false,
								)
							);
							?>
						</div>
					</div>

					<div class="ahbn-panel">
						<div class="ahbn-panel-header">
							<span class="dashicons dashicons-star-filled" aria-hidden="true"></span> 
							<?php esc_html_e( 'Features & Amenities', 'awesome-hotel-booking' ); ?>
						</div>
						<div class="ahbn-panel-body">
							<h4 style="margin-top:0;"><?php esc_html_e( 'Standard Amenities', 'awesome-hotel-booking' ); ?></h4>
							<?php if ( ! empty( $ahbn_global_amenities ) && is_array( $ahbn_global_amenities ) ) : ?>
								<?php foreach ( $ahbn_global_amenities as $ahbn_a ) : ?>
									<?php $amenity_name = isset( $ahbn_a['name'] ) ? $ahbn_a['name'] : ''; ?>
									<?php if ( ! empty( $amenity_name ) ) : ?>
										<div class="ahbn-toggle-item">
											<span><?php echo esc_html( $amenity_name ); ?></span>
											<label class="ahbn-switch">
												<input type="checkbox" name="room_amenities[]" value="<?php echo esc_attr( $amenity_name ); ?>" <?php checked( in_array( $amenity_name, $ahbn_s_amenities, true ) ); ?>>
												<span class="ahbn-slider"></span>
											</label>
										</div>
									<?php endif; ?>
								<?php endforeach; ?>
							<?php else : ?>
								<p style="color:#9ca3af;"><?php esc_html_e( 'No global amenities defined in settings.', 'awesome-hotel-booking' ); ?></p>
							<?php endif; ?>

							<h4 style="margin-top:20px;"><?php esc_html_e( 'Paid Extras', 'awesome-hotel-booking' ); ?></h4>
							<?php if ( ! empty( $ahbn_global_paid ) && is_array( $ahbn_global_paid ) ) : ?>
								<?php foreach ( $ahbn_global_paid as $ahbn_p ) : ?>
									<?php 
									$extra_name  = isset( $ahbn_p['name'] ) ? $ahbn_p['name'] : '';
									$extra_price = isset( $ahbn_p['price'] ) ? $ahbn_p['price'] : '';
									?>
									<?php if ( ! empty( $extra_name ) ) : ?>
										<div class="ahbn-toggle-item">
											<span>
												<?php echo esc_html( $extra_name ); ?> 
												<?php if ( '' !== $extra_price ) : ?>
													(+<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $extra_price ) : $extra_price ); ?>)
												<?php endif; ?>
											</span>
											<label class="ahbn-switch">
												<input type="checkbox" name="room_paid_extras[]" value="<?php echo esc_attr( $extra_name ); ?>" <?php checked( in_array( $extra_name, $ahbn_s_paid, true ) ); ?>>
												<span class="ahbn-slider"></span>
											</label>
										</div>
									<?php endif; ?>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<div class="ahbn-right">
					<div class="ahbn-panel">
						<div class="ahbn-panel-header"><?php esc_html_e( 'Room Details', 'awesome-hotel-booking' ); ?></div>
						<div class="ahbn-panel-body">
							<div class="ahbn-meta-row">
								<label for="ahbn_room_price"><?php esc_html_e( 'Nightly Price', 'awesome-hotel-booking' ); ?></label>
								<input type="number" step="0.01" min="0" id="ahbn_room_price" name="ahbn_room_price" class="ahbn-input-full" value="<?php echo esc_attr( $ahbn_price ); ?>">
							</div>
							<div class="ahbn-meta-row">
								<label for="ahbn_room_type_select"><?php esc_html_e( 'Room Type', 'awesome-hotel-booking' ); ?></label>
								<select id="ahbn_room_type_select" name="ahbn_room_type_select" class="ahbn-input-full">
									<option value=""><?php esc_html_e( 'Select Type', 'awesome-hotel-booking' ); ?></option>
									<?php if ( ! empty( $ahbn_global_types ) && is_array( $ahbn_global_types ) ) : ?>
										<?php foreach ( $ahbn_global_types as $ahbn_t ) : ?>
											<option value="<?php echo esc_attr( $ahbn_t ); ?>" <?php selected( $ahbn_room_type, $ahbn_t ); ?>><?php echo esc_html( $ahbn_t ); ?></option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
							</div>
							<div class="ahbn-meta-row">
								<label for="ahbn_room_no"><?php esc_html_e( 'Room Number', 'awesome-hotel-booking' ); ?></label>
								<select id="ahbn_room_no" name="ahbn_room_no" class="ahbn-input-full">
									<option value=""><?php esc_html_e( 'Assign Number', 'awesome-hotel-booking' ); ?></option>
									<?php if ( ! empty( $ahbn_global_room_nos ) && is_array( $ahbn_global_room_nos ) ) : ?>
										<?php foreach ( $ahbn_global_room_nos as $ahbn_n ) : ?>
											<option value="<?php echo esc_attr( $ahbn_n ); ?>" <?php selected( $ahbn_room_no, $ahbn_n ); ?>><?php echo esc_html( $ahbn_n ); ?></option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
							</div>
							<div style="display:flex; gap:10px;">
								<div class="ahbn-meta-row" style="flex:1;">
									<label for="ahbn_max_adults"><?php esc_html_e( 'Max Adults', 'awesome-hotel-booking' ); ?></label>
									<input type="number" min="1" id="ahbn_max_adults" name="ahbn_max_adults" class="ahbn-input-full" value="<?php echo esc_attr( $ahbn_m_adults ); ?>">
								</div>
								<div class="ahbn-meta-row" style="flex:1;">
									<label for="ahbn_max_children"><?php esc_html_e( 'Max Children', 'awesome-hotel-booking' ); ?></label>
									<input type="number" min="0" id="ahbn_max_children" name="ahbn_max_children" class="ahbn-input-full" value="<?php echo esc_attr( $ahbn_m_children ); ?>">
								</div>
							</div>
						</div>
					</div>

					<div class="ahbn-panel">
						<div class="ahbn-panel-header"><?php esc_html_e( 'Featured Image', 'awesome-hotel-booking' ); ?></div>
						<div class="ahbn-panel-body">
							<div class="ahbn-feat-img-box" id="ahbn_set_thumb">
								<?php if ( $ahbn_thumb_id ) : ?>
									<?php echo wp_get_attachment_image( $ahbn_thumb_id, 'medium' ); ?>
								<?php else : ?>
									<span><?php esc_html_e( 'Click to set image', 'awesome-hotel-booking' ); ?></span>
								<?php endif; ?>
							</div>
							<input type="hidden" name="ahbn_room_thumbnail_id" id="ahbn_thumb_id" value="<?php echo esc_attr( (string) $ahbn_thumb_id ); ?>">
						</div>
					</div>

					<div class="ahbn-panel">
						<div class="ahbn-panel-header"><?php esc_html_e( 'Room Gallery', 'awesome-hotel-booking' ); ?></div>
						<div class="ahbn-panel-body">
							<button type="button" class="button widefat" id="ahbn_add_gal"><?php esc_html_e( 'Add Gallery Images', 'awesome-hotel-booking' ); ?></button>
							<input type="hidden" name="ahbn_room_gallery" id="ahbn_gal_val" value="<?php echo esc_attr( $ahbn_gallery_ids ); ?>">
							<div class="ahbn-gal-grid" id="ahbn_gal_preview">
								<?php 
								if ( ! empty( $ahbn_gallery_ids ) ) {
									$g_ids = array_filter( array_map( 'absint', explode( ',', $ahbn_gallery_ids ) ) );
									foreach ( $g_ids as $ahbn_gid ) {
										$ahbn_url = wp_get_attachment_thumb_url( $ahbn_gid );
										if ( $ahbn_url ) {
											printf(
												'<div class="ahbn-gal-img" data-id="%1$d"><span class="ahbn-gal-remove" title="%2$s">&times;</span><img src="%3$s" alt=""></div>',
												absint( $ahbn_gid ),
												esc_attr__( 'Remove Image', 'awesome-hotel-booking' ),
												esc_url( $ahbn_url )
											);
										}
									}
								}
								?>
							</div>
						</div>
					</div>

					<button type="submit" class="button button-primary ahbn-submit-btn"><?php esc_html_e( 'Save Room Configuration', 'awesome-hotel-booking' ); ?></button>
				</div>
			</div>
		</form>
	</div>

	<script>
	jQuery(document).ready(function($){
		// Thumbnail Upload via WP Media API.
		$('#ahbn_set_thumb').on('click', function(e){
			e.preventDefault();
			var frame = wp.media({
				title: '<?php echo esc_js( __( 'Select Room Image', 'awesome-hotel-booking' ) ); ?>',
				multiple: false
			}).on('select', function(){
				var attachment = frame.state().get('selection').first().toJSON();
				$('#ahbn_thumb_id').val(attachment.id);
				$('#ahbn_set_thumb').html('<img src="' + encodeURI(attachment.url) + '" alt="">');
			}).open();
		});

		// Gallery Upload.
		$('#ahbn_add_gal').on('click', function(e){
			e.preventDefault();
			var frame = wp.media({
				title: '<?php echo esc_js( __( 'Select Gallery Images', 'awesome-hotel-booking' ) ); ?>',
				multiple: true
			}).on('select', function(){
				var selection = frame.state().get('selection');
				var rawVal = $('#ahbn_gal_val').val();
				var ids = rawVal ? rawVal.split(',') : [];

				selection.each(function(model){
					var att = model.toJSON();
					if ($.inArray(att.id.toString(), ids) === -1) {
						ids.push(att.id);
						$('#ahbn_gal_preview').append(
							$('<div/>', { class: 'ahbn-gal-img', 'data-id': att.id })
								.append($('<span/>', { class: 'ahbn-gal-remove', text: '×' }))
								.append($('<img/>', { src: att.url }))
						);
					}
				});
				$('#ahbn_gal_val').val(ids.join(','));
			}).open();
		});

		// Remove Gallery Image.
		$(document).on('click', '.ahbn-gal-remove', function(){
			var $parent = $(this).parent();
			var id = $parent.data('id').toString();
			var ids = $('#ahbn_gal_val').val().split(',').filter(function(val){
				return val !== id && val !== '';
			});
			$('#ahbn_gal_val').val(ids.join(','));
			$parent.remove();
		});
	});
	</script>
	<?php
}