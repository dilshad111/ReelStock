<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quality Cartons - ReelStock Inventory</title>
    <link rel="icon" type="image/svg+xml" href="/images/quality-cartons-logo.svg">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        /* CSS Custom Properties for Themes */
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --warning-color: #fd7e14;
            --danger-color: #dc3545;
            --info-color: #0dcaf0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --bg-color: #ffffff;
            --text-color: #212529;
            --navbar-bg: #343a40;
            --card-bg: #ffffff;
            --border-color: #dee2e6;
            --menu-text-color: #f8f9fa;
        }

        /* Modern Theme (Premium Glassmorphism) */
        [data-theme="modern"] {
            --primary-color: #6366f1; /* Indigo */
            --secondary-color: #64748b; /* Slate */
            --success-color: #10b981; /* Emerald */
            --warning-color: #f59e0b; /* Amber */
            --danger-color: #ef4444; /* Red */
            --info-color: #06b6d4; /* Cyan */
            --light-color: #f1f5f9; /* Slate 100 */
            --dark-color: #0f172a; /* Slate 900 */
            --bg-color: #f8fafc; /* Slate 50 */
            --text-color: #1e293b; /* Slate 800 */
            --navbar-bg: rgba(255, 255, 255, 0.8);
            --card-bg: rgba(255, 255, 255, 0.7);
            --border-color: rgba(255, 255, 255, 0.5);
            --glass-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --glass-border: 1px solid rgba(255, 255, 255, 0.5);
            --menu-text-color: #334155;
        }

        /* Modern Theme Overrides */
        [data-theme="modern"] body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            font-family: 'Montserrat', sans-serif;
        }

        [data-theme="modern"] .top-navbar {
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: var(--glass-border);
            box-shadow: var(--glass-shadow);
        }
        
        [data-theme="modern"] .top-navbar .navbar-brand,
        [data-theme="modern"] .top-navbar .nav-link,
        [data-theme="modern"] .top-navbar .text-white {
            color: #1e293b !important;
            font-weight: 600;
        }

        [data-theme="modern"] .top-navbar .btn-outline-light {
            color: #1e293b;
            border-color: #cbd5e1;
        }
        
        [data-theme="modern"] .top-navbar .btn-outline-light:hover {
            background-color: #1e293b;
            color: #fff;
        }

        [data-theme="modern"] .card {
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: var(--glass-border);
            box-shadow: var(--glass-shadow);
            border-radius: 1rem;
        }

        [data-theme="modern"] .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.4);
            transition: transform 0.2s;
        }

        [data-theme="modern"] .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 8px -1px rgba(99, 102, 241, 0.5);
        }

        [data-theme="modern"] .table {
            --bs-table-bg: transparent;
        }
        
        [data-theme="modern"] .dropdown-menu {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border: var(--glass-border);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-radius: 0.75rem;
        }

        /* Light Theme (Default) */
        [data-theme="light"] {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --warning-color: #fd7e14;
            --danger-color: #dc3545;
            --info-color: #0dcaf0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --bg-color: #ffffff;
            --text-color: #212529;
            --navbar-bg: #343a40;
            --card-bg: #ffffff;
            --border-color: #dee2e6;
        }

        /* Dark Theme */
        [data-theme="dark"] {
            --primary-color: #818cf8;
            --secondary-color: #94a3b8;
            --success-color: #34d399;
            --warning-color: #fbbf24;
            --danger-color: #f87171;
            --info-color: #22d3ee;
            --light-color: #1e293b;
            --dark-color: #f1f5f9;
            --bg-color: #0f172a;
            --text-color: #e2e8f0;
            --navbar-bg: #1e293b;
            --card-bg: #1e293b;
            --border-color: #334155;
            --menu-text-color: #e2e8f0;
        }

        [data-theme="dark"] body {
            background-color: #0f172a !important;
            color: #e2e8f0 !important;
        }

        /* Dark - Top Navbar */
        [data-theme="dark"] .top-navbar-fixed {
            background: #1e293b !important;
            border-bottom-color: #334155 !important;
        }
        [data-theme="dark"] .top-navbar-fixed h5,
        [data-theme="dark"] .top-navbar-fixed .text-muted {
            color: #94a3b8 !important;
        }
        [data-theme="dark"] .time-display {
            background: rgba(99, 102, 241, 0.15) !important;
            border-color: rgba(99, 102, 241, 0.25) !important;
        }

        /* Dark - Sidebar */
        [data-theme="dark"] .sidebar-el {
            background: #0b1120 !important;
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.5) !important;
        }

        /* Dark - Cards & Glassmorphism */
        [data-theme="dark"] .el-card,
        [data-theme="dark"] .card {
            background: rgba(30, 41, 59, 0.85) !important;
            border: 1px solid rgba(255, 255, 255, 0.06) !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35) !important;
            color: #e2e8f0 !important;
        }
        [data-theme="dark"] .card-body,
        [data-theme="dark"] .card-header {
            color: #e2e8f0 !important;
        }
        [data-theme="dark"] .el-card__header,
        [data-theme="dark"] .card-header {
            border-bottom-color: rgba(255, 255, 255, 0.06) !important;
        }

        /* Dark - Form Inputs */
        [data-theme="dark"] .el-input__inner,
        [data-theme="dark"] .el-textarea__inner,
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background-color: #1e293b !important;
            border-color: #475569 !important;
            color: #e2e8f0 !important;
        }
        [data-theme="dark"] .el-input__inner:focus,
        [data-theme="dark"] .el-textarea__inner:focus,
        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus {
            background-color: #1e293b !important;
            border-color: #818cf8 !important;
            box-shadow: 0 0 10px rgba(129, 140, 248, 0.3) !important;
            color: #f1f5f9 !important;
        }
        [data-theme="dark"] .form-control::placeholder {
            color: #64748b !important;
        }
        [data-theme="dark"] .form-label,
        [data-theme="dark"] label {
            color: #cbd5e1 !important;
        }
        [data-theme="dark"] .form-floating > label {
            color: #94a3b8 !important;
        }

        /* Dark - Element Plus Input Wrappers */
        [data-theme="dark"] .el-input__wrapper,
        [data-theme="dark"] .el-textarea__inner {
            background-color: #1e293b !important;
            box-shadow: 0 0 0 1px #475569 inset !important;
        }
        [data-theme="dark"] .el-input__wrapper.is-focus {
            box-shadow: 0 0 0 1px #818cf8 inset !important;
        }

        /* Dark - Tables */
        [data-theme="dark"] .table,
        [data-theme="dark"] .el-table {
            --bs-table-bg: transparent !important;
            --bs-table-color: #e2e8f0 !important;
            color: #e2e8f0 !important;
        }
        [data-theme="dark"] .table thead,
        [data-theme="dark"] .table-dark {
            background-color: #1e293b !important;
            color: #e2e8f0 !important;
        }
        [data-theme="dark"] .table-secondary {
            --bs-table-bg: #334155 !important;
            color: #e2e8f0 !important;
        }
        [data-theme="dark"] .table-striped > tbody > tr:nth-of-type(odd) > * {
            --bs-table-bg-type: rgba(255, 255, 255, 0.03) !important;
            color: #e2e8f0 !important;
        }
        [data-theme="dark"] .table td,
        [data-theme="dark"] .table th {
            border-color: #334155 !important;
            color: #e2e8f0 !important;
        }
        [data-theme="dark"] .table-hover > tbody > tr:hover > * {
            --bs-table-bg-state: rgba(99, 102, 241, 0.08) !important;
            color: #f1f5f9 !important;
        }
        [data-theme="dark"] .table-bordered {
            border-color: #334155 !important;
        }
        [data-theme="dark"] .table-light {
            --bs-table-bg: #1e293b !important;
            color: #e2e8f0 !important;
        }
        [data-theme="dark"] .el-table th.el-table__cell {
            background-color: #1e293b !important;
            color: #94a3b8 !important;
            border-bottom-color: #475569 !important;
        }
        [data-theme="dark"] .el-table td.el-table__cell {
            color: #e2e8f0 !important;
        }
        [data-theme="dark"] .el-table__row:hover td {
            background-color: rgba(99, 102, 241, 0.08) !important;
        }
        [data-theme="dark"] .el-table,
        [data-theme="dark"] .el-table__expanded-cell,
        [data-theme="dark"] .el-table tr {
            background-color: transparent !important;
        }

        /* Dark - Modals & Dialogs */
        [data-theme="dark"] .el-dialog {
            background: rgba(30, 41, 59, 0.95) !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }
        [data-theme="dark"] .el-dialog__body {
            color: #e2e8f0 !important;
        }
        [data-theme="dark"] .modal-content {
            background-color: #1e293b !important;
            color: #e2e8f0 !important;
            border-color: #334155 !important;
        }

        /* Dark - Dropdowns */
        [data-theme="dark"] .el-select-dropdown,
        [data-theme="dark"] .dropdown-menu {
            background: rgba(30, 41, 59, 0.95) !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }
        [data-theme="dark"] .el-select-dropdown__item {
            color: #cbd5e1 !important;
        }
        [data-theme="dark"] .el-select-dropdown__item.hover,
        [data-theme="dark"] .el-select-dropdown__item:hover,
        [data-theme="dark"] .dropdown-item:hover {
            background-color: rgba(99, 102, 241, 0.15) !important;
            color: #a5b4fc !important;
        }

        /* Dark - Badges & Tags */
        [data-theme="dark"] .badge.bg-info {
            background-color: rgba(34, 211, 238, 0.2) !important;
            color: #22d3ee !important;
        }
        [data-theme="dark"] .badge.bg-light {
            background-color: #334155 !important;
            color: #e2e8f0 !important;
        }

        /* Dark - Typography */
        [data-theme="dark"] h1, [data-theme="dark"] h2, [data-theme="dark"] h3,
        [data-theme="dark"] h4, [data-theme="dark"] h5, [data-theme="dark"] h6 {
            color: #f1f5f9 !important;
        }
        [data-theme="dark"] .text-muted {
            color: #64748b !important;
        }
        [data-theme="dark"] .text-dark {
            color: #e2e8f0 !important;
        }
        [data-theme="dark"] .text-primary {
            color: #818cf8 !important;
        }
        [data-theme="dark"] .fw-bold,
        [data-theme="dark"] .fw-semibold {
            color: #e2e8f0;
        }
        [data-theme="dark"] a {
            color: #818cf8;
        }
        [data-theme="dark"] .container,
        [data-theme="dark"] .container-fluid {
            color: #e2e8f0;
        }

        /* Dark - Backgrounds */
        [data-theme="dark"] .bg-light {
            background-color: #1e293b !important;
        }
        [data-theme="dark"] .bg-white {
            background-color: #1e293b !important;
        }
        [data-theme="dark"] .card.bg-light {
            background-color: rgba(30, 41, 59, 0.6) !important;
        }

        /* Dark - Footer */
        [data-theme="dark"] .footer-sidebar {
            background: #0b1120 !important;
            border-top-color: #1e293b !important;
        }

        /* Dark - Pagination */
        [data-theme="dark"] .el-pagination button,
        [data-theme="dark"] .el-pagination .el-pager li {
            background-color: #1e293b !important;
            color: #cbd5e1 !important;
            border-color: #475569 !important;
        }
        [data-theme="dark"] .el-pagination .el-pager li.is-active {
            background-color: #818cf8 !important;
            color: #fff !important;
        }

        /* Dark - Nav Tabs */
        [data-theme="dark"] .nav-tabs {
            border-bottom-color: #334155 !important;
        }
        [data-theme="dark"] .nav-tabs .nav-link {
            color: #94a3b8 !important;
        }
        [data-theme="dark"] .nav-tabs .nav-link.active {
            background-color: #1e293b !important;
            color: #818cf8 !important;
            border-color: #334155 #334155 #1e293b !important;
        }

        /* Dark - QC Inspection specific */
        [data-theme="dark"] .qc-entry-header .info-card {
            background: #1e293b !important;
            border-left-color: #475569 !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3) !important;
        }
        [data-theme="dark"] .qc-entry-header .info-card .value {
            color: #f1f5f9 !important;
        }
        [data-theme="dark"] .qc-entry-header .info-card.input-card {
            background: #1e293b !important;
        }
        [data-theme="dark"] .criteria-bar {
            background: rgba(8, 145, 178, 0.1) !important;
            border-color: rgba(8, 145, 178, 0.2) !important;
        }
        [data-theme="dark"] .criteria-label {
            color: #22d3ee !important;
        }
        [data-theme="dark"] .criteria-item {
            color: #67e8f9 !important;
        }
        [data-theme="dark"] .gradient-header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
        }

        /* Dark - Element Plus specific */
        [data-theme="dark"] .el-menu-item,
        [data-theme="dark"] .el-sub-menu__title {
            color: #e2e8f0 !important;
        }
        [data-theme="dark"] .el-button--default {
            background-color: #334155 !important;
            border-color: #475569 !important;
            color: #e2e8f0 !important;
        }
        [data-theme="dark"] .el-button--default:hover {
            background-color: #475569 !important;
            color: #f1f5f9 !important;
        }

        /* Dark - Scrollbar */
        [data-theme="dark"] ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        [data-theme="dark"] ::-webkit-scrollbar-track {
            background: #0f172a;
        }
        [data-theme="dark"] ::-webkit-scrollbar-thumb {
            background: #334155;
            border-radius: 4px;
        }
        [data-theme="dark"] ::-webkit-scrollbar-thumb:hover {
            background: #475569;
        }

        /* ====================================================================
           DARK MODE - Dashboard & Component Scoped Style Overrides
           These global [data-theme="dark"] selectors override scoped styles
           ==================================================================== */

        /* Dashboard container backgrounds */
        [data-theme="dark"] .dashboard-container,
        [data-theme="dark"] .transport-dashboard,
        [data-theme="dark"] .fg-dashboard,
        [data-theme="dark"] .rm-dashboard {
            background-color: #0f172a !important;
        }

        /* Dashboard title and headings */
        [data-theme="dark"] .dashboard-title,
        [data-theme="dark"] .text-slate-800,
        [data-theme="dark"] .text-slate-700 {
            color: #e2e8f0 !important;
        }

        /* Section titles */
        [data-theme="dark"] .section-title {
            color: #94a3b8 !important;
            border-bottom-color: #334155 !important;
        }

        /* Transport Dashboard - Stat Cards (scoped) */
        [data-theme="dark"] .stat-card {
            background: #1e293b !important;
            color: #e2e8f0 !important;
        }
        [data-theme="dark"] .stat-value {
            color: #f1f5f9 !important;
        }
        [data-theme="dark"] .stat-label {
            color: #94a3b8 !important;
        }
        [data-theme="dark"] .stat-icon-box {
            opacity: 0.9;
        }

        /* Dashboard KPI Action Widget Cards */
        [data-theme="dark"] .action-widget {
            background: #1e293b !important;
        }
        [data-theme="dark"] .action-widget h3,
        [data-theme="dark"] .action-widget .fw-bold {
            color: #f1f5f9 !important;
        }
        [data-theme="dark"] .financial-card {
            background: #1e293b !important;
        }

        /* Soft background utilities in dark mode */
        [data-theme="dark"] .bg-primary-soft { background-color: rgba(99, 102, 241, 0.15) !important; }
        [data-theme="dark"] .bg-success-soft { background-color: rgba(16, 185, 129, 0.15) !important; }
        [data-theme="dark"] .bg-info-soft { background-color: rgba(14, 165, 233, 0.15) !important; }
        [data-theme="dark"] .bg-warning-soft { background-color: rgba(245, 158, 11, 0.15) !important; }
        [data-theme="dark"] .bg-danger-soft { background-color: rgba(239, 68, 68, 0.15) !important; }
        [data-theme="dark"] .bg-indigo-soft { background-color: rgba(99, 102, 241, 0.15) !important; }

        /* Location cards in Dashboard */
        [data-theme="dark"] .location-card {
            background: #1e293b !important;
        }
        [data-theme="dark"] .location-card h5,
        [data-theme="dark"] .location-card h3,
        [data-theme="dark"] .location-card p {
            color: #e2e8f0 !important;
        }

        /* Glass cards in dark */
        [data-theme="dark"] .glass-card {
            background: #1e293b !important;
        }

        /* Chart.js label/legend text - override via canvas parent */
        [data-theme="dark"] .chart-container {
            color: #e2e8f0 !important;
        }

        /* High density tables */
        [data-theme="dark"] .high-density-table th {
            color: #94a3b8 !important;
            background: #1e293b !important;
        }
        [data-theme="dark"] .high-density-table td {
            color: #e2e8f0 !important;
        }
        [data-theme="dark"] .sticky-top {
            background: #1e293b !important;
        }

        /* El-Tabs in dark mode */
        [data-theme="dark"] .el-tabs__item {
            color: #94a3b8 !important;
        }
        [data-theme="dark"] .el-tabs__item.is-active {
            color: #818cf8 !important;
        }
        [data-theme="dark"] .el-tabs__nav-wrap::after {
            background-color: #334155 !important;
        }
        [data-theme="dark"] .el-tabs__active-bar {
            background-color: #818cf8 !important;
        }

        /* El-Tag in dark mode */
        [data-theme="dark"] .el-tag {
            background-color: rgba(99, 102, 241, 0.15) !important;
            border-color: rgba(99, 102, 241, 0.3) !important;
            color: #a5b4fc !important;
        }
        [data-theme="dark"] .el-tag--success {
            background-color: rgba(16, 185, 129, 0.15) !important;
            border-color: rgba(16, 185, 129, 0.3) !important;
            color: #34d399 !important;
        }
        [data-theme="dark"] .el-tag--warning {
            background-color: rgba(245, 158, 11, 0.15) !important;
            border-color: rgba(245, 158, 11, 0.3) !important;
            color: #fbbf24 !important;
        }
        [data-theme="dark"] .el-tag--danger {
            background-color: rgba(239, 68, 68, 0.15) !important;
            border-color: rgba(239, 68, 68, 0.3) !important;
            color: #f87171 !important;
        }
        [data-theme="dark"] .el-tag--info {
            background-color: rgba(100, 116, 139, 0.15) !important;
            border-color: rgba(100, 116, 139, 0.3) !important;
            color: #94a3b8 !important;
        }

        /* El-Radio-Group in dark mode */
        [data-theme="dark"] .el-radio-button__inner {
            background-color: #1e293b !important;
            border-color: #475569 !important;
            color: #cbd5e1 !important;
        }
        [data-theme="dark"] .el-radio-button__original-radio:checked + .el-radio-button__inner {
            background-color: #818cf8 !important;
            border-color: #818cf8 !important;
            color: #fff !important;
        }

        /* El-Progress in dark mode */
        [data-theme="dark"] .el-progress-bar__outer {
            background-color: #334155 !important;
        }

        /* Badges in dark mode */
        [data-theme="dark"] .badge.bg-warning-soft { background-color: rgba(245, 158, 11, 0.2) !important; }
        [data-theme="dark"] .badge.bg-success-soft { background-color: rgba(16, 185, 129, 0.2) !important; }
        [data-theme="dark"] .badge.bg-danger-soft { background-color: rgba(239, 68, 68, 0.2) !important; }
        [data-theme="dark"] .badge.bg-info-soft { background-color: rgba(14, 165, 233, 0.2) !important; }
        [data-theme="dark"] .badge.bg-indigo-soft { background-color: rgba(99, 102, 241, 0.2) !important; }

        /* Border-end and border-top in dark */
        [data-theme="dark"] .border-end { border-color: #334155 !important; }
        [data-theme="dark"] .border-top { border-color: #334155 !important; }
        [data-theme="dark"] .border-bottom { border-color: #334155 !important; }

        /* Card bg-transparent headers - ensure text is visible */
        [data-theme="dark"] .card-header.bg-transparent {
            color: #e2e8f0 !important;
        }

        /* bg-gradient-premium stays as is since it's a dark gradient already */

        /* General paragraphs and spans in dark */
        [data-theme="dark"] p,
        [data-theme="dark"] span,
        [data-theme="dark"] td,
        [data-theme="dark"] th,
        [data-theme="dark"] li,
        [data-theme="dark"] div {
            color: inherit;
        }

        /* Ensure the main-content area background is dark */
        [data-theme="dark"] .main-content {
            background-color: #0f172a !important;
        }

        /* Professional table header overrides */
        [data-theme="dark"] .professional-table .el-table__header-wrapper th {
            background-color: #1e293b !important;
            color: #94a3b8 !important;
        }

        /* El-Card header text */
        [data-theme="dark"] .el-card__header .fw-bold {
            color: #e2e8f0 !important;
        }

        /* Breadcrumbs, current view header */
        [data-theme="dark"] .menu-toggle-btn {
            color: #94a3b8 !important;
        }
        [data-theme="dark"] .menu-toggle-btn:hover {
            color: #818cf8 !important;
        }

        /* El-Alert & Info bar */
        [data-theme="dark"] .bg-light.rounded-3 {
            background-color: #1e293b !important;
            color: #94a3b8 !important;
        }

        /* Dark - Outline Buttons */
        [data-theme="dark"] .btn-outline-primary {
            color: #818cf8 !important;
            border-color: #818cf8 !important;
        }
        [data-theme="dark"] .btn-outline-primary:hover {
            background-color: #818cf8 !important;
            color: #fff !important;
        }
        [data-theme="dark"] .btn-outline-secondary {
            color: #94a3b8 !important;
            border-color: #475569 !important;
        }
        [data-theme="dark"] .btn-outline-secondary:hover {
            background-color: #475569 !important;
            color: #f1f5f9 !important;
        }

        /* Dark - Custom btn-purple fallback */
        [data-theme="dark"] .btn-purple {
            background-color: #7c3aed !important;
            border-color: #7c3aed !important;
            color: #fff !important;
        }
        [data-theme="dark"] .btn-purple:hover {
            background-color: #6d28d9 !important;
        }

        /* Dark - Badge visibility */
        [data-theme="dark"] .badge.bg-info.text-dark {
            background-color: rgba(34, 211, 238, 0.2) !important;
            color: #22d3ee !important;
        }
        [data-theme="dark"] .badge.bg-secondary {
            background-color: #475569 !important;
            color: #cbd5e1 !important;
        }

        /* Dark - Sticky Table Headers (used in Receipts, Issues, etc.) */
        [data-theme="dark"] .table-sticky-header thead th {
            background-color: #1e293b !important;
            color: #e2e8f0 !important;
            border-color: #475569 !important;
        }

        /* Dark - Custom Dropdowns (searchable selects) */
        [data-theme="dark"] .custom-dropdown {
            background: #1e293b !important;
            border-color: #475569 !important;
        }
        [data-theme="dark"] .dropdown-item-custom {
            color: #e2e8f0 !important;
            border-bottom-color: #334155 !important;
        }
        [data-theme="dark"] .dropdown-item-custom:hover {
            background-color: rgba(99, 102, 241, 0.15) !important;
            color: #a5b4fc !important;
        }

        /* Dark - Ensure all table th are visible */
        [data-theme="dark"] .table thead th,
        [data-theme="dark"] .table > thead > tr > th {
            background-color: #1e293b !important;
            color: #e2e8f0 !important;
            border-color: #475569 !important;
        }
        /* Blue Theme */
        [data-theme="blue"] {
            --primary-color: #007bff;
            --secondary-color: #5a6c7d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #e3f2fd;
            --dark-color: #0d47a1;
            --bg-color: #f5f9ff;
            --text-color: #0d47a1;
            --navbar-bg: #1565c0;
            --card-bg: #ffffff;
            --border-color: #bbdefb;
        }

        /* Green Theme */
        [data-theme="green"] {
            --primary-color: #28a745;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #e8f5e8;
            --dark-color: #1b5e20;
            --bg-color: #f8fff8;
            --text-color: #1b5e20;
            --navbar-bg: #2e7d32;
            --card-bg: #ffffff;
            --border-color: #c8e6c9;
        }

        /* Purple Theme */
        [data-theme="purple"] {
            --primary-color: #6f42c1;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #f3e5f5;
            --dark-color: #4a148c;
            --bg-color: #faf5ff;
            --text-color: #4a148c;
            --navbar-bg: #5e35b1;
            --card-bg: #ffffff;
            --border-color: #ce93d8;
        }

        /* Orange Theme */
        [data-theme="orange"] {
            --primary-color: #fd7e14;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #fff3e0;
            --dark-color: #e65100;
            --bg-color: #fff8f0;
            --text-color: #e65100;
            --navbar-bg: #f57c00;
            --card-bg: #ffffff;
            --border-color: #ffcc02;
        }

        /* Global Print Fixes */
        @media print {
            aside, 
            header, 
            footer,
            .sidebar-fixed, 
            .top-navbar-fixed, 
            .footer-sidebar, 
            .no-print, 
            .el-button,
            .notification-bell-btn,
            .menu-toggle-btn,
            .theme-selector,
            .scroll-to-top,
            .el-dropdown,
            .el-menu,
            button {
                display: none !important;
                visibility: hidden !important;
            }
            .main-container, .main-content, .app-container {
                margin: 0 !important;
                padding: 0 !important;
                left: 0 !important;
                width: 100% !important;
                display: block !important;
            }
            body {
                background: #fff !important;
                color: #000 !important;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }

        /* Red Theme */
        [data-theme="red"] {
            --primary-color: #dc3545;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #b02a37;
            --info-color: #17a2b8;
            --light-color: #ffebee;
            --dark-color: #c62828;
            --bg-color: #fff5f5;
            --text-color: #c62828;
            --navbar-bg: #d32f2f;
            --card-bg: #ffffff;
            --border-color: #ef9a9a;
        }

        /* Teal Theme */
        [data-theme="teal"] {
            --primary-color: #20c997;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #e0f2f1;
            --dark-color: #00695c;
            --bg-color: #f0fffe;
            --text-color: #00695c;
            --navbar-bg: #00897b;
            --card-bg: #ffffff;
            --border-color: #80cbc4;
        }

        /* Noble UI Theme (Elegant Professional) */
        [data-theme="noble-ui"] {
            --primary-color: #7367f0;
            --secondary-color: #82868b;
            --success-color: #28c76f;
            --warning-color: #ff9f43;
            --danger-color: #ea5455;
            --info-color: #00cfe8;
            --light-color: #f8f8f8;
            --dark-color: #4b4b4b;
            --bg-color: #f8f8f8;
            --text-color: #4b4b4b;
            --navbar-bg: #7367f0;
            --card-bg: #ffffff;
            --border-color: #ebe9f1;
        }

        /* Vuexy Theme (Modern Admin) */
        [data-theme="vuexy"] {
            --primary-color: #7367f0;
            --secondary-color: #82868b;
            --success-color: #28c76f;
            --warning-color: #ff9f43;
            --danger-color: #ea5455;
            --info-color: #00cfe8;
            --light-color: #f6f6f6;
            --dark-color: #5e5873;
            --bg-color: #f8f8f8;
            --text-color: #6e6b7b;
            --navbar-bg: #7367f0;
            --card-bg: #ffffff;
            --border-color: #d8d6de;
        }

        /* Vuely Theme (Clean & Minimal) */
        [data-theme="vuely"] {
            --primary-color: #5d92f4;
            --secondary-color: #8f98a8;
            --success-color: #46c35f;
            --warning-color: #ffc107;
            --danger-color: #ef1616;
            --info-color: #00d0bd;
            --light-color: #f3f4f7;
            --dark-color: #282f40;
            --bg-color: #ffffff;
            --text-color: #484848;
            --navbar-bg: #5d92f4;
            --card-bg: #ffffff;
            --border-color: #e8ecf1;
        }

        /* Sidebar Layout */
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .sidebar-el {
            width: 260px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 2000;
            background: #0f172a !important; /* Deep Premium Slate */
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            box-shadow: 4px 0 25px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .sidebar-el.is-collapsed {
            width: 64px;
        }

        .main-container {
            flex-grow: 1;
            margin-left: 260px;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            min-width: 0 !important;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .main-content {
            flex: 1 0 auto;
            padding: 24px;
            padding-top: 80px;
            overflow-x: hidden;
            min-width: 0 !important;
        }

        .sidebar-el.is-collapsed + .main-container {
            margin-left: 64px;
        }

        .top-navbar-fixed {
            position: fixed;
            top: 0;
            left: 260px;
            right: 0;
            height: 64px;
            background: var(--bg-color);
            border-bottom: 1px solid var(--border-color);
            z-index: 1000;
            display: flex;
            align-items: center;
            padding: 0 24px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-el.is-collapsed + .main-container .top-navbar-fixed {
            left: 64px;
        }

        .sidebar-logo {
            padding: 20px 24px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            background: rgba(0,0,0,0.2);
            height: 64px;
        }

        .sidebar-menu-el {
            border-right: none !important;
            flex-grow: 1;
            overflow-y: auto;
        }

        .sidebar-menu-el .bi {
            font-size: 1.1rem;
            vertical-align: middle;
            transition: transform 0.2s;
        }

        .sidebar-menu-el .el-menu-item:hover .bi {
            transform: scale(1.1);
        }

        /* Icon Colors */
        .icon-dashboard { color: #4dabf7 !important; }
        .icon-suppliers { color: #ff922b !important; }
        .icon-paper { color: #51cf66 !important; }
        .icon-reports { color: #cc5de8 !important; }
        .icon-cartons { color: #ff6b6b !important; }
        .icon-users { color: #20c997 !important; }
        .icon-setup { color: #868e96 !important; }
        .icon-transport { color: #fcc419 !important; }

        .navbar-brand-el {
            font-size: 1.1rem;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
        }

        [data-theme="modern"] .top-navbar-fixed {
            background-color: var(--navbar-bg) !important;
            border-bottom: var(--glass-border) !important;
            box-shadow: var(--glass-shadow);
        }

        .sidebar-menu-el .el-menu-item,
        .sidebar-menu-el .el-sub-menu__title {
            height: 50px !important;
            line-height: 50px !important;
            color: #E2E8F0 !important; /* Brighter Slate 200 */
            font-size: 14px;
            font-weight: 500;
        }

        .sidebar-menu-el .el-menu-item:hover,
        .sidebar-menu-el .el-sub-menu__title:hover {
            background-color: rgba(99, 102, 241, 0.2) !important;
            color: #ffffff !important;
        }

        .sidebar-menu-el .el-menu-item.is-active {
            color: #ffffff !important;
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.3) 0%, transparent 100%) !important;
            border-left: 4px solid #818cf8 !important; /* Brighter Indigo */
        }

        /* Ensure icon visibility */
        .sidebar-menu-el .bi {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        /* Ensure submenu items are also clearly visible */
        .sidebar-menu-el .el-menu .el-menu-item {
            background-color: transparent !important;
            padding-left: 50px !important;
            color: #CBD5E1 !important; /* Slate 300 */
        }

        .sidebar-menu-el .el-menu .el-menu-item:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.05) !important;
        }

        /* Expansion Arrow Color */
        .sidebar-menu-el .el-sub-menu__icon-arrow {
            color: #ffffff !important;
            font-weight: bold;
        }

        .el-menu--collapse .el-sub-menu__icon-arrow {
            display: none !important;
        }

        /* Fixed: Flyout Menu visibility (when sidebar is collapsed) */
        .el-menu--popup {
            background-color: #0f172a !important; /* Match sidebar background */
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            min-width: 180px !important;
        }

        .el-menu--popup .el-menu-item {
            color: #E2E8F0 !important;
            font-size: 14px !important;
            height: 45px !important;
            line-height: 45px !important;
        }

        .el-menu--popup .el-menu-item:hover {
            color: #ffffff !important;
            background-color: rgba(99, 102, 241, 0.2) !important;
        }

        .sidebar-logo .navbar-brand-el {
            transition: opacity 0.2s;
        }

        .sidebar-el.is-collapsed .sidebar-logo .navbar-brand-el {
            opacity: 0;
            pointer-events: none;
        }

        .menu-toggle-btn {
            background: transparent;
            border: none;
            font-size: 1.5rem;
            color: var(--text-color);
            cursor: pointer;
            padding: 0;
            margin-right: 20px;
            display: flex;
            align-items: center;
            transition: color 0.2s;
        }

        .menu-toggle-btn:hover {
            color: var(--primary-color);
        }

        .footer-sidebar {
            background: var(--bg-color);
            border-top: 1px solid var(--border-color);
            padding: 12px;
        }

        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            background: rgba(0,0,0,0.1);
        }

        .user-dropdown-link {
            display: flex;
            align-items: center;
            color: #E2E8F0;
            text-decoration: none;
            cursor: pointer;
            width: 100%;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .user-dropdown-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
        }

        .x-small {
            font-size: 10px;
        }

        .el-dropdown-menu.user-dropdown-style {
            background-color: #0f172a !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            padding: 8px 0;
            box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.5);
        }

        .user-dropdown-style .el-dropdown-menu__item {
            color: #E2E8F0 !important;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
        }

        .user-dropdown-style .el-dropdown-menu__item:hover {
            background-color: rgba(99, 102, 241, 0.2) !important;
            color: #fff !important;
        }

        .user-dropdown-style .el-dropdown-menu__item.text-danger:hover {
            background-color: rgba(239, 68, 68, 0.2) !important;
        }

        .btn {
            font-size: 12px !important;
            white-space: nowrap !important;
        }

        /* Adjust el-menu sub-menu display */
        .el-menu--horizontal > .el-sub-menu .el-sub-menu__title {
            height: 60px !important;
            line-height: 60px !important;
        }
        .el-menu--horizontal > .el-menu-item {
            height: 60px !important;
            line-height: 60px !important;
        }

        /* Time and Notification Styles */
        .time-display {
            background: rgba(99, 102, 241, 0.1);
            padding: 6px 16px;
            border-radius: 50px;
            border: 1px solid rgba(99, 102, 241, 0.2);
            display: flex;
            align-items: center;
        }
        
        .notification-bell-btn {
            position: relative;
            transition: all 0.3s;
        }
        
        .notification-bell-btn:hover {
            transform: scale(1.1);
            color: var(--primary-color) !important;
        }
        
        .animate__infinite {
            animation-iteration-count: infinite;
        }

        /* Footer Styles */
        .footer-sidebar {
            background: #000000 !important;
            color: #ffffff !important;
            padding: 12px 0;
            font-family: 'Outfit', sans-serif !important;
            letter-spacing: 0.8px;
            border-top: 2px solid #1a1a1a;
            position: relative;
            z-index: 10;
        }
        
        .footer-sidebar p {
             color: #ffffff !important;
             font-weight: 400 !important;
             font-size: 0.85rem;
             margin: 0;
             opacity: 0.9;
         }

        /* ==========================================================================
           COLORFUL ENTERPRISE GUI DESIGN OVERRIDES (REELSTOCK ERP PREMIUM UPGRADE)
           ========================================================================== */

        /* 1. Colorful Premium Buttons */
        .el-button, .btn {
            border-radius: 8px !important;
            font-weight: 600 !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05) !important;
            border: none !important;
        }

        /* Scale & translation on hover */
        .el-button:hover, .btn:hover {
            transform: translateY(-2px) scale(1.03) !important;
        }

        /* Active click compression */
        .el-button:active, .btn:active {
            transform: translateY(1px) scale(0.98) !important;
        }

        /* Primary Buttons - Gradient Blue & Glow */
        .el-button--primary, .btn-primary {
            background: linear-gradient(135deg, #2563eb, #3b82f6) !important;
            color: #ffffff !important;
        }
        .el-button--primary:hover, .btn-primary:hover {
            box-shadow: 0 0 14px rgba(59, 130, 246, 0.55) !important;
            background: linear-gradient(135deg, #1d4ed8, #2563eb) !important;
        }

        /* Success Buttons - Green Gradient & Glow */
        .el-button--success, .btn-success {
            background: linear-gradient(135deg, #10b981, #34d399) !important;
            color: #ffffff !important;
        }
        .el-button--success:hover, .btn-success:hover {
            box-shadow: 0 0 14px rgba(16, 185, 129, 0.55) !important;
            background: linear-gradient(135deg, #059669, #10b981) !important;
        }

        /* Warning Buttons - Orange/Yellow Gradient & Glow */
        .el-button--warning, .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #fbbf24) !important;
            color: #ffffff !important;
        }
        .el-button--warning:hover, .btn-warning:hover {
            box-shadow: 0 0 14px rgba(245, 158, 11, 0.55) !important;
            background: linear-gradient(135deg, #d97706, #f59e0b) !important;
        }

        /* Danger Buttons - Red Gradient & Glow */
        .el-button--danger, .btn-danger {
            background: linear-gradient(135deg, #ef4444, #f87171) !important;
            color: #ffffff !important;
        }
        .el-button--danger:hover, .btn-danger:hover {
            box-shadow: 0 0 14px rgba(239, 68, 68, 0.55) !important;
            background: linear-gradient(135deg, #dc2626, #ef4444) !important;
        }

        /* Info Buttons - Cool Indigo/Slate */
        .el-button--info, .btn-info {
            background: linear-gradient(135deg, #64748b, #94a3b8) !important;
            color: #ffffff !important;
        }
        .el-button--info:hover, .btn-info:hover {
            box-shadow: 0 0 14px rgba(100, 116, 139, 0.55) !important;
        }

        /* 2. Modern Inputs & Textboxes */
        .el-input__inner, .el-textarea__inner, .form-control {
            border-radius: 8px !important;
            border: 1px solid rgba(0, 0, 0, 0.08) !important;
            background-color: rgba(255, 255, 255, 0.8) !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
            padding: 8px 14px !important;
            color: #1e293b !important;
            font-weight: 500 !important;
        }
        
        /* Focus state with glowing borders */
        .el-input__inner:focus, .el-textarea__inner:focus, .form-control:focus,
        .el-input.is-focus .el-input__wrapper, .el-select .el-input.is-focus .el-input__wrapper {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.4) !important;
            background-color: #ffffff !important;
        }

        /* Color code forms by section wrappers (Finished Goods Indigo-to-Violet Theme Format) */
        .rm-item-management .el-input__inner:focus, 
        .rm-receipt-management .el-input__inner:focus, 
        .rm-consumption-management .el-input__inner:focus,
        .rm-report-management .el-input__inner:focus,
        .uom-management-container .el-input__inner:focus,
        .job-card-management .el-input__inner:focus, 
        .production-dashboard-management .el-input__inner:focus {
            border-color: #818cf8 !important;
            box-shadow: 0 0 10px rgba(129, 140, 248, 0.4) !important;
        }

        .cartage-management .el-input__inner:focus, 
        .finance-management .el-input__inner:focus {
            border-color: #10b981 !important;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.4) !important;
        }

        .stock-alert-management .el-input__inner:focus {
            border-color: #f59e0b !important;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.4) !important;
        }

        /* 3. Rich Dropdowns */
        .el-select-dropdown, .dropdown-menu {
            background: rgba(255, 255, 255, 0.85) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            border: 1px solid rgba(255, 255, 255, 0.5) !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12) !important;
            border-radius: 12px !important;
        }

        .el-select-dropdown__item {
            font-weight: 500 !important;
            padding: 8px 20px !important;
            color: #475569 !important;
        }

        .el-select-dropdown__item.hover, .el-select-dropdown__item:hover, .dropdown-item:hover {
            background-color: rgba(99, 102, 241, 0.1) !important;
            color: #4f46e5 !important;
            font-weight: 600 !important;
        }

        .el-select-dropdown__item.selected {
            color: #4f46e5 !important;
            font-weight: 700 !important;
            background-color: rgba(99, 102, 241, 0.15) !important;
        }

        /* 4. Soft Colorful Borders */
        .border-blue-glow { border: 1px solid rgba(59, 130, 246, 0.45) !important; }
        .border-purple-glow { border: 1px solid rgba(168, 85, 247, 0.45) !important; }
        .border-green-glow { border: 1px solid rgba(16, 185, 129, 0.45) !important; }
        .border-orange-glow { border: 1px solid rgba(245, 158, 11, 0.45) !important; }

        /* 5. Glassmorphism Enterprise Cards */
        .el-card, .card {
            background: rgba(255, 255, 255, 0.72) !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            border: 1px solid rgba(255, 255, 255, 0.5) !important;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.05) !important;
            border-radius: 16px !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        
        .el-card:hover, .card:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 12px 40px rgba(31, 38, 135, 0.1) !important;
        }

        /* Dark Mode preservation for card glassmorphism */
        [data-theme="dark"] .el-card, [data-theme="dark"] .card {
            background: rgba(30, 41, 59, 0.75) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3) !important;
        }

        /* 6. Form Sections Identity */
        .el-card__header, .card-header {
            background: transparent !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
            padding: 16px 24px !important;
        }

        /* Headers with beautiful vivid gradients (Finished Goods Indigo-to-Violet Theme Format) */
        .rm-item-management .el-card__header, 
        .rm-receipt-management .el-card__header, 
        .rm-consumption-management .el-card__header,
        .rm-report-management .el-card__header,
        .uom-management-container .professional-card .el-card__header,
        .job-card-management .el-card__header, 
        .production-dashboard-management .el-card__header {
            background: linear-gradient(135deg, #6366f1, #a78bfa) !important;
            color: #ffffff !important;
        }

        .cartage-management .el-card__header,
        .cartage-billing-container .el-card__header {
            background: linear-gradient(135deg, #064e3b, #10b981) !important;
            color: #ffffff !important;
        }

        .stock-alert-management .el-card__header {
            background: linear-gradient(135deg, #7c2d12, #f59e0b) !important;
            color: #ffffff !important;
        }

        /* 7. Premium Professional Tables */
        .el-table {
            border-radius: 12px !important;
            overflow: hidden !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02) !important;
            border: 1px solid rgba(0, 0, 0, 0.05) !important;
        }

        .el-table th.el-table__cell {
            background-color: #f8fafc !important;
            background-image: none !important;
            color: #475569 !important;
            font-weight: 700 !important;
            text-transform: uppercase !important;
            font-size: 11.5px !important;
            letter-spacing: 0.8px !important;
            padding: 14px 0 !important;
            border-bottom: 2px solid #e2e8f0 !important;
            font-family: 'Outfit', sans-serif !important;
        }

        .el-table__header-wrapper {
            border-bottom: 2px solid #e2e8f0 !important;
        }

        .el-table td.el-table__cell {
            padding: 12px 0 !important;
            color: #1e293b !important;
            font-size: 13px !important;
            font-family: 'Outfit', sans-serif !important;
        }

        .el-table__row {
            transition: background-color 0.2s ease !important;
        }

        .el-table__row--striped td {
            background-color: #f8fafc !important;
        }

        .el-table__row:hover td {
            background-color: rgba(59, 130, 246, 0.05) !important;
        }

        /* 8. Modern Popups & Dialogs */
        .el-dialog {
            border-radius: 16px !important;
            overflow: hidden !important;
            background: rgba(255, 255, 255, 0.88) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            border: 1px solid rgba(255, 255, 255, 0.5) !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
            animation: dialogFadeIn 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .el-dialog__header {
            background: linear-gradient(135deg, #1e293b, #334155) !important;
            padding: 20px 24px !important;
            margin-right: 0 !important;
        }

        .el-dialog__title {
            color: #ffffff !important;
            font-weight: 700 !important;
            font-size: 1.25rem !important;
            letter-spacing: 0.5px !important;
        }

        .el-dialog__headerbtn .el-dialog__close {
            color: #ffffff !important;
            font-size: 1.3rem !important;
        }

        .el-dialog__headerbtn:hover .el-dialog__close {
            color: #e2e8f0 !important;
            transform: rotate(90deg) !important;
            transition: all 0.25s ease;
        }

        .el-dialog__body {
            padding: 30px 24px 20px 24px !important;
        }

        .el-dialog__footer {
            border-top: 1px solid rgba(0, 0, 0, 0.05) !important;
            padding: 16px 24px 20px 24px !important;
        }

        @keyframes dialogFadeIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
    </style>
    @vite(['resources/js/app.js'])
</head>
<body>
    <div id="app">
        <div v-if="currentView === 'best-ui'">
            <best-ui-showcase-component></best-ui-showcase-component>
        </div>
        <div v-else-if="!user">
            <login-component @logged-in="user = $event; checkAuth()"></login-component>
        </div>
        <div v-else class="app-wrapper">
            <!-- Sidebar -->
            <aside class="sidebar-el" :class="{ 'is-collapsed': isSidebarCollapsed }">
                <div class="sidebar-logo">
                    <img src="/images/quality-cartons-logo.svg" alt="Logo" width="32" height="32" class="me-2" style="min-width: 32px;">
                    <span class="navbar-brand-el" v-show="!isSidebarCollapsed">REELSTOCK</span>
                </div>

                <el-menu
                    :default-active="currentView"
                    class="sidebar-menu-el"
                    background-color="transparent"
                    text-color="#E2E8F0"
                    active-text-color="#ffffff"
                    @select="setView"
                    :collapse="isSidebarCollapsed"
                    :collapse-transition="false"
                >
                    <el-sub-menu index="dashboards" v-if="canView('dashboard') || canView('management-dashboard') || canView('transport-dashboard')">
                        <template #title>
                            <i class="bi bi-speedometer2 me-2 icon-dashboard"></i> 
                            <span>Dashboards</span>
                        </template>
                        <el-menu-item index="dashboard" v-if="canView('dashboard')">Operational Dashboard</el-menu-item>
                        <el-menu-item index="management-dashboard" v-if="canView('management-dashboard')">Management Dashboard</el-menu-item>
                        <el-menu-item index="transport-dashboard" v-if="canView('transport-dashboard')">Transport Dashboard</el-menu-item>
                    </el-sub-menu>
                    
                    <el-sub-menu index="reels-inventory" v-if="canView('suppliers') || canView('qualities') || canView('monthly-consumption')">
                        <template #title>
                            <i class="bi bi-stack me-2" style="color: #60a5fa !important;"></i>
                            <span>Reels Inventory</span>
                        </template>
                        
                        <el-menu-item index="suppliers" v-if="canView('suppliers')">
                            <i class="bi bi-truck me-2 icon-suppliers"></i>
                            <span>Suppliers</span>
                        </el-menu-item>

                        <el-sub-menu index="paper" v-if="canView('qualities') || canView('receipts') || canView('issues') || canView('return-supplier') || canView('stock-alerts')">
                            <template #title>
                                <i class="bi bi-file-earmark-text me-2 icon-paper"></i>
                                <span>Paper</span>
                            </template>
                            <el-menu-item index="qualities" v-if="canView('qualities')">Paper Qualities</el-menu-item>
                            <el-menu-item index="paper-colors" v-if="canView('paper-colors')">Paper Colors</el-menu-item>
                            <el-menu-item index="receipts" v-if="canView('receipts')">Receipts</el-menu-item>
                            <el-menu-item index="issues" v-if="canView('issues')">Reel Issue</el-menu-item>
                            <el-menu-item index="return-supplier" v-if="canView('return-supplier')">Return to Supp.</el-menu-item>
                            <el-menu-item index="stock-alerts" v-if="canView('stock-alerts')">Stock Alerts</el-menu-item>
                        </el-sub-menu>

                        <el-sub-menu index="reports" v-if="canView('monthly-consumption') || canView('reel-stock') || canView('reel-receipt') || canView('monthly-closing') || canView('reel-stock-count') || canView('usage-intelligence') || canView('old-reels')">
                            <template #title>
                                <i class="bi bi-graph-up me-2 icon-reports"></i>
                                <span>Reports</span>
                            </template>
                            <el-menu-item index="monthly-consumption" v-if="canView('monthly-consumption')">Monthly Cons.</el-menu-item>
                            <el-menu-item index="reel-stock" v-if="canView('reel-stock')">Reel Stock</el-menu-item>
                            <el-menu-item index="reel-receipt" v-if="canView('reel-receipt')">Reel Received</el-menu-item>
                            <el-menu-item index="monthly-closing" v-if="canView('monthly-closing')">Monthly Closing</el-menu-item>
                            <el-menu-item index="reel-stock-count" v-if="canView('reel-stock-count')">Stock Count</el-menu-item>
                            <el-menu-item index="usage-intelligence" v-if="canView('usage-intelligence')">Usage Intel.</el-menu-item>
                            <el-menu-item index="old-reels" v-if="canView('old-reels')">Old Reels Report</el-menu-item>
                        </el-sub-menu>
                        
                        <el-sub-menu index="qc-module" v-if="canView('qc-inspection')">
                            <template #title>
                                <i class="bi bi-clipboard2-check-fill me-2" style="color: #2dd4bf !important;"></i>
                                <span>QC Inspection</span>
                            </template>
                            <el-menu-item index="qc-inspection" v-if="canView('qc-inspection')">Reel Inspection</el-menu-item>
                        </el-sub-menu>
                    </el-sub-menu>

                    <el-sub-menu index="transport" v-if="canView('customers') || canView('transporters') || canView('vehicles') || canView('vehicle-types') || canView('cartage-rates') || canView('cartage') || canView('cartage-list') || canView('cartage-report') || canView('cartage-increment') || canView('cartage-increment-history')">
                        <template #title>
                            <i class="bi bi-truck-flatbed me-2 icon-transport"></i>
                            <span>Transport</span>
                        </template>
                        <el-menu-item index="customers" v-if="canView('customers')">Customers</el-menu-item>
                        <el-menu-item index="transporters" v-if="canView('transporters')">Transporters</el-menu-item>
                        <el-menu-item index="vehicles" v-if="canView('vehicles')">Vehicles</el-menu-item>
                        <el-menu-item index="vehicle-types" v-if="canView('vehicle-types')">Vehicle Classifications</el-menu-item>
                        <el-menu-item index="cartage-rates" v-if="canView('cartage-rates')">Cartage Rates</el-menu-item>
                        <el-menu-item index="cartage" v-if="canView('cartage')">Cartage Billing</el-menu-item>
                        <el-menu-item index="cartage-list" v-if="canView('cartage-list')">Cartage Bill List</el-menu-item>
                        <el-menu-item index="cartage-report" v-if="canView('cartage-report')">Cartage Report</el-menu-item>
                        <el-menu-item index="cartage-increment" v-if="canView('cartage-increment')">Cartage Rate Increment</el-menu-item>
                        <el-menu-item index="cartage-increment-history" v-if="canView('cartage-increment-history')">Increment History</el-menu-item>
                    </el-sub-menu>

                    <el-sub-menu index="finished-goods" v-if="canView('fg-dashboard') || canView('fg-products') || canView('fg-receipts') || canView('fg-dispatches') || canView('fg-reports') || canView('fg-inventory-email')">
                        <template #title>
                            <i class="bi bi-box-seam-fill me-2" style="color: #a78bfa !important;"></i>
                            <span>Finished Goods</span>
                        </template>
                        <el-menu-item index="fg-dashboard" v-if="canView('fg-dashboard')">FG Dashboard</el-menu-item>
                        <el-menu-item index="fg-products" v-if="canView('fg-products')">Products</el-menu-item>
                        <el-menu-item index="fg-receipts" v-if="canView('fg-receipts')">Production Entry</el-menu-item>
                        <el-menu-item index="fg-dispatches" v-if="canView('fg-dispatches')">Dispatch Entry</el-menu-item>
                        <el-menu-item index="fg-reports" v-if="canView('fg-reports')">FG Reports</el-menu-item>
                        <el-menu-item index="fg-inventory-email" v-if="canView('fg-inventory-email')">Inventory Email</el-menu-item>
                    </el-sub-menu>

                    <el-sub-menu index="raw-materials" v-if="canView('rm-dashboard') || canView('rm-items') || canView('rm-receipts') || canView('rm-consumptions') || canView('rm-reports')">
                        <template #title>
                            <i class="bi bi-layers-fill me-2" style="color: #a78bfa !important;"></i>
                            <span>Raw Material</span>
                        </template>
                        <el-menu-item index="rm-dashboard" v-if="canView('rm-dashboard')">RM Dashboard</el-menu-item>
                        <el-menu-item index="rm-items" v-if="canView('rm-items')">Material Master</el-menu-item>
                        <el-menu-item index="unit-of-measures" v-if="canView('unit-of-measures')">UoM Master</el-menu-item>
                        <el-menu-item index="rm-receipts" v-if="canView('rm-receipts')">RM Receiving (GRN)</el-menu-item>
                        <el-menu-item index="rm-consumptions" v-if="canView('rm-consumptions')">RM Consumption</el-menu-item>
                        <el-menu-item index="rm-reports" v-if="canView('rm-reports')">RM Reports</el-menu-item>
                    </el-sub-menu>



                    <el-sub-menu index="production-module" v-if="canView('job-cards')">
                        <template #title>
                            <i class="bi bi-gear-wide-connected me-2" style="color: #a78bfa !important;"></i>
                            <span>Production</span>
                        </template>
                        <el-menu-item index="job-cards">Job Cards</el-menu-item>
                        <el-menu-item index="production-dashboard">Production Analytics</el-menu-item>
                    </el-sub-menu>

                    <el-sub-menu index="users" v-if="canView('users') || canView('audit-log')">
                        <template #title>
                            <i class="bi bi-people me-2 icon-users"></i>
                            <span>Users</span>
                        </template>
                        <el-menu-item index="users" v-if="canView('users')">Manage Users</el-menu-item>
                        <el-menu-item index="user-rights" v-if="canView('user-rights')">User Rights</el-menu-item>
                        <el-menu-item index="audit-log" v-if="canView('audit-log')">Audit Logs</el-menu-item>
                    </el-sub-menu>

                    <el-menu-item index="setup" v-if="canView('setup')">
                        <i class="bi bi-gear me-2 icon-setup"></i>
                        <span>Setup</span>
                    </el-menu-item>
                </el-menu>

                <!-- Sidebar User Footer -->
                <div class="sidebar-footer">
                    <el-dropdown trigger="hover" placement="top-start" style="width: 100%;">
                        <div class="user-dropdown-link">
                            <i class="bi bi-person-circle fs-5 me-2" style="color: #818cf8; min-width: 24px;"></i>
                            <div class="flex-grow-1 overflow-hidden" v-show="!isSidebarCollapsed">
                                <p class="mb-0 small fw-bold text-truncate">@{{ user.name }}</p>
                                <p class="mb-0 x-small text-muted text-truncate">@{{ user.email }}</p>
                            </div>
                            <i class="bi bi-chevron-up small ms-1 opacity-50" v-show="!isSidebarCollapsed"></i>
                        </div>
                        <template #dropdown>
                            <el-dropdown-menu class="user-dropdown-style">
                                <el-dropdown-item @click="setView('profile')">
                                    <i class="bi bi-person me-2"></i> My Profile
                                </el-dropdown-item>
                                <el-dropdown-item divided @click="logout" class="text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i> Logout
                                </el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>
                </div>
            </aside>

            <div class="main-container flex-grow-1">
                <!-- Top Header -->
                <header class="top-navbar-fixed d-flex justify-content-between">
                    <div class="d-flex align-items-center">
                        <button class="menu-toggle-btn" @click="isSidebarCollapsed = !isSidebarCollapsed">
                            <i class="bi" :class="isSidebarCollapsed ? 'bi-text-indent-left' : 'bi-text-indent-right'"></i>
                        </button>
                        <h5 class="mb-0 fw-bold text-muted text-uppercase small">
                            <i class="bi bi-chevron-right me-1"></i> @{{ currentView.replace('-', ' ') }}
                        </h5>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="time-display me-3 d-none d-md-block">
                            <i class="bi bi-clock me-1 text-primary"></i>
                            <span class="fw-bold text-muted small">@{{ currentTime }}</span>
                        </div>
                        <el-badge :value="triggeredCount" class="item" :hidden="triggeredCount === 0">
                            <el-button circle @click="setView('stock-alerts')" class="notification-bell-btn">
                                <i class="bi" :class="triggeredCount > 0 ? 'bi-bell-fill text-danger animate__animated animate__swing animate__infinite' : 'bi-bell'"></i>
                            </el-button>
                        </el-badge>

                        <el-badge :value="pendingCartageCount" class="item" :hidden="pendingCartageCount === 0" type="warning">
                            <el-button circle @click="setView('cartage-list')" class="notification-bell-btn">
                                <i class="bi" :class="pendingCartageCount > 0 ? 'bi-shield-check text-warning animate__animated animate__pulse animate__infinite' : 'bi-shield-check'"></i>
                            </el-button>
                        </el-badge>
                        <el-button circle @click="setView('reconciliation')" title="Stock Reconciliation">
                            <i class="bi bi-arrow-repeat"></i>
                        </el-button>
                        <theme-selector-component></theme-selector-component>
                    </div>
                </header>

                <!-- Main Content Area -->
                <main class="main-content">
                <dashboard-component v-if="currentView === 'dashboard'" :user="user" initial-tab="operational" :can-view-dashboard="canView('dashboard')" :can-see-amounts="canSeeAmounts('dashboard')" :can-view-management="canView('management-dashboard')"></dashboard-component>
                <dashboard-component v-else-if="currentView === 'management-dashboard'" :user="user" initial-tab="management" :can-view-dashboard="canView('dashboard')" :can-see-amounts="true" :can-view-management="true"></dashboard-component>
                <transport-dashboard-component v-else-if="currentView === 'transport-dashboard'" :user="user"></transport-dashboard-component>
                <supplier-component v-else-if="currentView === 'suppliers'" :user="user"></supplier-component>
                <paper-quality-component v-else-if="currentView === 'qualities'" :user="user"></paper-quality-component>
                <paper-color-component v-else-if="currentView === 'paper-colors'" :user="user"></paper-color-component>
                <reel-receipt-component v-else-if="currentView === 'receipts'" :user="user"></reel-receipt-component>
                <reel-issue-component v-else-if="currentView === 'issues'" :user="user"></reel-issue-component>
                <reel-return-supplier-component v-else-if="currentView === 'return-supplier'" :user="user"></reel-return-supplier-component>
                <monthly-consumption-report-component v-else-if="currentView === 'monthly-consumption'" :user="user" :can-see-amounts="canSeeAmounts('monthly-consumption')"></monthly-consumption-report-component>
                <reel-stock-report-component v-else-if="currentView === 'reel-stock'" :user="user" :can-see-amounts="canSeeAmounts('reel-stock')"></reel-stock-report-component>
                <reel-receipt-report-component v-else-if="currentView === 'reel-receipt'" :user="user" :can-see-amounts="canSeeAmounts('reel-receipt')"></reel-receipt-report-component>
                <monthly-closing-report-component v-else-if="currentView === 'monthly-closing'" :user="user" :can-see-amounts="canSeeAmounts('monthly-closing')"></monthly-closing-report-component>
                <reel-stock-count-report-component v-else-if="currentView === 'reel-stock-count'" :user="user"></reel-stock-count-report-component>
                <usage-intelligence-report-component v-else-if="currentView === 'usage-intelligence'" :user="user"></usage-intelligence-report-component>
                <old-reels-report-component v-else-if="currentView === 'old-reels'" :user="user" :can-see-amounts="canSeeAmounts('old-reels')"></old-reels-report-component>
                <reports-component v-else-if="currentView === 'reports'" :user="user"></reports-component>
                <user-component v-else-if="currentView === 'users'" :user="user"></user-component>
                <user-rights-component v-else-if="currentView === 'user-rights'" :user="user"></user-rights-component>
                <audit-log-component v-else-if="currentView === 'audit-log'" :user="user"></audit-log-component>
                <setup-component v-else-if="currentView === 'setup'" :user="user"></setup-component>
                <profile-component v-else-if="currentView === 'profile'" :user="user"></profile-component>
                <stock-alert-component v-else-if="currentView === 'stock-alerts'" :user="user" @update-triggered-count="triggeredCount = $event"></stock-alert-component>
                <reconciliation-component v-else-if="currentView === 'reconciliation'" :user="user"></reconciliation-component>
                <customer-component v-else-if="currentView === 'customers'" :user="user"></customer-component>
                <transporter-component v-else-if="currentView === 'transporters'" :user="user"></transporter-component>
                <vehicle-component v-else-if="currentView === 'vehicles'" :user="user"></vehicle-component>
                <vehicle-type-component v-else-if="currentView === 'vehicle-types'" :user="user"></vehicle-type-component>
                <cartage-rate-component v-else-if="currentView === 'cartage-rates'" :user="user"></cartage-rate-component>
                <cartage-rate-increment-component v-else-if="currentView === 'cartage-increment'" :user="user"></cartage-rate-increment-component>
                <cartage-increment-history-component v-else-if="currentView === 'cartage-increment-history'" :user="user"></cartage-increment-history-component>
                <cartage-billing-component v-else-if="currentView === 'cartage'" :user="user" @update-pending-count="fetchPendingCartageCount"></cartage-billing-component>
                <cartage-billing-component v-else-if="currentView === 'cartage-list'" :user="user" :initial-history="true" @update-pending-count="fetchPendingCartageCount"></cartage-billing-component>
                <cartage-report-component v-else-if="currentView === 'cartage-report'" :user="user"></cartage-report-component>
                <fg-dashboard-component v-else-if="currentView === 'fg-dashboard'" :user="user"></fg-dashboard-component>
                <product-component v-else-if="currentView === 'fg-products'" :user="user"></product-component>
                <fg-receipt-component v-else-if="currentView === 'fg-receipts'" :user="user"></fg-receipt-component>
                <fg-dispatch-component v-else-if="currentView === 'fg-dispatches'" :user="user"></fg-dispatch-component>
                <fg-report-component v-else-if="currentView === 'fg-reports'" :user="user" :can-see-amounts="canSeeAmounts('fg-reports')"></fg-report-component>
                <fg-inventory-email-component v-else-if="currentView === 'fg-inventory-email'" :user="user"></fg-inventory-email-component>
                <qc-inspection-component v-else-if="currentView === 'qc-inspection'" :user="user"></qc-inspection-component>
                
                <rm-dashboard-component v-else-if="currentView === 'rm-dashboard'" :user="user"></rm-dashboard-component>
                <rm-item-component v-else-if="currentView === 'rm-items'" :user="user"></rm-item-component>
                <rm-receipt-component v-else-if="currentView === 'rm-receipts'" :user="user"></rm-receipt-component>
                <rm-consumption-component v-else-if="currentView === 'rm-consumptions'" :user="user"></rm-consumption-component>
                <rm-report-component v-else-if="currentView === 'rm-reports'" :user="user"></rm-report-component>
                <unit-of-measure-component v-else-if="currentView === 'unit-of-measures'" :user="user"></unit-of-measure-component>
                <job-card-component v-else-if="currentView === 'job-cards'" :user="user"></job-card-component>
                <production-dashboard-component v-else-if="currentView === 'production-dashboard'" :user="user"></production-dashboard-component>

                <best-ui-showcase-component v-else-if="currentView === 'best-ui'"></best-ui-showcase-component>
                <!-- Add other components here -->
                <div v-else>
                    <h2>@{{ currentView.charAt(0).toUpperCase() + currentView.slice(1) }} Management</h2>
                    <p>Component for @{{ currentView }} will be added here.</p>
                    <p>Debug: Current view is "@{{ currentView }}"</p>
                </div>
                </main>

                <footer class="footer-sidebar">
                    <div class="d-flex justify-content-between px-4">
                        <span>Software Developed by SACHAAN TECHSOL &copy; 2026</span>
                        <span>Contact / WhatsApp: 03002566358</span>
                    </div>
                </footer>
            </div>
        </div>
        <scroll-to-top-component></scroll-to-top-component>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
