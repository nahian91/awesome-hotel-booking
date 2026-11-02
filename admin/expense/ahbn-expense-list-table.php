<?php
/**
 * Expense Records Directory View.
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

	$page_slug = 'awesome_hotel_booking';
	$base_url  = admin_url( 'admin.php?page=' . rawurlencode( $page_slug ) . '&tab=expense' );
	$paged     = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$posts_per_page = 20;

	// 1. Setup Query for Expenses.
	$args = array(
		'post_type'      => 'ahbn_expense',
		'posts_per_page' => $posts_per_page,
		'paged'          => $paged,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	$expense_query = new WP_Query( $args );
	$expenses      = $expense_query->posts;
	$total_pages   = $expense_query->max_num_pages;
	$total_records = $expense_query->found_posts;

	// 2. Calculate Total Expenditure Across All Records.
	$all_expenses = get_posts(
		array(
			'post_type'              => 'ahbn_expense',
			'posts_per_page'         => -1,
			'post_status'            => 'publish',
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		)
	);

	$total_sum = 0.0;
	if ( ! empty( $all_expenses ) ) {
		foreach ( $all_expenses as $exp_id ) {
			$exp_data = get_post_meta( $exp_id, '_ahbn_expense_data', true );
			if ( isset( $exp_data['amount'] ) ) {
				$total_sum += (float) $exp_data['amount'];
			}
		}
	}

	$add_url = add_query_arg( 'sub', 'add', $base_url );
	?>

	<div class="ahbn-main-wrapper">
		<div class="ahbn-stats-bar">
			<div class="ahbn-stats-item">
				<span class="ahbn-stats-lbl"><?php esc_html_e( 'Total Records', 'awesome-hotel-booking' ); ?></span>
				<span class="ahbn-stats-val"><?php echo esc_html( (string) $total_records ); ?></span>
			</div>
			<div class="ahbn-stats-item">
				<span class="ahbn-stats-lbl"><?php esc_html_e( 'Total Expenditure', 'awesome-hotel-booking' ); ?></span>
				<span class="ahbn-stats-val">
					<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $total_sum ) : (string) number_format_i18n( $total_sum, 2 ) ); ?>
				</span>
			</div>
			<div style="margin-left: auto;">
				<a href="<?php echo esc_url( $add_url ); ?>" class="button button-primary" style="background:#D9B061; color:#0A1A38; border:none; font-weight:700; height:40px; border-radius:8px; display:inline-flex; align-items:center; gap:6px;">
					<span class="dashicons dashicons-plus-alt" aria-hidden="true"></span>
					<?php esc_html_e( 'Log New Expense', 'awesome-hotel-booking' ); ?>
				</a>
			</div>
		</div>

		<div class="ahbn-table-card">
			<table class="ahbn-expense-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Details', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Category & Dept', 'awesome-hotel-booking' ); ?></th>
						<th><?php esc_html_e( 'Amount', 'awesome-hotel-booking' ); ?></th>
						<th style="text-align:right;"><?php esc_html_e( 'Actions', 'awesome-hotel-booking' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $expenses ) ) : ?>
						<?php
						foreach ( $expenses as $post ) :
							$data        = (array) get_post_meta( $post->ID, '_ahbn_expense_data', true );
							$date_val    = isset( $data['date'] ) && ! empty( $data['date'] ) ? $data['date'] : get_the_date( 'Y-m-d', $post );
							$desc_val    = isset( $data['description'] ) ? $data['description'] : '';
							$cat_val     = isset( $data['category'] ) && ! empty( $data['category'] ) ? $data['category'] : __( 'General', 'awesome-hotel-booking' );
							$dept_val    = isset( $data['department'] ) && ! empty( $data['department'] ) ? $data['department'] : __( 'N/A', 'awesome-hotel-booking' );
							$amount_val  = isset( $data['amount'] ) ? (float) $data['amount'] : 0.00;

							$edit_url = add_query_arg(
								array(
									'sub'        => 'edit',
									'expense_id' => $post->ID,
								),
								$base_url
							);

							$del_url = wp_nonce_url(
								add_query_arg(
									array(
										'action'     => 'delete',
										'expense_id' => $post->ID,
									),
									$base_url
								),
								'ahbn_expense_action'
							);
							?>
							<tr>
								<td style="color: #6b7280; font-size: 13px;">
									<?php echo esc_html( $date_val ); ?>
								</td>
								<td>
									<div style="font-weight:700; color:#0A1A38;"><?php echo esc_html( $post->post_title ); ?></div>
									<?php if ( ! empty( $desc_val ) ) : ?>
										<div style="font-size:11px; color:#9ca3af;"><?php echo esc_html( wp_trim_words( $desc_val, 8 ) ); ?></div>
									<?php endif; ?>
								</td>
								<td>
									<span class="badge-cat"><?php echo esc_html( $cat_val ); ?></span>
									<span class="badge-dept"><?php echo esc_html( $dept_val ); ?></span>
								</td>
								<td>
									<span class="price-text">
										<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $amount_val ) : (string) number_format_i18n( $amount_val, 2 ) ); ?>
									</span>
								</td>
								<td style="text-align:right;">
									<div style="display:flex; gap:8px; justify-content:flex-end;">
										<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small" title="<?php esc_attr_e( 'Edit Record', 'awesome-hotel-booking' ); ?>">
											<span class="dashicons dashicons-edit" style="margin-top:4px;" aria-hidden="true"></span>
										</a>
										<a 
											href="<?php echo esc_url( $del_url ); ?>" 
											class="button button-small" 
											style="color:#ef4444;" 
											title="<?php esc_attr_e( 'Delete Record', 'awesome-hotel-booking' ); ?>"
											onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this expense record?', 'awesome-hotel-booking' ) ); ?>');"
										>
											<span class="dashicons dashicons-trash" style="margin-top:4px;" aria-hidden="true"></span>
										</a>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="5" style="text-align:center; padding:50px; color:#9ca3af;">
								<?php esc_html_e( 'No expense records found.', 'awesome-hotel-booking' ); ?>
							</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $total_pages > 1 ) : ?>
			<div class="tablenav" style="margin-top:15px;">
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
	<?php
}