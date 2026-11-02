<?php
/**
 * Customer Directory, CRM Profile, and Booking Details View.
 *
 * Rewritten for custom SQL database tables:
 * {$wpdb->prefix}ahbn_bookings joined with {$wpdb->prefix}ahbn_rooms.
 * Integrated with the Champagne Gold & Deep Navy enterprise UI system.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Customers management tab.
 *
 * @return void
 */
function ahbn_customers_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Access Denied.', 'awesome-hotel-booking' ) );
	}

	global $wpdb;

	$table_bookings = $wpdb->prefix . 'ahbn_bookings';
	$table_rooms    = $wpdb->prefix . 'ahbn_rooms';

	$page_slug = 'awesome_hotel_booking';
	$base_url  = admin_url( 'admin.php?page=' . rawurlencode( $page_slug ) . '&tab=customers' );

	// Navigation Query Parameters.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$current_view    = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';
	$booking_details = isset( $_GET['booking_details'] ) ? absint( wp_unslash( $_GET['booking_details'] ) ) : 0;
	$search_query    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$paged           = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	?>

	<div class="ahbn-crm-container">

		<?php
		/* -------------------------------------------------------------------------
		   VIEW 1: SINGLE BOOKING DETAILS
		   ------------------------------------------------------------------------- */
		if ( $booking_details > 0 ) :
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$booking = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT 
						b.*,
						r.room_number,
						r.title AS room_title,
						r.room_type,
						r.price_per_night,
						r.thumbnail_id
					 FROM {$table_bookings} b
					 LEFT JOIN {$table_rooms} r ON b.room_id = r.id
					 WHERE b.id = %d",
					$booking_details
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( ! $booking ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Booking record not found.', 'awesome-hotel-booking' ) . '</p></div>';
				echo '</div>';
				return;
			}

			$customer    = ! empty( $booking->guest_name ) ? $booking->guest_name : __( 'Guest User', 'awesome-hotel-booking' );
			$email       = (string) $booking->guest_email;
			$phone       = (string) $booking->guest_phone;
			$checkin     = (string) $booking->checkin_date;
			$checkout    = (string) $booking->checkout_date;
			$status_slug = ! empty( $booking->status ) ? sanitize_key( $booking->status ) : 'pending';
			$total       = (float) $booking->total_amount;
			$room_no     = (string) $booking->room_number;
			$room_type   = (string) $booking->room_type;
			$room_thumb  = $booking->thumbnail_id ? wp_get_attachment_image_url( $booking->thumbnail_id, 'medium' ) : '';

			$nights = max( 1, absint( $booking->nights ) );
			if ( $nights <= 1 && ! empty( $checkin ) && ! empty( $checkout ) ) {
				$time_in  = strtotime( $checkin );
				$time_out = strtotime( $checkout );
				if ( $time_in && $time_out && $time_out > $time_in ) {
					$nights = max( 1, (int) round( ( $time_out - $time_in ) / DAY_IN_SECONDS ) );
				}
			}

			$nightly_rate = (float) $booking->room_rate > 0 ? (float) $booking->room_rate : ( (float) $booking->price_per_night > 0 ? (float) $booking->price_per_night : ( $total / $nights ) );
			$folio_code   = ! empty( $booking->booking_code ) ? $booking->booking_code : '#' . $booking->id;
			$back_to_crm  = ! empty( $email ) ? add_query_arg( 'view', rawurlencode( $email ), $base_url ) : $base_url;
			?>
			<div class="ahbn-crm-header">
				<div>
					<h1 class="ahbn-crm-title">
						<?php
						printf(
							/* translators: %s: Booking Folio Code */
							esc_html__( 'Reservation Folio (%s)', 'awesome-hotel-booking' ),
							esc_html( $folio_code )
						);
						?>
					</h1>
					<p class="ahbn-crm-subtitle"><?php esc_html_e( 'Detailed stay invoice, assigned inventory, and payment record.', 'awesome-hotel-booking' ); ?></p>
				</div>
				<a href="<?php echo esc_url( $back_to_crm ); ?>" class="ahbn-btn-back">
					<span class="dashicons dashicons-arrow-left-alt" aria-hidden="true"></span>
					<?php esc_html_e( 'Back to Customer Record', 'awesome-hotel-booking' ); ?>
				</a>
			</div>

			<div class="ahbn-crm-grid">
				<div class="ahbn-sidebar-col">
					<div class="ahbn-card">
						<div class="card-header">
							<span class="dashicons dashicons-admin-home" aria-hidden="true"></span>
							<?php esc_html_e( 'Room Assignment', 'awesome-hotel-booking' ); ?>
						</div>
						<div class="room-preview-body">
							<?php if ( ! empty( $room_thumb ) ) : ?>
								<img src="<?php echo esc_url( $room_thumb ); ?>" class="room-thumb-img" alt="<?php echo esc_attr( $booking->room_title ); ?>">
							<?php else : ?>
								<div class="room-thumb-placeholder">
									<span class="dashicons dashicons-admin-home" aria-hidden="true"></span>
								</div>
							<?php endif; ?>

							<h3 class="room-preview-heading">
								<?php echo esc_html( ! empty( $booking->room_title ) ? $booking->room_title : __( 'Standard Room', 'awesome-hotel-booking' ) ); ?>
							</h3>
							<span class="ahbn-badge-pill">
								<?php echo esc_html( ! empty( $room_type ) ? $room_type : __( 'Standard', 'awesome-hotel-booking' ) ); ?>
							</span>

							<div class="ahbn-data-list" style="margin-top: 18px;">
								<div class="data-row">
									<span class="data-label"><?php esc_html_e( 'Suite #', 'awesome-hotel-booking' ); ?></span>
									<strong style="color: #c5a880; font-size: 15px; font-weight: 800;">
										#<?php echo esc_html( ! empty( $room_no ) ? $room_no : __( 'Unassigned', 'awesome-hotel-booking' ) ); ?>
									</strong>
								</div>
								<div class="data-row">
									<span class="data-label"><?php esc_html_e( 'Rate / Night', 'awesome-hotel-booking' ); ?></span>
									<strong style="font-family: 'JetBrains Mono', monospace;">
										<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $nightly_rate ) : (string) number_format_i18n( $nightly_rate, 2 ) ); ?>
									</strong>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="ahbn-main-col">
					<div class="ahbn-card">
						<div class="stay-banner">
							<div class="stay-date-box">
								<h4><?php esc_html_e( 'Check-In', 'awesome-hotel-booking' ); ?></h4>
								<div class="stay-date-val">
									<?php echo esc_html( ! empty( $checkin ) ? wp_date( 'D, M j, Y', strtotime( $checkin ) ) : '—' ); ?>
								</div>
							</div>
							<div class="stay-duration">
								<span class="dashicons dashicons-moon" aria-hidden="true"></span>
								<?php
								printf(
									/* translators: %d: Number of nights */
									esc_html( _n( '%d Night Stay', '%d Nights Stay', $nights, 'awesome-hotel-booking' ) ),
									absint( $nights )
								);
								?>
							</div>
							<div class="stay-date-box">
								<h4><?php esc_html_e( 'Check-Out', 'awesome-hotel-booking' ); ?></h4>
								<div class="stay-date-val">
									<?php echo esc_html( ! empty( $checkout ) ? wp_date( 'D, M j, Y', strtotime( $checkout ) ) : '—' ); ?>
								</div>
							</div>
						</div>
					</div>

					<div class="ahbn-card">
						<div class="card-header">
							<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
							<?php esc_html_e( 'Guest Particulars & Billing Summary', 'awesome-hotel-booking' ); ?>
						</div>
						<div class="ahbn-data-list">
							<div class="data-row">
								<span class="data-label"><?php esc_html_e( 'Guest Name', 'awesome-hotel-booking' ); ?></span>
								<strong style="color: #0a192f;"><?php echo esc_html( $customer ); ?></strong>
							</div>
							<div class="data-row">
								<span class="data-label"><?php esc_html_e( 'Email Address', 'awesome-hotel-booking' ); ?></span>
								<span><?php echo esc_html( ! empty( $email ) ? $email : __( 'Not provided', 'awesome-hotel-booking' ) ); ?></span>
							</div>
							<div class="data-row">
								<span class="data-label"><?php esc_html_e( 'Phone Number', 'awesome-hotel-booking' ); ?></span>
								<span><?php echo esc_html( ! empty( $phone ) ? $phone : __( 'Not provided', 'awesome-hotel-booking' ) ); ?></span>
							</div>
							<div class="data-row">
								<span class="data-label"><?php esc_html_e( 'Stay Status', 'awesome-hotel-booking' ); ?></span>
								<span class="ahbn-status-pill status-confirmed">
									<?php echo esc_html( strtoupper( str_replace( '_', ' ', $status_slug ) ) ); ?>
								</span>
							</div>
							<div class="data-row">
								<span class="data-label"><?php esc_html_e( 'Settlement Status', 'awesome-hotel-booking' ); ?></span>
								<span class="ahbn-pay-badge <?php echo 'paid' === sanitize_key( $booking->payment_status ) ? 'is-paid' : 'is-unpaid'; ?>">
									<?php echo 'paid' === sanitize_key( $booking->payment_status ) ? '&#10003; ' . esc_html__( 'Settled', 'awesome-hotel-booking' ) : '&#9679; ' . esc_html__( 'Unpaid', 'awesome-hotel-booking' ); ?>
								</span>
							</div>
							<?php if ( ! empty( $booking->notes ) ) : ?>
								<div class="data-row" style="flex-direction: column; align-items: flex-start; gap: 6px;">
									<span class="data-label"><?php esc_html_e( 'Concierge Notes', 'awesome-hotel-booking' ); ?></span>
									<div class="ahbn-notes-box"><?php echo nl2br( esc_html( $booking->notes ) ); ?></div>
								</div>
							<?php endif; ?>
						</div>

						<div class="total-footer">
							<div class="total-footer-flex">
								<div>
									<div class="pricing-math">
										<?php
										$rate_str = function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $nightly_rate ) : (string) number_format_i18n( $nightly_rate, 2 );
										printf(
											/* translators: 1: nightly rate, 2: number of nights */
											esc_html__( '%1$s × %2$d Nights', 'awesome-hotel-booking' ),
											esc_html( $rate_str ),
											absint( $nights )
										);
										?>
									</div>
									<span class="data-label"><?php esc_html_e( 'Total Realized Amount', 'awesome-hotel-booking' ); ?></span>
								</div>
								<span class="price-large">
									<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $total ) : (string) number_format_i18n( $total, 2 ) ); ?>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>

		<?php
		/* -------------------------------------------------------------------------
		   VIEW 2: CUSTOMER CRM PROFILE (BY EMAIL OR USER ID)
		   ------------------------------------------------------------------------- */
		elseif ( ! empty( $current_view ) ) :
			$customer_filter = sanitize_text_field( $current_view );

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( is_numeric( $customer_filter ) ) {
				$customer_bookings = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT b.*, r.room_number, r.title AS room_title 
						 FROM {$table_bookings} b
						 LEFT JOIN {$table_rooms} r ON b.room_id = r.id
						 WHERE b.user_id = %d OR b.guest_email = (SELECT user_email FROM {$wpdb->users} WHERE ID = %d)
						 ORDER BY b.checkin_date DESC, b.id DESC",
						absint( $customer_filter ),
						absint( $customer_filter )
					)
				);
				$wp_user = get_userdata( absint( $customer_filter ) );
			} else {
				$customer_bookings = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT b.*, r.room_number, r.title AS room_title 
						 FROM {$table_bookings} b
						 LEFT JOIN {$table_rooms} r ON b.room_id = r.id
						 WHERE b.guest_email = %s
						 ORDER BY b.checkin_date DESC, b.id DESC",
						$customer_filter
					)
				);
				$wp_user = get_user_by( 'email', $customer_filter );
			}
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( empty( $customer_bookings ) && ! $wp_user ) {
				echo '<div class="notice notice-error"><p>' . esc_html__( 'Customer profile not found.', 'awesome-hotel-booking' ) . '</p></div>';
				echo '</div>';
				return;
			}

			$first_record = ! empty( $customer_bookings ) ? $customer_bookings[0] : null;
			$display_name = $wp_user ? $wp_user->display_name : ( $first_record ? $first_record->guest_name : __( 'Guest User', 'awesome-hotel-booking' ) );
			$display_mail = $wp_user ? $wp_user->user_email : ( $first_record ? $first_record->guest_email : $customer_filter );
			$display_tel  = $first_record ? $first_record->guest_phone : '';
			$display_addr = $first_record ? $first_record->guest_address : '';

			$total_spent     = 0.00;
			$completed_stays = 0;
			foreach ( $customer_bookings as $cb ) {
				$total_spent += (float) $cb->total_amount;
				if ( in_array( $cb->status, array( 'confirmed', 'checked_in', 'checked_out' ), true ) ) {
					$completed_stays++;
				}
			}
			?>
			<div class="ahbn-crm-header">
				<div>
					<h1 class="ahbn-crm-title"><?php esc_html_e( 'Guest CRM Portfolio', 'awesome-hotel-booking' ); ?></h1>
					<p class="ahbn-crm-subtitle">
						<?php
						printf(
							/* translators: %s: Guest display name */
							esc_html__( 'Lifetime booking history and guest profile for %s', 'awesome-hotel-booking' ),
							esc_html( $display_name )
						);
						?>
					</p>
				</div>
				<a href="<?php echo esc_url( $base_url ); ?>" class="ahbn-btn-back">
					<span class="dashicons dashicons-arrow-left-alt" aria-hidden="true"></span>
					<?php esc_html_e( 'Back to Guest Directory', 'awesome-hotel-booking' ); ?>
				</a>
			</div>

			<div class="ahbn-crm-grid">
				<div class="ahbn-sidebar-col">
					<div class="ahbn-card">
						<div class="card-header">
							<span class="dashicons dashicons-id-alt" aria-hidden="true"></span>
							<?php esc_html_e( 'Profile Summary', 'awesome-hotel-booking' ); ?>
						</div>
						<div class="profile-card-body">
							<div class="ahbn-avatar-large">
								<?php echo esc_html( strtoupper( substr( $display_name, 0, 1 ) ) ); ?>
							</div>
							<h3 class="profile-name"><?php echo esc_html( $display_name ); ?></h3>
							<p class="profile-email"><?php echo esc_html( $display_mail ); ?></p>

							<?php if ( ! empty( $display_tel ) ) : ?>
								<p class="profile-contact">
									<span class="dashicons dashicons-phone" aria-hidden="true"></span>
									<?php echo esc_html( $display_tel ); ?>
								</p>
							<?php endif; ?>

							<?php if ( ! empty( $display_addr ) ) : ?>
								<p class="profile-contact">
									<span class="dashicons dashicons-location" aria-hidden="true"></span>
									<?php echo esc_html( $display_addr ); ?>
								</p>
							<?php endif; ?>

							<div class="profile-metric-box">
								<span class="metric-label"><?php esc_html_e( 'Lifetime Value (LTV)', 'awesome-hotel-booking' ); ?></span>
								<strong class="metric-val">
									<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $total_spent ) : (string) number_format_i18n( $total_spent, 2 ) ); ?>
								</strong>
							</div>

							<div class="profile-metric-box" style="margin-top: 10px;">
								<span class="metric-label"><?php esc_html_e( 'Completed Stays', 'awesome-hotel-booking' ); ?></span>
								<strong class="metric-val" style="color: #0a192f;">
									<?php echo esc_html( (string) $completed_stays ); ?>
								</strong>
							</div>

							<?php if ( $wp_user ) : ?>
								<div style="margin-top: 15px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 11px; color: #94a3b8;">
									<?php
									printf(
										/* translators: %s: Member registration date */
										esc_html__( 'WP Member Since %s', 'awesome-hotel-booking' ),
										esc_html( wp_date( 'F Y', strtotime( $wp_user->user_registered ) ) )
									);
									?>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<div class="ahbn-main-col">
					<div class="ahbn-table-container">
						<div class="ahbn-table-header">
							<h3 class="ahbn-table-title"><?php esc_html_e( 'Reservation History', 'awesome-hotel-booking' ); ?></h3>
							<span class="ahbn-table-badge">
								<?php
								printf(
									/* translators: %d: count */
									esc_html__( '%d Bookings', 'awesome-hotel-booking' ),
									count( $customer_bookings )
								);
								?>
							</span>
						</div>

						<table class="ahbn-crm-table widefat striped">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Folio Ref', 'awesome-hotel-booking' ); ?></th>
									<th><?php esc_html_e( 'Assigned Suite', 'awesome-hotel-booking' ); ?></th>
									<th><?php esc_html_e( 'Dates of Stay', 'awesome-hotel-booking' ); ?></th>
									<th><?php esc_html_e( 'Total Amount', 'awesome-hotel-booking' ); ?></th>
									<th><?php esc_html_e( 'Status', 'awesome-hotel-booking' ); ?></th>
									<th style="text-align: right; width: 100px;"><?php esc_html_e( 'Action', 'awesome-hotel-booking' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php if ( ! empty( $customer_bookings ) ) : ?>
									<?php foreach ( $customer_bookings as $b_row ) :
										$b_id       = (int) $b_row->id;
										$b_price    = (float) $b_row->total_amount;
										$b_status   = ! empty( $b_row->status ) ? sanitize_key( $b_row->status ) : 'pending';
										$detail_url = add_query_arg( 'booking_details', $b_id, $base_url );
										$suite_lbl  = ! empty( $b_row->room_title ) ? $b_row->room_title : __( 'Standard Room', 'awesome-hotel-booking' );
										if ( ! empty( $b_row->room_number ) ) {
											$suite_lbl = '#' . $b_row->room_number . ' ' . $suite_lbl;
										}
										?>
										<tr>
											<td>
												<span class="ahbn-code-badge">
													<?php echo esc_html( ! empty( $b_row->booking_code ) ? $b_row->booking_code : '#' . $b_id ); ?>
												</span>
											</td>
											<td>
												<strong style="color: #0a192f;"><?php echo esc_html( $suite_lbl ); ?></strong>
											</td>
											<td>
												<span style="font-size: 12px; font-weight: 600; color: #334155;">
													<?php echo esc_html( wp_date( 'M j', strtotime( $b_row->checkin_date ) ) ); ?> &rarr; <?php echo esc_html( wp_date( 'M j, Y', strtotime( $b_row->checkout_date ) ) ); ?>
												</span>
												<small style="display: block; color: #64748b; font-size: 11px;">
													<?php
													printf(
														/* translators: %d: nights */
														esc_html( _n( '%d Night', '%d Nights', (int) $b_row->nights, 'awesome-hotel-booking' ) ),
														absint( $b_row->nights )
													);
													?>
												</small>
											</td>
											<td>
												<strong class="ahbn-amount-highlight">
													<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $b_price ) : (string) number_format_i18n( $b_price, 2 ) ); ?>
												</strong>
											</td>
											<td>
												<span class="ahbn-status-pill status-confirmed">
													<?php echo esc_html( strtoupper( str_replace( '_', ' ', $b_status ) ) ); ?>
												</span>
											</td>
											<td style="text-align: right;">
												<a href="<?php echo esc_url( $detail_url ); ?>" class="ahbn-quick-btn" title="<?php esc_attr_e( 'View Folio', 'awesome-hotel-booking' ); ?>">
													<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
												</a>
											</td>
										</tr>
									<?php endforeach; ?>
								<?php else : ?>
									<tr>
										<td colspan="6" style="text-align: center; padding: 40px; color: #94a3b8;">
											<?php esc_html_e( 'No booking records found for this guest.', 'awesome-hotel-booking' ); ?>
										</td>
									</tr>
								<?php endif; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>

		<?php
		/* -------------------------------------------------------------------------
		   VIEW 3: MAIN CUSTOMER DIRECTORY (AGGREGATED FROM CUSTOM SQL TABLE)
		   ------------------------------------------------------------------------- */
		else :
			$per_page = 20;
			$offset   = ( $paged - 1 ) * $per_page;

			$where_sql = "WHERE b.guest_email != ''";
			$params    = array();

			if ( ! empty( $search_query ) ) {
				$where_sql .= ' AND (b.guest_name LIKE %s OR b.guest_email LIKE %s OR b.guest_phone LIKE %s)';
				$like_val   = '%' . $wpdb->esc_like( $search_query ) . '%';
				$params[]   = $like_val;
				$params[]   = $like_val;
				$params[]   = $like_val;
			}

			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			// Count unique customer profiles.
			$count_query = "SELECT COUNT(DISTINCT b.guest_email) FROM {$table_bookings} b {$where_sql}";
			if ( ! empty( $params ) ) {
				$total_customers = (int) $wpdb->get_var( $wpdb->prepare( $count_query, $params ) );
			} else {
				$total_customers = (int) $wpdb->get_var( $count_query );
			}

			$total_pages = ceil( $total_customers / $per_page );

			// Query Aggregated Customer Details.
			$fetch_query = "SELECT 
								b.guest_email,
								MAX(b.guest_name) AS guest_name,
								MAX(b.guest_phone) AS guest_phone,
								MAX(b.user_id) AS user_id,
								COUNT(b.id) AS total_bookings,
								COALESCE(SUM(b.total_amount), 0.00) AS total_ltv,
								MAX(b.checkin_date) AS last_stay
							FROM {$table_bookings} b
							{$where_sql}
							GROUP BY b.guest_email
							ORDER BY total_ltv DESC
							LIMIT %d OFFSET %d";

			$fetch_params   = $params;
			$fetch_params[] = $per_page;
			$fetch_params[] = $offset;

			$customers = $wpdb->get_results( $wpdb->prepare( $fetch_query, $fetch_params ) );
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			?>
			<div class="ahbn-crm-header">
				<div>
					<h1 class="ahbn-crm-title"><?php esc_html_e( 'Guest Directory & CRM', 'awesome-hotel-booking' ); ?></h1>
					<p class="ahbn-crm-subtitle"><?php esc_html_e( 'Aggregated customer profiles, lifetime spend, booking frequency, and contact records.', 'awesome-hotel-booking' ); ?></p>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=awesome_hotel_booking&tab=bookings&action=add' ) ); ?>" class="ahbn-btn-primary">
					<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
					<?php esc_html_e( 'New Reservation', 'awesome-hotel-booking' ); ?>
				</a>
			</div>

			<!-- Search & Filter Controls -->
			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="ahbn-crm-filter-bar">
				<input type="hidden" name="page" value="<?php echo esc_attr( $page_slug ); ?>">
				<input type="hidden" name="tab" value="customers">

				<div class="ahbn-search-box">
					<input 
						type="text" 
						name="s" 
						class="ahbn-field" 
						placeholder="<?php esc_attr_e( 'Search guest by name, email, or telephone...', 'awesome-hotel-booking' ); ?>"
						value="<?php echo esc_attr( $search_query ); ?>"
					>
					<button type="submit" class="ahbn-btn-primary" style="height: 38px;">
						<span class="dashicons dashicons-search" aria-hidden="true"></span>
						<?php esc_html_e( 'Search Guests', 'awesome-hotel-booking' ); ?>
					</button>

					<?php if ( ! empty( $search_query ) ) : ?>
						<a href="<?php echo esc_url( $base_url ); ?>" class="ahbn-clear-link">
							<?php esc_html_e( 'Clear Search', 'awesome-hotel-booking' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</form>

			<!-- Customer Records Table -->
			<div class="ahbn-table-container">
				<table class="ahbn-crm-table widefat striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Guest Particulars', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Email Address', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Telephone', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Total Stays', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Lifetime Value (LTV)', 'awesome-hotel-booking' ); ?></th>
							<th><?php esc_html_e( 'Recent Visit', 'awesome-hotel-booking' ); ?></th>
							<th style="text-align: right; width: 120px;"><?php esc_html_e( 'Actions', 'awesome-hotel-booking' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if ( ! empty( $customers ) ) : ?>
							<?php foreach ( $customers as $c ) :
								$view_url = add_query_arg( 'view', rawurlencode( $c->guest_email ), $base_url );
								$c_name   = ! empty( $c->guest_name ) ? $c->guest_name : __( 'Guest User', 'awesome-hotel-booking' );
								?>
								<tr>
									<td>
										<div class="ahbn-guest-flex">
											<div class="ahbn-avatar-sm">
												<?php echo esc_html( strtoupper( substr( $c_name, 0, 1 ) ) ); ?>
											</div>
											<div>
												<strong class="ahbn-guest-name"><?php echo esc_html( $c_name ); ?></strong>
												<?php if ( $c->user_id > 0 ) : ?>
													<small class="ahbn-wp-tag"><?php esc_html_e( 'Registered Account', 'awesome-hotel-booking' ); ?></small>
												<?php endif; ?>
											</div>
										</div>
									</td>
									<td>
										<span style="color: #334155; font-size: 13px;"><?php echo esc_html( $c->guest_email ); ?></span>
									</td>
									<td>
										<span style="color: #64748b; font-size: 13px;"><?php echo esc_html( ! empty( $c->guest_phone ) ? $c->guest_phone : '—' ); ?></span>
									</td>
									<td>
										<span class="ahbn-count-badge">
											<?php
											printf(
												/* translators: %d: Total bookings count */
												esc_html( _n( '%d Stay', '%d Stays', (int) $c->total_bookings, 'awesome-hotel-booking' ) ),
												absint( $c->total_bookings )
											);
											?>
										</span>
									</td>
									<td>
										<strong class="ahbn-amount-highlight">
											<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( (float) $c->total_ltv ) : (string) number_format_i18n( (float) $c->total_ltv, 2 ) ); ?>
										</strong>
									</td>
									<td>
										<span style="font-size: 12px; color: #64748b;">
											<?php echo esc_html( ! empty( $c->last_stay ) ? wp_date( 'M j, Y', strtotime( $c->last_stay ) ) : '—' ); ?>
										</span>
									</td>
									<td style="text-align: right;">
										<a href="<?php echo esc_url( $view_url ); ?>" class="ahbn-btn-portfolio">
											<span class="dashicons dashicons-businessman" aria-hidden="true"></span>
											<?php esc_html_e( 'Portfolio', 'awesome-hotel-booking' ); ?>
										</a>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td colspan="7" style="text-align: center; padding: 50px; color: #94a3b8;">
									<span class="dashicons dashicons-groups" style="font-size: 36px; width: 36px; height: 36px; color: #cbd5e1;" aria-hidden="true"></span>
									<p style="margin: 8px 0 0; font-size: 13px;"><?php esc_html_e( 'No guest records found.', 'awesome-hotel-booking' ); ?></p>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav" style="margin-top: 15px;">
					<div class="tablenav-pages">
						<?php
						$pagination_args = array(
							'base'      => add_query_arg( 'paged', '%#%' ),
							'format'    => '',
							'prev_text' => __( '&laquo; Previous', 'awesome-hotel-booking' ),
							'next_text' => __( 'Next &raquo;', 'awesome-hotel-booking' ),
							'total'     => $total_pages,
							'current'   => $paged,
						);
						echo wp_kses_post( paginate_links( $pagination_args ) );
						?>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<style>
		.ahbn-crm-container {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-crm-header {
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

		.ahbn-crm-title {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 24px;
			font-weight: 700;
			color: #0a192f;
			margin: 0 0 4px;
		}

		.ahbn-crm-subtitle {
			color: #64748b;
			margin: 0;
			font-size: 13px;
		}

		.ahbn-btn-primary {
			background: #0a192f !important;
			color: #ffffff !important;
			border: 1px solid #0a192f !important;
			padding: 9px 16px !important;
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

		.ahbn-btn-back {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			padding: 8px 14px;
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 8px;
			color: #334155;
			font-size: 13px;
			font-weight: 600;
			text-decoration: none;
			transition: all 0.2s ease;
		}

		.ahbn-btn-back:hover {
			border-color: #0a192f;
			color: #0a192f;
			background: #f8fafc;
		}

		/* Grid & Layouts */
		.ahbn-crm-grid {
			display: grid;
			grid-template-columns: 280px 1fr;
			gap: 20px;
			align-items: start;
		}

		.ahbn-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			overflow: hidden;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.card-header {
			padding: 16px 20px;
			background: #fafafa;
			border-bottom: 1px solid #f1f5f9;
			font-weight: 800;
			font-size: 12px;
			text-transform: uppercase;
			letter-spacing: 0.06em;
			color: #0a192f;
			display: flex;
			align-items: center;
			gap: 8px;
		}

		.card-header .dashicons {
			color: #c5a880;
			font-size: 17px;
			width: 17px;
			height: 17px;
		}

		.profile-card-body {
			padding: 24px;
			text-align: center;
		}

		.ahbn-avatar-large {
			width: 64px;
			height: 64px;
			border-radius: 50%;
			background: #0a192f;
			color: #ffffff;
			font-size: 24px;
			font-weight: 800;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			margin-bottom: 12px;
			box-shadow: 0 4px 12px rgba(10, 25, 47, 0.15);
		}

		.profile-name {
			margin: 0 0 4px;
			font-size: 17px;
			font-weight: 800;
			color: #0a192f;
		}

		.profile-email {
			margin: 0 0 14px;
			font-size: 12px;
			color: #64748b;
		}

		.profile-contact {
			display: flex;
			align-items: center;
			justify-content: center;
			gap: 6px;
			font-size: 12px;
			color: #334155;
			margin: 0 0 6px;
		}

		.profile-contact .dashicons {
			font-size: 14px;
			width: 14px;
			height: 14px;
			color: #94a3b8;
		}

		.profile-metric-box {
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 10px;
			padding: 12px;
			text-align: center;
			margin-top: 14px;
		}

		.metric-label {
			display: block;
			font-size: 10px;
			font-weight: 700;
			text-transform: uppercase;
			color: #64748b;
			margin-bottom: 2px;
		}

		.metric-val {
			font-family: 'JetBrains Mono', monospace;
			font-size: 18px;
			font-weight: 800;
			color: #059669;
		}

		/* Data Row Stack */
		.ahbn-data-list .data-row {
			padding: 12px 20px;
			border-bottom: 1px solid #f1f5f9;
			display: flex;
			justify-content: space-between;
			align-items: center;
			font-size: 13px;
		}

		.ahbn-data-list .data-row:last-child {
			border-bottom: none;
		}

		.data-label {
			color: #64748b;
			font-weight: 600;
		}

		.stay-banner {
			display: flex;
			justify-content: space-around;
			align-items: center;
			padding: 24px 20px;
			text-align: center;
		}

		.stay-date-box h4 {
			margin: 0 0 4px;
			font-size: 11px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #64748b;
		}

		.stay-date-val {
			font-size: 15px;
			font-weight: 800;
			color: #0a192f;
		}

		.stay-duration {
			font-family: 'JetBrains Mono', monospace;
			font-size: 12px;
			font-weight: 800;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			padding: 6px 14px;
			border-radius: 99px;
			color: #0a192f;
			display: inline-flex;
			align-items: center;
			gap: 6px;
		}

		.room-preview-body {
			padding: 20px;
			text-align: center;
		}

		.room-thumb-img {
			width: 100%;
			height: 140px;
			object-fit: cover;
			border-radius: 8px;
			margin-bottom: 12px;
		}

		.room-thumb-placeholder {
			height: 120px;
			background: #f1f5f9;
			border-radius: 8px;
			display: flex;
			align-items: center;
			justify-content: center;
			margin-bottom: 12px;
		}

		.room-thumb-placeholder .dashicons {
			font-size: 36px;
			width: 36px;
			height: 36px;
			color: #cbd5e1;
		}

		.room-preview-heading {
			margin: 0 0 6px;
			font-size: 15px;
			font-weight: 800;
			color: #0a192f;
		}

		.ahbn-badge-pill {
			display: inline-block;
			background: #eff6ff;
			color: #1e40af;
			border: 1px solid #bfdbfe;
			padding: 2px 10px;
			border-radius: 99px;
			font-size: 11px;
			font-weight: 700;
		}

		.ahbn-notes-box {
			width: 100%;
			font-size: 12px;
			color: #475569;
			background: #f8fafc;
			padding: 10px 12px;
			border-radius: 6px;
			border: 1px solid #e2e8f0;
			line-height: 1.5;
		}

		.total-footer {
			padding: 20px;
			background: #f8fafc;
			border-top: 1px solid #e2e8f0;
		}

		.total-footer-flex {
			display: flex;
			justify-content: space-between;
			align-items: flex-end;
		}

		.pricing-math {
			font-size: 11px;
			color: #64748b;
			margin-bottom: 2px;
		}

		.price-large {
			font-family: 'JetBrains Mono', monospace;
			font-size: 24px;
			font-weight: 800;
			color: #0a192f;
		}

		/* Filters & Search */
		.ahbn-crm-filter-bar {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 14px 18px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-search-box {
			display: flex;
			align-items: center;
			gap: 12px;
			flex-wrap: wrap;
		}

		.ahbn-field {
			height: 38px !important;
			width: 320px !important;
			max-width: 100%;
			border: 1px solid #cbd5e1 !important;
			border-radius: 8px !important;
			padding: 0 12px !important;
			font-size: 13px !important;
			background: #ffffff !important;
			color: #0f172a !important;
			box-shadow: none !important;
		}

		.ahbn-field:focus {
			border-color: #0a192f !important;
			box-shadow: 0 0 0 2px rgba(10, 25, 47, 0.1) !important;
		}

		.ahbn-clear-link {
			font-size: 12px;
			color: #ef4444;
			text-decoration: none;
			font-weight: 600;
		}

		.ahbn-clear-link:hover {
			text-decoration: underline;
		}

		/* Table Structure */
		.ahbn-table-container {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			overflow: hidden;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-table-header {
			padding: 16px 20px;
			background: #fafafa;
			border-bottom: 1px solid #f1f5f9;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}

		.ahbn-table-title {
			margin: 0;
			font-size: 13px;
			font-weight: 800;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #0a192f;
		}

		.ahbn-table-badge {
			font-family: 'JetBrains Mono', monospace;
			font-size: 11px;
			font-weight: 700;
			color: #64748b;
			background: #f1f5f9;
			padding: 3px 8px;
			border-radius: 4px;
		}

		.ahbn-crm-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 13px;
		}

		.ahbn-crm-table th {
			background: #f8fafc !important;
			padding: 12px 16px !important;
			font-weight: 700 !important;
			font-size: 11px !important;
			text-transform: uppercase !important;
			letter-spacing: 0.05em !important;
			color: #64748b !important;
			border-bottom: 1px solid #e2e8f0 !important;
		}

		.ahbn-crm-table td {
			padding: 14px 16px !important;
			vertical-align: middle !important;
			border-bottom: 1px solid #f1f5f9 !important;
		}

		.ahbn-guest-flex {
			display: flex;
			align-items: center;
			gap: 12px;
		}

		.ahbn-avatar-sm {
			width: 34px;
			height: 34px;
			border-radius: 50%;
			background: #f1f5f9;
			border: 1px solid #e2e8f0;
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 800;
			color: #0a192f;
			font-size: 12px;
		}

		.ahbn-guest-name {
			display: block;
			color: #0f172a;
			font-size: 13px;
		}

		.ahbn-wp-tag {
			display: inline-block;
			font-size: 10px;
			font-weight: 600;
			color: #059669;
			background: #ecfdf5;
			border: 1px solid #a7f3d0;
			padding: 1px 6px;
			border-radius: 4px;
			margin-top: 2px;
		}

		.ahbn-count-badge {
			font-family: 'JetBrains Mono', monospace;
			background: #f1f5f9;
			color: #0a192f;
			font-weight: 700;
			font-size: 11px;
			padding: 3px 8px;
			border-radius: 4px;
			border: 1px solid #e2e8f0;
		}

		.ahbn-amount-highlight {
			font-family: 'JetBrains Mono', monospace;
			font-size: 13px;
			font-weight: 700;
			color: #059669;
		}

		.ahbn-code-badge {
			font-family: 'JetBrains Mono', monospace;
			background: #f8fafc;
			color: #0a192f;
			font-weight: 700;
			font-size: 11px;
			padding: 3px 8px;
			border-radius: 4px;
			border: 1px solid #e2e8f0;
		}

		.ahbn-status-pill {
			display: inline-block;
			padding: 3px 10px;
			border-radius: 9999px;
			font-size: 11px;
			font-weight: 700;
		}

		.status-confirmed {
			background: #ecfdf5;
			color: #065f46;
			border: 1px solid #a7f3d0;
		}

		.ahbn-pay-badge {
			font-size: 11px;
			font-weight: 700;
		}

		.ahbn-pay-badge.is-paid {
			color: #059669;
		}

		.ahbn-pay-badge.is-unpaid {
			color: #d97706;
		}

		.ahbn-quick-btn {
			width: 28px;
			height: 28px;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 6px;
			color: #475569;
			text-decoration: none;
			transition: all 0.15s ease;
		}

		.ahbn-quick-btn .dashicons {
			font-size: 15px;
			width: 15px;
			height: 15px;
		}

		.ahbn-quick-btn:hover {
			background: #0a192f;
			color: #ffffff;
			border-color: #0a192f;
		}

		.ahbn-btn-portfolio {
			display: inline-flex;
			align-items: center;
			gap: 4px;
			padding: 5px 10px;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 6px;
			color: #334155;
			font-size: 12px;
			font-weight: 700;
			text-decoration: none;
			transition: all 0.15s ease;
		}

		.ahbn-btn-portfolio .dashicons {
			font-size: 14px;
			width: 14px;
			height: 14px;
			color: #c5a880;
		}

		.ahbn-btn-portfolio:hover {
			background: #0a192f;
			border-color: #0a192f;
			color: #ffffff;
		}

		.ahbn-btn-portfolio:hover .dashicons {
			color: #c5a880;
		}

		@media (max-width: 1024px) {
			.ahbn-crm-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>
	<?php
}