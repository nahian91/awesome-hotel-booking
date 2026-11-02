<?php
/**
 * Rooms Inventory List View.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Flush rooms list cache when a room is modified.
 *
 * @param int $post_id Post ID.
 * @return void
 */
function ahbn_clear_rooms_cache( $post_id ) {
	if ( 'ahbn_room' === get_post_type( $post_id ) ) {
		delete_transient( 'ahbn_all_rooms_list' );
	}
}
add_action( 'save_post_ahbn_room', 'ahbn_clear_rooms_cache' );
add_action( 'deleted_post', 'ahbn_clear_rooms_cache' );

/**
 * Render Room Inventory List.
 *
 * @return void
 */
function ahbn_rooms_list() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	$page_slug        = 'awesome_hotel_booking';
	$rooms_cache_key  = 'ahbn_all_rooms_list';
	$ahbn_rooms       = get_transient( $rooms_cache_key );

	if ( false === $ahbn_rooms ) {
		$args = array(
			'post_type'              => 'ahbn_room',
			'posts_per_page'         => -1,
			'post_status'            => array( 'publish', 'draft', 'pending', 'private' ),
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'orderby'                => 'date',
			'order'                  => 'DESC',
		);

		$rooms_query = new WP_Query( $args );
		$ahbn_rooms  = $rooms_query->posts;

		if ( ! empty( $ahbn_rooms ) ) {
			set_transient( $rooms_cache_key, $ahbn_rooms, HOUR_IN_SECONDS );
		}
	}

	// Batch-fetch total booking history for all rooms in a single query to eliminate N+1 queries.
	global $wpdb;
	$history_counts = array();
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
	$counts_results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT meta_value AS room_id, COUNT(post_id) AS total_count 
			 FROM {$wpdb->postmeta} 
			 WHERE meta_key = %s 
			 GROUP BY meta_value",
			'ahbn_book_room_id'
		)
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery

	if ( ! empty( $counts_results ) ) {
		foreach ( $counts_results as $count_row ) {
			$history_counts[ (int) $count_row->room_id ] = (int) $count_row->total_count;
		}
	}
	?>

	<div class="ahbn-table-container">
		<table class="ahbn-main-table">
			<thead>
				<tr>
					<th style="width: 80px;"><?php esc_html_e( 'Preview', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Room & Capacity', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Availability', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Type', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Rate', 'awesome-hotel-booking' ); ?></th>
					<th style="text-align:right;"><?php esc_html_e( 'Management', 'awesome-hotel-booking' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( ! empty( $ahbn_rooms ) ) :
					foreach ( $ahbn_rooms as $room ) :
						$rid           = $room->ID;
						$rno           = (string) get_post_meta( $rid, 'ahbn_room_no', true );
						$raw_price     = get_post_meta( $rid, 'ahbn_room_price', true );
						$price         = (float) preg_replace( '/[^\d.]/', '', (string) $raw_price );
						$type          = (string) get_post_meta( $rid, 'ahbn_room_type_select', true );
						$adults        = get_post_meta( $rid, 'ahbn_max_adults', true );
						$children      = get_post_meta( $rid, 'ahbn_max_children', true );
						$adults_num    = ( '' !== $adults ) ? absint( $adults ) : 2;
						$children_num  = ( '' !== $children ) ? absint( $children ) : 0;
						$total_history = isset( $history_counts[ $rid ] ) ? $history_counts[ $rid ] : 0;

						$base_url = admin_url( 'admin.php?page=' . rawurlencode( $page_slug ) . '&tab=rooms' );
						$view_url = add_query_arg(
							array(
								'sub'     => 'view',
								'room_id' => $rid,
							),
							$base_url
						);
						$edit_url = add_query_arg(
							array(
								'sub'     => 'edit',
								'room_id' => $rid,
							),
							$base_url
						);
						$del_url  = wp_nonce_url(
							add_query_arg(
								array(
									'action'  => 'delete',
									'room_id' => $rid,
								),
								$base_url
							),
							'ahbn_room_action'
						);
						?>
						<tr>
							<td>
								<?php if ( has_post_thumbnail( $rid ) ) : ?>
									<?php echo get_the_post_thumbnail( $rid, array( 60, 45 ), array( 'style' => 'border-radius:8px; object-fit:cover; display:block;' ) ); ?>
								<?php else : ?>
									<div style="background:#eee; width:60px; height:45px; border-radius:8px; display:flex; align-items:center; justify-content:center;">
										<span class="dashicons dashicons-format-image" style="color:#ccc;" aria-hidden="true"></span>
									</div>
								<?php endif; ?>
							</td>
							<td>
								<div style="font-weight:700; color:var(--navy);"><?php echo esc_html( $room->post_title ); ?></div>
								<div style="font-size:11px; color:#6b7280;">
									<?php
									printf(
										/* translators: %s: Room number */
										esc_html__( 'No. %s', 'awesome-hotel-booking' ),
										esc_html( ! empty( $rno ) ? $rno : __( 'N/A', 'awesome-hotel-booking' ) )
									);
									?>
								</div>
								<div class="capacity-pill">
									<?php
									printf(
										/* translators: 1: Adult count, 2: Child count */
										esc_html__( '%1$d Adults, %2$d Children', 'awesome-hotel-booking' ),
										$adults_num,
										$children_num
									);
									?>
								</div>
							</td>
							<td>
								<?php
								$is_ready     = ( 'publish' === $room->post_status );
								$status_class = $is_ready ? 'stat-avail' : 'stat-busy';
								$status_text  = $is_ready ? __( 'READY', 'awesome-hotel-booking' ) : strtoupper( $room->post_status );
								?>
								<span class="stat-indicator <?php echo esc_attr( $status_class ); ?>"></span>
								<span style="font-size:11px; font-weight:800;"><?php echo esc_html( $status_text ); ?></span>
								<div class="booking-count">
									<?php
									printf(
										/* translators: %d: Total number of bookings in history */
										esc_html( _n( '%d Booking', '%d Bookings', $total_history, 'awesome-hotel-booking' ) ),
										absint( $total_history )
									);
									?>
								</div>
							</td>
							<td>
								<span class="badge-cat"><?php echo esc_html( ! empty( $type ) ? $type : __( 'Standard', 'awesome-hotel-booking' ) ); ?></span>
							</td>
							<td>
								<div style="font-weight:800; color:var(--navy);">
									<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $price ) : (string) number_format_i18n( $price, 2 ) ); ?>
								</div>
							</td>
							<td>
								<div class="ahbn-actions">
									<a class="btn-action btn-view" href="<?php echo esc_url( $view_url ); ?>" title="<?php esc_attr_e( 'View Room', 'awesome-hotel-booking' ); ?>">
										<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
									</a>
									<a class="btn-action btn-edit" href="<?php echo esc_url( $edit_url ); ?>" title="<?php esc_attr_e( 'Edit Room', 'awesome-hotel-booking' ); ?>">
										<span class="dashicons dashicons-edit" aria-hidden="true"></span>
									</a>
									<a 
										class="btn-action btn-del" 
										href="<?php echo esc_url( $del_url ); ?>" 
										title="<?php esc_attr_e( 'Trash Room', 'awesome-hotel-booking' ); ?>"
										onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this room?', 'awesome-hotel-booking' ) ); ?>');"
									>
										<span class="dashicons dashicons-trash" aria-hidden="true"></span>
									</a>
								</div>
							</td>
						</tr>
						<?php
					endforeach;
				else :
					?>
					<tr>
						<td colspan="6" style="text-align:center; padding:80px; color:#9ca3af; font-style:italic;">
							<?php esc_html_e( 'No rooms found in the system.', 'awesome-hotel-booking' ); ?>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}