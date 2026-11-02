<?php
/**
 * [ahbn_login] Hotel AJAX Authentication & Login Form.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*--------------------------------------------------------------
# 1. AJAX Login Authenticator
--------------------------------------------------------------*/

/**
 * Handle AJAX login request.
 *
 * @return void
 */
function ahbn_handle_ajax_login() {
	// Verify Security Nonce.
	$nonce = isset( $_POST['security'] ) ? sanitize_key( wp_unslash( $_POST['security'] ) ) : '';
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'ahbn-login-nonce' ) ) {
		wp_send_json_error( esc_html__( 'Session expired. Please refresh the page.', 'awesome-hotel-booking' ) );
	}

	// Validate presence of credentials.
	if ( empty( $_POST['log_user'] ) || empty( $_POST['log_pass'] ) ) {
		wp_send_json_error( esc_html__( 'Please enter both username/email and password.', 'awesome-hotel-booking' ) );
	}

	$user_input = sanitize_text_field( wp_unslash( $_POST['log_user'] ) );
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$password   = isset( $_POST['log_pass'] ) ? wp_unslash( $_POST['log_pass'] ) : '';

	$creds = array(
		'user_login'    => $user_input,
		'user_password' => $password,
		'remember'      => true,
	);

	// Authenticate through WordPress core.
	$user = wp_signon( $creds, is_ssl() );

	if ( is_wp_error( $user ) ) {
		wp_send_json_error( esc_html__( 'Invalid username, email, or password.', 'awesome-hotel-booking' ) );
	} else {
		// Determine safe redirect target.
		$account_pid = (int) get_option( 'ahbn_account_page' );
		$default_url = $account_pid ? get_permalink( $account_pid ) : home_url( '/ahbn-account/' );

		$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
		$safe_target = ! empty( $redirect_to ) ? wp_validate_redirect( $redirect_to, $default_url ) : $default_url;

		wp_send_json_success( array( 'redirect' => $safe_target ) );
	}
}
add_action( 'wp_ajax_nopriv_ahbn_ajax_login', 'ahbn_handle_ajax_login' );
add_action( 'wp_ajax_ahbn_ajax_login', 'ahbn_handle_ajax_login' );

/*--------------------------------------------------------------
# 2. Login Form Shortcode
--------------------------------------------------------------*/

/**
 * Render the guest AJAX login form shortcode.
 *
 * @return string Rendered HTML.
 */
function ahbn_login_shortcode_callback() {
	$account_pid = (int) get_option( 'ahbn_account_page' );
	$account_url = $account_pid ? get_permalink( $account_pid ) : home_url( '/ahbn-account/' );

	if ( is_user_logged_in() ) {
		if ( ! headers_sent() ) {
			wp_safe_redirect( $account_url );
			exit;
		}
		return '<script>window.location.href = "' . esc_url( $account_url ) . '";</script>';
	}

	$reg_pid = (int) get_option( 'ahbn_registration_page' );
	$reg_url = $reg_pid ? get_permalink( $reg_pid ) : home_url( '/ahbn-registration/' );

	ob_start();
	?>
	<style>
		:root {
			--hotel-primary: #1a2b48;
			--hotel-accent: #bf9441;
			--hotel-text: #475569;
			--hotel-bg: #f8fafc;
		}

		.ahbn-auth-page-wrapper { 
			max-width: 1000px; 
			margin: 60px auto; 
			display: flex; 
			background: #fff; 
			border-radius: 16px; 
			overflow: hidden; 
			box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
		}

		.ahbn-auth-side-info { 
			flex: 1; 
			background: linear-gradient(rgba(26, 43, 72, 0.9), rgba(26, 43, 72, 0.9)), 
						url('https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=800&q=80');
			background-size: cover;
			background-position: center;
			padding: 60px; 
			color: #fff; 
			display: flex; 
			flex-direction: column; 
			justify-content: center;
		}

		.ahbn-auth-card { flex: 1.2; padding: 60px; }
		.ahbn-auth-side-info h2 { color: #fff; font-size: 34px; margin-bottom: 16px; font-weight: 800; line-height: 1.2; }
		.ahbn-auth-side-info p { color: #cbd5e1; font-size: 15px; line-height: 1.6; margin-bottom: 25px; }
		.ahbn-auth-title { font-size: 26px; font-weight: 800; color: var(--hotel-primary); margin-bottom: 8px; }
		.ahbn-auth-subtitle { color: #64748b; font-size: 14px; margin-bottom: 30px; }
		.ahbn-login-field { margin-bottom: 22px; }
		.ahbn-login-field label { display: block; font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
		.ahbn-login-field input { 
			width: 100%; padding: 13px 15px; border: 1px solid #cbd5e1; border-radius: 8px; 
			font-size: 14px; transition: all 0.3s; background: #fff; box-sizing: border-box;
		}
		.ahbn-login-field input:focus { border-color: var(--hotel-accent); outline: none; box-shadow: 0 0 0 3px rgba(191,148,65,0.15); }
		.ahbn-login-btn { 
			width: 100%; background: var(--hotel-primary); color: #fff; border: none; 
			padding: 15px; border-radius: 8px; font-weight: 700; font-size: 15px; 
			cursor: pointer; transition: 0.3s; 
		}
		.ahbn-login-btn:hover { background: #0f172a; transform: translateY(-1px); }
		.status-error { margin-top: 15px; color: #b91c1c; background: #fef2f2; padding: 12px; border-radius: 8px; font-size: 13px; text-align: center; border: 1px solid #fee2e2; }
		.status-success { margin-top: 15px; color: #15803d; background: #f0fdf4; padding: 12px; border-radius: 8px; font-size: 13px; text-align: center; border: 1px solid #dcfce7; }

		@media (max-width: 850px) {
			.ahbn-auth-page-wrapper { flex-direction: column; margin: 20px; }
			.ahbn-auth-side-info { padding: 35px; }
			.ahbn-auth-card { padding: 35px; }
		}
	</style>

	<div class="ahbn-auth-page-wrapper">
		<div class="ahbn-auth-side-info">
			<h2><?php esc_html_e( 'Experience Excellence.', 'awesome-hotel-booking' ); ?></h2>
			<p><?php esc_html_e( 'Sign in to manage your upcoming stays, access exclusive guest rates, and customize your reservation requests.', 'awesome-hotel-booking' ); ?></p>
			<div style="border-top: 1px solid rgba(255,255,255,0.2); display: flex; align-items: center; gap: 10px; padding-top: 20px;">
				<span class="dashicons dashicons-star-filled" style="color:var(--hotel-accent)" aria-hidden="true"></span>
				<span style="font-size: 14px; font-weight:600;"><?php esc_html_e( 'Luxury Hospitality Guarantee', 'awesome-hotel-booking' ); ?></span>
			</div>
		</div>

		<div class="ahbn-auth-card">
			<h2 class="ahbn-auth-title"><?php esc_html_e( 'Guest Login', 'awesome-hotel-booking' ); ?></h2>
			<p class="ahbn-auth-subtitle"><?php esc_html_e( 'Welcome back! Please enter your account credentials.', 'awesome-hotel-booking' ); ?></p>

			<form id="ahbn-login-form">
				<input type="hidden" id="ahbn_login_nonce" value="<?php echo esc_attr( wp_create_nonce( 'ahbn-login-nonce' ) ); ?>">
				
				<div class="ahbn-login-field">
					<label for="ahbn_login_user"><?php esc_html_e( 'Email or Username', 'awesome-hotel-booking' ); ?></label>
					<input type="text" id="ahbn_login_user" placeholder="<?php esc_attr_e( 'Enter email or username', 'awesome-hotel-booking' ); ?>" required autocomplete="username">
				</div>

				<div class="ahbn-login-field">
					<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
						<label for="ahbn_login_pass" style="margin-bottom:0;"><?php esc_html_e( 'Password', 'awesome-hotel-booking' ); ?></label>
						<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" style="font-size: 12px; color: var(--hotel-accent); text-decoration: none; font-weight:600;">
							<?php esc_html_e( 'Forgot Password?', 'awesome-hotel-booking' ); ?>
						</a>
					</div>
					<input type="password" id="ahbn_login_pass" placeholder="••••••••" required autocomplete="current-password">
				</div>

				<button type="submit" id="ahbn-login-submit" class="ahbn-login-btn">
					<?php esc_html_e( 'Sign In to Account', 'awesome-hotel-booking' ); ?>
				</button>

				<div id="ahbn-login-status" role="alert" aria-live="polite"></div>
				
				<p style="text-align: center; margin-top: 25px; font-size: 14px; color: #64748b;">
					<?php esc_html_e( 'New guest?', 'awesome-hotel-booking' ); ?> 
					<a href="<?php echo esc_url( $reg_url ); ?>" style="color: var(--hotel-primary); font-weight: 700; text-decoration: none;">
						<?php esc_html_e( 'Create an Account', 'awesome-hotel-booking' ); ?>
					</a>
				</p>
			</form>
		</div>
	</div>

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		const loginForm = document.getElementById('ahbn-login-form');
		const statusDiv = document.getElementById('ahbn-login-status');
		const submitBtn = document.getElementById('ahbn-login-submit');

		if (!loginForm) return;

		loginForm.addEventListener('submit', function(e) {
			e.preventDefault();
			
			statusDiv.innerHTML = '<p style="text-align:center; color: #64748b; margin-top:15px;"><?php echo esc_js( __( 'Verifying credentials...', 'awesome-hotel-booking' ) ); ?></p>';
			submitBtn.disabled = true;

			const urlParams = new URLSearchParams(window.location.search);
			const redirectTo = urlParams.get('redirect_to') || '';

			const formData = new FormData();
			formData.append('action', 'ahbn_ajax_login');
			formData.append('log_user', document.getElementById('ahbn_login_user').value);
			formData.append('log_pass', document.getElementById('ahbn_login_pass').value);
			formData.append('security', document.getElementById('ahbn_login_nonce').value);
			formData.append('redirect_to', redirectTo);

			fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(res => {
				if (res.success) {
					statusDiv.innerHTML = '<div class="status-success"><?php echo esc_js( __( 'Success! Redirecting...', 'awesome-hotel-booking' ) ); ?></div>';
					window.location.href = res.data.redirect || '<?php echo esc_url( $account_url ); ?>';
				} else {
					statusDiv.innerHTML = '<div class="status-error">' + (res.data || '<?php echo esc_js( __( 'Authentication failed.', 'awesome-hotel-booking' ) ); ?>') + '</div>';
					submitBtn.disabled = false;
				}
			})
			.catch(() => {
				statusDiv.innerHTML = '<div class="status-error"><?php echo esc_js( __( 'Connection error. Please try again.', 'awesome-hotel-booking' ) ); ?></div>';
				submitBtn.disabled = false;
			});
		});
	});
	</script>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ahbn_login', 'ahbn_login_shortcode_callback' );