<?php
/**
 * Main Expense Management Tab Router & Controller.
 *
 * Built on high-performance custom relational database tables:
 * {$wpdb->prefix}ahbn_expenses
 *
 * Styled with the Champagne Gold & Deep Navy enterprise UI system.
 * Handles sub-tab routing for: All Expenses, Add/Edit Expense, and Categories/Settings.
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

	global $wpdb;
	$table_expenses = $wpdb->prefix . 'ahbn_expenses';

	$ahbn_page_slug = 'awesome_hotel_booking';
	$base_url       = admin_url( 'admin.php?page=' . rawurlencode( $ahbn_page_slug ) . '&tab=expense' );

	// Capture State.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$ahbn_active_sub = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'all';
	$ahbn_expense_id = isset( $_GET['expense_id'] ) ? absint( wp_unslash( $_GET['expense_id'] ) ) : 0;
	$action          = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	// Handle Destructive Action: Delete Expense from custom SQL table.
	if ( 'delete' === $action && $ahbn_expense_id > 0 ) {
		$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'ahbn_expense_action' ) ) {
			wp_die( esc_html__( 'Security check failed. Please return to the expense list and try again.', 'awesome-hotel-booking' ) );
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			$table_expenses,
			array( 'id' => $ahbn_expense_id ),
			array( '%d' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( $deleted ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				sprintf(
					/* translators: %d: Expense Record ID */
					esc_html__( 'Expense record #%d has been permanently removed.', 'awesome-hotel-booking' ),
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
			'label' => __( 'Record Expense', 'awesome-hotel-booking' ),
			'icon'  => 'dashicons-plus-alt2',
		),
		'settings' => array(
			'label' => __( 'Expense Settings', 'awesome-hotel-booking' ),
			'icon'  => 'dashicons-admin-generic',
		),
	);

	$is_edit_active = ( 'edit' === $ahbn_active_sub && $ahbn_expense_id > 0 );
	?>
	<div class="ahbn-expense-wrap">
		
		<!-- Header Section -->
		<header class="ahbn-expense-header">
			<div>
				<h2 class="ahbn-expense-title"><?php esc_html_e( 'Hotel Expense Management', 'awesome-hotel-booking' ); ?></h2>
				<p class="ahbn-expense-subtitle">
					<?php esc_html_e( 'Track operational disbursements, housekeeping supplies, maintenance invoices, and staff department costs.', 'awesome-hotel-booking' ); ?>
				</p>
			</div>

			<?php if ( 'add' !== $ahbn_active_sub && ! $is_edit_active ) : ?>
				<?php $add_url = add_query_arg( 'sub', 'add', $base_url ); ?>
				<div>
					<a href="<?php echo esc_url( $add_url ); ?>" class="ahbn-btn-primary">
						<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
						<?php esc_html_e( 'Record Expense', 'awesome-hotel-booking' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</header>

		<!-- Sub-Tabs Navigation Bar -->
		<div class="ahbn-subtabs-nav-bar">
			<?php
			foreach ( $ahbn_sub_tabs as $ahbn_key => $ahbn_data ) :
				$tab_url        = add_query_arg( 'sub', $ahbn_key, $base_url );
				$ahbn_is_active = ( $ahbn_active_sub === $ahbn_key );
				?>
				<a href="<?php echo esc_url( $tab_url ); ?>" class="ahbn-subtab-link <?php echo $ahbn_is_active ? 'is-active' : ''; ?>">
					<span class="dashicons <?php echo esc_attr( $ahbn_data['icon'] ); ?>" aria-hidden="true"></span>
					<span><?php echo esc_html( $ahbn_data['label'] ); ?></span>
				</a>
			<?php endforeach; ?>

			<?php if ( $is_edit_active ) : ?>
				<span class="ahbn-subtab-link is-active is-context-tab">
					<span class="dashicons dashicons-edit" aria-hidden="true"></span>
					<span><?php echo esc_html( sprintf( /* translators: %d: Expense ID */ __( 'Edit Expense #%d', 'awesome-hotel-booking' ), $ahbn_expense_id ) ); ?></span>
				</span>
			<?php endif; ?>
		</div>

		<!-- Main Content Router -->
		<main class="ahbn-expense-canvas">
			<?php
			switch ( $ahbn_active_sub ) {
				case 'add':
					if ( function_exists( 'ahbn_expense_add_edit' ) ) {
						ahbn_expense_add_edit();
					} else {
						ahbn_render_default_expense_form();
					}
					break;

				case 'edit':
					if ( $ahbn_expense_id > 0 && function_exists( 'ahbn_expense_add_edit' ) ) {
						ahbn_expense_add_edit( $ahbn_expense_id );
					} elseif ( $ahbn_expense_id > 0 ) {
						ahbn_render_default_expense_form( $ahbn_expense_id );
					} elseif ( function_exists( 'ahbn_expense_list' ) ) {
						ahbn_expense_list();
					} else {
						ahbn_render_default_expense_list();
					}
					break;

				case 'settings':
					if ( function_exists( 'ahbn_expense_settings' ) ) {
						ahbn_expense_settings();
					} else {
						echo '<div class="ahbn-panel"><div class="ahbn-panel-body" style="text-align:center; padding:50px; color:#64748b;">';
						echo '<span class="dashicons dashicons-admin-generic" style="font-size:36px; width:36px; height:36px; color:#cbd5e1;" aria-hidden="true"></span>';
						echo '<h3 style="color:#0a192f; margin:10px 0 6px;">' . esc_html__( 'Expense Settings', 'awesome-hotel-booking' ) . '</h3>';
						echo '<p style="margin:0;">' . esc_html__( 'Please load ahbn-expense-settings.php to configure custom expense categories and department rules.', 'awesome-hotel-booking' ) . '</p>';
						echo '</div></div>';
					}
					break;

				case 'all':
				default:
					if ( function_exists( 'ahbn_expense_list' ) ) {
						ahbn_expense_list();
					} else {
						ahbn_render_default_expense_list();
					}
					break;
			}
			?>
		</main>
	</div>

	<style>
		.ahbn-expense-wrap {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		.ahbn-expense-header {
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

		.ahbn-expense-title {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 24px;
			font-weight: 700;
			color: #0a192f;
			margin: 0 0 4px;
		}

		.ahbn-expense-subtitle {
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

		/* Sub-tabs Navigation Bar */
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

		/* Reusable Panel Styling */
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
			padding: 24px;
			display: flex;
			flex-direction: column;
			gap: 16px;
		}

		.ahbn-field-group {
			display: flex;
			flex-direction: column;
			gap: 6px;
		}

		.ahbn-label {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #334155;
		}

		.ahbn-input,
		.ahbn-select,
		.ahbn-textarea {
			width: 100%;
			border: 1px solid #cbd5e1 !important;
			border-radius: 8px !important;
			padding: 8px 12px !important;
			font-size: 13px !important;
			background: #ffffff !important;
			color: #0f172a !important;
			box-shadow: none !important;
		}

		.ahbn-input:focus,
		.ahbn-select:focus,
		.ahbn-textarea:focus {
			border-color: #0a192f !important;
			box-shadow: 0 0 0 2px rgba(10, 25, 47, 0.1) !important;
		}

		.ahbn-panel-footer {
			padding: 16px 24px;
			background: #f8fafc;
			border-top: 1px solid #e2e8f0;
			display: flex;
			justify-content: flex-end;
			gap: 10px;
		}

		.ahbn-btn-cancel {
			padding: 9px 18px;
			background: #ffffff;
			border: 1px solid #cbd5e1;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 600;
			color: #475569;
			text-decoration: none;
			display: inline-flex;
			align-items: center;
			transition: all 0.2s ease;
		}

		.ahbn-btn-cancel:hover {
			background: #f1f5f9;
			color: #0f172a;
		}

		.ahbn-btn-save {
			padding: 9px 20px;
			background: #0a192f;
			border: 1px solid #0a192f;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 700;
			color: #ffffff;
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.ahbn-btn-save:hover {
			background: #c5a880;
			border-color: #c5a880;
			color: #0a192f;
		}

		/* Stat Cards */
		.ahbn-stats-grid {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 16px;
			margin-bottom: 20px;
		}

		.ahbn-stat-card {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 18px 20px;
			display: flex;
			flex-direction: column;
			gap: 4px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-stat-label {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.06em;
			color: #64748b;
		}

		.ahbn-stat-value {
			font-family: 'JetBrains Mono', monospace;
			font-size: 26px;
			font-weight: 800;
			color: #0a192f;
		}

		.ahbn-stat-total { border-left: 4px solid #ef4444; }
		.ahbn-stat-total .ahbn-stat-label,
		.ahbn-stat-total .ahbn-stat-value { color: #dc2626; }

		.ahbn-stat-avg { border-left: 4px solid #c5a880; }
		.ahbn-stat-avg .ahbn-stat-label { color: #b39264; }

		/* Table Structure */
		.ahbn-table-container {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			overflow: hidden;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-expense-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 13px;
		}

		.ahbn-expense-table th {
			background: #f8fafc !important;
			padding: 12px 16px !important;
			font-weight: 700 !important;
			font-size: 11px !important;
			text-transform: uppercase !important;
			letter-spacing: 0.05em !important;
			color: #64748b !important;
			border-bottom: 1px solid #e2e8f0 !important;
		}

		.ahbn-expense-table td {
			padding: 14px 16px !important;
			vertical-align: middle !important;
			border-bottom: 1px solid #f1f5f9 !important;
		}

		.ahbn-cat-badge {
			font-family: 'JetBrains Mono', monospace;
			background: #f8fafc;
			color: #0a192f;
			font-weight: 700;
			font-size: 11px;
			padding: 3px 8px;
			border-radius: 4px;
			border: 1px solid #e2e8f0;
			display: inline-block;
		}

		.ahbn-dept-tag {
			font-size: 11px;
			color: #64748b;
			display: block;
			margin-top: 2px;
		}

		.ahbn-action-btns {
			display: inline-flex;
			gap: 5px;
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
			cursor: pointer;
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

		.ahbn-quick-btn.is-delete:hover {
			background: #e11d48;
			border-color: #e11d48;
			color: #ffffff;
		}

		@media (max-width: 1024px) {
			.ahbn-stats-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>
	<?php
}

/**
 * Fallback View: Default Expense Record List (when ahbn-expense-list.php is not loaded).
 *
 * @return void
 */
function ahbn_render_default_expense_list() {
	global $wpdb;
	$table_expenses = $wpdb->prefix . 'ahbn_expenses';
	$base_url       = admin_url( 'admin.php?page=awesome_hotel_booking&tab=expense' );

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$expenses = $wpdb->get_results( "SELECT * FROM {$table_expenses} ORDER BY expense_date DESC, id DESC" );

	$metrics = $wpdb->get_row(
		"SELECT 
			COUNT(*) AS total_count,
			COALESCE(SUM(amount), 0.00) AS total_sum,
			COALESCE(AVG(amount), 0.00) AS avg_sum
		 FROM {$table_expenses}"
	);
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	$total_count = $metrics ? (int) $metrics->total_count : 0;
	$total_sum   = $metrics ? (float) $metrics->total_sum : 0.00;
	$avg_sum     = $metrics ? (float) $metrics->avg_sum : 0.00;
	?>
	<!-- Metrics Cards -->
	<div class="ahbn-stats-grid">
		<div class="ahbn-stat-card ahbn-stat-total">
			<span class="ahbn-stat-label"><?php esc_html_e( 'Total Hotel Expenses', 'awesome-hotel-booking' ); ?></span>
			<span class="ahbn-stat-value"><?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $total_sum ) : (string) number_format_i18n( $total_sum, 2 ) ); ?></span>
		</div>
		<div class="ahbn-stat-card">
			<span class="ahbn-stat-label"><?php esc_html_e( 'Total Recorded Receipts', 'awesome-hotel-booking' ); ?></span>
			<span class="ahbn-stat-value"><?php echo esc_html( (string) $total_count ); ?></span>
		</div>
		<div class="ahbn-stat-card ahbn-stat-avg">
			<span class="ahbn-stat-label"><?php esc_html_e( 'Average Expense Amount', 'awesome-hotel-booking' ); ?></span>
			<span class="ahbn-stat-value"><?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $avg_sum ) : (string) number_format_i18n( $avg_sum, 2 ) ); ?></span>
		</div>
	</div>

	<!-- Expenses Table -->
	<div class="ahbn-table-container">
		<table class="ahbn-expense-table widefat striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Description / Title', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Amount', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Category & Department', 'awesome-hotel-booking' ); ?></th>
					<th><?php esc_html_e( 'Expense Date', 'awesome-hotel-booking' ); ?></th>
					<th style="text-align: right; width: 120px;"><?php esc_html_e( 'Actions', 'awesome-hotel-booking' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $expenses ) ) : ?>
					<?php foreach ( $expenses as $row ) :
						$edit_url = add_query_arg( array( 'sub' => 'edit', 'expense_id' => $row->id ), $base_url );
						$del_url  = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'expense_id' => $row->id ), $base_url ), 'ahbn_expense_action' );
						?>
						<tr>
							<td>
								<strong style="color:#0a192f;"><?php echo esc_html( $row->title ); ?></strong>
								<?php if ( ! empty( $row->description ) ) : ?>
									<small style="display:block; color:#64748b; font-size:11px; margin-top:2px;"><?php echo esc_html( wp_trim_words( $row->description, 10, '...' ) ); ?></small>
								<?php endif; ?>
							</td>
							<td>
								<strong style="color:#dc2626; font-size:14px; font-family:'JetBrains Mono', monospace;">
									<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $row->amount ) : (string) number_format_i18n( $row->amount, 2 ) ); ?>
								</strong>
							</td>
							<td>
								<span class="ahbn-cat-badge"><?php echo esc_html( $row->category ); ?></span>
								<span class="ahbn-dept-tag"><?php echo esc_html( $row->department ); ?></span>
							</td>
							<td>
								<span style="font-size:12px; color:#334155;">
									<?php echo esc_html( ! empty( $row->expense_date ) ? wp_date( get_option( 'date_format' ), strtotime( $row->expense_date ) ) : '—' ); ?>
								</span>
							</td>
							<td style="text-align: right;">
								<div class="ahbn-action-btns">
									<a href="<?php echo esc_url( $edit_url ); ?>" class="ahbn-quick-btn" title="<?php esc_attr_e( 'Edit Expense', 'awesome-hotel-booking' ); ?>">
										<span class="dashicons dashicons-edit" aria-hidden="true"></span>
									</a>
									<a href="<?php echo esc_url( $del_url ); ?>" class="ahbn-quick-btn is-delete" title="<?php esc_attr_e( 'Delete Expense', 'awesome-hotel-booking' ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to permanently delete this expense?', 'awesome-hotel-booking' ) ); ?>');">
										<span class="dashicons dashicons-trash" aria-hidden="true"></span>
									</a>
								</div>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="5" style="text-align:center; padding:50px; color:#94a3b8;">
							<span class="dashicons dashicons-chart-pie" style="font-size:36px; width:36px; height:36px; color:#cbd5e1;" aria-hidden="true"></span>
							<p style="margin:8px 0 0;"><?php esc_html_e( 'No expense transactions recorded yet.', 'awesome-hotel-booking' ); ?></p>
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}

/**
 * Fallback View: Add/Edit Expense Form (when ahbn-expense-add-edit.php is not loaded).
 *
 * @param int $expense_id Optional expense ID for editing.
 * @return void
 */
function ahbn_render_default_expense_form( $expense_id = 0 ) {
	global $wpdb;
	$table_expenses = $wpdb->prefix . 'ahbn_expenses';
	$base_url       = admin_url( 'admin.php?page=awesome_hotel_booking&tab=expense' );

	// Process Save / Update.
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_expense'] ) ) {
		$nonce = isset( $_POST['ahbn_exp_nonce'] ) ? sanitize_key( wp_unslash( $_POST['ahbn_exp_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'ahbn_save_expense_action' ) ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Security check failed.', 'awesome-hotel-booking' ) . '</p></div>';
		} else {
			$post_id     = isset( $_POST['expense_id'] ) ? absint( wp_unslash( $_POST['expense_id'] ) ) : 0;
			$title       = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
			$amount      = isset( $_POST['amount'] ) ? (float) wp_unslash( $_POST['amount'] ) : 0.00;
			$category    = isset( $_POST['category'] ) ? sanitize_text_field( wp_unslash( $_POST['category'] ) ) : 'General';
			$department  = isset( $_POST['department'] ) ? sanitize_text_field( wp_unslash( $_POST['department'] ) ) : 'Operations';
			$exp_date    = isset( $_POST['expense_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expense_date'] ) ) : current_time( 'Y-m-d' );
			$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';

			if ( empty( $title ) || $amount <= 0 ) {
				echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Please enter an expense title and an amount greater than zero.', 'awesome-hotel-booking' ) . '</p></div>';
			} else {
				$expense_data = array(
					'title'        => $title,
					'amount'       => $amount,
					'category'     => $category,
					'department'   => $department,
					'expense_date' => $exp_date,
					'description'  => $description,
					'created_by'   => get_current_user_id(),
				);

				$format = array( '%s', '%f', '%s', '%s', '%s', '%s', '%d' );

				// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				if ( $post_id > 0 ) {
					$wpdb->update(
						$table_expenses,
						$expense_data,
						array( 'id' => $post_id ),
						$format,
						array( '%d' )
					);
					echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Expense record updated.', 'awesome-hotel-booking' ) . '</p></div>';
				} else {
					$wpdb->insert( $table_expenses, $expense_data, $format );
					echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Success:', 'awesome-hotel-booking' ) . '</strong> ' . esc_html__( 'Expense recorded successfully.', 'awesome-hotel-booking' ) . '</p></div>';
				}
				// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
		}
	}

	$edit_record = null;
	if ( $expense_id > 0 ) {
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$edit_record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_expenses} WHERE id = %d", $expense_id ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	$categories = array( 'Maintenance', 'Housekeeping Supplies', 'Utilities & Energy', 'Food & Beverage', 'Marketing & Ads', 'Software & Licenses', 'Salaries & Wages', 'General' );
	$departments = array( 'Operations', 'Front Desk', 'Housekeeping', 'Facilities & Engineering', 'Management', 'Food & Beverage' );
	?>
	<div class="ahbn-panel" style="max-width: 680px; margin: 0 auto; width: 100%;">
		<div class="ahbn-panel-head">
			<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
			<h2><?php echo $edit_record ? esc_html__( 'Edit Expense Record', 'awesome-hotel-booking' ) : esc_html__( 'Record New Hotel Expense', 'awesome-hotel-booking' ); ?></h2>
		</div>

		<form method="post" action="<?php echo esc_url( $base_url ); ?>">
			<?php wp_nonce_field( 'ahbn_save_expense_action', 'ahbn_exp_nonce' ); ?>
			<input type="hidden" name="expense_id" value="<?php echo esc_attr( (string) ( $edit_record ? $edit_record->id : 0 ) ); ?>">

			<div class="ahbn-panel-body">
				<div class="ahbn-field-group">
					<label for="exp_title" class="ahbn-label"><?php esc_html_e( 'Expense Title / Payee *', 'awesome-hotel-booking' ); ?></label>
					<input type="text" name="title" id="exp_title" class="ahbn-input" required value="<?php echo esc_attr( $edit_record ? $edit_record->title : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. Linens & Towels restocking, AC servicing Suite 201', 'awesome-hotel-booking' ); ?>">
				</div>

				<div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
					<div class="ahbn-field-group">
						<label for="exp_amount" class="ahbn-label"><?php esc_html_e( 'Amount *', 'awesome-hotel-booking' ); ?></label>
						<input type="number" step="0.01" min="0.01" name="amount" id="exp_amount" class="ahbn-input" required value="<?php echo esc_attr( (string) ( $edit_record ? $edit_record->amount : '' ) ); ?>" placeholder="0.00" style="font-family:'JetBrains Mono',monospace; font-weight:700;">
					</div>

					<div class="ahbn-field-group">
						<label for="exp_date" class="ahbn-label"><?php esc_html_e( 'Disbursement Date *', 'awesome-hotel-booking' ); ?></label>
						<input type="date" name="expense_date" id="exp_date" class="ahbn-input" required value="<?php echo esc_attr( $edit_record ? $edit_record->expense_date : current_time( 'Y-m-d' ) ); ?>">
					</div>
				</div>

				<div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
					<div class="ahbn-field-group">
						<label for="exp_category" class="ahbn-label"><?php esc_html_e( 'Category', 'awesome-hotel-booking' ); ?></label>
						<select name="category" id="exp_category" class="ahbn-select">
							<?php foreach ( $categories as $cat ) : ?>
								<option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $edit_record ? $edit_record->category : 'General', $cat ); ?>>
									<?php echo esc_html( $cat ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="ahbn-field-group">
						<label for="exp_department" class="ahbn-label"><?php esc_html_e( 'Department', 'awesome-hotel-booking' ); ?></label>
						<select name="department" id="exp_department" class="ahbn-select">
							<?php foreach ( $departments as $dept ) : ?>
								<option value="<?php echo esc_attr( $dept ); ?>" <?php selected( $edit_record ? $edit_record->department : 'Operations', $dept ); ?>>
									<?php echo esc_html( $dept ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="ahbn-field-group">
					<label for="exp_description" class="ahbn-label"><?php esc_html_e( 'Notes / Description', 'awesome-hotel-booking' ); ?></label>
					<textarea name="description" id="exp_description" rows="3" class="ahbn-textarea" placeholder="<?php esc_attr_e( 'Invoice reference numbers, vendor contacts, or check details...', 'awesome-hotel-booking' ); ?>"><?php echo esc_textarea( $edit_record ? $edit_record->description : '' ); ?></textarea>
				</div>
			</div>

			<div class="ahbn-panel-footer">
				<a href="<?php echo esc_url( $base_url ); ?>" class="ahbn-btn-cancel">
					<?php esc_html_e( 'Cancel', 'awesome-hotel-booking' ); ?>
				</a>
				<button type="submit" name="ahbn_save_expense" class="ahbn-btn-save">
					<?php echo $edit_record ? esc_html__( 'Update Expense Record', 'awesome-hotel-booking' ) : esc_html__( 'Save Expense Record', 'awesome-hotel-booking' ); ?>
				</button>
			</div>
		</form>
	</div>
	<?php
}