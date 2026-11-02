<?php
/**
 * Main Expense Management Tab Router & Controller.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 1. Auto-load Expense Sub-View Files if organized in an /expense/ directory.
$ahbn_expense_files = array(
	AHBN_PATH . 'admin/expense/ahbn-expense-list.php',
	AHBN_PATH . 'admin/expense/ahbn-expense-add-edit.php',
	AHBN_PATH . 'admin/expense/ahbn-expense-settings.php',
);

foreach ( $ahbn_expense_files as $ahbn_file ) {
	if ( file_exists( $ahbn_file ) ) {
		require_once $ahbn_file;
	}
}

/**
 * Render the Expense Management tab.
 *
 * @return void
 */
function ahbn_expense_tab() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	$ahbn_page_slug = 'awesome_hotel_booking';
	$base_url       = admin_url( 'admin.php?page=' . rawurlencode( $ahbn_page_slug ) . '&tab=expense' );

	// Capture State.
	$ahbn_active_sub = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$ahbn_expense_id = isset( $_GET['expense_id'] ) ? absint( wp_unslash( $_GET['expense_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$action          = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	// Handle Destructive Action: Trash Expense.
	if ( 'delete' === $action && $ahbn_expense_id > 0 ) {
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'ahbn_expense_action' ) ) {
			wp_die( esc_html__( 'Security check failed. Please return to the expense list and try again.', 'awesome-hotel-booking' ) );
		}

		if ( ! current_user_can( 'delete_post', $ahbn_expense_id ) ) {
			wp_die( esc_html__( 'You are not allowed to delete this expense.', 'awesome-hotel-booking' ) );
		}

		if ( 'ahbn_expense' === get_post_type( $ahbn_expense_id ) ) {
			wp_trash_post( $ahbn_expense_id );

			printf(
				'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
				sprintf(
					/* translators: %d: Expense Post ID */
					esc_html__( 'Expense record #%d has been moved to trash.', 'awesome-hotel-booking' ),
					absint( $ahbn_expense_id )
				)
			);
		}

		$ahbn_active_sub = 'all';
		$ahbn_expense_id = 0;
	}

	// 2. Define Sub-Tabs.
	$ahbn_sub_tabs = array(
		'all'      => array(
			'label' => __( 'All Expenses', 'awesome-hotel-booking' ),
			'icon'  => 'dashicons-list-view',
		),
		'add'      => array(
			'label' => __( 'Add New', 'awesome-hotel-booking' ),
			'icon'  => 'dashicons-plus',
		),
		'settings' => array(
			'label' => __( 'Settings', 'awesome-hotel-booking' ),
			'icon'  => 'dashicons-admin-generic',
		),
	);
	?>
	<div class="ahbn-admin-container" style="margin-right:20px; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
		
		<!-- Header Section -->
		<header class="ahbn-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; margin-top:20px;">
			<div class="ahbn-header-text">
				<h1 style="font-size:26px; font-weight:800; color:#0A1A38; margin:0;">
					<?php esc_html_e( 'Expense Management', 'awesome-hotel-booking' ); ?>
				</h1>
				<p style="margin:5px 0 0; color:#6b7280; font-size:14px;">
					<?php esc_html_e( 'Track hotel operational costs, maintenance, and utility bills.', 'awesome-hotel-booking' ); ?>
				</p>
			</div>

			<?php if ( 'add' !== $ahbn_active_sub ) : ?>
				<?php $add_url = add_query_arg( 'sub', 'add', $base_url ); ?>
				<a href="<?php echo esc_url( $add_url ); ?>" class="button button-primary" style="background:#0A1A38; border:none; height:40px; padding:0 20px; border-radius:8px; display:inline-flex; align-items:center; gap:8px; font-weight:600;">
					<span class="dashicons dashicons-plus-alt" aria-hidden="true"></span>
					<?php esc_html_e( 'Record Expense', 'awesome-hotel-booking' ); ?>
				</a>
			<?php endif; ?>
		</header>

		<!-- Navigation Tabs -->
		<nav class="ahbn-nav-tabs" style="display:flex; gap:30px; border-bottom:1px solid #e5e7eb; margin-bottom:30px;">
			<?php
			foreach ( $ahbn_sub_tabs as $ahbn_key => $ahbn_data ) :
				$tab_url        = add_query_arg( 'sub', $ahbn_key, $base_url );
				$ahbn_is_active = ( $ahbn_active_sub === $ahbn_key || ( 'all' === $ahbn_key && 'edit' === $ahbn_active_sub ) );
				$tab_style      = $ahbn_is_active
					? 'border-bottom:3px solid #D9B061; color:#0A1A38; font-weight:700;'
					: 'color:#9ca3af; border-bottom:3px solid transparent;';
				?>
				<a 
					href="<?php echo esc_url( $tab_url ); ?>" 
					style="text-decoration:none; padding:12px 5px; display:flex; align-items:center; gap:10px; font-size:14px; transition:0.2s; <?php echo esc_attr( $tab_style ); ?>"
				>
					<span class="dashicons <?php echo esc_attr( $ahbn_data['icon'] ); ?>" aria-hidden="true"></span>
					<span><?php echo esc_html( $ahbn_data['label'] ); ?></span>
				</a>
			<?php endforeach; ?>
		</nav>

		<!-- Main Content Router -->
		<main class="ahbn-content-card">
			<?php
			switch ( $ahbn_active_sub ) {
				case 'add':
					if ( function_exists( 'ahbn_expense_add_edit' ) ) {
						ahbn_expense_add_edit();
					} else {
						echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Add Expense Form coming soon.', 'awesome-hotel-booking' ) . '</p></div>';
					}
					break;

				case 'edit':
					if ( $ahbn_expense_id > 0 && function_exists( 'ahbn_expense_add_edit' ) ) {
						ahbn_expense_add_edit( $ahbn_expense_id );
					} elseif ( function_exists( 'ahbn_expense_list' ) ) {
						ahbn_expense_list();
					}
					break;

				case 'settings':
					if ( function_exists( 'ahbn_expense_settings' ) ) {
						ahbn_expense_settings();
					} else {
						echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Expense Settings (Categories, Tax, etc.) coming soon.', 'awesome-hotel-booking' ) . '</p></div>';
					}
					break;

				case 'all':
				default:
					if ( function_exists( 'ahbn_expense_list' ) ) {
						ahbn_expense_list();
					} else {
						echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Expense List coming soon.', 'awesome-hotel-booking' ) . '</p></div>';
					}
					break;
			}
			?>
		</main>
	</div>
	<?php
}