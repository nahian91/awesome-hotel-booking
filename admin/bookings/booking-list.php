<?php
/**
 * Bookings List View.
 *
 * Rewritten for custom SQL database tables:
 * {$wpdb->prefix}ahbn_bookings joined with {$wpdb->prefix}ahbn_rooms.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$table_bookings = $wpdb->prefix . 'ahbn_bookings';
$table_rooms    = $wpdb->prefix . 'ahbn_rooms';

// 1. Sanitize Search, Filter, and Pagination Parameters.
// phpcs:disable WordPress.Security.NonceVerification.Recommended
$ahbn_search_query  = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$ahbn_status_filter = isset( $_GET['status_filter'] ) ? sanitize_key( wp_unslash( $_GET['status_filter'] ) ) : '';
$ahbn_paged         = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
// phpcs:enable WordPress.Security.NonceVerification.Recommended

$ahbn_per_page = 20;
$ahbn_offset   = ( $ahbn_paged - 1 ) * $ahbn_per_page;

$where_clauses = array();
$query_params  = array();

if ( ! empty( $ahbn_status_filter ) ) {
	$where_clauses[] = 'b.status = %s';
	$query_params[]  = $ahbn_status_filter;
}

if ( ! empty( $ahbn_search_query ) ) {
	if ( is_numeric( $ahbn_search_query ) ) {
		$where_clauses[] = '(b.id = %d OR b.booking_code LIKE %s)';
		$query_params[]  = absint( $ahbn_search_query );
		$query_params[]  = '%' . $wpdb->esc_like( $ahbn_search_query ) . '%';
	} else {
		$like_query      = '%' . $wpdb->esc_like( $ahbn_search_query ) . '%';
		$where_clauses[] = '(b.booking_code LIKE %s OR b.guest_name LIKE %s OR b.guest_email LIKE %s OR b.guest_phone LIKE %s OR r.title LIKE %s OR r.room_number LIKE %s)';
		$query_params[]  = $like_query;
		$query_params[]  = $like_query;
		$query_params[]  = $like_query;
		$query_params[]  = $like_query;
		$query_params[]  = $like_query;
		$query_params[]  = $like_query;
	}
}

$where_sql = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$count_query = "SELECT COUNT(*) FROM {$table_bookings} b LEFT JOIN {$table_rooms} r ON b.room_id = r.id {$where_sql}";

if ( ! empty( $query_params ) ) {
	$total_records = (int) $wpdb->get_var( $wpdb->prepare( $count_query, $query_params ) );
} else {
	$total_records = (int) $wpdb->get_var( $count_query );
}

$ahbn_total_pages = ceil( $total_records / $ahbn_per_page );

$fetch_query = "SELECT 
					b.*,
					r.room_number,
					r.title AS room_title,
					r.room_type,
					r.price_per_night AS base_room_price
				FROM {$table_bookings} b
				LEFT JOIN {$table_rooms} r ON b.room_id = r.id
				{$where_sql}
				ORDER BY b.checkin_date DESC, b.id DESC
				LIMIT %d OFFSET %d";

$fetch_params   = $query_params;
$fetch_params[] = $ahbn_per_page;
$fetch_params[] = $ahbn_offset;

$ahbn_bookings_list = $wpdb->get_results( $wpdb->prepare( $fetch_query, $fetch_params ) );
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

$ahbn_base_url = admin_url( 'admin.php?page=awesome_hotel_booking&tab=bookings' );
?>

<div class="ahbn-bookings-list-content">
	<div class="ahbn-bookings-header">
		<div>
			<h1 class="ahbn-bookings-title"><?php esc_html_e( 'Room Bookings', 'awesome-hotel-booking' ); ?></h1>
			<p class="ahbn-bookings-subtitle"><?php esc_html_e( 'Inspect, filter, and manage property reservations.', 'awesome-hotel-booking' ); ?></p>
		</div>
	</div>

	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="awesome_hotel_booking">
		<input type="hidden" name="tab" value="bookings">

		<div class="ahbn-filter-bar">
			<input 
				type="text" 
				name="s" 
				class="ahbn-search-input" 
				placeholder="<?php esc_attr_e( 'Search guest, phone, email, or folio #...', 'awesome-hotel-booking' ); ?>" 
				value="<?php echo esc_attr( $ahbn_search_query ); ?>"
			>
			<select name="status_filter" class="ahbn-filter-select">
				<option value=""><?php esc_html_e( 'All Statuses', 'awesome-hotel-booking' ); ?></option>
				<option value="pending" <?php selected( $ahbn_status_filter, 'pending' ); ?>><?php esc_html_e( 'Pending Approval', 'awesome-hotel-booking' ); ?></option>
				<option value="confirmed" <?php selected( $ahbn_status_filter, 'confirmed' ); ?>><?php esc_html_e( 'Confirmed', 'awesome-hotel-booking' ); ?></option>
				<option value="checked_in" <?php selected( $ahbn_status_filter, 'checked_in' ); ?>><?php esc_html_e( 'In-House (Checked-In)', 'awesome-hotel-booking' ); ?></option>
				<option value="checked_out" <?php selected( $ahbn_status_filter, 'checked_out' ); ?>><?php esc_html_e( 'Checked Out', 'awesome-hotel-booking' ); ?></option>
				<option value="cancelled" <?php selected( $ahbn_status_filter, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'awesome-hotel-booking' ); ?></option>
			</select>
			<button type="submit" class="button btn-filter"><?php esc_html_e( 'Apply Filter', 'awesome-hotel-booking' ); ?></button>
			<?php if ( ! empty( $ahbn_search_query ) || ! empty( $ahbn_status_filter ) ) : ?>
				<a href="<?php echo esc_url( $ahbn_base_url ); ?>" class="ahbn-clear-filters">
					<?php esc_html_e( 'Clear Filter', 'awesome-hotel-booking' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</form>

	<div class="ahbn-table-container">
		<table class="ahbn-main-table widefat striped">
			<thead>
				<tr>
					<th style="width: 100px;"><?php esc_html_e( 'Folio Code', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Guest Particulars', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Assigned Suite', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Check-in / Check-out', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Total Amount', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Payment', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Status', 'awesome-hotel-booking' ); ?></th>
					<th style="text-align:right; width: 130px;"><?php esc_html_e( 'Actions', 'awesome-hotel-booking' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( ! empty( $ahbn_bookings_list ) ) :
					foreach ( $ahbn_bookings_list as $row ) :
						$ahbn_bid         = (int) $row->id;
						$customer_display = ! empty( $row->guest_name ) ? $row->guest_name : __( 'Guest User', 'awesome-hotel-booking' );
						$email_display    = ! empty( $row->guest_email ) ? $row->guest_email : __( 'No email provided', 'awesome-hotel-booking' );
						$phone_display    = ! empty( $row->guest_phone ) ? $row->guest_phone : '';
						$status_display   = ! empty( $row->status ) ? sanitize_key( $row->status ) : 'pending';
						$total_display    = (float) $row->total_amount;
						$pay_status       = ! empty( $row->payment_status ) ? sanitize_key( $row->payment_status ) : 'unpaid';

						$suite_label = ! empty( $row->room_title ) ? $row->room_title : __( 'Unassigned', 'awesome-hotel-booking' );
						if ( ! empty( $row->room_number ) ) {
							$suite_label = '#' . $row->room_number . ' ' . $suite_label;
						}

						$ahbn_view_url = add_query_arg( array( 'action' => 'view', 'booking_id' => $ahbn_bid ), $ahbn_base_url );
						$ahbn_edit_url = add_query_arg( array( 'action' => 'edit', 'booking_id' => $ahbn_bid ), $ahbn_base_url );
						$ahbn_del_url  = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'booking_id' => $ahbn_bid ), $ahbn_base_url ), 'ahbn_booking_action' );

						$badge_class = 'status-pending';
						$badge_text  = __( 'Pending', 'awesome-hotel-booking' );

						if ( 'confirmed' === $status_display ) {
							$badge_class = 'status-confirmed';
							$badge_text  = __( 'Confirmed', 'awesome-hotel-booking' );
						} elseif ( 'checked_in' === $status_display ) {
							$badge_class = 'status-inhouse';
							$badge_text  = __( 'In-House', 'awesome-hotel-booking' );
						} elseif ( 'checked_out' === $status_display ) {
							$badge_class = 'status-checkedout';
							$badge_text  = __( 'Checked Out', 'awesome-hotel-booking' );
						} elseif ( 'cancelled' === $status_display ) {
							$badge_class = 'status-cancelled';
							$badge_text  = __( 'Cancelled', 'awesome-hotel-booking' );
						}
						?>
						<tr>
							<td>
								<span class="ahbn-code-badge">
									<?php echo esc_html( ! empty( $row->booking_code ) ? $row->booking_code : '#' . $ahbn_bid ); ?>
								</span>
							</td>
							<td>
								<div class="ahbn-guest-cell">
									<strong><?php echo esc_html( $customer_display ); ?></strong>
									<span><?php echo esc_html( ! empty( $phone_display ) ? $phone_display : $email_display ); ?></span>
								</div>
							</td>
							<td>
								<div class="ahbn-suite-cell">
									<strong><?php echo esc_html( $suite_label ); ?></strong>
									<span><?php echo esc_html( ! empty( $row->room_type ) ? $row->room_type : 'Standard' ); ?></span>
								</div>
							</td>
							<td>
								<div class="ahbn-dates-cell">
									<strong>
										<?php echo esc_html( ! empty( $row->checkin_date ) ? wp_date( 'M j, Y', strtotime( $row->checkin_date ) ) : '—' ); ?>
										<span aria-hidden="true">&rarr;</span>
										<?php echo esc_html( ! empty( $row->checkout_date ) ? wp_date( 'M j, Y', strtotime( $row->checkout_date ) ) : '—' ); ?>
									</strong>
									<span>
										<?php
										printf(
											/* translators: %d: number of nights */
											esc_html( _n( '%d Night', '%d Nights', (int) $row->nights, 'awesome-hotel-booking' ) ),
											absint( $row->nights )
										);
										?>
									</span>
								</div>
							</td>
							<td>
								<strong class="ahbn-price-text">
									<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $total_display ) : (string) number_format_i18n( $total_display, 2 ) ); ?>
								</strong>
							</td>
							<td>
								<?php if ( 'paid' === $pay_status ) : ?>
									<span class="ahbn-pay-badge is-paid">&#10003; <?php esc_html_e( 'Settled', 'awesome-hotel-booking' ); ?></span>
								<?php else : ?>
									<span class="ahbn-pay-badge is-unpaid">&#9679; <?php esc_html_e( 'Unpaid', 'awesome-hotel-booking' ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<span class="ahbn-status-pill <?php echo esc_attr( $badge_class ); ?>">
									<?php echo esc_html( $badge_text ); ?>
								</span>
							</td>
							<td style="text-align:right;">
								<div class="ahbn-action-btns">
									<a href="<?php echo esc_url( $ahbn_view_url ); ?>" class="ahbn-quick-btn" title="<?php esc_attr_e( 'View Folio', 'awesome-hotel-booking' ); ?>">
										<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
									</a>
									<a href="<?php echo esc_url( $ahbn_edit_url ); ?>" class="ahbn-quick-btn" title="<?php esc_attr_e( 'Edit Reservation', 'awesome-hotel-booking' ); ?>">
										<span class="dashicons dashicons-edit" aria-hidden="true"></span>
									</a>
									<a 
										href="<?php echo esc_url( $ahbn_del_url ); ?>" 
										class="ahbn-quick-btn is-delete" 
										title="<?php esc_attr_e( 'Delete Reservation', 'awesome-hotel-booking' ); ?>"
										onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to permanently delete this reservation?', 'awesome-hotel-booking' ) ); ?>');"
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
						<td colspan="8" style="text-align:center; padding:50px; color:#94a3b8;">
							<span class="dashicons dashicons-calendar-alt" style="font-size:36px; width:36px; height:36px; color:#cbd5e1;" aria-hidden="true"></span>
							<p style="margin: 8px 0 0;"><?php esc_html_e( 'No reservations match your filter criteria.', 'awesome-hotel-booking' ); ?></p>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<?php if ( $ahbn_total_pages > 1 ) : ?>
		<div class="tablenav" style="margin-top: 15px;">
			<div class="tablenav-pages">
				<?php
				$pagination_args = array(
					'base'      => add_query_arg( 'paged', '%#%' ),
					'format'    => '',
					'prev_text' => __( '&laquo; Previous', 'awesome-hotel-booking' ),
					'next_text' => __( 'Next &raquo;', 'awesome-hotel-booking' ),
					'total'     => $ahbn_total_pages,
					'current'   => $ahbn_paged,
				);
				echo wp_kses_post( paginate_links( $pagination_args ) );
				?>
			</div>
		</div>
	<?php endif; ?>
</div>

<style>
	.ahbn-bookings-list-content { display: flex; flex-direction: column; gap: 18px; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
	.ahbn-bookings-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; }
	.ahbn-bookings-title { font-family: 'Playfair Display', Georgia, serif; font-size: 24px; font-weight: 700; color: #0a192f; margin: 0 0 4px 0; }
	.ahbn-bookings-subtitle { margin: 0; font-size: 13px; color: #64748b; }
	.ahbn-btn-solid { background: #0a192f !important; color: #ffffff !important; border: 1px solid #0a192f !important; padding: 9px 16px !important; border-radius: 8px !important; font-size: 13px !important; font-weight: 700 !important; text-decoration: none !important; display: inline-flex !important; align-items: center !important; gap: 6px !important; cursor: pointer; transition: all 0.2s ease !important; }
	.ahbn-btn-solid:hover { background: #c5a880 !important; border-color: #c5a880 !important; color: #0a192f !important; }
	.ahbn-filter-bar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; background: #ffffff; border: 1px solid #e2e8f0; padding: 12px 16px; border-radius: 12px; }
	.ahbn-search-input { height: 38px; width: 320px; max-width: 100%; border: 1px solid #e2e8f0 !important; border-radius: 8px !important; padding: 0 12px !important; font-size: 13px !important; }
	.ahbn-filter-select { height: 38px; border: 1px solid #e2e8f0 !important; border-radius: 8px !important; font-size: 13px !important; color: #334155; }
	.btn-filter { height: 38px !important; padding: 0 16px !important; border-radius: 8px !important; font-weight: 700 !important; color: #0a192f !important; }
	.ahbn-clear-filters { font-size: 12px; color: #ef4444; text-decoration: none; font-weight: 600; }
	.ahbn-clear-filters:hover { text-decoration: underline; }
	.ahbn-table-container { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04); }
	.ahbn-main-table { width: 100%; border-collapse: collapse; font-size: 13px; }
	.ahbn-main-table th { background: #f8fafc !important; padding: 12px 16px !important; font-weight: 700 !important; font-size: 11px !important; text-transform: uppercase !important; letter-spacing: 0.05em !important; color: #64748b !important; border-bottom: 1px solid #e2e8f0 !important; }
	.ahbn-main-table td { padding: 14px 16px !important; vertical-align: middle !important; border-bottom: 1px solid #f1f5f9 !important; }
	.ahbn-code-badge { font-family: 'JetBrains Mono', monospace; background: #f1f5f9; color: #0a192f; font-weight: 700; font-size: 11px; padding: 3px 8px; border-radius: 4px; border: 1px solid #e2e8f0; }
	.ahbn-guest-cell strong, .ahbn-suite-cell strong, .ahbn-dates-cell strong { display: block; color: #0f172a; }
	.ahbn-guest-cell span, .ahbn-suite-cell span, .ahbn-dates-cell span { display: block; font-size: 11px; color: #64748b; margin-top: 2px; }
	.ahbn-price-text { font-family: 'JetBrains Mono', monospace; color: #0a192f; font-size: 14px; }
	.ahbn-pay-badge { font-size: 11px; font-weight: 700; }
	.ahbn-pay-badge.is-paid { color: #059669; }
	.ahbn-pay-badge.is-unpaid { color: #d97706; }
	.ahbn-status-pill { display: inline-block; padding: 3px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; letter-spacing: 0.02em; }
	.status-confirmed  { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
	.status-inhouse    { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
	.status-checkedout { background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
	.status-pending    { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
	.status-cancelled  { background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; }
	.ahbn-action-btns { display: inline-flex; gap: 5px; }
	.ahbn-quick-btn { width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; color: #475569; cursor: pointer; text-decoration: none; transition: all 0.15s ease; }
	.ahbn-quick-btn .dashicons { font-size: 15px; width: 15px; height: 15px; }
	.ahbn-quick-btn:hover { background: #0a192f; color: #ffffff; border-color: #0a192f; }
	.ahbn-quick-btn.is-delete:hover { background: #e11d48; border-color: #e11d48; color: #ffffff; }
</style>