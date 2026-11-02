<?php
/**
 * Bookings Sub-View Dispatcher with Top Sub-Tabs.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$action     = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$booking_id = isset( $_GET['booking_id'] ) ? absint( wp_unslash( $_GET['booking_id'] ) ) : 0;

// Base URLs for top sub-tabs.
$base_bookings_url = admin_url( 'admin.php?page=awesome_hotel_booking&tab=bookings' );
$add_booking_url   = add_query_arg( 'action', 'add', $base_bookings_url );

// Precise active tab detection.
$is_add_active  = ( 'add' === $action );
$is_edit_active = ( 'edit' === $action );
$is_view_active = ( 'view' === $action );
$is_list_active = ( empty( $action ) || 'delete' === $action );
?>

<div class="ahbn-subtabs-nav-bar">
	<a href="<?php echo esc_url( $base_bookings_url ); ?>" class="ahbn-subtab-link <?php echo $is_list_active ? 'is-active' : ''; ?>">
		<span class="dashicons dashicons-list-view" aria-hidden="true"></span>
		<span><?php esc_html_e( 'All Bookings', 'awesome-hotel-booking' ); ?></span>
	</a>

	<a href="<?php echo esc_url( $add_booking_url ); ?>" class="ahbn-subtab-link <?php echo $is_add_active ? 'is-active' : ''; ?>">
		<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
		<span><?php esc_html_e( 'Add Booking', 'awesome-hotel-booking' ); ?></span>
	</a>

	<?php if ( $is_edit_active && $booking_id > 0 ) : ?>
		<span class="ahbn-subtab-link is-active is-context-tab">
			<span class="dashicons dashicons-edit" aria-hidden="true"></span>
			<span><?php echo esc_html( sprintf( /* translators: %d: booking ID */ __( 'Edit Booking #%d', 'awesome-hotel-booking' ), $booking_id ) ); ?></span>
		</span>
	<?php endif; ?>

	<?php if ( $is_view_active && $booking_id > 0 ) : ?>
		<span class="ahbn-subtab-link is-active is-context-tab">
			<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
			<span><?php echo esc_html( sprintf( /* translators: %d: booking ID */ __( 'Folio #%d', 'awesome-hotel-booking' ), $booking_id ) ); ?></span>
		</span>
	<?php endif; ?>
</div>

<style>
	.ahbn-subtabs-nav-bar {
		display: inline-flex;
		align-items: center;
		gap: 6px;
		background: #f8fafc;
		border: 1px solid #e2e8f0;
		padding: 5px;
		border-radius: 12px;
		margin-bottom: 22px;
	}

	.ahbn-subtab-link {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		padding: 8px 16px;
		border-radius: 8px;
		font-size: 13px;
		font-weight: 700;
		color: #64748b;
		text-decoration: none;
		border: 1px solid transparent;
		transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
		cursor: pointer;
	}

	.ahbn-subtab-link .dashicons {
		font-size: 16px;
		width: 16px;
		height: 16px;
		color: #94a3b8;
		transition: color 0.2s ease;
	}

	.ahbn-subtab-link:hover {
		color: #0a192f;
		background: #ffffff;
		border-color: #e2e8f0;
	}

	.ahbn-subtab-link:hover .dashicons {
		color: #c5a880;
	}

	.ahbn-subtab-link.is-active {
		background: #0a192f;
		color: #ffffff !important;
		border-color: #040914;
		box-shadow: 0 4px 12px rgba(10, 25, 47, 0.15);
	}

	.ahbn-subtab-link.is-active .dashicons {
		color: #c5a880 !important;
	}

	.ahbn-subtab-link.is-context-tab {
		cursor: default;
	}

	@media print {
		.ahbn-subtabs-nav-bar {
			display: none !important;
		}
	}
</style>

<?php
echo '<div class="ahbn-bookings-wrapper">';

switch ( $action ) {
	case 'add':
		$booking_id = 0;
		$file       = AHBN_PATH . 'admin/bookings/booking-add-edit.php';
		if ( file_exists( $file ) ) {
			require $file;
		}
		break;

	case 'edit':
		if ( $booking_id > 0 ) {
			$file = AHBN_PATH . 'admin/bookings/booking-add-edit.php';
			if ( file_exists( $file ) ) {
				require $file;
			}
		} else {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Invalid booking ID for editing.', 'awesome-hotel-booking' ) . '</p></div>';
			$file = AHBN_PATH . 'admin/bookings/booking-list.php';
			if ( file_exists( $file ) ) {
				require $file;
			}
		}
		break;

	case 'view':
		if ( $booking_id > 0 ) {
			$file = AHBN_PATH . 'admin/bookings/booking-view.php';
			if ( file_exists( $file ) ) {
				require $file;
			}
		} else {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Please select a booking to view.', 'awesome-hotel-booking' ) . '</p></div>';
			$file = AHBN_PATH . 'admin/bookings/booking-list.php';
			if ( file_exists( $file ) ) {
				require $file;
			}
		}
		break;

	case 'print':
		if ( $booking_id > 0 ) {
			$file = AHBN_PATH . 'admin/bookings/booking-print.php';
			if ( file_exists( $file ) ) {
				require $file;
			}
		}
		break;

	case 'delete':
	default:
		$file = AHBN_PATH . 'admin/bookings/booking-list.php';
		if ( file_exists( $file ) ) {
			require $file;
		}
		break;
}

echo '</div>';