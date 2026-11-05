<?php
/**
 * [ahbn_account] Guest Customer Account Portal Shortcode.
 *
 * Rewritten for custom SQL database tables:
 * {$wpdb->prefix}ahbn_bookings joined with {$wpdb->prefix}ahbn_rooms.
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
			.ahbn-auth-locked { max-width: 450px; margin: 80px auto; text-align: center; background: #fff; padding: 50px; border-radius: 20px; box-shadow: 0 20px 40px rgba(10,25,47,0.06); border: 1px solid #e2e8f0; font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
			.ahbn-lock-icon { font-size: 42px; margin-bottom: 20px; }
			.ahbn-login-btn-link { display: inline-block; background: #0a192f; color: #fff; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 700; margin-top: 20px; transition: 0.3s; }
			.ahbn-login-btn-link:hover { background: #c5a880; color: #0a192f; }
		</style>
		<?php
		return ob_get_clean();
	}

	global $wpdb;
	$current_user    = wp_get_current_user();
	$user_id         = (int) $current_user->ID;
	$user_email      = sanitize_email( $current_user->user_email );
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
					$ahbn_status_msg = '<div class="ahbn-alert success"><div class="ahbn-alert-content"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><span>' . esc_html__( 'Password updated. Please sign in again.', 'awesome-hotel-booking' ) . '</span></div><button type="button" class="ahbn-alert-close" aria-label="' . esc_attr__( 'Dismiss alert', 'awesome-hotel-booking' ) . '">&times;</button></div>';
				} else {
					$ahbn_status_msg = '<div class="ahbn-alert error"><div class="ahbn-alert-content"><span class="dashicons dashicons-warning" aria-hidden="true"></span><span>' . esc_html__( 'Passwords do not match.', 'awesome-hotel-booking' ) . '</span></div><button type="button" class="ahbn-alert-close" aria-label="' . esc_attr__( 'Dismiss alert', 'awesome-hotel-booking' ) . '">&times;</button></div>';
				}
			} else {
				$ahbn_status_msg = '<div class="ahbn-alert success"><div class="ahbn-alert-content"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><span>' . esc_html__( 'Profile updated successfully.', 'awesome-hotel-booking' ) . '</span></div><button type="button" class="ahbn-alert-close" aria-label="' . esc_attr__( 'Dismiss alert', 'awesome-hotel-booking' ) . '">&times;</button></div>';
			}

			// Refresh user object and clear transient cache.
			$current_user = wp_get_current_user();
			delete_transient( 'ahbn_user_bookings_' . $user_id );
		} else {
			$ahbn_status_msg = '<div class="ahbn-alert error"><div class="ahbn-alert-content"><span class="dashicons dashicons-warning" aria-hidden="true"></span><span>' . esc_html__( 'Security verification failed. Please try again.', 'awesome-hotel-booking' ) . '</span></div><button type="button" class="ahbn-alert-close" aria-label="' . esc_attr__( 'Dismiss alert', 'awesome-hotel-booking' ) . '">&times;</button></div>';
		}
	}

	// 3. Data Fetch: User Bookings from Custom Tables.
	$cache_key     = 'ahbn_user_bookings_' . $user_id;
	$ahbn_bookings = get_transient( $cache_key );

	if ( false === $ahbn_bookings ) {
		$table_bookings = $wpdb->prefix . 'ahbn_bookings';
		$table_rooms    = $wpdb->prefix . 'ahbn_rooms';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ahbn_bookings = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT b.*, r.room_number, r.title AS room_title, r.room_type, r.thumbnail_id
				 FROM {$table_bookings} b
				 LEFT JOIN {$table_rooms} r ON b.room_id = r.id
				 WHERE b.guest_email = %s
				 ORDER BY b.checkin_date DESC, b.id DESC",
				$user_email
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		set_transient( $cache_key, $ahbn_bookings, 12 * HOUR_IN_SECONDS );
	}

	// Calculate Total Spent.
	$total_spent = 0.0;
	if ( ! empty( $ahbn_bookings ) ) {
		foreach ( $ahbn_bookings as $booking ) {
			if ( 'cancelled' !== $booking->status ) {
				$total_spent += (float) $booking->total_amount;
			}
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
				<li class="active"><a href="#tab-dashboard" class="ahbn-trigger" data-tab="tab-dashboard"><span class="dashicons dashicons-performance" aria-hidden="true"></span> <?php esc_html_e( 'Dashboard', 'awesome-hotel-booking' ); ?></a></li>
				<li><a href="#tab-bookings" class="ahbn-trigger" data-tab="tab-bookings"><span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span> <?php esc_html_e( 'Bookings', 'awesome-hotel-booking' ); ?></a></li>
				<li><a href="#tab-profile" class="ahbn-trigger" data-tab="tab-profile"><span class="dashicons dashicons-admin-users" aria-hidden="true"></span> <?php esc_html_e( 'Profile', 'awesome-hotel-booking' ); ?></a></li>
				<li><a href="#tab-password" class="ahbn-trigger" data-tab="tab-password"><span class="dashicons dashicons-lock" aria-hidden="true"></span> <?php esc_html_e( 'Password', 'awesome-hotel-booking' ); ?></a></li>
				<li style="margin-top:20px; border-top:1px solid #e2e8f0; padding-top:15px;">
					<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" style="color:#ef4444;"><span class="dashicons dashicons-external" aria-hidden="true"></span> <?php esc_html_e( 'Sign Out', 'awesome-hotel-booking' ); ?></a>
				</li>
			</ul>
		</aside>

		<main class="ahbn-main-content">
			<?php echo wp_kses_post( $ahbn_status_msg ); ?>

			<!-- Tab: Dashboard -->
			<div id="tab-dashboard" class="ahbn-tab-content active">
				<h2 style="margin-top:0; font-family:'Playfair Display',Georgia,serif; font-size:26px; color:#0a192f;">
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
						<b><?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $total_spent ) : '$' . number_format( $total_spent, 2 ) ); ?></b>
						<small><?php esc_html_e( 'Total Spent', 'awesome-hotel-booking' ); ?></small>
					</div>
					<div class="ahbn-stat-box">
						<b><?php echo esc_html( wp_date( 'Y', strtotime( $current_user->user_registered ) ) ); ?></b>
						<small><?php esc_html_e( 'Member Since', 'awesome-hotel-booking' ); ?></small>
					</div>
				</div>
				<div style="background:linear-gradient(135deg, #07111e 0%, #0a192f 100%); color:#fff; padding:35px; border-radius:18px; border:1px solid rgba(197,168,128,0.2);">
					<h3 style="margin:0; font-size:20px; font-family:'Playfair Display',Georgia,serif; color:#ffffff;"><?php esc_html_e( 'Planning your next visit?', 'awesome-hotel-booking' ); ?></h3>
					<p style="color:#94a3b8; margin:10px 0 20px; font-size:14px;">
						<?php esc_html_e( 'Explore our premium suites and book directly for exclusive upgrades and loyalty perks.', 'awesome-hotel-booking' ); ?>
					</p>
					<a href="<?php echo esc_url( home_url( '/rooms' ) ); ?>" style="background:#c5a880; color:#0a192f; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:700; display:inline-block; transition:0.2s;">
						<?php esc_html_e( 'Browse Available Rooms', 'awesome-hotel-booking' ); ?>
					</a>
				</div>
			</div>

			<!-- Tab: Bookings -->
			<div id="tab-bookings" class="ahbn-tab-content">
				<h2 style="margin-top:0; font-family:'Playfair Display',Georgia,serif; font-size:26px; color:#0a192f;"><?php esc_html_e( 'Your Stay History', 'awesome-hotel-booking' ); ?></h2>
				<?php if ( ! empty( $ahbn_bookings ) ) : ?>
					<div class="ahbn-res-list">
						<?php foreach ( $ahbn_bookings as $booking ) :
							$status_val  = ! empty( $booking->status ) ? sanitize_key( $booking->status ) : 'pending';
							$suite_title = ! empty( $booking->room_title ) ? $booking->room_title : __( 'Luxury Suite', 'awesome-hotel-booking' );
							if ( ! empty( $booking->room_number ) ) {
								$suite_title = '#' . $booking->room_number . ' ' . $suite_title;
							}
							$total_clean = (float) $booking->total_amount;
							
							// Thumbnail URL
							$thumb_url = '';
							if ( ! empty( $booking->thumbnail_id ) ) {
								$thumb_url = wp_get_attachment_image_url( (int) $booking->thumbnail_id, 'thumbnail' );
							}
							?>
							<div class="ahbn-res-card">
								<div style="display:flex; gap:16px; align-items:center;">
									<?php if ( ! empty( $thumb_url ) ) : ?>
										<img src="<?php echo esc_url( $thumb_url ); ?>" alt="" class="ahbn-res-img" style="width:64px; height:64px; border-radius:10px; object-fit:cover; border:1px solid #e2e8f0;">
									<?php else : ?>
										<div style="width:64px; height:64px; background:#f8fafc; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#94a3b8; border:1px solid #e2e8f0;">
											<span class="dashicons dashicons-building" aria-hidden="true"></span>
										</div>
									<?php endif; ?>
									<div>
										<span class="ahbn-folio-code"><?php echo esc_html( ! empty( $booking->booking_code ) ? $booking->booking_code : '#' . $booking->id ); ?></span>
										<h4 style="margin:4px 0; font-size:15px; color:#0f172a;"><?php echo esc_html( $suite_title ); ?></h4>
										<div style="font-size:12px; color:#64748b;">
											<span class="dashicons dashicons-calendar" style="font-size:14px; width:14px; height:14px; vertical-align:middle;" aria-hidden="true"></span> 
											<?php echo esc_html( ! empty( $booking->checkin_date ) ? wp_date( 'M j, Y', strtotime( $booking->checkin_date ) ) : '—' ); ?> &rarr; <?php echo esc_html( ! empty( $booking->checkout_date ) ? wp_date( 'M j, Y', strtotime( $booking->checkout_date ) ) : '—' ); ?>
										</div>
									</div>
								</div>
								<div style="text-align:right;">
									<div style="font-family:'JetBrains Mono',monospace; font-weight:800; color:#0a192f; font-size:16px; margin-bottom:6px;">
										<?php echo esc_html( function_exists( 'ahbn_format_price' ) ? ahbn_format_price( $total_clean ) : '$' . number_format( $total_clean, 2 ) ); ?>
									</div>
									<span class="ahbn-status-badge status-<?php echo esc_attr( $status_val ); ?>">
										<?php echo esc_html( ucfirst( str_replace( '_', ' ', $status_val ) ) ); ?>
									</span>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<div style="text-align:center; padding:60px; color:#94a3b8; border:1px dashed #cbd5e1; border-radius:16px; background:#fafafa;">
						<span class="dashicons dashicons-calendar-alt" style="font-size:36px; width:36px; height:36px; color:#cbd5e1;" aria-hidden="true"></span>
						<p style="margin:10px 0 0; font-size:14px;"><?php esc_html_e( "You haven't made any reservations yet.", 'awesome-hotel-booking' ); ?></p>
					</div>
				<?php endif; ?>
			</div>

			<!-- Tab: Profile -->
			<div id="tab-profile" class="ahbn-tab-content">
				<h2 style="margin-top:0; font-family:'Playfair Display',Georgia,serif; font-size:26px; color:#0a192f;"><?php esc_html_e( 'Guest Profile', 'awesome-hotel-booking' ); ?></h2>
				<form method="POST" action="">
					<?php wp_nonce_field( 'ahbn_account_action', 'ahbn_nonce' ); ?>
					<div class="ahbn-field-group">
						<label for="ahbn_fname"><?php esc_html_e( 'Display / First Name', 'awesome-hotel-booking' ); ?></label>
						<input type="text" id="ahbn_fname" name="fname" value="<?php echo esc_attr( $current_user->first_name ); ?>" required class="ahbn-input-field">
					</div>
					<div class="ahbn-field-group">
						<label for="ahbn_phone"><?php esc_html_e( 'Phone Number', 'awesome-hotel-booking' ); ?></label>
						<input type="text" id="ahbn_phone" name="phone" value="<?php echo esc_attr( get_user_meta( $user_id, 'billing_phone', true ) ); ?>" class="ahbn-input-field">
					</div>
					<div class="ahbn-field-group">
						<label for="ahbn_address"><?php esc_html_e( 'Billing Address', 'awesome-hotel-booking' ); ?></label>
						<textarea id="ahbn_address" name="address" rows="3" class="ahbn-textarea-field"><?php echo esc_textarea( get_user_meta( $user_id, 'billing_address_1', true ) ); ?></textarea>
					</div>
					<button type="submit" name="ahbn_save_account" class="ahbn-submit-btn">
						<?php esc_html_e( 'Update Profile Information', 'awesome-hotel-booking' ); ?>
					</button>
				</form>
			</div>

			<!-- Tab: Password -->
			<div id="tab-password" class="ahbn-tab-content">
				<h2 style="margin-top:0; font-family:'Playfair Display',Georgia,serif; font-size:26px; color:#0a192f;"><?php esc_html_e( 'Security Settings', 'awesome-hotel-booking' ); ?></h2>
				<form method="POST" action="">
					<?php wp_nonce_field( 'ahbn_account_action', 'ahbn_nonce' ); ?>
					<p style="color:#64748b; margin-bottom:25px; font-size:13px;">
						<?php esc_html_e( 'Update your password to keep your account secure.', 'awesome-hotel-booking' ); ?>
					</p>
					<div class="ahbn-field-group">
						<label for="ahbn_new_pass"><?php esc_html_e( 'New Password', 'awesome-hotel-booking' ); ?></label>
						<input type="password" id="ahbn_new_pass" name="new_pass" placeholder="<?php esc_attr_e( 'Enter new password', 'awesome-hotel-booking' ); ?>" class="ahbn-input-field">
					</div>
					<div class="ahbn-field-group">
						<label for="ahbn_conf_pass"><?php esc_html_e( 'Confirm New Password', 'awesome-hotel-booking' ); ?></label>
						<input type="password" id="ahbn_conf_pass" name="conf_pass" placeholder="<?php esc_attr_e( 'Repeat new password', 'awesome-hotel-booking' ); ?>" class="ahbn-input-field">
					</div>
					<button type="submit" name="ahbn_save_account" class="ahbn-submit-btn">
						<?php esc_html_e( 'Update Account Password', 'awesome-hotel-booking' ); ?>
					</button>
				</form>
			</div>
		</main>
	</div>

	<style>
		.ahbn-portal-container {
			display: grid;
			grid-template-columns: 280px 1fr;
			gap: 30px;
			max-width: 1200px;
			margin: 40px auto;
			padding: 0 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
			box-sizing: border-box;
		}

		.ahbn-portal-container * {
			box-sizing: border-box;
		}

		/* Sidebar */
		.ahbn-sidebar {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 16px;
			padding: 24px;
			height: fit-content;
			box-shadow: 0 4px 20px -2px rgba(10, 20, 38, 0.04);
		}

		.ahbn-user-profile {
			text-align: center;
			padding-bottom: 20px;
			border-bottom: 1px solid #f1f5f9;
			margin-bottom: 16px;
		}

		.ahbn-user-profile img {
			border-radius: 50%;
			border: 2px solid #c5a880;
			margin-bottom: 10px;
		}

		.ahbn-user-profile strong {
			display: block;
			font-size: 16px;
			color: #0a192f;
		}

		.ahbn-user-profile span {
			font-size: 11px;
			color: #64748b;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em;
		}

		.ahbn-menu {
			list-style: none;
			margin: 0;
			padding: 0;
		}

		.ahbn-menu li {
			margin-bottom: 6px;
		}

		.ahbn-menu li a {
			display: flex;
			align-items: center;
			gap: 10px;
			padding: 10px 14px;
			border-radius: 8px;
			color: #475569;
			text-decoration: none;
			font-size: 13px;
			font-weight: 600;
			transition: all 0.2s ease;
		}

		.ahbn-menu li a .dashicons {
			color: #94a3b8;
			font-size: 18px;
			width: 18px;
			height: 18px;
			transition: color 0.2s ease;
		}

		.ahbn-menu li a:hover {
			background: #f8fafc;
			color: #0a192f;
		}

		.ahbn-menu li.active a {
			background: #0a192f;
			color: #ffffff;
		}

		.ahbn-menu li.active a .dashicons {
			color: #c5a880;
		}

		/* Main Content Box */
		.ahbn-main-content {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 16px;
			padding: 32px;
			box-shadow: 0 4px 20px -2px rgba(10, 20, 38, 0.04);
		}

		.ahbn-tab-content {
			display: none;
		}

		.ahbn-tab-content.active {
			display: block;
		}

		/* Alerts with dismiss close button */
		.ahbn-alert {
			padding: 12px 18px;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 600;
			margin-bottom: 24px;
			display: flex;
			align-items: center;
			justify-content: space-between;
			animation: ahbnAlertFadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1);
		}

		@keyframes ahbnAlertFadeIn {
			from { opacity: 0; transform: translateY(-4px); }
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
			color: inherit;
			opacity: 0.6;
			cursor: pointer;
			padding: 0 0 0 10px;
			transition: opacity 0.15s ease;
		}

		.ahbn-alert-close:hover {
			opacity: 1;
		}

		.ahbn-alert.success {
			background: #ecfdf5;
			color: #065f46;
			border: 1px solid #a7f3d0;
			border-left: 4px solid #10b981;
		}

		.ahbn-alert.error {
			background: #fff1f2;
			color: #9f1239;
			border: 1px solid #fecdd3;
			border-left: 4px solid #ef4444;
		}

		/* Dashboard Stats */
		.ahbn-stats-row {
			display: grid;
			grid-template-columns: repeat(3, 1fr);
			gap: 16px;
			margin-bottom: 24px;
		}

		.ahbn-stat-box {
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 20px;
			text-align: center;
		}

		.ahbn-stat-box b {
			display: block;
			font-family: 'JetBrains Mono', monospace;
			font-size: 26px;
			font-weight: 800;
			color: #0a192f;
			margin-bottom: 4px;
		}

		.ahbn-stat-box small {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #64748b;
		}

		/* Reservations List */
		.ahbn-res-list {
			display: flex;
			flex-direction: column;
			gap: 14px;
		}

		.ahbn-res-card {
			display: flex;
			justify-content: space-between;
			align-items: center;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 16px 20px;
			gap: 16px;
		}

		.ahbn-folio-code {
			font-family: 'JetBrains Mono', monospace;
			font-size: 11px;
			font-weight: 700;
			background: #f1f5f9;
			padding: 2px 6px;
			border-radius: 4px;
			border: 1px solid #cbd5e1;
			color: #0a192f;
			display: inline-block;
		}

		.ahbn-status-badge {
			display: inline-block;
			padding: 3px 10px;
			border-radius: 99px;
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
		}

		.status-confirmed  { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
		.status-checked_in { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
		.status-checked_out{ background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; }
		.status-pending    { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
		.status-cancelled  { background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; }

		/* Form Fields */
		.ahbn-field-group {
			display: flex;
			flex-direction: column;
			gap: 6px;
			margin-bottom: 18px;
		}

		.ahbn-field-group label {
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.05em;
			color: #334155;
		}

		.ahbn-input-field,
		.ahbn-textarea-field {
			width: 100%;
			border: 1px solid #cbd5e1 !important;
			border-radius: 8px !important;
			padding: 10px 14px !important;
			font-size: 13px !important;
			background: #ffffff !important;
			color: #0f172a !important;
			outline: none;
		}

		.ahbn-input-field:focus,
		.ahbn-textarea-field:focus {
			border-color: #0a192f !important;
			box-shadow: 0 0 0 2px rgba(10, 25, 47, 0.1) !important;
		}

		.ahbn-submit-btn {
			background: #0a192f;
			color: #ffffff;
			border: 1px solid #0a192f;
			padding: 10px 22px;
			border-radius: 8px;
			font-size: 13px;
			font-weight: 700;
			cursor: pointer;
			transition: all 0.2s ease;
		}

		.ahbn-submit-btn:hover {
			background: #c5a880;
			border-color: #c5a880;
			color: #0a192f;
		}

		@media (max-width: 850px) {
			.ahbn-portal-container {
				grid-template-columns: 1fr;
			}
			.ahbn-stats-row {
				grid-template-columns: 1fr;
			}
		}
	</style>

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

		// Dismissible alert handler
		document.addEventListener('click', function(e) {
			if (e.target.matches('.ahbn-alert-close')) {
				e.preventDefault();
				const alertBox = e.target.closest('.ahbn-alert');
				if (alertBox) {
					alertBox.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
					alertBox.style.opacity = '0';
					alertBox.style.transform = 'translateY(-4px)';
					setTimeout(() => alertBox.remove(), 200);
				}
			}
		});
	});
	</script>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ahbn_account', 'ahbn_account_shortcode_callback' );