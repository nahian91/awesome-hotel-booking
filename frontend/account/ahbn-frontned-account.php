<?php
/**
 * [ahbn_account] Guest Customer Account Portal Shortcode.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode callback for user dashboard and booking history.
 *
 * @return string Rendered HTML output.
 */
function ahbn_account_shortcode_callback() {
	// 1. Security & Authentication Check.
	if ( ! is_user_logged_in() ) {
		$login_url = wp_login_url( get_permalink() );
		ob_start();
		?>
		<div class="ahbn-auth-locked">
			<div class="ahbn-lock-icon" aria-hidden="true">&#x1F512;</div>
			<h2><?php esc_html_e( 'Guest Portal Access', 'awesome-hotel-booking' ); ?></h2>
			<p><?php esc_html_e( 'Please sign in to manage your luxury reservations and guest profile.', 'awesome-hotel-booking' ); ?></p>
			<a href="<?php echo esc_url( $login_url ); ?>" class="ahbn-login-btn-link">
				<?php esc_html_e( 'Sign In to Account', 'awesome-hotel-booking' ); ?>
			</a>
		</div>
		<style>
			.ahbn-auth-locked { max-width: 450px; margin: 80px auto; text-align: center; background: #fff; padding: 50px; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
			.ahbn-lock-icon { font-size: 50px; margin-bottom: 20px; }
			.ahbn-login-btn-link { display: inline-block; background: #0f172a; color: #fff; padding: 14px 30px; border-radius: 10px; text-decoration: none; font-weight: 700; margin-top: 20px; transition: 0.3s; }
			.ahbn-login-btn-link:hover { background: #bf9441; color: #fff; }
		</style>
		<?php
		return ob_get_clean();
	}

	$current_user   = wp_get_current_user();
	$user_id        = $current_user->ID;
	$user_email     = sanitize_email( $current_user->user_email );
	$ahbn_status_msg = '';

	// 2. Profile & Security Updates.
	if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) && isset( $_POST['ahbn_save_account'], $_POST['ahbn_nonce'] ) ) {
		$nonce = sanitize_key( wp_unslash( $_POST['ahbn_nonce'] ) );

		if ( wp_verify_nonce( $nonce, 'ahbn_account_action' ) ) {
			if ( isset( $_POST['fname'] ) ) {
				update_user_meta( $user_id, 'first_name', sanitize_text_field( wp_unslash( $_POST['fname'] ) ) );
			}

			if ( isset( $_POST['phone'] ) ) {
				update_user_meta( $user_id, 'billing_phone', sanitize_text_field( wp_unslash( $_POST['phone'] ) ) );
			}

			if ( isset( $_POST['address'] ) ) {
				update_user_meta( $user_id, 'billing_address_1', sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) );
			}

			// Password Handling.
			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$new_pass  = isset( $_POST['new_pass'] ) ? wp_unslash( $_POST['new_pass'] ) : '';
			$conf_pass = isset( $_POST['conf_pass'] ) ? wp_unslash( $_POST['conf_pass'] ) : '';
			// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			if ( ! empty( $new_pass ) ) {
				if ( $new_pass === $conf_pass ) {
					wp_set_password( $new_pass, $user_id );
					$ahbn_status_msg = '<div class="ahbn-alert success">' . esc_html__( 'Password updated. Please sign in again.', 'awesome-hotel-booking' ) . '</div>';
				} else {
					$ahbn_status_msg = '<div class="ahbn-alert error">' . esc_html__( 'Passwords do not match.', 'awesome-hotel-booking' ) . '</div>';
				}
			} else {
				$ahbn_status_msg = '<div class="ahbn-alert success">' . esc_html__( 'Profile updated successfully.', 'awesome-hotel-booking' ) . '</div>';
			}

			// Refresh user object after meta update.
			$current_user = wp_get_current_user();
		} else {
			$ahbn_status_msg = '<div class="ahbn-alert error">' . esc_html__( 'Security verification failed. Please try again.', 'awesome-hotel-booking' ) . '</div>';
		}
	}

	// 3. Data Fetch: User Bookings.
	$cache_key     = 'ahbn_user_bookings_' . $user_id;
	$ahbn_bookings = get_transient( $cache_key );

	if ( false === $ahbn_bookings ) {
		$args = array(
			'post_type'              => 'ahbn_booking',
			'posts_per_page'         => -1,
			'post_status'            => 'publish',
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'meta_query'             => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array(
					'key'     => 'user_id',
					'value'   => $user_id,
					'compare' => '=',
				),
				array(
					'key'     => 'ahbn_book_email',
					'value'   => $user_email,
					'compare' => '=',
				),
			),
			'orderby'                => 'date',
			'order'                  => 'DESC',
		);

		$query         = new WP_Query( $args );
		$ahbn_bookings = $query->posts;

		set_transient( $cache_key, $ahbn_bookings, 12 * HOUR_IN_SECONDS );
	}

	// Statistics.
	$total_spent = 0.0;
	if ( ! empty( $ahbn_bookings ) ) {
		foreach ( $ahbn_bookings as $booking_post ) {
			$raw_p       = get_post_meta( $booking_post->ID, 'ahbn_book_total', true );
			$total_spent += (float) preg_replace( '/[^0-9.]/', '', (string) $raw_p );
		}
	}

	$display_name = ! empty( $current_user->first_name ) ? $current_user->first_name : $current_user->display_name;

	ob_start();
	?>
	<div class="ahbn-portal-container">
		<aside class="ahbn-sidebar">
			<div class="ahbn-user-profile">
				<?php echo get_avatar( $user_id, 80 ); ?>
				<strong><?php echo esc_html( $display_name ); ?></strong>
				<span><?php esc_html_e( 'Premium Guest Member', 'awesome-hotel-booking' ); ?></span>
			</div>
			<ul class="ahbn-menu">
				<li class="active"><a class="ahbn-trigger" data-tab="tab-dashboard">&#128202; <?php esc_html_e( 'Dashboard', 'awesome-hotel-booking' ); ?></a></li>
				<li><a class="ahbn-trigger" data-tab="tab-bookings">&#128197; <?php esc_html_e( 'Bookings', 'awesome-hotel-booking' ); ?></a></li>
				<li><a class="ahbn-trigger" data-tab="tab-profile">&#128100; <?php esc_html_e( 'Profile', 'awesome-hotel-booking' ); ?></a></li>
				<li><a class="ahbn-trigger" data-tab="tab-password">&#128274; <?php esc_html_e( 'Password', 'awesome-hotel-booking' ); ?></a></li>
				<li style="margin-top:20px; border-top:1px solid #eee; padding-top:15px;">
					<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" style="color:#ef4444;">&#128682; <?php esc_html_e( 'Sign Out', 'awesome-hotel-booking' ); ?></a>
				</li>
			</ul>
		</aside>

		<main class="ahbn-main-content">
			<?php echo wp_kses_post( $ahbn_status_msg ); ?>

			<div id="tab-dashboard" class="ahbn-tab-content active">
				<h2 style="margin-top:0; font-weight:800;">
					<?php
					printf(
						/* translators: %s: Guest first name */
						esc_html__( 'Welcome Back, %s', 'awesome-hotel-booking' ),
						esc_html( $display_name )
					);
					?>
				</h2>
				<div class="ahbn-stats-row">
					<div class="ahbn-stat-box">
						<b><?php echo esc_html( (string) count( $ahbn_bookings ) ); ?></b>
						<small><?php esc_html_e( 'Total Stays', 'awesome-hotel-booking' ); ?></small>
					</div>
					<div class="ahbn-stat-box">
						<b><?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $total_spent ) : (string) number_format_i18n( $total_spent, 2 ) ); ?></b>
						<small><?php esc_html_e( 'Total Spent', 'awesome-hotel-booking' ); ?></small>
					</div>
					<div class="ahbn-stat-box">
						<b><?php echo esc_html( wp_date( 'Y', strtotime( $current_user->user_registered ) ) ); ?></b>
						<small><?php esc_html_e( 'Member Since', 'awesome-hotel-booking' ); ?></small>
					</div>
				</div>
				<div style="background:#0f172a; color:#fff; padding:35px; border-radius:18px;">
					<h3 style="margin:0; font-size:20px;"><?php esc_html_e( 'Planning your next visit?', 'awesome-hotel-booking' ); ?></h3>
					<p style="color:#94a3b8; margin:10px 0 20px; font-size:14px;">
						<?php esc_html_e( 'Explore our premium suites and book directly for exclusive upgrades and loyalty perks.', 'awesome-hotel-booking' ); ?>
					</p>
					<a href="<?php echo esc_url( home_url( '/rooms' ) ); ?>" style="background:#bf9441; color:#fff; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:700; display:inline-block;">
						<?php esc_html_e( 'Browse Available Rooms', 'awesome-hotel-booking' ); ?>
					</a>
				</div>
			</div>

			<div id="tab-bookings" class="ahbn-tab-content">
				<h2 style="margin-top:0; font-weight:800;"><?php esc_html_e( 'Your Stay History', 'awesome-hotel-booking' ); ?></h2>
				<?php if ( ! empty( $ahbn_bookings ) ) : ?>
					<?php
					foreach ( $ahbn_bookings as $booking ) :
						$rid          = absint( get_post_meta( $booking->ID, 'ahbn_book_room_id', true ) );
						$raw_status   = (string) get_post_meta( $booking->ID, 'status', true );
						$status_val   = ! empty( $raw_status ) ? sanitize_key( $raw_status ) : 'pending';
						$room_no      = (string) get_post_meta( $rid, 'ahbn_room_no', true );
						$checkin_str  = (string) get_post_meta( $booking->ID, 'ahbn_book_checkin', true );
						$checkout_str = (string) get_post_meta( $booking->ID, 'ahbn_book_checkout', true );
						$total_str    = get_post_meta( $booking->ID, 'ahbn_book_total', true );
						$total_clean  = (float) preg_replace( '/[^0-9.]/', '', (string) $total_str );
						?>
						<div class="ahbn-res-card">
							<div style="display:flex; gap:20px; align-items:center;">
								<?php if ( $rid && has_post_thumbnail( $rid ) ) : ?>
									<?php echo get_the_post_thumbnail( $rid, array( 70, 70 ), array( 'class' => 'ahbn-res-img', 'style' => 'border-radius:12px; object-fit:cover;' ) ); ?>
								<?php else : ?>
									<div style="width:70px; height:70px; background:#f1f5f9; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#94a3b8; font-size:24px;">&#127976;</div>
								<?php endif; ?>
								<div>
									<h4 style="margin:0; font-size:16px; color:#0f172a;">
										<?php echo esc_html( $rid ? get_the_title( $rid ) : __( 'Standard Room', 'awesome-hotel-booking' ) ); ?>
										<?php if ( ! empty( $room_no ) ) : ?>
											<small style="color:#bf9441;">
												(<?php
												printf(
													/* translators: %s: Room number */
													esc_html__( 'Rm %s', 'awesome-hotel-booking' ),
													esc_html( $room_no )
												);
												?>)
											</small>
										<?php endif; ?>
									</h4>
									<div style="font-size:13px; color:#64748b; margin-top:5px;">
										&#128197; <?php echo esc_html( ! empty( $checkin_str ) ? $checkin_str : '—' ); ?> — <?php echo esc_html( ! empty( $checkout_str ) ? $checkout_str : '—' ); ?>
									</div>
								</div>
							</div>
							<div style="text-align:right;">
								<div style="font-weight:800; color:#0f172a; font-size:18px; margin-bottom:5px;">
									<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $total_clean ) : (string) number_format_i18n( $total_clean, 2 ) ); ?>
								</div>
								<span class="status-pill status-<?php echo esc_attr( $status_val ); ?>">
									<?php echo esc_html( ucfirst( $status_val ) ); ?>
								</span>
							</div>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<div style="text-align:center; padding:60px; color:#94a3b8; border:1px dashed #cbd5e1; border-radius:16px;">
						<?php esc_html_e( "You haven't made any reservations yet.", 'awesome-hotel-booking' ); ?>
					</div>
				<?php endif; ?>
			</div>

			<div id="tab-profile" class="ahbn-tab-content">
				<h2 style="margin-top:0; font-weight:800;"><?php esc_html_e( 'Guest Profile', 'awesome-hotel-booking' ); ?></h2>
				<form method="POST" action="">
					<?php wp_nonce_field( 'ahbn_account_action', 'ahbn_nonce' ); ?>
					<div class="ahbn-field-group">
						<label for="ahbn_fname"><?php esc_html_e( 'First / Display Name', 'awesome-hotel-booking' ); ?></label>
						<input type="text" id="ahbn_fname" name="fname" value="<?php echo esc_attr( $current_user->first_name ); ?>" required>
					</div>
					<div class="ahbn-field-group">
						<label for="ahbn_phone"><?php esc_html_e( 'Phone Number', 'awesome-hotel-booking' ); ?></label>
						<input type="text" id="ahbn_phone" name="phone" value="<?php echo esc_attr( get_user_meta( $user_id, 'billing_phone', true ) ); ?>">
					</div>
					<div class="ahbn-field-group">
						<label for="ahbn_address"><?php esc_html_e( 'Address', 'awesome-hotel-booking' ); ?></label>
						<textarea id="ahbn_address" name="address" rows="3"><?php echo esc_textarea( get_user_meta( $user_id, 'billing_address_1', true ) ); ?></textarea>
					</div>
					<button type="submit" name="ahbn_save_account" class="ahbn-submit-btn">
						<?php esc_html_e( 'Update Profile Information', 'awesome-hotel-booking' ); ?>
					</button>
				</form>
			</div>

			<div id="tab-password" class="ahbn-tab-content">
				<h2 style="margin-top:0; font-weight:800;"><?php esc_html_e( 'Security Settings', 'awesome-hotel-booking' ); ?></h2>
				<form method="POST" action="">
					<?php wp_nonce_field( 'ahbn_account_action', 'ahbn_nonce' ); ?>
					<p style="color:#64748b; margin-bottom:25px;">
						<?php esc_html_e( 'Update your password to keep your account secure.', 'awesome-hotel-booking' ); ?>
					</p>
					<div class="ahbn-field-group">
						<label for="ahbn_new_pass"><?php esc_html_e( 'New Password', 'awesome-hotel-booking' ); ?></label>
						<input type="password" id="ahbn_new_pass" name="new_pass" placeholder="<?php esc_attr_e( 'Enter new password', 'awesome-hotel-booking' ); ?>">
					</div>
					<div class="ahbn-field-group">
						<label for="ahbn_conf_pass"><?php esc_html_e( 'Confirm New Password', 'awesome-hotel-booking' ); ?></label>
						<input type="password" id="ahbn_conf_pass" name="conf_pass" placeholder="<?php esc_attr_e( 'Repeat new password', 'awesome-hotel-booking' ); ?>">
					</div>
					<button type="submit" name="ahbn_save_account" class="ahbn-submit-btn">
						<?php esc_html_e( 'Update Account Password', 'awesome-hotel-booking' ); ?>
					</button>
				</form>
			</div>
		</main>
	</div>

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		const triggers = document.querySelectorAll('.ahbn-trigger');
		const contents = document.querySelectorAll('.ahbn-tab-content');

		triggers.forEach(trigger => {
			trigger.addEventListener('click', function(e) {
				e.preventDefault();
				triggers.forEach(t => t.parentElement.classList.remove('active'));
				this.parentElement.classList.add('active');

				contents.forEach(c => c.classList.remove('active'));
				const targetId = this.dataset.tab;
				const targetEl = document.getElementById(targetId);
				if (targetEl) {
					targetEl.classList.add('active');
				}
			});
		});
	});
	</script>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ahbn_account', 'ahbn_account_shortcode_callback' );