<?php
/**
 * Room Deletion Handler.
 *
 * Refactored for custom relational database tables:
 * {$wpdb->prefix}ahbn_rooms
 * Supports permanent SQL deletion or soft-delete status flags,
 * booking relationship safety checks, and transient invalidation.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handle Room Deletion via SQL.
 *
 * Hooked to admin_post_ahbn_delete_room.
 *
 * @return void
 */
function ahbn_handle_room_deletion() {
    // 1. Verify User Capabilities.
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'awesome-hotel-booking' ) );
    }

    global $wpdb;
    $table_rooms    = $wpdb->prefix . 'ahbn_rooms';
    $table_bookings = $wpdb->prefix . 'ahbn_bookings';

    // 2. Validate & Sanitize Input.
    // phpcs:disable WordPress.Security.NonceVerification.Recommended
    $ahbn_room_id = isset( $_GET['room_id'] ) ? absint( wp_unslash( $_GET['room_id'] ) ) : ( isset( $_GET['room'] ) ? absint( wp_unslash( $_GET['room'] ) ) : 0 );
    $ahbn_nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
    $force_delete = isset( $_GET['force'] ) && '1' === $_GET['force'];
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    // 3. Security Nonce Verification (supports both action variants).
    $is_valid_nonce = wp_verify_nonce( $ahbn_nonce, 'ahbn_room_action' ) || wp_verify_nonce( $ahbn_nonce, 'ahbn_delete_room_' . $ahbn_room_id );

    if ( ! $ahbn_room_id || ! $is_valid_nonce ) {
        wp_die( esc_html__( 'Security verification failed. Please return to room inventory and try again.', 'awesome-hotel-booking' ) );
    }

    // 4. Verify Record Exists in Custom SQL Table.
    // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $existing_room = $wpdb->get_row(
        $wpdb->prepare( "SELECT id, title, room_number FROM {$table_rooms} WHERE id = %d", $ahbn_room_id )
    );

    if ( ! $existing_room ) {
        wp_die( esc_html__( 'The specified room record does not exist in inventory.', 'awesome-hotel-booking' ) );
    }

    // 5. Booking Dependency Guard Check.
    // Prevent deleting a room if there are active/future bookings attached.
    $active_bookings = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_bookings} WHERE room_id = %d AND status IN ('confirmed', 'checked_in')",
            $ahbn_room_id
        )
    );

    if ( $active_bookings > 0 && ! $force_delete ) {
        $redirect_error_url = add_query_arg(
            array(
                'page'    => 'awesome_hotel_booking',
                'tab'     => 'rooms',
                'sub'     => 'all',
                'msg'     => 'has_bookings',
                'room_id' => $ahbn_room_id,
            ),
            admin_url( 'admin.php' )
        );
        wp_safe_redirect( $redirect_error_url );
        exit;
    }

    // 6. Execute SQL Deletion.
    $deleted = $wpdb->delete(
        $table_rooms,
        array( 'id' => $ahbn_room_id ),
        array( '%d' )
    );
    // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

    // 7. Invalidate Caches & Transients.
    if ( function_exists( 'ahbn_clear_rooms_cache' ) ) {
        ahbn_clear_rooms_cache();
    }
    delete_transient( 'ahbn_booking_count_' . $ahbn_room_id );
    delete_transient( 'ahbn_dashboard_stats' );

    // 8. Safe Redirect back to Inventory List with Notice.
    $redirect_url = add_query_arg(
        array(
            'page' => 'awesome_hotel_booking',
            'tab'  => 'rooms',
            'sub'  => 'all',
            'msg'  => $deleted ? 'deleted' : 'delete_failed',
        ),
        admin_url( 'admin.php' )
    );

    wp_safe_redirect( $redirect_url );
    exit;
}
add_action( 'admin_post_ahbn_delete_room', 'ahbn_handle_room_deletion' );

/**
 * Display Admin Notice when a room is removed or blocked.
 *
 * @return void
 */
function ahbn_room_delete_admin_notice() {
    // phpcs:disable WordPress.Security.NonceVerification.Recommended
    if ( ! isset( $_GET['page'], $_GET['tab'], $_GET['msg'] ) ) {
        return;
    }

    if ( 'awesome_hotel_booking' !== $_GET['page'] || 'rooms' !== $_GET['tab'] ) {
        return;
    }

    $msg = sanitize_key( wp_unslash( $_GET['msg'] ) );
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    if ( 'deleted' === $msg ) {
        ?>
        <div class="notice notice-success is-dismissible" style="border-left-color: #059669;">
            <p>
                <strong><?php esc_html_e( 'Success:', 'awesome-hotel-booking' ); ?></strong>
                <?php esc_html_e( 'Room record has been permanently removed from inventory.', 'awesome-hotel-booking' ); ?>
            </p>
        </div>
        <?php
    } elseif ( 'has_bookings' === $msg ) {
        ?>
        <div class="notice notice-error is-dismissible" style="border-left-color: #e11d48;">
            <p>
                <strong><?php esc_html_e( 'Cannot Delete Suite:', 'awesome-hotel-booking' ); ?></strong>
                <?php esc_html_e( 'This room cannot be removed because it has active or confirmed guest reservations assigned to it. Please reassign or cancel those bookings first.', 'awesome-hotel-booking' ); ?>
            </p>
        </div>
        <?php
    } elseif ( 'delete_failed' === $msg ) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>
                <strong><?php esc_html_e( 'Error:', 'awesome-hotel-booking' ); ?></strong>
                <?php esc_html_e( 'Could not delete the room from the database. Please try again.', 'awesome-hotel-booking' ); ?>
            </p>
        </div>
        <?php
    }
}
add_action( 'admin_notices', 'ahbn_room_delete_admin_notice' );