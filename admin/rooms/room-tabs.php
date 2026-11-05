<?php
/**
 * Room Management Tab Controller & Sub-navigation.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render the Room Management Tab and route sub-actions.
 *
 * @return void
 */
function ahbn_rooms_tab() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
    }

    global $wpdb;
    $table_rooms = $wpdb->prefix . 'ahbn_rooms';

    // 1. Capture State.
    // phpcs:disable WordPress.Security.NonceVerification.Recommended
    $ahbn_sub     = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'all';
    $ahbn_room_id = isset( $_GET['room_id'] ) ? absint( wp_unslash( $_GET['room_id'] ) ) : 0;
    $action       = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    $page_slug = 'awesome_hotel_booking';
    $base_url  = admin_url( 'admin.php?page=' . rawurlencode( $page_slug ) . '&tab=rooms' );

    // 2. Buffer Notifications to Render Above Subtabs.
    $notices_html = '';

    // Handle Permanent Room Deletion via SQL directly from the tabs router if requested.
    if ( 'delete' === $action && $ahbn_room_id > 0 ) {
        $nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';

        if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'ahbn_room_action' ) ) {
            $notices_html .= '<div class="ahbn-alert ahbn-alert-error"><div class="ahbn-alert-content"><span class="dashicons dashicons-warning" aria-hidden="true"></span><span>' . esc_html__( 'Security check failed. Please refresh and try again.', 'awesome-hotel-booking' ) . '</span></div><button type="button" class="ahbn-alert-close" aria-label="' . esc_attr__( 'Dismiss alert', 'awesome-hotel-booking' ) . '">&times;</button></div>';
        } else {
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $deleted = $wpdb->delete(
                $table_rooms,
                array( 'id' => $ahbn_room_id ),
                array( '%d' )
            );
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

            if ( $deleted ) {
                if ( function_exists( 'ahbn_clear_rooms_cache' ) ) {
                    ahbn_clear_rooms_cache();
                }
                delete_transient( 'ahbn_booking_count_' . $ahbn_room_id );
                delete_transient( 'ahbn_dashboard_stats' );

                $notices_html .= sprintf(
                    '<div class="ahbn-alert ahbn-alert-success"><div class="ahbn-alert-content"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><span>%s</span></div><button type="button" class="ahbn-alert-close" aria-label="%s">&times;</button></div>',
                    sprintf(
                        /* translators: %d: Room Record ID */
                        esc_html__( 'Room record #%d has been permanently removed from inventory.', 'awesome-hotel-booking' ),
                        absint( $ahbn_room_id )
                    ),
                    esc_attr__( 'Dismiss alert', 'awesome-hotel-booking' )
                );
            } else {
                $notices_html .= '<div class="ahbn-alert ahbn-alert-error"><div class="ahbn-alert-content"><span class="dashicons dashicons-warning" aria-hidden="true"></span><span>' . esc_html__( 'Database error: Unable to remove room record.', 'awesome-hotel-booking' ) . '</span></div><button type="button" class="ahbn-alert-close" aria-label="' . esc_attr__( 'Dismiss alert', 'awesome-hotel-booking' ) . '">&times;</button></div>';
            }

            $ahbn_sub     = 'all';
            $ahbn_room_id = 0;
        }
    }

    // Catch Updated Notice Flag from Form Redirection (if present).
    // phpcs:disable WordPress.Security.NonceVerification.Recommended
    if ( isset( $_GET['updated'] ) && empty( $notices_html ) ) {
        $updated_rid   = isset( $_GET['room_id'] ) ? absint( wp_unslash( $_GET['room_id'] ) ) : 0;
        $notices_html .= sprintf(
            '<div class="ahbn-alert ahbn-alert-success"><div class="ahbn-alert-content"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><span>%s</span></div><button type="button" class="ahbn-alert-close" aria-label="%s">&times;</button></div>',
            $updated_rid > 0
                ? sprintf(
                    /* translators: %d: Room ID */
                    esc_html__( 'Room #%d has been saved successfully.', 'awesome-hotel-booking' ),
                    absint( $updated_rid )
                )
                : esc_html__( 'Room details successfully updated.', 'awesome-hotel-booking' ),
            esc_attr__( 'Dismiss alert', 'awesome-hotel-booking' )
        );
    }
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    // 3. Define Tab Navigation Items.
    $tabs = array(
        'all'    => array(
            'label' => __( 'All Rooms', 'awesome-hotel-booking' ),
            'icon'  => 'dashicons-list-view',
        ),
        'add'    => array(
            'label' => __( 'Add New Room', 'awesome-hotel-booking' ),
            'icon'  => 'dashicons-plus-alt2',
        ),
        'extras' => array(
            'label' => __( 'Extras', 'awesome-hotel-booking' ),
            'icon'  => 'dashicons-admin-plugins',
        ),
    );

    $is_edit_active = ( 'edit' === $ahbn_sub && $ahbn_room_id > 0 );
    $is_view_active = ( 'view' === $ahbn_sub && $ahbn_room_id > 0 );
    ?>
    <div class="ahbn-rooms-container">
        
        <!-- Executive Brand Header -->
        <header class="ahbn-rooms-header">
            <div>
                <h1 class="ahbn-rooms-title"><?php esc_html_e( 'Room Management', 'awesome-hotel-booking' ); ?></h1>
                <p class="ahbn-rooms-subtitle">
                    <?php esc_html_e( 'Manage your luxury inventory, pricing schedules, and amenities.', 'awesome-hotel-booking' ); ?>
                </p>
            </div>

            <?php if ( 'add' !== $ahbn_sub && ! $is_edit_active && ! $is_view_active ) : ?>
                <?php $add_url = add_query_arg( 'sub', 'add', $base_url ); ?>
                <a href="<?php echo esc_url( $add_url ); ?>" class="ahbn-btn-primary">
                    <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
                    <?php esc_html_e( 'Add New Room', 'awesome-hotel-booking' ); ?>
                </a>
            <?php endif; ?>
        </header>

        <!-- 1. Dedicated Top Notification Anchor Above Subtabs -->
        <div class="ahbn-top-notices-area">
            <?php
            if ( ! empty( $notices_html ) ) {
                echo wp_kses_post( $notices_html );
            }
            ?>
        </div>

        <!-- 2. Sub-Navigation Pill Bar -->
        <div class="ahbn-subtabs-nav-bar">
            <?php
            foreach ( $tabs as $k => $data ) :
                $sub_url   = add_query_arg( 'sub', $k, $base_url );
                $is_active = ( $ahbn_sub === $k || ( 'all' === $k && in_array( $ahbn_sub, array( 'edit', 'view' ), true ) && ! $is_edit_active && ! $is_view_active ) );
                ?>
                <a href="<?php echo esc_url( $sub_url ); ?>" class="ahbn-subtab-link <?php echo $is_active ? 'is-active' : ''; ?>">
                    <span class="dashicons <?php echo esc_attr( $data['icon'] ); ?>" aria-hidden="true"></span>
                    <span><?php echo esc_html( $data['label'] ); ?></span>
                </a>
            <?php endforeach; ?>

            <?php if ( $is_edit_active ) : ?>
                <span class="ahbn-subtab-link is-active is-context-tab">
                    <span class="dashicons dashicons-edit" aria-hidden="true"></span>
                    <span><?php echo esc_html( sprintf( /* translators: %d: Room ID */ __( 'Edit Room #%d', 'awesome-hotel-booking' ), $ahbn_room_id ) ); ?></span>
                </span>
            <?php elseif ( $is_view_active ) : ?>
                <span class="ahbn-subtab-link is-active is-context-tab">
                    <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                    <span><?php echo esc_html( sprintf( /* translators: %d: Room ID */ __( 'Room Details #%d', 'awesome-hotel-booking' ), $ahbn_room_id ) ); ?></span>
                </span>
            <?php endif; ?>
        </div>

        <!-- 3. Main Content Router -->
        <main class="ahbn-rooms-canvas">
            <?php
            switch ( $ahbn_sub ) {
                case 'add':
                    if ( function_exists( 'ahbn_add_edit_room_tab' ) ) {
                        ahbn_add_edit_room_tab();
                    } else {
                        echo '<div class="ahbn-alert ahbn-alert-error"><div class="ahbn-alert-content"><span class="dashicons dashicons-warning" aria-hidden="true"></span><span>' . esc_html__( 'Add room view function (ahbn_add_edit_room_tab) is missing.', 'awesome-hotel-booking' ) . '</span></div><button type="button" class="ahbn-alert-close" aria-label="' . esc_attr__( 'Dismiss alert', 'awesome-hotel-booking' ) . '">&times;</button></div>';
                    }
                    break;

                case 'edit':
                    if ( $ahbn_room_id > 0 && function_exists( 'ahbn_add_edit_room_tab' ) ) {
                        ahbn_add_edit_room_tab( $ahbn_room_id );
                    } elseif ( function_exists( 'ahbn_rooms_list' ) ) {
                        ahbn_rooms_list();
                    }
                    break;

                case 'view':
                    if ( $ahbn_room_id > 0 && function_exists( 'ahbn_view_room_tab' ) ) {
                        ahbn_view_room_tab( $ahbn_room_id );
                    } elseif ( function_exists( 'ahbn_rooms_list' ) ) {
                        ahbn_rooms_list();
                    }
                    break;

                case 'extras':
                    if ( function_exists( 'ahbn_rooms_settings' ) ) {
                        ahbn_rooms_settings();
                    } else {
                        echo '<div class="ahbn-alert ahbn-alert-error"><div class="ahbn-alert-content"><span class="dashicons dashicons-warning" aria-hidden="true"></span><span>' . esc_html__( 'Extras configuration function (ahbn_rooms_settings) is missing.', 'awesome-hotel-booking' ) . '</span></div><button type="button" class="ahbn-alert-close" aria-label="' . esc_attr__( 'Dismiss alert', 'awesome-hotel-booking' ) . '">&times;</button></div>';
                    }
                    break;

                default:
                    if ( function_exists( 'ahbn_rooms_list' ) ) {
                        ahbn_rooms_list();
                    } else {
                        echo '<div class="ahbn-alert ahbn-alert-error"><div class="ahbn-alert-content"><span class="dashicons dashicons-warning" aria-hidden="true"></span><span>' . esc_html__( 'Rooms list view function (ahbn_rooms_list) is missing.', 'awesome-hotel-booking' ) . '</span></div><button type="button" class="ahbn-alert-close" aria-label="' . esc_attr__( 'Dismiss alert', 'awesome-hotel-booking' ) . '">&times;</button></div>';
                    }
                    break;
            }
            ?>
        </main>
    </div>

    <style>
        .ahbn-sidebar-container .notice,
        .ahbn-sidebar-container div.error,
        .ahbn-sidebar-container div.updated {
            display: none !important;
        }

        .ahbn-top-notices-area {
            margin-bottom: 6px;
        }

        .ahbn-top-notices-area:empty {
            margin-bottom: 0;
        }

        .ahbn-alert {
            margin: 0 0 16px 0 !important;
            padding: 12px 18px !important;
            border-radius: 10px !important;
            border-left: 4px solid transparent !important;
            background: #ffffff !important;
            box-shadow: 0 2px 10px rgba(10, 25, 47, 0.05) !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: ahbnAlertFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes ahbnAlertFadeIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .ahbn-alert-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ahbn-alert-content .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
        }

        .ahbn-alert-close {
            background: none;
            border: none;
            font-size: 20px;
            line-height: 1;
            color: #94a3b8;
            cursor: pointer;
            padding: 0 0 0 12px;
            transition: color 0.15s ease, transform 0.15s ease;
        }

        .ahbn-alert-close:hover {
            color: #0f172a;
            transform: scale(1.15);
        }

        .ahbn-alert-error {
            border-left-color: #ef4444 !important;
            background: #fff5f5 !important;
            color: #991b1b !important;
        }

        .ahbn-alert-error .dashicons {
            color: #ef4444 !important;
        }

        .ahbn-alert-success {
            border-left-color: #10b981 !important;
            background: #ecfdf5 !important;
            color: #065f46 !important;
        }

        .ahbn-alert-success .dashicons {
            color: #10b981 !important;
        }

        .ahbn-rooms-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #0f172a;
        }

        .ahbn-rooms-header {
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

        .ahbn-rooms-title {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 24px;
            font-weight: 700;
            color: #0a192f;
            margin: 0 0 4px;
        }

        .ahbn-rooms-subtitle {
            color: #64748b;
            margin: 0;
            font-size: 13px;
        }

        .ahbn-btn-primary {
            background: #0a192f !important;
            color: #ffffff !important;
            border: 1px solid #0a192f !important;
            padding: 9px 18px !important;
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

        .ahbn-subtabs-nav-bar {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 5px;
            border-radius: 12px;
            width: fit-content;
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

        .ahbn-rooms-canvas {
            min-width: 0;
        }
    </style>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $(document).on('click', '.ahbn-alert-close', function(e) {
            e.preventDefault();
            $(this).closest('.ahbn-alert').fadeOut(180, function() {
                $(this).remove();
            });
        });
    });
    </script>
    <?php
}