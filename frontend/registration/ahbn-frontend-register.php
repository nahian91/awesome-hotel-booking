<?php
/**
 * [ahbn_register] Guest Registration & Account Creation System.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*--------------------------------------------------------------
# 1. AJAX Guest Registration Handler
--------------------------------------------------------------*/

/**
 * Process AJAX guest registration request.
 *
 * @return void
 */
function ahbn_handle_guest_registration_action() {
	// Verify Security Nonce.
	$nonce = isset( $_POST['security'] ) ? sanitize_key( wp_unslash( $_POST['security'] ) ) : '';
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'ahbn-reg-nonce' ) ) {
		wp_send_json_error( esc_html__( 'Security check failed. Please refresh the page.', 'awesome-hotel-booking' ) );
	}

	// Sanitize Inputs.
	$username  = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
	$email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$full_name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
	$phone     = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$address   = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';

	// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$password = isset( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : '';
	$confirm  = isset( $_POST['confirm_password'] ) ? wp_unslash( $_POST['confirm_password'] ) : '';
	// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

	// Validation.
	if ( empty( $username ) || empty( $email ) || empty( $password ) ) {
		wp_send_json_error( esc_html__( 'Please complete all required fields.', 'awesome-hotel-booking' ) );
	}

	if ( ! is_email( $email ) ) {
		wp_send_json_error( esc_html__( 'Please provide a valid email address.', 'awesome-hotel-booking' ) );
	}

	if ( $password !== $confirm ) {
		wp_send_json_error( esc_html__( 'Passwords do not match.', 'awesome-hotel-booking' ) );
	}

	if ( strlen( $password ) < 6 ) {
		wp_send_json_error( esc_html__( 'Password must be at least 6 characters.', 'awesome-hotel-booking' ) );
	}

	if ( username_exists( $username ) ) {
		wp_send_json_error( esc_html__( 'Username is already taken.', 'awesome-hotel-booking' ) );
	}

	if ( email_exists( $email ) ) {
		wp_send_json_error( esc_html__( 'Email is already registered.', 'awesome-hotel-booking' ) );
	}

	// Create User.
	$user_id = wp_create_user( $username, $password, $email );

	if ( is_wp_error( $user_id ) ) {
		wp_send_json_error( esc_html( $user_id->get_error_message() ) );
	}

	// Update Profile Metadata.
	wp_update_user(
		array(
			'ID'           => $user_id,
			'display_name' => ! empty( $full_name ) ? $full_name : $username,
			'first_name'   => $full_name,
		)
	);

	update_user_meta( $user_id, 'billing_phone', $phone );
	update_user_meta( $user_id, 'billing_address_1', $address );

	// Auto-login the new guest.
	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id );

	// Resolve redirect target.
	$account_pid = (int) get_option( 'ahbn_account_page' );
	$account_url = $account_pid ? get_permalink( $account_pid ) : home_url( '/ahbn-account/' );

	wp_send_json_success(
		array(
			'message'  => esc_html__( 'Account created successfully! Redirecting...', 'awesome-hotel-booking' ),
			'redirect' => $account_url,
		)
	);
}
add_action( 'wp_ajax_nopriv_ahbn_ajax_register', 'ahbn_handle_guest_registration_action' );
add_action( 'wp_ajax_ahbn_ajax_register', 'ahbn_handle_guest_registration_action' );

/*--------------------------------------------------------------
# 2. Registration Shortcode Interface
--------------------------------------------------------------*/

/**
 * Render the guest registration form shortcode.
 *
 * @return string Rendered HTML.
 */
function ahbn_register_shortcode_callback() {
	$account_pid = (int) get_option( 'ahbn_account_page' );
	$account_url = $account_pid ? get_permalink( $account_pid ) : home_url( '/ahbn-account/' );

	if ( is_user_logged_in() ) {
		if ( ! headers_sent() ) {
			wp_safe_redirect( $account_url );
			exit;
		}
		return '<script>window.location.href = "' . esc_url( $account_url ) . '";</script>';
	}

	$login_pid = (int) get_option( 'ahbn_login_page' );
	$login_url = $login_pid ? get_permalink( $login_pid ) : wp_login_url();

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
						url('https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=800&q=80'); 
			background-size: cover;
			background-position: center;
			padding: 60px; 
			color: #fff; 
			display: flex; 
			flex-direction: column; 
			justify-content: center;
		}

		.ahbn-auth-card { flex: 1.4; padding: 60px; }
		.ahbn-auth-side-info h2 { color: #fff; font-size: 34px; margin-bottom: 16px; font-weight: 800; line-height: 1.2; }
		.ahbn-auth-side-info p { color: #cbd5e1; font-size: 15px; line-height: 1.6; }

		.ahbn-auth-title { font-size: 26px; font-weight: 800; color: var(--hotel-primary); margin-bottom: 8px; }
		.ahbn-auth-subtitle { color: #64748b; font-size: 14px; margin-bottom: 30px; }

		.ahbn-reg-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
		.ahbn-field-full { grid-column: span 2; }

		.ahbn-login-field { margin-bottom: 20px; }
		.ahbn-login-field label { display: block; font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 6px; }
		.ahbn-login-field input, .ahbn-login-field textarea { 
			width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px; 
			font-size: 14px; transition: all 0.3s; box-sizing: border-box; background: #fff;
		}

		.ahbn-login-field input:focus, .ahbn-login-field textarea:focus { 
			border-color: var(--hotel-accent); outline: none; box-shadow: 0 0 0 3px rgba(191,148,65,0.15); 
		}

		.ahbn-login-btn { 
			width: 100%; background: var(--hotel-primary); color: #fff; border: none; 
			padding: 15px; border-radius: 8px; font-weight: 700; font-size: 15px; 
			cursor: pointer; transition: 0.3s; margin-top: 10px;
		}

		.ahbn-login-btn:hover { background: #0f172a; transform: translateY(-1px); }
		.status-msg { margin-top: 15px; padding: 12px; border-radius: 8px; font-size: 14px; text-align: center; display: none; }
		.msg-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; display: block; }
		.msg-success { background: #f0fdf4; color: #16a34a; border: 1px solid #dcfce7; display: block; }

		@media (max-width: 850px) {
			.ahbn-auth-page-wrapper { flex-direction: column; margin: 20px; }
			.ahbn-auth-side-info { padding: 35px; }
			.ahbn-auth-card { padding: 35px; }
			.ahbn-reg-grid { grid-template-columns: 1fr; }
			.ahbn-field-full { grid-column: span 1; }
		}
	</style>

	<div class="ahbn-auth-page-wrapper">
		<div class="ahbn-auth-side-info">
			<h2><?php esc_html_e( 'Join Our Community.', 'awesome-hotel-booking' ); ?></h2>
			<p><?php esc_html_e( 'Create a guest profile to unlock member-only pricing, manage your reservations, and enjoy a personalized hospitality experience.', 'awesome-hotel-booking' ); ?></p>
		</div>

		<div class="ahbn-auth-card">
			<h2 class="ahbn-auth-title"><?php esc_html_e( 'Create Account', 'awesome-hotel-booking' ); ?></h2>
			<p class="ahbn-auth-subtitle"><?php esc_html_e( 'Please provide your details to register as a guest member.', 'awesome-hotel-booking' ); ?></p>

			<form id="ahbn-register-form-main">
				<input type="hidden" id="ahbn_reg_nonce_val" value="<?php echo esc_attr( wp_create_nonce( 'ahbn-reg-nonce' ) ); ?>">
				
				<div class="ahbn-reg-grid">
					<div class="ahbn-login-field">
						<label for="reg_fullname"><?php esc_html_e( 'Full Name', 'awesome-hotel-booking' ); ?></label>
						<input type="text" id="reg_fullname" placeholder="<?php esc_attr_e( 'e.g. John Doe', 'awesome-hotel-booking' ); ?>" required autocomplete="name">
					</div>
					<div class="ahbn-login-field">
						<label for="reg_phone"><?php esc_html_e( 'Phone Number', 'awesome-hotel-booking' ); ?></label>
						<input type="tel" id="reg_phone" placeholder="<?php esc_attr_e( '+1 (555) 000-0000', 'awesome-hotel-booking' ); ?>" required autocomplete="tel">
					</div>
					<div class="ahbn-login-field">
						<label for="reg_username"><?php esc_html_e( 'Username', 'awesome-hotel-booking' ); ?></label>
						<input type="text" id="reg_username" placeholder="<?php esc_attr_e( 'johndoe', 'awesome-hotel-booking' ); ?>" required autocomplete="username">
					</div>
					<div class="ahbn-login-field">
						<label for="reg_email"><?php esc_html_e( 'Email Address', 'awesome-hotel-booking' ); ?></label>
						<input type="email" id="reg_email" placeholder="<?php esc_attr_e( 'john@example.com', 'awesome-hotel-booking' ); ?>" required autocomplete="email">
					</div>
					<div class="ahbn-login-field ahbn-field-full">
						<label for="reg_address"><?php esc_html_e( 'Residential Address', 'awesome-hotel-booking' ); ?></label>
						<textarea id="reg_address" rows="2" placeholder="<?php esc_attr_e( 'Street, City, Postcode', 'awesome-hotel-booking' ); ?>" required autocomplete="street-address"></textarea>
					</div>
					<div class="ahbn-login-field">
						<label for="reg_password"><?php esc_html_e( 'Create Password', 'awesome-hotel-booking' ); ?></label>
						<input type="password" id="reg_password" placeholder="••••••••" required autocomplete="new-password">
					</div>
					<div class="ahbn-login-field">
						<label for="reg_password_confirm"><?php esc_html_e( 'Confirm Password', 'awesome-hotel-booking' ); ?></label>
						<input type="password" id="reg_password_confirm" placeholder="••••••••" required autocomplete="new-password">
					</div>
				</div>

				<button type="submit" id="ahbn-reg-submit-btn" class="ahbn-login-btn">
					<?php esc_html_e( 'Create Guest Profile', 'awesome-hotel-booking' ); ?>
				</button>
				<div id="ahbn-reg-status-div" class="status-msg" role="alert" aria-live="polite"></div>
				
				<p style="text-align: center; margin-top: 25px; font-size: 14px; color: #64748b;">
					<?php esc_html_e( 'Already have an account?', 'awesome-hotel-booking' ); ?> 
					<a href="<?php echo esc_url( $login_url ); ?>" style="color: var(--hotel-primary); font-weight: 700; text-decoration: none;">
						<?php esc_html_e( 'Login here', 'awesome-hotel-booking' ); ?>
					</a>
				</p>
			</form>
		</div>
	</div>

	<script>
	document.addEventListener('DOMContentLoaded', function() {
		const rForm = document.getElementById('ahbn-register-form-main');
		const rStatus = document.getElementById('ahbn-reg-status-div');
		const rSubmit = document.getElementById('ahbn-reg-submit-btn');

		if (!rForm) return;

		rForm.addEventListener('submit', function(e) {
			e.preventDefault();
			
			const pass = document.getElementById('reg_password').value;
			const confirm = document.getElementById('reg_password_confirm').value;

			if (pass !== confirm) {
				rStatus.className = 'status-msg msg-error';
				rStatus.innerHTML = '<?php echo esc_js( __( 'Passwords do not match.', 'awesome-hotel-booking' ) ); ?>';
				rStatus.style.display = 'block';
				return;
			}

			if (pass.length < 6) {
				rStatus.className = 'status-msg msg-error';
				rStatus.innerHTML = '<?php echo esc_js( __( 'Password must be at least 6 characters.', 'awesome-hotel-booking' ) ); ?>';
				rStatus.style.display = 'block';
				return;
			}

			rStatus.className = 'status-msg';
			rStatus.innerHTML = '<?php echo esc_js( __( 'Creating your profile...', 'awesome-hotel-booking' ) ); ?>';
			rStatus.style.display = 'block';
			rSubmit.disabled = true;

			const rFormData = new FormData();
			rFormData.append('action', 'ahbn_ajax_register');
			rFormData.append('security', document.getElementById('ahbn_reg_nonce_val').value);
			rFormData.append('full_name', document.getElementById('reg_fullname').value);
			rFormData.append('phone', document.getElementById('reg_phone').value);
			rFormData.append('username', document.getElementById('reg_username').value);
			rFormData.append('email', document.getElementById('reg_email').value);
			rFormData.append('address', document.getElementById('reg_address').value);
			rFormData.append('password', pass);
			rFormData.append('confirm_password', confirm);

			fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
				method: 'POST',
				body: rFormData
			})
			.then(res => res.json())
			.then(data => {
				if (data.success) {
					rStatus.className = 'status-msg msg-success';
					rStatus.innerHTML = data.data.message || '<?php echo esc_js( __( 'Account created successfully!', 'awesome-hotel-booking' ) ); ?>';
					setTimeout(() => {
						window.location.href = data.data.redirect || '<?php echo esc_url( $account_url ); ?>';
					}, 1200);
				} else {
					rStatus.className = 'status-msg msg-error';
					rStatus.innerHTML = data.data || '<?php echo esc_js( __( 'Registration failed.', 'awesome-hotel-booking' ) ); ?>';
					rSubmit.disabled = false;
				}
			})
			.catch(() => {
				rStatus.className = 'status-msg msg-error';
				rStatus.innerHTML = '<?php echo esc_js( __( 'Connection error. Please try again.', 'awesome-hotel-booking' ) ); ?>';
				rSubmit.disabled = false;
			});
		});
	});
	</script>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ahbn_register', 'ahbn_register_shortcode_callback' );