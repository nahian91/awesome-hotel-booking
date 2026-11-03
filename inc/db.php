<?php
/**
 * Custom Relational Database Management & Schema Engine (dbDelta).
 *
 * Fully compliant with WordPress dbDelta formatting rules:
 * - One column/key definition per line
 * - Exactly two spaces after "PRIMARY KEY"
 * - Standardized indexes, explicit defaults, and collation
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create or upgrade custom database tables.
 *
 * @return void
 */
function ahbn_install_database_schema() {
	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	// 1. Rooms & Suites Table.
	$table_rooms = $wpdb->prefix . 'ahbn_rooms';
	$sql_rooms   = "CREATE TABLE {$table_rooms} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		room_number varchar(50) NOT NULL DEFAULT '',
		title varchar(255) NOT NULL DEFAULT '',
		slug varchar(200) NOT NULL DEFAULT '',
		description longtext NULL,
		room_type varchar(100) NOT NULL DEFAULT 'Standard',
		price_per_night decimal(10,2) NOT NULL DEFAULT '0.00',
		max_adults int(11) NOT NULL DEFAULT '2',
		max_children int(11) NOT NULL DEFAULT '0',
		amenities longtext NULL,
		paid_extras longtext NULL,
		thumbnail_id bigint(20) unsigned NOT NULL DEFAULT 0,
		gallery longtext NULL,
		status varchar(20) NOT NULL DEFAULT 'publish',
		menu_order int(11) NOT NULL DEFAULT '0',
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY room_number (room_number),
		KEY slug (slug(191)),
		KEY status (status),
		KEY room_type (room_type)
	) {$charset_collate};";
	dbDelta( $sql_rooms );

	// 2. Bookings & Reservations Table.
	$table_bookings = $wpdb->prefix . 'ahbn_bookings';
	$sql_bookings   = "CREATE TABLE {$table_bookings} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		booking_code varchar(64) NOT NULL DEFAULT '',
		room_id bigint(20) unsigned NOT NULL,
		user_id bigint(20) unsigned NOT NULL DEFAULT 0,
		guest_name varchar(255) NOT NULL DEFAULT '',
		guest_email varchar(100) NOT NULL DEFAULT '',
		guest_phone varchar(50) NOT NULL DEFAULT '',
		guest_address text NULL,
		checkin_date date NOT NULL,
		checkout_date date NOT NULL,
		adults int(11) NOT NULL DEFAULT '1',
		children int(11) NOT NULL DEFAULT '0',
		nights int(11) NOT NULL DEFAULT '1',
		room_rate decimal(10,2) NOT NULL DEFAULT '0.00',
		total_amount decimal(10,2) NOT NULL DEFAULT '0.00',
		payment_method varchar(50) NOT NULL DEFAULT 'pay_on_arrival',
		payment_status varchar(30) NOT NULL DEFAULT 'unpaid',
		status varchar(30) NOT NULL DEFAULT 'pending',
		notes text NULL,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY booking_code (booking_code),
		KEY room_id (room_id),
		KEY user_id (user_id),
		KEY guest_email (guest_email),
		KEY checkin_date (checkin_date),
		KEY checkout_date (checkout_date),
		KEY status (status)
	) {$charset_collate};";
	dbDelta( $sql_bookings );

	// 3. Operational Expenses Table.
	$table_expenses = $wpdb->prefix . 'ahbn_expenses';
	$sql_expenses   = "CREATE TABLE {$table_expenses} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		title varchar(255) NOT NULL DEFAULT '',
		amount decimal(10,2) NOT NULL DEFAULT '0.00',
		category varchar(100) NOT NULL DEFAULT 'General',
		department varchar(100) NOT NULL DEFAULT 'Operations',
		expense_date date NOT NULL,
		description text NULL,
		receipt_id bigint(20) unsigned NOT NULL DEFAULT 0,
		created_by bigint(20) unsigned NOT NULL DEFAULT 0,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY category (category),
		KEY department (department),
		KEY expense_date (expense_date)
	) {$charset_collate};";
	dbDelta( $sql_expenses );

	// 4. Housekeeping & Room Turnaround Table.
	$table_housekeeping = $wpdb->prefix . 'ahbn_housekeeping';
	$sql_housekeeping   = "CREATE TABLE {$table_housekeeping} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		room_id bigint(20) unsigned NOT NULL,
		housekeeper_id bigint(20) unsigned NOT NULL DEFAULT 0,
		clean_status varchar(30) NOT NULL DEFAULT 'dirty',
		priority varchar(20) NOT NULL DEFAULT 'normal',
		inspection_status varchar(30) NOT NULL DEFAULT 'pending',
		maintenance_notes text NULL,
		last_cleaned_at datetime NULL,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY room_id (room_id),
		KEY clean_status (clean_status),
		KEY housekeeper_id (housekeeper_id)
	) {$charset_collate};";
	dbDelta( $sql_housekeeping );

	// 5. Coupons & Promo Codes Table.
	$table_coupons = $wpdb->prefix . 'ahbn_coupons';
	$sql_coupons   = "CREATE TABLE {$table_coupons} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		code varchar(50) NOT NULL DEFAULT '',
		discount_type varchar(20) NOT NULL DEFAULT 'percentage',
		discount_value decimal(10,2) NOT NULL DEFAULT '0.00',
		min_spend decimal(10,2) NOT NULL DEFAULT '0.00',
		max_spend decimal(10,2) NOT NULL DEFAULT '0.00',
		usage_limit int(11) NOT NULL DEFAULT 0,
		usage_count int(11) NOT NULL DEFAULT 0,
		expiry_date date NULL,
		status varchar(20) NOT NULL DEFAULT 'active',
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY code (code),
		KEY status (status)
	) {$charset_collate};";
	dbDelta( $sql_coupons );

	// 6. Seasonal Rates & Dynamic Pricing Table.
	$table_rates = $wpdb->prefix . 'ahbn_rates';
	$sql_rates   = "CREATE TABLE {$table_rates} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		title varchar(150) NOT NULL DEFAULT '',
		room_id bigint(20) unsigned NOT NULL DEFAULT 0,
		start_date date NOT NULL,
		end_date date NOT NULL,
		rate_multiplier decimal(5,2) NOT NULL DEFAULT '1.00',
		fixed_rate decimal(10,2) NOT NULL DEFAULT '0.00',
		min_stay int(11) NOT NULL DEFAULT 1,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY room_id (room_id),
		KEY start_date (start_date),
		KEY end_date (end_date)
	) {$charset_collate};";
	dbDelta( $sql_rates );

	// 7. Hotel Staff & Crew Roster Table.
	$table_staff = $wpdb->prefix . 'ahbn_staff';
	$sql_staff   = "CREATE TABLE {$table_staff} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		user_id bigint(20) unsigned NOT NULL DEFAULT 0,
		full_name varchar(150) NOT NULL DEFAULT '',
		role varchar(50) NOT NULL DEFAULT 'reception',
		phone varchar(50) NOT NULL DEFAULT '',
		email varchar(100) NOT NULL DEFAULT '',
		shift varchar(50) NOT NULL DEFAULT 'morning',
		status varchar(20) NOT NULL DEFAULT 'active',
		hire_date date NULL,
		notes text NULL,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY user_id (user_id),
		KEY role (role),
		KEY status (status)
	) {$charset_collate};";
	dbDelta( $sql_staff );

	// 8. Add-ons & Extra Services Table.
	$table_services = $wpdb->prefix . 'ahbn_services';
	$sql_services   = "CREATE TABLE {$table_services} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		name varchar(255) NOT NULL DEFAULT '',
		price decimal(10,2) NOT NULL DEFAULT '0.00',
		charge_type varchar(50) NOT NULL DEFAULT 'per_stay',
		description text NULL,
		status varchar(20) NOT NULL DEFAULT 'publish',
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY status (status),
		KEY charge_type (charge_type)
	) {$charset_collate};";
	dbDelta( $sql_services );

	// 9. SEO & Rich Snippets Table.
	$table_seo = $wpdb->prefix . 'ahbn_seo';
	$sql_seo   = "CREATE TABLE {$table_seo} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		entity_type varchar(50) NOT NULL DEFAULT 'room',
		entity_id bigint(20) unsigned NOT NULL DEFAULT 0,
		meta_title varchar(255) NOT NULL DEFAULT '',
		meta_description text NULL,
		schema_type varchar(50) NOT NULL DEFAULT 'HotelRoom',
		og_image_id bigint(20) unsigned NOT NULL DEFAULT 0,
		canonical_url varchar(255) NOT NULL DEFAULT '',
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		UNIQUE KEY entity (entity_type, entity_id)
	) {$charset_collate};";
	dbDelta( $sql_seo );

	$current_ver = defined( 'AHBN_DB_VERSION' ) ? AHBN_DB_VERSION : '1.0.0';
	update_option( 'ahbn_db_version', $current_ver );
}

/**
 * Check if DB version bump requires schema upgrade.
 *
 * @return void
 */
function ahbn_check_db_updates() {
	$target_ver = defined( 'AHBN_DB_VERSION' ) ? AHBN_DB_VERSION : '1.0.0';
	if ( get_option( 'ahbn_db_version' ) !== $target_ver ) {
		ahbn_install_database_schema();
	}
}
add_action( 'plugins_loaded', 'ahbn_check_db_updates' );