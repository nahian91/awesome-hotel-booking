=== Awesome Hotel Booking ===
Contributors: nahian91
Tags: hotel booking, room reservation, hotel pms, booking calendar, hospitality management
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enterprise Hotel Booking & Hospitality Management Engine built on high-performance custom relational database tables.

== Description ==

**Awesome Hotel Booking** is an enterprise-grade PMS (Property Management System) and online reservation engine designed for luxury hotels, resorts, boutique inns, and serviced apartments.

Operating on **9 high-performance relational database tables** (`wp_ahbn_rooms`, `wp_ahbn_bookings`, `wp_ahbn_services`, `wp_ahbn_coupons`, `wp_ahbn_staff`, `wp_ahbn_housekeeping`, `wp_ahbn_expenses`, `wp_ahbn_rates`, and `wp_ahbn_seo`), the plugin eliminates post-meta performance bottlenecks, providing instant availability lookups and seamless guest checkouts.

Styled with a signature **Champagne Gold & Deep Navy** enterprise UI system, the plugin delivers a cohesive experience across management ledgers and customer booking portals.

== Key Modules & Features ==

### 📊 Operations Dashboard
Real-time property intelligence at a glance:
- Real-time room occupancy rates and upcoming check-ins/check-outs.
- Daily revenue snapshots and financial metrics in your chosen currency.
- Quick-action shortcuts for daily hotel desk operations.

### 🛏️ Suite & Room Inventory Management
Granular control over room inventory and configurations:
- **Full Inventory View:** Comprehensive suite ledger powered by direct relational database queries.
- **Dynamic Room Number Synchronization:** Selecting a suite classification automatically filters and displays related door/room numbers.
- **Physical Specifications:** Standardized bedding setups (King, Queen, Twin), room dimensions (sq ft / sqm), bathroom setups, and view orientations (Oceanfront, Skyline, Poolside).
- **Interactive Switchboard:** Toggle complimentary amenities and attachable paid services with iOS-style pill switches.
- **Media Gallery:** Native WordPress Media Library integration for hero images and multi-image photo carousels.

### 📅 Booking Control Center & Ledgers
High-volume reservation management:
- **Direct Folio Tracking:** Track reservations from booking inquiry, check-in, housekeeping turn, to guest check-out.
- **Status Workflows:** Real-time status toggles for `confirmed`, `checked_in`, `checked_out`, and `cancelled`.

### 🧹 Housekeeping & Facility Management
Maintain room readiness across your property:
- Assign cleaning tasks directly to housekeeping staff.
- Track room status transitions (`clean`, `dirty`, `in_progress`, `inspected`).

### 👔 Staff & Shift Roster
Comprehensive hospitality employee directory:
- Department categorization (Management, Front Desk, Housekeeping, Concierge, Maintenance).
- Shift scheduling (Morning, Evening, Night Audit, Flexible).
- Quick modal preview for employee contact info, hire dates, and CPR/safety certifications.
- Optional linking to native WordPress user accounts.

### 🎟️ Promotional Coupons & Discounts
Flexible campaign management with built-in guardrails:
- Percentage-based (%) or flat-rate currency discounts.
- Minimum booking spend thresholds and maximum discount caps.
- Strict expiration dates and usage limit tracking.

### 🛎️ Attachable Paid Extra Services
Maximize Average Order Value (AOV) on reservation checkout:
- Sourced directly from `wp_ahbn_services`.
- Customizable billing cadences: per stay, per night, or per guest.
- Upsells for airport transfers, champagne breakfast, late checkouts, and spa packages.

### ⚙️ Property Policies & Global Settings
Tailor property operations to your brand:
- Configurable standard check-in and check-out times.
- Minimum guest booking age and cancellation policy windows.
- Multi-currency engine with customizable symbol positioning (e.g. £, $, €).
- Property house rules (pet allowances, smoking regulations).

== ⌨️ Shortcode Library ==

Deploy a complete luxury hospitality website using our shortcode suite:

- `[ahbn_header_auth]` - Dynamic header authentication widget showing guest avatar, name, and account link when logged in, or Sign In / Join Now buttons when logged out.
- `[ahbn_search_bar]` - Frontend booking search bar with check-in/out date selection and guest capacity counters.
- `[ahbn_all_rooms]` - Full room inventory showcase in an interactive, responsive grid with pricing, amenities, and suite details links.
- `[ahbn_room_details id="1"]` - Dedicated single room showcase with photo carousels, full specifications, and reservation actions.
- `[ahbn_checkout]` - Reservation checkout funnel with add-on upsells, coupon redemption, and instant booking submission.
- `[ahbn_thanks]` - Post-booking confirmation screen displaying reservation codes, stay details, and invoice summaries.
- `[ahbn_login]` - AJAX-powered luxury guest authentication and login card.
- `[ahbn_register]` - Member sign-up portal with automatic login and guest profile creation.
- `[ahbn_account]` - Self-service guest dashboard for viewing stay history, folios, and editing personal profile details.

== Installation ==

1. Upload the `awesome-hotel-booking` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. The plugin will automatically construct the required custom database tables and publish default core pages (`/rooms/`, `/room-details/`, `/ahbn-account/`, `/ahbn-login/`, `/ahbn-registration/`, `/ahbn-thanks/`).
4. Navigate to **Hotel Booking > Settings** to verify your currency symbol, check-in times, and room numbering maps.
5. Use the shortcodes to integrate booking features into your pages or Elementor templates.

== Frequently Asked Questions ==

= Does this plugin create custom post types or custom SQL tables? =
Awesome Hotel Booking utilizes 9 custom relational database tables for core hotel functions (rooms, bookings, staff, housekeeping, coupons, extra services, rates, SEO, and expenses). This prevents WordPress post-meta query slowdowns and ensures instant availability calculations.

= How do I assign specific room numbers to a suite type? =
Go to **Hotel Booking > Settings**. In the **Suite Classifications & Assigned Door Numbers** repeater, add your suite classification (e.g., Deluxe King Suite) and list the door numbers separated by commas (e.g., `201, 202, 203, 204`). When editing or creating a room under that classification, the room number dropdown will automatically filter to those numbers.

= Can I use £ (GBP) or other currencies? =
Yes. Navigate to **Hotel Booking > Settings** to set your preferred currency symbol and choose between left or right placement.

= What happens when a guest creates an account? =
The registration system automatically validates their details, creates a guest profile, updates their billing address and phone metadata, logs them in instantly via secure authentication cookies, and redirects them to their reservation dashboard.

== Changelog ==

= 1.1.0 =
* Complete transition to high-performance relational database engine across all core entities.
* Modularized codebase structure across all administrative views.
* Upgraded Room Editor with full-width segmented tab navigation and iOS-style toggle switches.
* Added dynamic door number filtering synchronized with selected suite classifications.
* Added Hotel Staff & Shift Management module with instant profile view modals.
* Enhanced promotional coupon system with usage limits and minimum/maximum spend guardrails.
* Added header authentication bar shortcode with avatar recognition.
* Optimized direct SQL queries, table indexing, and database install scripts.

= 1.0.0 =
* Initial public release.