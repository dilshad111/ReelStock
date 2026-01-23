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
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --warning-color: #fd7e14;
            --danger-color: #dc3545;
            --info-color: #0dcaf0;
            --light-color: #495057;
            --dark-color: #f8f9fa;
            --bg-color: #212529;
            --text-color: #f8f9fa;
            --navbar-bg: #1a1d20;
            --card-bg: #343a40;
            --border-color: #495057;
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

        .main-content {
            flex-grow: 1;
            margin-left: 260px;
            padding: 24px;
            padding-top: 80px;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-el.is-collapsed + .main-container .main-content {
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
                    <el-menu-item index="dashboard" v-if="!permissionsLoaded || canView('dashboard')">
                        <i class="bi bi-speedometer2 me-2 icon-dashboard"></i> 
                        <span>Dashboard</span>
                    </el-menu-item>
                    
                    <el-menu-item index="suppliers" v-if="!permissionsLoaded || canView('suppliers')">
                        <i class="bi bi-truck me-2 icon-suppliers"></i>
                        <span>Suppliers</span>
                    </el-menu-item>

                    <el-sub-menu index="paper">
                        <template #title>
                            <i class="bi bi-file-earmark-text me-2 icon-paper"></i>
                            <span>Paper</span>
                        </template>
                        <el-menu-item index="qualities">Paper Qualities</el-menu-item>
                        <el-menu-item index="receipts" v-if="!permissionsLoaded || canView('receipts')">Receipts</el-menu-item>
                        <el-menu-item index="issues" v-if="!permissionsLoaded || canView('issues')">Reel Issue</el-menu-item>
                        <el-menu-item index="return-supplier" v-if="!permissionsLoaded || canView('return-supplier')">Return to Supp.</el-menu-item>
                        <el-menu-item index="stock-alerts" v-if="!permissionsLoaded || canView('stock-alerts')">Stock Alerts</el-menu-item>
                    </el-sub-menu>

                    <el-sub-menu index="reports">
                        <template #title>
                            <i class="bi bi-graph-up me-2 icon-reports"></i>
                            <span>Reports</span>
                        </template>
                        <el-menu-item index="monthly-consumption" v-if="!permissionsLoaded || canView('monthly-consumption')">Monthly Cons.</el-menu-item>
                        <el-menu-item index="reel-stock" v-if="!permissionsLoaded || canView('reel-stock')">Reel Stock</el-menu-item>
                        <el-menu-item index="reel-receipt" v-if="!permissionsLoaded || canView('reel-receipt')">Reel Received</el-menu-item>
                        <el-menu-item index="monthly-closing" v-if="!permissionsLoaded || canView('monthly-closing')">Monthly Closing</el-menu-item>
                        <el-menu-item index="reel-stock-count" v-if="!permissionsLoaded || canView('reel-stock-count')">Stock Count</el-menu-item>
                        <el-menu-item index="usage-intelligence" v-if="!permissionsLoaded || canView('usage-intelligence')">Usage Intel.</el-menu-item>
                        <el-menu-item index="old-reels" v-if="!permissionsLoaded || canView('old-reels')">Old Reels Report</el-menu-item>
                    </el-sub-menu>

                    <el-sub-menu index="cartons" v-if="!permissionsLoaded || canView('cartons')">
                        <template #title>
                            <i class="bi bi-box me-2 icon-cartons"></i>
                            <span>Cartons</span>
                        </template>
                        <el-menu-item index="customers">Customers</el-menu-item>
                        <el-menu-item index="sketch-generator">Sketch Gen.</el-menu-item>
                    </el-sub-menu>

                    <el-sub-menu index="users" v-if="user.role.name === 'Admin' || user.email === 'superadmin@qc.com'">
                        <template #title>
                            <i class="bi bi-people me-2 icon-users"></i>
                            <span>Users</span>
                        </template>
                        <el-menu-item index="users">Manage Users</el-menu-item>
                        <el-menu-item index="user-rights">User Rights</el-menu-item>
                        <el-menu-item index="audit-log">Audit Logs</el-menu-item>
                    </el-sub-menu>

                    <el-menu-item index="setup" v-if="user.role.name === 'Admin' || user.email === 'superadmin@qc.com'">
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
                        <el-button circle @click="setView('reconciliation')" title="Stock Reconciliation">
                            <i class="bi bi-arrow-repeat"></i>
                        </el-button>
                        <theme-selector-component></theme-selector-component>
                    </div>
                </header>

                <!-- Main Content Area -->
                <main class="main-content">
                <dashboard-component v-if="currentView === 'dashboard'" :user="user" :can-view-dashboard="canView('dashboard')" :can-see-amounts="canSeeAmounts('dashboard')"></dashboard-component>
                <supplier-component v-else-if="currentView === 'suppliers'" :user="user"></supplier-component>
                <paper-quality-component v-else-if="currentView === 'qualities'" :user="user"></paper-quality-component>
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
                <cartons-component v-else-if="currentView === 'cartons'" :user="user"></cartons-component>
                <customer-component v-else-if="currentView === 'customers'" :user="user"></customer-component>
                <sketch-generator-component v-else-if="currentView === 'sketch-generator'" :user="user"></sketch-generator-component>
                <reports-component v-else-if="currentView === 'reports'" :user="user"></reports-component>
                <user-component v-else-if="currentView === 'users'" :user="user"></user-component>
                <user-rights-component v-else-if="currentView === 'user-rights'" :user="user"></user-rights-component>
                <audit-log-component v-else-if="currentView === 'audit-log'" :user="user"></audit-log-component>
                <setup-component v-else-if="currentView === 'setup'" :user="user"></setup-component>
                <profile-component v-else-if="currentView === 'profile'" :user="user"></profile-component>
                <stock-alert-component v-else-if="currentView === 'stock-alerts'" :user="user" @update-triggered-count="triggeredCount = $event"></stock-alert-component>
                <reconciliation-component v-else-if="currentView === 'reconciliation'" :user="user"></reconciliation-component>
                <best-ui-showcase-component v-else-if="currentView === 'best-ui'"></best-ui-showcase-component>
                <!-- Add other components here -->
                <div v-else>
                    <h2>@{{ currentView.charAt(0).toUpperCase() + currentView.slice(1) }} Management</h2>
                    <p>Component for @{{ currentView }} will be added here.</p>
                    <p>Debug: Current view is "@{{ currentView }}"</p>
                </div>
                </main>

                <footer class="footer-sidebar text-center py-3 mt-auto">
                    <p class="mb-0 small text-muted">Developed by DILSHAD KB &copy; 2026 SACHAAN TECHSOL. | Contact: 0300-2566358</p>
                </footer>
            </div>
        </div>
        <scroll-to-top-component></scroll-to-top-component>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
