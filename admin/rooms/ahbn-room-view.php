<?php
/**
 * Room Single View.
 *
 * Rewritten for custom SQL database tables:
 * {$wpdb->prefix}ahbn_rooms and {$wpdb->prefix}ahbn_bookings.
 * Styled with the Champagne Gold & Deep Navy enterprise UI system.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render single room details view.
 *
 * @param int $room_id Room database ID.
 * @return void
 */
function ahbn_view_room_tab( $room_id ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	global $wpdb;
	$table_rooms    = $wpdb->prefix . 'ahbn_rooms';
	$table_bookings = $wpdb->prefix . 'ahbn_bookings';

	$room_id = absint( $room_id );

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$room = $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$table_rooms} WHERE id = %d", $room_id )
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	if ( ! $room ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Room record not found.', 'awesome-hotel-booking' ) . '</p></div>';
		return;
	}

	// 1. Data Retrieval from Custom Table.
	$page_slug   = 'awesome_hotel_booking';
	$price       = (float) $room->price_per_night;
	$room_no     = (string) $room->room_number;
	$room_type   = (string) $room->room_type;
	$gallery     = (string) $room->gallery;
	$adults_val  = absint( $room->max_adults );
	$children_val= absint( $room->max_children );
	$thumb_id    = absint( $room->thumbnail_id );
	$status      = ! empty( $room->status ) ? $room->status : 'publish';

	$decoded_amenities = json_decode( (string) $room->amenities, true );
	$amenities         = is_array( $decoded_amenities ) ? $decoded_amenities : array();

	$decoded_paid = json_decode( (string) $room->paid_extras, true );
	$paid_extras  = is_array( $decoded_paid ) ? $decoded_paid : array();

	// 2. Query Realized Stays Count from Bookings Table.
	$cache_key = 'ahbn_booking_count_' . $room_id;

	if ( isset( $_GET['refresh'] ) ) {
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( wp_verify_nonce( $nonce, 'ahbn_refresh_cache' ) ) {
			delete_transient( $cache_key );
		}
	}

	$ahbn_total_books = get_transient( $cache_key );

	if ( false === $ahbn_total_books ) {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ahbn_total_books = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_bookings} WHERE room_id = %d",
				$room_id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

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

	$thumb_url    = $thumb_id > 0 ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';
	$is_ready     = ( 'publish' === $status );
	$status_class = $is_ready ? 'status-active-now' : 'status-disabled';
	$status_label = $is_ready ? __( 'Published & Active', 'awesome-hotel-booking' ) : __( 'Draft / Maintenance', 'awesome-hotel-booking' );
	?>

	<div class="ahbn-view-wrapper">
		<!-- Header Actions Bar -->
		<div class="ahbn-view-header">
			<div>
				<a href="<?php echo esc_url( $base_url ); ?>" class="ahbn-back-link">
					<span class="dashicons dashicons-arrow-left-alt" aria-hidden="true"></span>
					<?php esc_html_e( 'Back to Room Inventory', 'awesome-hotel-booking' ); ?>
				</a>
				<h1 class="ahbn-suite-headline">
					<?php echo esc_html( $room->title ); ?>
				</h1>
				<div class="ahbn-headline-meta">
					<span class="ahbn-room-num-badge">
						#<?php echo esc_html( ! empty( $room_no ) ? $room_no : $room_id ); ?>
					</span>
					<span class="ahbn-type-badge">
						<?php echo esc_html( ! empty( $room_type ) ? $room_type : __( 'Standard Room', 'awesome-hotel-booking' ) ); ?>
					</span>
					<span class="ahbn-status-pill <?php echo esc_attr( $status_class ); ?>">
						<?php echo esc_html( $status_label ); ?>
					</span>
				</div>
			</div>

			<div>
				<a href="<?php echo esc_url( $edit_url ); ?>" class="ahbn-btn-primary">
					<span class="dashicons dashicons-edit" aria-hidden="true"></span>
					<?php esc_html_e( 'Edit Suite Details', 'awesome-hotel-booking' ); ?>
				</a>
			</div>
		</div>

		<div class="ahbn-view-grid">
			<!-- Left Column: Primary Visual Media & Financial Metrics -->
			<div class="ahbn-sidebar-media">
				<div class="ahbn-panel">
					<div class="ahbn-panel-body" style="padding: 16px;">
						<?php if ( ! empty( $thumb_url ) ) : ?>
							<img src="<?php echo esc_url( $thumb_url ); ?>" class="ahbn-hero-img" alt="<?php echo esc_attr( $room->title ); ?>">
						<?php else : ?>
							<div class="ahbn-hero-placeholder">
								<span class="dashicons dashicons-building" aria-hidden="true"></span>
								<p><?php esc_html_e( 'No featured image set', 'awesome-hotel-booking' ); ?></p>
							</div>
						<?php endif; ?>

						<?php if ( ! empty( $gallery ) ) : ?>
							<?php
							$gallery_ids = array_filter( array_map( 'absint', explode( ',', $gallery ) ) );
							?>
							<?php if ( ! empty( $gallery_ids ) ) : ?>
								<div class="ahbn-gallery-thumbnails">
									<?php foreach ( array_slice( $gallery_ids, 0, 6 ) as $img_id ) : ?>
										<?php $img_url = wp_get_attachment_thumb_url( $img_id ); ?>
										<?php if ( $img_url ) : ?>
											<div class="ahbn-gal-thumb">
												<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php esc_attr_e( 'Gallery Thumbnail', 'awesome-hotel-booking' ); ?>">
											</div>
										<?php endif; ?>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						<?php endif; ?>

						<div class="ahbn-price-box">
							<span class="ahbn-price-amount">
								<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $price ) : (string) number_format_i18n( $price, 2 ) ); ?>
							</span>
							<span class="ahbn-price-unit"><?php esc_html_e( 'Base Rate / Night', 'awesome-hotel-booking' ); ?></span>
						</div>
					</div>
				</div>

				<!-- Quick Performance Metrics -->
				<div class="ahbn-stats-card-group">
					<div class="ahbn-stat-tile">
						<span class="ahbn-tile-label"><?php esc_html_e( 'Realized Stays', 'awesome-hotel-booking' ); ?></span>
						<span class="ahbn-tile-value"><?php echo esc_html( (string) absint( $ahbn_total_books ) ); ?></span>
					</div>
					<div class="ahbn-stat-tile">
						<span class="ahbn-tile-label"><?php esc_html_e( 'Total Capacity', 'awesome-hotel-booking' ); ?></span>
						<span class="ahbn-tile-value"><?php echo esc_html( (string) ( $adults_val + $children_val ) ); ?></span>
					</div>
				</div>
			</div>

			<!-- Right Column: Room Folio Details -->
			<div class="ahbn-main-details">
				
				<!-- Occupancy Specifications -->
				<div class="ahbn-panel">
					<div class="ahbn-panel-head">
						<span class="dashicons dashicons-groups" aria-hidden="true"></span>
						<h2><?php esc_html_e( 'Room Capacity & Guest Limits', 'awesome-hotel-booking' ); ?></h2>
					</div>
					<div class="ahbn-panel-body" style="flex-direction: row; gap: 12px; flex-wrap: wrap;">
						<div class="ahbn-occupancy-badge">
							<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
							<span>
								<?php
								printf(
									/* translators: %d: Maximum adults */
									esc_html( _n( '%d Adult Allowed', '%d Adults Allowed', $adults_val, 'awesome-hotel-booking' ) ),
									absint( $adults_val )
								);
								?>
							</span>
						</div>
						<div class="ahbn-occupancy-badge">
							<span class="dashicons dashicons-welcome-learn-more" aria-hidden="true"></span>
							<span>
								<?php
								printf(
									/* translators: %d: Maximum children */
									esc_html( _n( '%d Child Allowed', '%d Children Allowed', $children_val, 'awesome-hotel-booking' ) ),
									absint( $children_val )
								);
								?>
							</span>
						</div>
					</div>
				</div>

				<!-- Marketing Overview & Content -->
				<div class="ahbn-panel">
					<div class="ahbn-panel-head">
						<span class="dashicons dashicons-text-align-left" aria-hidden="true"></span>
						<h2><?php esc_html_e( 'Suite Overview & Descriptions', 'awesome-hotel-booking' ); ?></h2>
					</div>
					<div class="ahbn-panel-body">
						<div class="ahbn-prose-content">
							<?php
							if ( ! empty( $room->description ) ) {
								echo wp_kses_post( wpautop( $room->description ) );
							} else {
								echo '<p style="color:#94a3b8; font-style:italic; margin:0;">' . esc_html__( 'No description provided for this suite.', 'awesome-hotel-booking' ) . '</p>';
							}
							?>
						</div>
					</div>
				</div>

				<!-- Standard Amenities -->
				<div class="ahbn-panel">
					<div class="ahbn-panel-head">
						<span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
						<h2><?php esc_html_e( 'Complimentary In-Suite Amenities', 'awesome-hotel-booking' ); ?></h2>
					</div>
					<div class="ahbn-panel-body">
						<?php if ( ! empty( $amenities ) ) : ?>
							<div class="ahbn-pill-grid">
								<?php foreach ( $amenities as $item ) : ?>
									<?php if ( is_string( $item ) && '' !== trim( $item ) ) : ?>
										<span class="ahbn-amenity-chip">
											<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
											<?php echo esc_html( $item ); ?>
										</span>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						<?php else : ?>
							<p style="color:#94a3b8; margin:0; font-size:13px;"><?php esc_html_e( 'No standard amenities linked to this suite.', 'awesome-hotel-booking' ); ?></p>
						<?php endif; ?>
					</div>
				</div>

				<!-- Available Paid Extras -->
				<?php if ( ! empty( $paid_extras ) ) : ?>
					<div class="ahbn-panel">
						<div class="ahbn-panel-head">
							<span class="dashicons dashicons-cart" aria-hidden="true"></span>
							<h2><?php esc_html_e( 'Configured Paid Add-ons & Upsells', 'awesome-hotel-booking' ); ?></h2>
						</div>
						<div class="ahbn-panel-body">
							<div class="ahbn-pill-grid">
								<?php foreach ( $paid_extras as $extra ) : ?>
									<?php if ( is_string( $extra ) && '' !== trim( $extra ) ) : ?>
										<span class="ahbn-extra-chip">
											<span class="dashicons dashicons-plus" aria-hidden="true"></span>
											<?php echo esc_html( $extra ); ?>
										</span>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				<?php endif; ?>

			</div>
		</div>
	</div>

	<style>
		.ahbn-view-wrapper {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-view-header {
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

		.ahbn-back-link {
			text-decoration: none;
			color: #64748b;
			font-size: 12px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			display: inline-flex;
			align-items: center;
			gap: 6px;
			margin-bottom: 8px;
			transition: color 0.2s ease;
		}

		.ahbn-back-link:hover {
			color: #0a192f;
		}

		.ahbn-back-link .dashicons {
			font-size: 15px;
			width: 15px;
			height: 15px;
		}

		.ahbn-suite-headline {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 26px;
			font-weight: 700;
			color: #0a192f;
			margin: 0 0 10px;
			line-height: 1.2;
		}

		.ahbn-headline-meta {
			display: flex;
			align-items: center;
			gap: 10px;
			flex-wrap: wrap;
		}

		.ahbn-room-num-badge {
			font-family: 'JetBrains Mono', monospace;
			background: #f8fafc;
			color: #0a192f;
			font-weight: 800;
			font-size: 12px;
			padding: 3px 8px;
			border-radius: 6px;
			border: 1px solid #cbd5e1;
		}

		.ahbn-type-badge {
			font-family: 'JetBrains Mono', monospace;
			background: #f8fafc;
			color: #0a192f;
			font-weight: 700;
			font-size: 11px;
			padding: 3px 8px;
			border-radius: 6px;
			border: 1px solid #e2e8f0;
		}

		.ahbn-status-pill {
			display: inline-block;
			padding: 4px 10px;
			border-radius: 9999px;
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
		}

		.status-active-now { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
		.status-disabled   { background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; }

		.ahbn-btn-primary {
			background: #0a192f !important;
			color: #ffffff !important;
			border: 1px solid #0a192f !important;
			padding: 10px 20px !important;
			border-radius: 8px !important;
			font-size: 13px !important;
			font-weight: 700 !important;
			text-decoration: none !important;
			display: inline-flex !important;
			align-items: center !important;
			gap: 6px !important;
			cursor: pointer;
			transition: all 0.2s ease !important;
		}

		.ahbn-btn-primary:hover {
			background: #c5a880 !important;
			border-color: #c5a880 !important;
			color: #0a192f !important;
		}

		/* Grid Layout */
		.ahbn-view-grid {
			display: grid;
			grid-template-columns: 380px 1fr;
			gap: 20px;
			align-items: start;
		}

		.ahbn-sidebar-media,
		.ahbn-main-details {
			display: flex;
			flex-direction: column;
			gap: 20px;
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
			padding: 22px;
			display: flex;
			flex-direction: column;
			gap: 16px;
		}

		/* Hero Images & Gallery */
		.ahbn-hero-img {
			width: 100%;
			height: 230px;
			object-fit: cover;
			border-radius: 10px;
			border: 1px solid #e2e8f0;
			display: block;
		}

		.ahbn-hero-placeholder {
			width: 100%;
			height: 200px;
			background: #f8fafc;
			border: 2px dashed #cbd5e1;
			border-radius: 10px;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			color: #94a3b8;
		}

		.ahbn-hero-placeholder .dashicons {
			font-size: 40px;
			width: 40px;
			height: 40px;
		}

		.ahbn-hero-placeholder p {
			margin: 6px 0 0;
			font-size: 12px;
			font-weight: 600;
		}

		.ahbn-gallery-thumbnails {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 8px;
			margin-top: 10px;
		}

		.ahbn-gal-thumb {
			height: 70px;
			border-radius: 6px;
			overflow: hidden;
			border: 1px solid #e2e8f0;
			background: #f1f5f9;
		}

		.ahbn-gal-thumb img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			display: block;
		}

		.ahbn-price-box {
			margin-top: 16px;
			padding-top: 16px;
			border-top: 1px solid #f1f5f9;
			text-align: center;
		}

		.ahbn-price-amount {
			display: block;
			font-family: 'JetBrains Mono', monospace;
			font-size: 28px;
			font-weight: 800;
			color: #059669;
		}

		.ahbn-price-unit {
			color: #64748b;
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em;
		}

		/* Stat Cards */
		.ahbn-stats-card-group {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 12px;
		}

		.ahbn-stat-tile {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 16px;
			display: flex;
			flex-direction: column;
			gap: 4px;
			text-align: center;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-tile-label {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #64748b;
		}

		.ahbn-tile-value {
			font-family: 'JetBrains Mono', monospace;
			font-size: 24px;
			font-weight: 800;
			color: #0a192f;
		}

		/* Details Elements */
		.ahbn-occupancy-badge {
			display: inline-flex;
			align-items: center;
			gap: 8px;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			padding: 8px 16px;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 700;
			color: #0a192f;
		}

		.ahbn-occupancy-badge .dashicons {
			color: #c5a880;
			font-size: 16px;
			width: 16px;
			height: 16px;
		}

		.ahbn-prose-content {
			font-size: 14px;
			line-height: 1.7;
			color: #334155;
		}

		.ahbn-prose-content p {
			margin: 0 0 12px;
		}

		.ahbn-prose-content p:last-child {
			margin-bottom: 0;
		}

		/* Chips / Pills */
		.ahbn-pill-grid {
			display: flex;
			flex-wrap: wrap;
			gap: 10px;
		}

		.ahbn-amenity-chip {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: #ecfdf5;
			color: #065f46;
			border: 1px solid #a7f3d0;
			padding: 6px 14px;
			border-radius: 8px;
			font-size: 12px;
			font-weight: 700;
		}

		.ahbn-amenity-chip .dashicons {
			color: #10b981;
			font-size: 14px;
			width: 14px;
			height: 14px;
		}

		.ahbn-extra-chip {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: #f8fafc;
			color: #0a192f;
			border: 1px solid #cbd5e1;
			padding: 6px 14px;
			border-radius: 8px;
			font-size: 12px;
			font-weight: 700;
		}

		.ahbn-extra-chip .dashicons {
			color: #c5a880;
			font-size: 13px;
			width: 13px;
			height: 13px;
		}

		@media (max-width: 1024px) {
			.ahbn-view-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>
	<?php
}