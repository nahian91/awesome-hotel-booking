<?php
/**
 * [ahbn_all_rooms] Room Inventory Grid Shortcode.
 *
 * Query engine rewritten for custom relational database table:
 * {$wpdb->prefix}ahbn_rooms
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the full room inventory grid shortcode.
 *
 * @param array $atts Shortcode attributes.
 * @return string Rendered HTML output.
 */
function ahbn_all_rooms_shortcode_callback( $atts = array() ) {
	global $wpdb;

	$atts = shortcode_atts(
		array(
			'columns'   => 3,
			'limit'     => -1,
			'room_type' => '',
			'order_by'  => 'menu_order',
			'order'     => 'ASC',
		),
		$atts,
		'ahbn_all_rooms'
	);

	$table_rooms = $wpdb->prefix . 'ahbn_rooms';

	// 1. Sanitize ORDER BY and ORDER against strict whitelists.
	$allowed_order_by = array(
		'menu_order'      => 'menu_order',
		'price_per_night' => 'price_per_night',
		'id'              => 'id',
		'title'           => 'title',
	);
	$order_by = isset( $allowed_order_by[ $atts['order_by'] ] ) ? $allowed_order_by[ $atts['order_by'] ] : 'menu_order';
	$order    = 'DESC' === strtoupper( $atts['order'] ) ? 'DESC' : 'ASC';

	// 2. Build Prepared Query & Parameters.
	$query_params = array();

	// Accept 'publish', empty string, or NULL so rooms without explicit status still display.
	$where_sql = "(status = 'publish' OR status = '' OR status IS NULL)";

	if ( ! empty( $atts['room_type'] ) ) {
		$where_sql     .= ' AND room_type = %s';
		$query_params[] = sanitize_text_field( $atts['room_type'] );
	}

	$limit_clause = '';
	$limit        = (int) $atts['limit'];
	if ( $limit > 0 ) {
		$limit_clause   = ' LIMIT %d';
		$query_params[] = $limit;
	}

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	if ( ! empty( $query_params ) ) {
		$prepared_query = $wpdb->prepare( "SELECT * FROM {$table_rooms} WHERE {$where_sql} ORDER BY {$order_by} {$order}{$limit_clause}", $query_params );
	} else {
		$prepared_query = "SELECT * FROM {$table_rooms} WHERE {$where_sql} ORDER BY {$order_by} {$order}";
	}

	$rooms = $wpdb->get_results( $prepared_query );
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

	if ( empty( $rooms ) ) {
		return '<div class="ahbn-empty-state">
					<span class="dashicons dashicons-admin-home" aria-hidden="true"></span>
					<p>' . esc_html__( 'Our exclusive suites are currently being prepared. Please check back soon.', 'awesome-hotel-booking' ) . '</p>
				</div>';
	}

	// Columns Class.
	$columns_class = 'ahbn-grid-3';
	if ( 2 === (int) $atts['columns'] ) {
		$columns_class = 'ahbn-grid-2';
	} elseif ( 4 === (int) $atts['columns'] ) {
		$columns_class = 'ahbn-grid-4';
	}

	ob_start();
	?>
	<div class="ahbn-rooms-wrapper">
		<div class="ahbn-grid-container <?php echo esc_attr( $columns_class ); ?>">
			<?php
			foreach ( $rooms as $room ) :
				$rid         = (int) $room->id;
				$room_number = $room->room_number;
				$title       = ! empty( $room->title ) ? $room->title : sprintf( __( 'Suite #%s', 'awesome-hotel-booking' ), $room_number );
				$slug        = ! empty( $room->slug ) ? $room->slug : sanitize_title( $title );
				$room_type   = ! empty( $room->room_type ) ? $room->room_type : __( 'Standard', 'awesome-hotel-booking' );
				$price       = (float) $room->price_per_night;
				$max_guests  = (int) $room->max_adults + (int) $room->max_children;

				// Permalink Resolution.
				$details_link = home_url( '/room-details/' . $slug . '/' );

				// Amenities Parsing.
				$amenities = array();
				if ( ! empty( $room->amenities ) ) {
					$decoded   = json_decode( $room->amenities, true );
					$amenities = is_array( $decoded ) ? $decoded : array_filter( array_map( 'trim', explode( ',', $room->amenities ) ) );
				}

				// Thumbnail Image Resolution.
				$thumbnail_url = '';
				if ( ! empty( $room->thumbnail_id ) ) {
					$thumbnail_url = wp_get_attachment_image_url( (int) $room->thumbnail_id, 'large' );
				}
				?>
				<article class="ahbn-suite-card">
					<div class="ahbn-card-visual">
						<a href="<?php echo esc_url( $details_link ); ?>" tabindex="-1" aria-hidden="true">
							<?php if ( ! empty( $thumbnail_url ) ) : ?>
								<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="ahbn-suite-img" loading="lazy">
							<?php else : ?>
								<div class="ahbn-media-placeholder">
									<span class="dashicons dashicons-building" aria-hidden="true"></span>
								</div>
							<?php endif; ?>
						</a>
						<span class="ahbn-floating-badge"><?php echo esc_html( $room_type ); ?></span>
					</div>

					<div class="ahbn-card-details">
						<div class="ahbn-header-group">
							<?php if ( ! empty( $room_number ) ) : ?>
								<span class="ahbn-label-gold">
									<?php
									printf(
										/* translators: %s: Room Number */
										esc_html__( 'Suite %s', 'awesome-hotel-booking' ),
										esc_html( $room_number )
									);
									?>
								</span>
							<?php endif; ?>
							<h3 class="ahbn-suite-name">
								<a href="<?php echo esc_url( $details_link ); ?>"><?php echo esc_html( $title ); ?></a>
							</h3>
						</div>

						<div class="ahbn-suite-specs">
							<span class="ahbn-spec-item" title="<?php esc_attr_e( 'Maximum Occupancy', 'awesome-hotel-booking' ); ?>">
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
								<?php
								printf(
									/* translators: %d: maximum guest count */
									esc_html__( 'Up to %d Guests', 'awesome-hotel-booking' ),
									absint( $max_guests )
								);
								?>
							</span>
						</div>

						<!-- Amenities Display -->
						<div class="ahbn-amenity-grid">
							<?php
							$amenity_slice = array_slice( $amenities, 0, 4 );
							if ( ! empty( $amenity_slice ) ) :
								foreach ( $amenity_slice as $amenity_item ) :
									?>
									<div class="ahbn-amenity-item">
										<span class="ahbn-icon-svg" aria-hidden="true">
											<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>
										</span>
										<span class="ahbn-icon-label"><?php echo esc_html( $amenity_item ); ?></span>
									</div>
									<?php
								endforeach;
							else :
								?>
								<div class="ahbn-amenity-item">
									<span class="ahbn-icon-svg" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
									<span class="ahbn-icon-label"><?php esc_html_e( 'High-Speed Wi-Fi', 'awesome-hotel-booking' ); ?></span>
								</div>
								<div class="ahbn-amenity-item">
									<span class="ahbn-icon-svg" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg></span>
									<span class="ahbn-icon-label"><?php esc_html_e( 'Climate Control', 'awesome-hotel-booking' ); ?></span>
								</div>
							<?php endif; ?>
						</div>

						<div class="ahbn-footer-action">
							<div class="ahbn-pricing">
								<span class="ahbn-price-main">
									<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $price ) : '$' . number_format( $price, 2 ) ); ?>
								</span>
								<span class="ahbn-price-unit"><?php esc_html_e( '/night', 'awesome-hotel-booking' ); ?></span>
							</div>
							<a href="<?php echo esc_url( $details_link ); ?>" class="ahbn-btn-primary">
								<span><?php esc_html_e( 'Details', 'awesome-hotel-booking' ); ?></span>
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
							</a>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>

	<style>
		.ahbn-rooms-wrapper {
			--ahbn-navy: #0a192f;
			--ahbn-navy-dark: #040914;
			--ahbn-gold: #c5a880;
			--ahbn-gold-hover: #b39264;
			--ahbn-slate: #64748b;
			max-width: 1240px;
			margin: 40px auto;
			padding: 0 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			box-sizing: border-box;
		}

		.ahbn-rooms-wrapper * {
			box-sizing: border-box;
		}

		.ahbn-empty-state {
			text-align: center;
			padding: 60px 20px;
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 16px;
			max-width: 600px;
			margin: 40px auto;
			font-family: 'Plus Jakarta Sans', sans-serif;
		}

		.ahbn-empty-state .dashicons {
			font-size: 42px;
			width: 42px;
			height: 42px;
			color: #cbd5e1;
		}

		.ahbn-empty-state p {
			color: #64748b;
			font-size: 15px;
			margin: 12px 0 0;
		}

		.ahbn-grid-container {
			display: grid;
			gap: 28px;
		}

		.ahbn-grid-2 { grid-template-columns: repeat(2, 1fr); }
		.ahbn-grid-3 { grid-template-columns: repeat(3, 1fr); }
		.ahbn-grid-4 { grid-template-columns: repeat(4, 1fr); }

		.ahbn-suite-card {
			background: #ffffff;
			border-radius: 16px;
			overflow: hidden;
			border: 1px solid #e2e8f0;
			transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
			display: flex;
			flex-direction: column;
			height: 100%;
			box-shadow: 0 4px 20px -2px rgba(10, 20, 38, 0.04);
		}

		.ahbn-suite-card:hover {
			transform: translateY(-6px);
			border-color: var(--ahbn-gold);
			box-shadow: 0 20px 30px -4px rgba(10, 25, 47, 0.1);
		}

		.ahbn-card-visual {
			height: 240px;
			position: relative;
			overflow: hidden;
			background: #0a192f;
		}

		.ahbn-suite-img {
			width: 100%;
			height: 100%;
			object-fit: cover;
			transition: transform 0.6s ease;
			display: block;
		}

		.ahbn-suite-card:hover .ahbn-suite-img {
			transform: scale(1.05);
		}

		.ahbn-media-placeholder {
			height: 100%;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #475569;
			background: linear-gradient(135deg, #07111e 0%, #0a192f 100%);
		}

		.ahbn-media-placeholder .dashicons {
			font-size: 48px;
			width: 48px;
			height: 48px;
			color: var(--ahbn-gold);
		}

		.ahbn-floating-badge {
			position: absolute;
			top: 14px;
			left: 14px;
			background: rgba(10, 25, 47, 0.92);
			backdrop-filter: blur(4px);
			color: var(--ahbn-gold);
			padding: 4px 12px;
			border-radius: 6px;
			font-size: 11px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			border: 1px solid rgba(197, 168, 128, 0.25);
		}

		.ahbn-card-details {
			padding: 24px;
			flex-grow: 1;
			display: flex;
			flex-direction: column;
		}

		.ahbn-label-gold {
			font-family: 'JetBrains Mono', monospace;
			color: var(--ahbn-gold-hover);
			font-size: 11px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.06em;
		}

		.ahbn-suite-name {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 19px;
			font-weight: 700;
			margin: 6px 0 10px;
			line-height: 1.35;
		}

		.ahbn-suite-name a {
			color: var(--ahbn-navy);
			text-decoration: none;
			transition: color 0.2s ease;
		}

		.ahbn-suite-name a:hover {
			color: var(--ahbn-gold);
		}

		.ahbn-suite-specs {
			display: flex;
			align-items: center;
			gap: 12px;
			margin-bottom: 16px;
		}

		.ahbn-spec-item {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			font-size: 12px;
			font-weight: 600;
			color: var(--ahbn-slate);
		}

		.ahbn-spec-item svg {
			width: 14px;
			height: 14px;
			color: var(--ahbn-gold);
		}

		.ahbn-amenity-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 10px;
			margin-bottom: 22px;
			padding-top: 14px;
			border-top: 1px solid #f1f5f9;
		}

		.ahbn-amenity-item {
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.ahbn-icon-svg {
			width: 14px;
			height: 14px;
			color: var(--ahbn-gold);
			display: flex;
			align-items: center;
			flex-shrink: 0;
		}

		.ahbn-icon-label {
			font-size: 12px;
			color: #475569;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		.ahbn-footer-action {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding-top: 18px;
			border-top: 1px solid #f1f5f9;
			margin-top: auto;
		}

		.ahbn-price-main {
			font-family: 'JetBrains Mono', monospace;
			font-size: 21px;
			font-weight: 800;
			color: var(--ahbn-navy);
		}

		.ahbn-price-unit {
			font-size: 12px;
			font-weight: 600;
			color: #94a3b8;
		}

		.ahbn-btn-primary {
			background: var(--ahbn-navy);
			color: #ffffff;
			text-decoration: none;
			padding: 9px 18px;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 700;
			display: inline-flex;
			align-items: center;
			gap: 6px;
			transition: all 0.2s ease;
			border: 1px solid var(--ahbn-navy);
		}

		.ahbn-btn-primary:hover {
			background: var(--ahbn-gold);
			border-color: var(--ahbn-gold);
			color: var(--ahbn-navy-dark);
		}

		@media (max-width: 1024px) {
			.ahbn-grid-3, .ahbn-grid-4 { grid-template-columns: repeat(2, 1fr); }
		}

		@media (max-width: 650px) {
			.ahbn-grid-container { grid-template-columns: 1fr !important; }
			.ahbn-card-visual { height: 210px; }
		}
	</style>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ahbn_all_rooms', 'ahbn_all_rooms_shortcode_callback' );