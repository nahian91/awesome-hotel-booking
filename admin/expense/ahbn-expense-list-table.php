<?php
/**
 * Expense Records Directory View.
 *
 * Built on high-performance custom relational database tables:
 * {$wpdb->prefix}ahbn_expenses
 *
 * Styled with the Champagne Gold & Deep Navy enterprise UI system.
 * Supports keyword search, department/category filtering, and pagination.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Expense Directory Table.
 *
 * @return void
 */
function ahbn_expense_list() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'awesome-hotel-booking' ) );
	}

	global $wpdb;
	$table_expenses = $wpdb->prefix . 'ahbn_expenses';

	$page_slug = 'awesome_hotel_booking';
	$base_url  = admin_url( 'admin.php?page=' . rawurlencode( $page_slug ) . '&tab=expense' );

	// 1. Resolve Query Parameters.
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$paged           = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
	$search_query    = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
	$filter_cat      = isset( $_GET['cat'] ) ? sanitize_text_field( wp_unslash( $_GET['cat'] ) ) : '';
	$filter_dept     = isset( $_GET['dept'] ) ? sanitize_text_field( wp_unslash( $_GET['dept'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$per_page = 20;
	$offset   = ( $paged - 1 ) * $per_page;

	// 2. Build Dynamic SQL Filters.
	$where_clauses = array( '1=1' );
	$params        = array();

	if ( ! empty( $search_query ) ) {
		$where_clauses[] = '(title LIKE %s OR description LIKE %s)';
		$like_val        = '%' . $wpdb->esc_like( $search_query ) . '%';
		$params[]        = $like_val;
		$params[]        = $like_val;
	}

	if ( ! empty( $filter_cat ) ) {
		$where_clauses[] = 'category = %s';
		$params[]        = $filter_cat;
	}

	if ( ! empty( $filter_dept ) ) {
		$where_clauses[] = 'department = %s';
		$params[]        = $filter_dept;
	}

	$where_sql = implode( ' AND ', $where_clauses );

	// 3. Query Totals & Filtered Results.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( ! empty( $params ) ) {
		$total_records = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$table_expenses} WHERE {$where_sql}", $params )
		);
	} else {
		$total_records = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_expenses} WHERE {$where_sql}" );
	}

	$total_pages = ceil( $total_records / $per_page );

	$fetch_sql    = "SELECT * FROM {$table_expenses} WHERE {$where_sql} ORDER BY expense_date DESC, id DESC LIMIT %d OFFSET %d";
	$fetch_params = $params;
	$fetch_params[] = $per_page;
	$fetch_params[] = $offset;

	$expenses = $wpdb->get_results( $wpdb->prepare( $fetch_sql, $fetch_params ) );

	// Lifetime and Filtered Aggregate Metrics.
	$metrics = $wpdb->get_row(
		"SELECT 
			COUNT(*) AS total_count,
			COALESCE(SUM(amount), 0.00) AS total_sum,
			COALESCE(AVG(amount), 0.00) AS avg_sum
		 FROM {$table_expenses}"
	);

	// Get Distinct Categories & Departments for Quick Filter Dropdowns.
	$available_categories  = $wpdb->get_col( "SELECT DISTINCT category FROM {$table_expenses} WHERE category != '' ORDER BY category ASC" );
	$available_departments = $wpdb->get_col( "SELECT DISTINCT department FROM {$table_expenses} WHERE department != '' ORDER BY department ASC" );
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	$total_sum   = $metrics ? (float) $metrics->total_sum : 0.00;
	$total_count = $metrics ? (int) $metrics->total_count : 0;
	$avg_sum     = $metrics ? (float) $metrics->avg_sum : 0.00;

	$add_url = add_query_arg( 'sub', 'add', $base_url );
	?>

	<div class="ahbn-expense-list-wrap">
		<!-- KPI Metric Cards Grid -->
		<div class="ahbn-stats-grid">
			<div class="ahbn-stat-card ahbn-stat-total">
				<span class="ahbn-stat-label"><?php esc_html_e( 'Total Hotel Expenses', 'awesome-hotel-booking' ); ?></span>
				<span class="ahbn-stat-value">
					<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $total_sum ) : (string) number_format_i18n( $total_sum, 2 ) ); ?>
				</span>
			</div>
			<div class="ahbn-stat-card">
				<span class="ahbn-stat-label"><?php esc_html_e( 'Total Recorded Receipts', 'awesome-hotel-booking' ); ?></span>
				<span class="ahbn-stat-value"><?php echo esc_html( (string) $total_count ); ?></span>
			</div>
			<div class="ahbn-stat-card ahbn-stat-avg">
				<span class="ahbn-stat-label"><?php esc_html_e( 'Average Expense Cost', 'awesome-hotel-booking' ); ?></span>
				<span class="ahbn-stat-value">
					<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $avg_sum ) : (string) number_format_i18n( $avg_sum, 2 ) ); ?>
				</span>
			</div>
		</div>

		<!-- Search & Filter Bar -->
		<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="ahbn-expense-filter-bar">
			<input type="hidden" name="page" value="<?php echo esc_attr( $page_slug ); ?>">
			<input type="hidden" name="tab" value="expense">
			<input type="hidden" name="sub" value="all">

			<div class="ahbn-filter-controls">
				<div class="ahbn-search-field">
					<input 
						type="text" 
						name="s" 
						class="ahbn-input" 
						placeholder="<?php esc_attr_e( 'Search by payee, title, or note...', 'awesome-hotel-booking' ); ?>"
						value="<?php echo esc_attr( $search_query ); ?>"
					>
				</div>

				<?php if ( ! empty( $available_categories ) ) : ?>
					<div class="ahbn-select-field">
						<select name="cat" class="ahbn-select" onchange="this.form.submit();">
							<option value=""><?php esc_html_e( 'All Categories', 'awesome-hotel-booking' ); ?></option>
							<?php foreach ( $available_categories as $cat ) : ?>
								<option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $filter_cat, $cat ); ?>>
									<?php echo esc_html( $cat ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $available_departments ) ) : ?>
					<div class="ahbn-select-field">
						<select name="dept" class="ahbn-select" onchange="this.form.submit();">
							<option value=""><?php esc_html_e( 'All Departments', 'awesome-hotel-booking' ); ?></option>
							<?php foreach ( $available_departments as $dept ) : ?>
								<option value="<?php echo esc_attr( $dept ); ?>" <?php selected( $filter_dept, $dept ); ?>>
									<?php echo esc_html( $dept ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				<?php endif; ?>

				<button type="submit" class="ahbn-btn-search">
					<span class="dashicons dashicons-search" aria-hidden="true"></span>
					<?php esc_html_e( 'Filter', 'awesome-hotel-booking' ); ?>
				</button>

				<?php if ( ! empty( $search_query ) || ! empty( $filter_cat ) || ! empty( $filter_dept ) ) : ?>
					<a href="<?php echo esc_url( $base_url ); ?>" class="ahbn-clear-filters">
						<?php esc_html_e( 'Reset Filters', 'awesome-hotel-booking' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</form>

		<!-- Expense Directory Table -->
		<div class="ahbn-table-container">
			<table class="ahbn-expense-table widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Description / Payee', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Disbursed Amount', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Category & Department', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Disbursement Date', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Voucher', 'awesome-hotel-booking' ); ?></th>
						<th style="text-align: right; width: 120px;"><?php esc_html_e( 'Actions', 'awesome-hotel-booking' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $expenses ) ) : ?>
						<?php foreach ( $expenses as $row ) :
							$amount_val  = (float) $row->amount;
							$edit_url    = add_query_arg( array( 'sub' => 'edit', 'expense_id' => $row->id ), $base_url );
							$del_url     = wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'expense_id' => $row->id ), $base_url ), 'ahbn_expense_action' );
							$receipt_id  = (int) $row->receipt_id;
							$receipt_url = $receipt_id > 0 ? wp_get_attachment_url( $receipt_id ) : '';
							?>
							<tr>
								<td>
									<strong class="ahbn-expense-title"><?php echo esc_html( $row->title ); ?></strong>
									<?php if ( ! empty( $row->description ) ) : ?>
										<small class="ahbn-expense-desc"><?php echo esc_html( wp_trim_words( $row->description, 10, '...' ) ); ?></small>
									<?php endif; ?>
								</td>
								<td>
									<strong class="ahbn-expense-amount">
										<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $amount_val ) : (string) number_format_i18n( $amount_val, 2 ) ); ?>
									</strong>
								</td>
								<td>
									<span class="ahbn-cat-badge"><?php echo esc_html( ! empty( $row->category ) ? $row->category : __( 'General', 'awesome-hotel-booking' ) ); ?></span>
									<span class="ahbn-dept-tag"><?php echo esc_html( ! empty( $row->department ) ? $row->department : __( 'Operations', 'awesome-hotel-booking' ) ); ?></span>
								</td>
								<td>
									<span class="ahbn-expense-date">
										<?php echo esc_html( ! empty( $row->expense_date ) ? wp_date( get_option( 'date_format' ), strtotime( $row->expense_date ) ) : '—' ); ?>
									</span>
								</td>
								<td>
									<?php if ( ! empty( $receipt_url ) ) : ?>
										<a href="<?php echo esc_url( $receipt_url ); ?>" target="_blank" rel="noopener noreferrer" class="ahbn-receipt-link" title="<?php esc_attr_e( 'View Attached Invoice Document', 'awesome-hotel-booking' ); ?>">
											<span class="dashicons dashicons-paperclip" aria-hidden="true"></span>
											<span><?php esc_html_e( 'Voucher', 'awesome-hotel-booking' ); ?></span>
										</a>
									<?php else : ?>
										<span class="ahbn-no-receipt">—</span>
									<?php endif; ?>
								</td>
								<td style="text-align: right;">
									<div class="ahbn-action-btns">
										<a href="<?php echo esc_url( $edit_url ); ?>" class="ahbn-quick-btn" title="<?php esc_attr_e( 'Edit Expense', 'awesome-hotel-booking' ); ?>">
											<span class="dashicons dashicons-edit" aria-hidden="true"></span>
										</a>
										<a href="<?php echo esc_url( $del_url ); ?>" class="ahbn-quick-btn is-delete" title="<?php esc_attr_e( 'Delete Expense', 'awesome-hotel-booking' ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to permanently delete this expense record?', 'awesome-hotel-booking' ) ); ?>');">
											<span class="dashicons dashicons-trash" aria-hidden="true"></span>
										</a>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="6" class="ahbn-empty-row">
								<span class="dashicons dashicons-chart-pie" aria-hidden="true"></span>
								<p><?php esc_html_e( 'No expense transactions found matching your criteria.', 'awesome-hotel-booking' ); ?></p>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<!-- Pagination Links -->
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
	</div>

	<style>
		.ahbn-expense-list-wrap {
			display: flex;
			flex-direction: column;
			gap: 16px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
		}

		/* Stat Cards */
		.ahbn-stats-grid {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 16px;
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

		/* Filter Bar */
		.ahbn-expense-filter-bar {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 14px 18px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-filter-controls {
			display: flex;
			align-items: center;
			gap: 12px;
			flex-wrap: wrap;
		}

		.ahbn-search-field {
			flex: 1;
			min-width: 220px;
		}

		.ahbn-input,
		.ahbn-select {
			height: 38px !important;
			border: 1px solid #cbd5e1 !important;
			border-radius: 8px !important;
			padding: 0 12px !important;
			font-size: 13px !important;
			background: #ffffff !important;
			color: #0f172a !important;
			box-shadow: none !important;
			width: 100%;
		}

		.ahbn-input:focus,
		.ahbn-select:focus {
			border-color: #0a192f !important;
			box-shadow: 0 0 0 2px rgba(10, 25, 47, 0.1) !important;
		}

		.ahbn-btn-search {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: #0a192f;
			color: #ffffff;
			border: 1px solid #0a192f;
			padding: 0 16px;
			height: 38px;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 700;
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.ahbn-btn-search:hover {
			background: #c5a880;
			border-color: #c5a880;
			color: #0a192f;
		}

		.ahbn-clear-filters {
			font-size: 12px;
			color: #ef4444;
			text-decoration: none;
			font-weight: 600;
		}

		.ahbn-clear-filters:hover {
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

		.ahbn-expense-title {
			color: #0a192f;
			font-size: 13px;
			display: block;
		}

		.ahbn-expense-desc {
			display: block;
			color: #64748b;
			font-size: 11px;
			margin-top: 2px;
		}

		.ahbn-expense-amount {
			color: #dc2626;
			font-size: 14px;
			font-family: 'JetBrains Mono', monospace;
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

		.ahbn-expense-date {
			font-size: 12px;
			color: #334155;
		}

		.ahbn-receipt-link {
			display: inline-flex;
			align-items: center;
			gap: 4px;
			color: #0a192f;
			font-size: 12px;
			font-weight: 600;
			text-decoration: none;
			background: #f1f5f9;
			padding: 3px 8px;
			border-radius: 6px;
			border: 1px solid #e2e8f0;
			transition: all 0.2s ease;
		}

		.ahbn-receipt-link .dashicons {
			font-size: 14px;
			width: 14px;
			height: 14px;
			color: #c5a880;
		}

		.ahbn-receipt-link:hover {
			background: #0a192f;
			color: #ffffff;
			border-color: #0a192f;
		}

		.ahbn-no-receipt {
			color: #cbd5e1;
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

		.ahbn-empty-row {
			text-align: center;
			padding: 50px !important;
			color: #94a3b8;
		}

		.ahbn-empty-row .dashicons {
			font-size: 36px;
			width: 36px;
			height: 36px;
			color: #cbd5e1;
		}

		.ahbn-empty-row p {
			margin: 8px 0 0;
		}

		@media (max-width: 1024px) {
			.ahbn-stats-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>
	<?php
}