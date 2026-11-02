<?php
/**
 * [ahbn_header_auth] Header Authentication & Profile Bar Shortcode.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode callback for header authentication buttons and profile avatar.
 *
 * @return string Rendered HTML output.
 */
function ahbn_header_auth_shortcode_callback() {
	ob_start();
	?>
	<style>
		.ahbn-header-auth {
			display: flex;
			align-items: center;
			font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
		}

		/* User Profile Style (Logged In) */
		.ahbn-user-wrapper {
			display: flex;
			align-items: center;
			gap: 12px;
			text-decoration: none;
			padding: 6px 12px;
			border-radius: 50px;
			transition: all 0.3s ease;
			background: rgba(26, 43, 72, 0.05);
		}

		.ahbn-user-wrapper:hover {
			background: rgba(26, 43, 72, 0.1);
		}

		.ahbn-avatar-img {
			width: 36px;
			height: 36px;
			border-radius: 50%;
			object-fit: cover;
			border: 2px solid #bf9441;
		}

		.ahbn-user-info {
			display: flex;
			flex-direction: column;
			line-height: 1.2;
		}

		.ahbn-hi {
			font-size: 11px;
			color: #64748b;
			font-weight: 500;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}

		.ahbn-user-name {
			font-size: 14px;
			font-weight: 700;
			color: #1a2b48;
		}

		/* Auth Group Style (Logged Out) */
		.ahbn-auth-group {
			display: flex;
			align-items: center;
			gap: 15px;
		}

		.ahbn-h-btn {
			text-decoration: none;
			font-size: 14px;
			font-weight: 600;
			transition: all 0.3s ease;
			display: flex;
			align-items: center;
			gap: 6px;
		}

		.ahbn-h-login {
			color: #1a2b48;
			padding: 8px 16px;
		}

		.ahbn-h-login:hover {
			color: #bf9441;
		}

		.ahbn-h-icon {
			width: 18px;
			height: 18px;
			fill: currentColor;
		}

		.ahbn-h-reg {
			background: #bf9441;
			color: #fff !important;
			padding: 10px 22px;
			border-radius: 8px;
			box-shadow: 0 4px 10px rgba(191, 148, 65, 0.2);
		}

		.ahbn-h-reg:hover {
			background: #1a2b48;
			transform: translateY(-1px);
			box-shadow: 0 6px 15px rgba(26, 43, 72, 0.2);
		}

		@media (max-width: 480px) {
			.ahbn-hi { display: none; }
			.ahbn-h-login { padding: 8px 10px; }
			.ahbn-h-reg { padding: 8px 15px; font-size: 13px; }
		}
	</style>

	<div class="ahbn-header-auth">
		<?php
		if ( is_user_logged_in() ) :
			$user        = wp_get_current_user();
			$first_name  = ! empty( $user->first_name ) ? $user->first_name : $user->display_name;
			$avatar_url  = get_avatar_url( $user->ID, array( 'size' => 72 ) );
			$account_pid = (int) get_option( 'ahbn_account_page' );
			$account_url = $account_pid ? get_permalink( $account_pid ) : home_url( '/ahbn-account/' );
			?>
			<a href="<?php echo esc_url( $account_url ); ?>" class="ahbn-user-wrapper">
				<img src="<?php echo esc_url( $avatar_url ); ?>" alt="<?php esc_attr_e( 'Guest Avatar', 'awesome-hotel-booking' ); ?>" class="ahbn-avatar-img">
				<div class="ahbn-user-info">
					<span class="ahbn-hi"><?php esc_html_e( 'Welcome,', 'awesome-hotel-booking' ); ?></span>
					<span class="ahbn-user-name"><?php echo esc_html( $first_name ); ?></span>
				</div>
			</a>

		<?php
		else :
			$login_pid = (int) get_option( 'ahbn_login_page' );
			$reg_pid   = (int) get_option( 'ahbn_registration_page' );

			$login_url = $login_pid ? get_permalink( $login_pid ) : wp_login_url();
			$reg_url   = $reg_pid ? get_permalink( $reg_pid ) : wp_registration_url();
			?>
			<div class="ahbn-auth-group">
				<a href="<?php echo esc_url( $login_url ); ?>" class="ahbn-h-btn ahbn-h-login">
					<svg class="ahbn-h-icon" viewBox="0 0 24 24" aria-hidden="true">
						<path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/>
					</svg>
					<?php esc_html_e( 'Sign In', 'awesome-hotel-booking' ); ?>
				</a>
				<a href="<?php echo esc_url( $reg_url ); ?>" class="ahbn-h-btn ahbn-h-reg">
					<?php esc_html_e( 'Join Now', 'awesome-hotel-booking' ); ?>
				</a>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ahbn_header_auth', 'ahbn_header_auth_shortcode_callback' );