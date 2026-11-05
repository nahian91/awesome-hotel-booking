<?php
/**
 * Master Staff View & Styling Wrapper.
 *
 * @package AwesomeHotelBooking
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the master staff container and route sub-views.
 *
 * @return void
 */
function ahbn_render_staff_view() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$action   = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
	$staff_id = isset( $_GET['staff_id'] ) ? absint( wp_unslash( $_GET['staff_id'] ) ) : 0;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
	?>
	<!-- Subtabs Bar -->
	<?php ahbn_render_staff_tabs( $action, $staff_id ); ?>

	<div class="ahbn-staff-wrap">
		<?php
		if ( 'add' === $action || 'edit' === $action ) {
			ahbn_render_staff_add_edit( $staff_id );
		} else {
			ahbn_render_staff_list();
		}
		?>
	</div>

	<style>
		.ahbn-subtabs-nav-bar {
			display: inline-flex;
			align-items: center;
			gap: 6px;
			background: #f8fafc;
			border: 1px solid #e2e8f0;
			padding: 5px;
			border-radius: 12px;
			margin-bottom: 22px;
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

		.ahbn-staff-wrap {
			display: flex;
			flex-direction: column;
			gap: 20px;
			font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			color: #0f172a;
			margin-top: 0 !important;
		}

		.ahbn-staff-header {
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

		.ahbn-staff-title {
			font-family: 'Playfair Display', Georgia, serif;
			font-size: 24px;
			font-weight: 700;
			color: #0a192f;
			margin: 0 0 4px;
		}

		.ahbn-staff-subtitle {
			color: #64748b;
			margin: 0;
			font-size: 13px;
		}

		/* Stat Cards */
		.ahbn-stats-grid {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
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
			text-decoration: none;
			color: inherit;
			transition: all 0.2s ease;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-stat-card:hover {
			border-color: #cbd5e1;
			box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
			transform: translateY(-2px);
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

		.ahbn-stat-active-duty { border-left: 4px solid #10b981; }
		.ahbn-stat-active-duty .ahbn-stat-label,
		.ahbn-stat-active-duty .ahbn-stat-value { color: #059669; }

		.ahbn-stat-hk { border-left: 4px solid #c5a880; }
		.ahbn-stat-hk .ahbn-stat-label { color: #b39264; }

		.ahbn-stat-rec { border-left: 4px solid #2563eb; }
		.ahbn-stat-rec .ahbn-stat-label { color: #2563eb; }

		.ahbn-stat-active {
			border-color: #0a192f !important;
			background: #fbf8f3 !important;
		}

		/* Filter Bar */
		.ahbn-staff-filter-bar {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 12px;
			padding: 14px 18px;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-filter-controls {
			display: flex;
			gap: 12px;
			align-items: center;
			flex-wrap: wrap;
		}

		.ahbn-search-field {
			flex: 1;
			min-width: 220px;
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

		.ahbn-input,
		.ahbn-select {
			height: 38px !important;
		}

		.ahbn-input:focus,
		.ahbn-select:focus,
		.ahbn-textarea:focus {
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

		/* Table Elements */
		.ahbn-table-container {
			background: #ffffff;
			border: 1px solid #e2e8f0;
			border-radius: 14px;
			overflow: hidden;
			box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
		}

		.ahbn-staff-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 13px;
		}

		.ahbn-staff-table th {
			background: #f8fafc !important;
			padding: 12px 16px !important;
			font-weight: 700 !important;
			font-size: 11px !important;
			text-transform: uppercase !important;
			letter-spacing: 0.05em !important;
			color: #64748b !important;
			border-bottom: 1px solid #e2e8f0 !important;
		}

		.ahbn-staff-table td {
			padding: 14px 16px !important;
			vertical-align: middle !important;
			border-bottom: 1px solid #f1f5f9 !important;
		}

		.ahbn-emp-cell {
			display: flex;
			align-items: center;
			gap: 12px;
		}

		.ahbn-emp-avatar {
			width: 38px;
			height: 38px;
			border-radius: 50%;
			background: #0a192f;
			border: 1px solid #040914;
			display: flex;
			align-items: center;
			justify-content: center;
			font-weight: 800;
			color: #c5a880;
			font-size: 13px;
			flex-shrink: 0;
		}

		.ahbn-emp-name {
			color: #0f172a;
			display: block;
			font-size: 13px;
		}

		.ahbn-emp-linked {
			display: block;
			font-size: 11px;
			color: #94a3b8;
			margin-top: 2px;
		}

		.ahbn-role-badge {
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

		.ahbn-contact-stack {
			font-size: 12px;
			line-height: 1.5;
			color: #475569;
		}

		.ahbn-contact-stack .dashicons {
			font-size: 14px;
			width: 14px;
			height: 14px;
			color: #c5a880;
			vertical-align: middle;
			margin-right: 3px;
		}

		.ahbn-shift-text {
			font-size: 12px;
			font-weight: 600;
			color: #334155;
		}

		.ahbn-status-pill {
			display: inline-block;
			padding: 4px 10px;
			border-radius: 9999px;
			font-size: 11px;
			font-weight: 700;
			text-transform: uppercase;
		}

		.status-active   { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
		.status-leave    { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
		.status-inactive { background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; }

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

		/* Form Panels (Add / Edit Sub-View) */
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
			font-size: 18px;
			width: 18px;
			height: 18px;
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
			display: inline-flex;
			align-items: center;
			gap: 6px;
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

		.ahbn-btn-save .dashicons {
			font-size: 17px;
			width: 17px;
			height: 17px;
			color: #c5a880;
		}

		.ahbn-btn-save:hover {
			background: #c5a880;
			border-color: #c5a880;
			color: #0a192f;
		}

		.ahbn-btn-save:hover .dashicons {
			color: #0a192f;
		}

		@media (max-width: 1024px) {
			.ahbn-stats-grid {
				grid-template-columns: repeat(2, 1fr);
			}
		}

		@media (max-width: 600px) {
			.ahbn-stats-grid {
				grid-template-columns: 1fr;
			}
		}
	</style>
	<?php
}