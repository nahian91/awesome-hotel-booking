<?php
/**
 * [ahbn_all_rooms] Room Inventory Grid Shortcode.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the full room inventory grid shortcode.
 *
 * @return string Rendered HTML output.
 */
function ahbn_all_rooms_shortcode_callback() {
	// 1. Query Published Rooms.
	$args = array(
		'post_type'              => 'ahbn_room',
		'posts_per_page'         => -1,
		'post_status'            => 'publish',
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
		'orderby'                => 'menu_order',
		'order'                  => 'ASC',
	);

	$room_query = new WP_Query( $args );

	if ( ! $room_query->have_posts() ) {
		return '<div class="ahbn-empty-state" style="text-align:center; padding:50px 20px; font-family:\'Inter\',-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">
					<span class="dashicons dashicons-admin-home" style="font-size:40px; width:40px; height:40px; color:#cbd5e1;" aria-hidden="true"></span>
					<p style="color:#64748b; font-size:15px; margin-top:12px;">' . esc_html__( 'Our exclusive suites are currently being prepared. Please check back soon.', 'awesome-hotel-booking' ) . '</p>
				</div>';
	}

	ob_start();
	?>
	<div class="ahbn-rooms-wrapper">
		<div class="ahbn-grid-3-column">
			<?php
			while ( $room_query->have_posts() ) :
				$room_query->the_post();
				$rid = get_the_ID();

				// Permalink Resolution.
				$details_link = get_permalink( $rid );

				// Room Metadata.
				$raw_price = get_post_meta( $rid, 'ahbn_room_price', true );
				$price     = (float) preg_replace( '/[^\d.]/', '', (string) $raw_price );
				$rno       = (string) get_post_meta( $rid, 'ahbn_room_no', true );
				$type      = (string) get_post_meta( $rid, 'ahbn_room_type_select', true );
				$type_name = ! empty( $type ) ? $type : __( 'Premium Suite', 'awesome-hotel-booking' );
				$amenities = (array) get_post_meta( $rid, 'ahbn_room_amenities', true );
				?>
				<div class="ahbn-suite-card">
					<div class="ahbn-card-visual">
						<a href="<?php echo esc_url( $details_link ); ?>">
							<?php if ( has_post_thumbnail( $rid ) ) : ?>
								<?php echo wp_kses_post( get_the_post_thumbnail( $rid, 'large', array( 'class' => 'ahbn-suite-img' ) ) ); ?>
							<?php else : ?>
								<div class="ahbn-media-placeholder">
									<span class="dashicons dashicons-format-image" aria-hidden="true"></span>
								</div>
							<?php endif; ?>
						</a>
						<div class="ahbn-floating-badge"><?php echo esc_html( $type_name ); ?></div>
					</div>

					<div class="ahbn-card-details">
						<div class="ahbn-header-group">
							<?php if ( ! empty( $rno ) ) : ?>
								<span class="ahbn-label-gold">
									<?php
									printf(
										/* translators: %s: Room Number */
										esc_html__( 'Suite %s', 'awesome-hotel-booking' ),
										esc_html( $rno )
									);
									?>
								</span>
							<?php endif; ?>
							<h3 class="ahbn-suite-name">
								<a href="<?php echo esc_url( $details_link ); ?>"><?php the_title(); ?></a>
							</h3>
						</div>

						<div class="ahbn-amenity-grid">
							<?php
							$count = 0;
							if ( ! empty( $amenities ) ) :
								foreach ( $amenities as $slug ) :
									if ( $count >= 4 ) {
										break;
									}
									if ( ! is_string( $slug ) || '' === trim( $slug ) ) {
										continue;
									}
									$label = str_replace( array( '-', '_' ), ' ', ucfirst( $slug ) );
									?>
									<div class="ahbn-amenity-item">
										<span class="ahbn-icon-svg" aria-hidden="true">
											<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
										</span>
										<span class="ahbn-icon-label"><?php echo esc_html( $label ); ?></span>
									</div>
									<?php
									$count++;
								endforeach;
							endif;
							?>
						</div>

						<div class="ahbn-footer-action">
							<div class="ahbn-pricing">
								<span class="ahbn-price-main">
									<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $price ) : (string) number_format_i18n( $price, 2 ) ); ?>
								</span>
								<span class="ahbn-price-unit"><?php esc_html_e( '/night', 'awesome-hotel-booking' ); ?></span>
							</div>
							<a href="<?php echo esc_url( $details_link ); ?>" class="ahbn-btn-primary">
								<?php esc_html_e( 'Details', 'awesome-hotel-booking' ); ?>
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
							</a>
						</div>
					</div>
				</div>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
	</div>

	<style>
		:root { --ahbn-navy: #1a2b48; --ahbn-gold: #bf9441; }
		.ahbn-rooms-wrapper { max-width: 1240px; margin: 40px auto; padding: 0 20px; font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
		.ahbn-grid-3-column { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; }
		.ahbn-suite-card { background: #fff; border-radius: 16px; overflow: hidden; border: 1px solid #f1f5f9; transition: 0.4s; display: flex; flex-direction: column; height: 100%; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
		.ahbn-suite-card:hover { transform: translateY(-8px); border-color: var(--ahbn-gold); box-shadow: 0 20px 40px rgba(26, 43, 72, 0.1); }
		.ahbn-card-visual { height: 230px; position: relative; overflow: hidden; background: #f8f9fa; }
		.ahbn-suite-img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; display: block; }
		.ahbn-suite-card:hover .ahbn-suite-img { transform: scale(1.05); }
		.ahbn-media-placeholder { height: 100%; display: flex; align-items: center; justify-content: center; color: #ccd0d4; }
		.ahbn-media-placeholder span { font-size: 48px; width: 48px; height: 48px; }
		.ahbn-floating-badge { position: absolute; top: 15px; left: 15px; background: rgba(26, 43, 72, 0.9); color: var(--ahbn-gold); padding: 5px 12px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; z-index: 2; letter-spacing: 0.5px; }
		.ahbn-card-details { padding: 25px; flex-grow: 1; display: flex; flex-direction: column; }
		.ahbn-label-gold { color: var(--ahbn-gold); font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
		.ahbn-suite-name { font-size: 20px; font-weight: 800; margin: 8px 0 16px; min-height: 52px; line-height: 1.3; }
		.ahbn-suite-name a { color: var(--ahbn-navy); text-decoration: none; transition: 0.2s; }
		.ahbn-suite-name a:hover { color: var(--ahbn-gold); }
		.ahbn-amenity-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 25px; padding-top: 15px; border-top: 1px solid #f1f5f9; }
		.ahbn-amenity-item { display: flex; align-items: center; gap: 8px; }
		.ahbn-icon-svg { width: 14px; height: 14px; color: var(--ahbn-gold); display: flex; align-items: center; flex-shrink: 0; }
		.ahbn-icon-label { font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
		.ahbn-footer-action { display: flex; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 1px solid #f1f5f9; margin-top: auto; }
		.ahbn-price-main { font-size: 22px; font-weight: 900; color: var(--ahbn-navy); }
		.ahbn-price-unit { font-size: 12px; color: #94a3b8; }
		.ahbn-btn-primary { background: var(--ahbn-navy); color: #fff; text-decoration: none; padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 6px; transition: 0.3s; }
		.ahbn-btn-primary:hover { background: var(--ahbn-gold); color: #fff; }
		@media (max-width: 1024px) { .ahbn-grid-3-column { grid-template-columns: repeat(2, 1fr); } }
		@media (max-width: 650px) { .ahbn-grid-3-column { grid-template-columns: 1fr; } }
	</style>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ahbn_all_rooms', 'ahbn_all_rooms_shortcode_callback' );