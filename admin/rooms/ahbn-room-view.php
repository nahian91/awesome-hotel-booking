<?php
/**
 * Room Single View.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render single room details view.
 *
 * @param int $room_id Room post ID.
 * @return void
 */
function ahbn_view_room_tab( $room_id ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	$room_id = absint( $room_id );
	$room    = get_post( $room_id );

	if ( ! $room || 'ahbn_room' !== $room->post_type ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Room not found.', 'awesome-hotel-booking' ) . '</p></div>';
		return;
	}

	// 1. Data Retrieval.
	$page_slug   = 'awesome_hotel_booking';
	$price       = get_post_meta( $room_id, 'ahbn_room_price', true );
	$room_no     = (string) get_post_meta( $room_id, 'ahbn_room_no', true );
	$room_type   = (string) get_post_meta( $room_id, 'ahbn_room_type_select', true );
	$amenities   = (array) get_post_meta( $room_id, 'ahbn_room_amenities', true );
	$paid_extras = (array) get_post_meta( $room_id, 'ahbn_room_paid_extras', true );
	$gallery     = (string) get_post_meta( $room_id, 'ahbn_room_gallery', true );

	// Capacity Data.
	$max_adults   = get_post_meta( $room_id, 'ahbn_max_adults', true );
	$max_children = get_post_meta( $room_id, 'ahbn_max_children', true );
	$adults_val   = '' !== $max_adults ? absint( $max_adults ) : 2;
	$children_val = '' !== $max_children ? absint( $max_children ) : 0;

	// 2. Booking Count Cache Handling.
	$cache_key = 'ahbn_booking_count_' . $room_id;

	if ( isset( $_GET['refresh'] ) ) {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( wp_verify_nonce( $nonce, 'ahbn_refresh_cache' ) ) {
			delete_transient( $cache_key );
		}
	}

	$ahbn_total_books = get_transient( $cache_key );

	if ( false === $ahbn_total_books ) {
		$booking_query = new WP_Query(
			array(
				'post_type'      => 'ahbn_booking',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => false,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'ahbn_book_room_id',
						'value'   => $room_id,
						'compare' => '=',
					),
				),
			)
		);

		$ahbn_total_books = (int) $booking_query->found_posts;
		set_transient( $cache_key, $ahbn_total_books, 12 * HOUR_IN_SECONDS );
	}

	$base_url = admin_url( 'admin.php?page=' . rawurlencode( $page_slug ) . '&tab=rooms' );
	$edit_url = add_query_arg(
		array(
			'sub'     => 'edit',
			'room_id' => $room_id,
		),
		$base_url
	);

	$clean_price = (float) preg_replace( '/[^\d.]/', '', (string) $price );
	?>

	<div class="ahbn-view-wrapper">
		<div class="ahbn-view-header">
			<div>
				<a href="<?php echo esc_url( $base_url ); ?>" style="text-decoration:none; color:#6b7280; font-size:13px; font-weight:600; display:inline-flex; align-items:center; gap:5px; margin-bottom:10px;">
					<span class="dashicons dashicons-arrow-left-alt" aria-hidden="true"></span>
					<?php esc_html_e( 'Back to Inventory', 'awesome-hotel-booking' ); ?>
				</a>
				<h1 style="margin:0; font-size:28px; font-weight:800; color:var(--ahbn-navy);">
					<?php echo esc_html( $room->post_title ); ?>
				</h1>
				<div style="margin-top:10px; display:flex; align-items:center; gap:12px;">
					<span class="ahbn-type-badge">
						<?php echo esc_html( ! empty( $room_type ) ? $room_type : __( 'Standard Room', 'awesome-hotel-booking' ) ); ?>
					</span>
					<span style="color:#4b5563; font-size:14px; font-weight:600;">
						<?php
						printf(
							/* translators: %s: The room number */
							esc_html__( 'Room No. %s', 'awesome-hotel-booking' ),
							esc_html( ! empty( $room_no ) ? $room_no : __( 'N/A', 'awesome-hotel-booking' ) )
						);
						?>
					</span>
				</div>
			</div>

			<a href="<?php echo esc_url( $edit_url ); ?>" class="btn-edit-room">
				<span class="dashicons dashicons-edit" aria-hidden="true"></span>
				<?php esc_html_e( 'Edit Room', 'awesome-hotel-booking' ); ?>
			</a>
		</div>

		<div class="ahbn-view-grid">
			<div class="ahbn-sidebar">
				<div class="ahbn-card">
					<div class="ahbn-card-body">
						<?php if ( has_post_thumbnail( $room_id ) ) : ?>
							<?php echo get_the_post_thumbnail( $room_id, 'large', array( 'class' => 'ahbn-main-img' ) ); ?>
						<?php else : ?>
							<div style="width:100%; aspect-ratio:16/10; background:#f3f4f6; border-radius:8px; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#9ca3af; border:1px dashed #ccc;">
								<span class="dashicons dashicons-format-image" style="font-size:48px;" aria-hidden="true"></span>
								<p style="font-size:10px; font-weight:700; margin:5px 0 0;"><?php esc_html_e( 'NO IMAGE', 'awesome-hotel-booking' ); ?></p>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $gallery ) ) : ?>
							<div class="ahbn-gallery-grid">
								<?php
								$gallery_ids = array_filter( array_map( 'absint', explode( ',', $gallery ) ) );
								foreach ( array_slice( $gallery_ids, 0, 8 ) as $img_id ) {
									echo wp_kses_post( wp_get_attachment_image( $img_id, 'thumbnail' ) );
								}
								?>
							</div>
						<?php endif; ?>

						<div style="margin-top:25px; padding-top:20px; border-top:1px solid #f3f4f6; text-align:center;">
							<span class="ahbn-price-tag">
								<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $clean_price ) : (string) number_format_i18n( $clean_price, 2 ) ); ?>
							</span>
							<span style="color:#9ca3af; font-size:12px; font-weight:700; text-transform:uppercase;">
								<?php esc_html_e( 'Base Rate / Night', 'awesome-hotel-booking' ); ?>
							</span>
						</div>
					</div>
				</div>

				<div class="ahbn-card" style="display:flex; background:var(--ahbn-bg);">
					<div class="ahbn-stat-item">
						<span class="ahbn-stat-label"><?php esc_html_e( 'Total Stays', 'awesome-hotel-booking' ); ?></span>
						<span class="ahbn-stat-val"><?php echo esc_html( (string) absint( $ahbn_total_books ) ); ?></span>
					</div>
					<div class="ahbn-stat-item" style="border-right:none;">
						<span class="ahbn-stat-label"><?php esc_html_e( 'Max Capacity', 'awesome-hotel-booking' ); ?></span>
						<span class="ahbn-stat-val"><?php echo esc_html( (string) ( $adults_val + $children_val ) ); ?></span>
					</div>
				</div>
			</div>

			<div class="ahbn-main-content">
				<div class="ahbn-card">
					<div class="ahbn-card-head">
						<span class="dashicons dashicons-groups" aria-hidden="true"></span>
						<?php esc_html_e( 'Room Capacity', 'awesome-hotel-booking' ); ?>
					</div>
					<div class="ahbn-card-body">
						<span class="ahbn-pill gold">
							<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
							<?php
							printf(
								/* translators: %d: Maximum number of adults allowed */
								esc_html( _n( '%d Adult', '%d Adults', $adults_val, 'awesome-hotel-booking' ) ),
								absint( $adults_val )
							);
							?>
						</span>
						<span class="ahbn-pill gold">
							<span class="dashicons dashicons-welcome-learn-more" aria-hidden="true"></span>
							<?php
							printf(
								/* translators: %d: Maximum number of children allowed */
								esc_html( _n( '%d Child', '%d Children', $children_val, 'awesome-hotel-booking' ) ),
								absint( $children_val )
							);
							?>
						</span>
					</div>
				</div>

				<div class="ahbn-card">
					<div class="ahbn-card-head">
						<span class="dashicons dashicons-text-align-left" aria-hidden="true"></span>
						<?php esc_html_e( 'Room Details', 'awesome-hotel-booking' ); ?>
					</div>
					<div class="ahbn-card-body">
						<div style="line-height:1.7; color:var(--ahbn-text);">
							<?php
							if ( ! empty( $room->post_content ) ) {
								echo wp_kses_post( wpautop( $room->post_content ) );
							} else {
								echo '<i>' . esc_html__( 'No description available.', 'awesome-hotel-booking' ) . '</i>';
							}
							?>
						</div>
					</div>
				</div>

				<div class="ahbn-card">
					<div class="ahbn-card-head">
						<span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
						<?php esc_html_e( 'Standard Amenities', 'awesome-hotel-booking' ); ?>
					</div>
					<div class="ahbn-card-body" style="background:var(--ahbn-bg);">
						<?php if ( ! empty( $amenities ) ) : ?>
							<?php foreach ( $amenities as $item ) : ?>
								<?php if ( is_string( $item ) && '' !== trim( $item ) ) : ?>
									<span class="ahbn-pill green">
										<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
										<?php echo esc_html( $item ); ?>
									</span>
								<?php endif; ?>
							<?php endforeach; ?>
						<?php else : ?>
							<p style="color:#9ca3af; margin:0;"><?php esc_html_e( 'No standard amenities assigned.', 'awesome-hotel-booking' ); ?></p>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( ! empty( $paid_extras ) ) : ?>
					<div class="ahbn-card">
						<div class="ahbn-card-head">
							<span class="dashicons dashicons-cart" aria-hidden="true"></span>
							<?php esc_html_e( 'Available Paid Add-ons', 'awesome-hotel-booking' ); ?>
						</div>
						<div class="ahbn-card-body">
							<?php foreach ( $paid_extras as $extra ) : ?>
								<?php if ( is_string( $extra ) && '' !== trim( $extra ) ) : ?>
									<span class="ahbn-pill" style="border-color:var(--ahbn-gold); color:var(--ahbn-navy); font-weight:700;">
										<span class="dashicons dashicons-plus" style="color:var(--ahbn-gold);" aria-hidden="true"></span>
										<?php echo esc_html( $extra ); ?>
									</span>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}