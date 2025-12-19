<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quality Cartons - ReelStock Inventory</title>
    <link rel="icon" type="image/svg+xml" href="/reelStock/images/quality-cartons-logo.svg">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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

        /* Sidebar styles */
        .main-content {
            padding: 20px;
            padding-top: 100px;
            min-height: 100vh;
            position: relative;
        }

        .top-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background-color: var(--navbar-bg);
        }

        .btn {
            font-size: 12px !important;
            white-space: nowrap !important;
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
        <div v-else>
            <nav class="navbar navbar-expand-lg navbar-dark top-navbar">
                <div class="container-fluid">
                    <a class="navbar-brand" href="#">
                        <img src="/reelStock/images/quality-cartons-logo.svg" alt="Quality Cartons Logo" width="32" height="32" class="d-inline-block align-text-top me-2">
                        QUALITY CARTONS (PVT.) LTD.
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar" aria-controls="topNavbar" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="topNavbar">
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                            <li class="nav-item" v-if="!permissionsLoaded || canView('dashboard')">
                                <button class="nav-link btn btn-link" :class="{ active: currentView === 'dashboard' }" @click="setView('dashboard')">
                                    Dashboard
                                </button>
                            </li>
                            <li class="nav-item" v-if="!permissionsLoaded || canView('suppliers')">
                                <button class="nav-link btn btn-link" :class="{ active: currentView === 'suppliers' }" @click="setView('suppliers')">
                                    Suppliers
                                </button>
                            </li>
                            <li class="nav-item dropdown">
                                <button class="nav-link btn btn-link dropdown-toggle" id="paperDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    Paper
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="paperDropdown">
                                    <li><button class="dropdown-item" @click="setView('qualities')">Paper Qualities</button></li>
                                    <li v-if="!permissionsLoaded || canView('receipts')"><button class="dropdown-item" @click="setView('receipts')">Receipts</button></li>
                                    <li v-if="!permissionsLoaded || canView('issues')"><button class="dropdown-item" @click="setView('issues')">Reel Issue</button></li>
                                    <li v-if="!permissionsLoaded || canView('return-supplier')"><button class="dropdown-item" @click="setView('return-supplier')">Reel Return to Supplier</button></li>
                                </ul>
                            </li>
                            <li class="nav-item dropdown">
                                <button class="nav-link btn btn-link dropdown-toggle" id="reportsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    Reports
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="reportsDropdown">
                                    <li v-if="!permissionsLoaded || canView('monthly-consumption')"><button class="dropdown-item" @click="setView('monthly-consumption')">Monthly Consumption</button></li>
                                    <li v-if="!permissionsLoaded || canView('reel-stock')"><button class="dropdown-item" @click="setView('reel-stock')">Reel Stock</button></li>
                                    <li v-if="!permissionsLoaded || canView('reel-receipt')"><button class="dropdown-item" @click="setView('reel-receipt')">Reel Received Report</button></li>
                                    <li v-if="!permissionsLoaded || canView('monthly-closing')"><button class="dropdown-item" @click="setView('monthly-closing')">Monthly Closing Stock</button></li>
                                </ul>
                            </li>
                            <li class="nav-item dropdown" v-if="!permissionsLoaded || canView('cartons')">
                                <button class="nav-link btn btn-link dropdown-toggle" id="cartonsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    Cartons
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="cartonsDropdown">
                                    <li><button class="dropdown-item" @click="setView('customers')">Customers</button></li>
                                    <li><button class="dropdown-item" @click="setView('sketch-generator')">Sketch Generator</button></li>
                                </ul>
                            </li>
                            <li class="nav-item dropdown" v-if="user.role.name === 'Admin' || user.email === 'superadmin@qc.com'">
                                <button class="nav-link btn btn-link dropdown-toggle" id="usersDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    Users
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="usersDropdown">
                                    <li>
                                        <button class="dropdown-item" :class="{ active: currentView === 'users' }" @click="setView('users')">
                                            Manage Users
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" :class="{ active: currentView === 'user-rights' }" @click="setView('user-rights')">
                                            User Rights
                                        </button>
                                    </li>
                                    <li>
                                        <button class="dropdown-item" :class="{ active: currentView === 'audit-log' }" @click="setView('audit-log')">
                                            Audit Logs
                                        </button>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item" v-if="user.role.name === 'Admin' || user.email === 'superadmin@qc.com'">
                                <button class="nav-link btn btn-link" :class="{ active: currentView === 'setup' }" @click="setView('setup')">
                                    Setup
                                </button>
                            </li>
                        </ul>
                        <div class="d-flex align-items-center">
                            <span class="text-white me-3">
                                <i class="bi bi-person-circle me-2"></i>Welcome, @{{ user.name }}
                            </span>
                            <div class="me-2">
                                <theme-selector-component></theme-selector-component>
                            </div>
                            <button @click="logout" class="btn btn-sm btn-outline-light">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </button>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="main-content">
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
                <cartons-component v-else-if="currentView === 'cartons'" :user="user"></cartons-component>
                <customer-component v-else-if="currentView === 'customers'" :user="user"></customer-component>
                <sketch-generator-component v-else-if="currentView === 'sketch-generator'" :user="user"></sketch-generator-component>
                <reports-component v-else-if="currentView === 'reports'" :user="user"></reports-component>
                <user-component v-else-if="currentView === 'users'" :user="user"></user-component>
                <user-rights-component v-else-if="currentView === 'user-rights'" :user="user"></user-rights-component>
                <audit-log-component v-else-if="currentView === 'audit-log'" :user="user"></audit-log-component>
                <setup-component v-else-if="currentView === 'setup'" :user="user"></setup-component>
                <best-ui-showcase-component v-else-if="currentView === 'best-ui'"></best-ui-showcase-component>
                <!-- Add other components here -->
                <div v-else>
                    <h2>@{{ currentView.charAt(0).toUpperCase() + currentView.slice(1) }} Management</h2>
                    <p>Component for @{{ currentView }} will be added here.</p>
                    <p>Debug: Current view is "@{{ currentView }}"</p>
                </div>
            </div>
        </div>
        <scroll-to-top-component></scroll-to-top-component>
    </div>
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">This software is developed by DILSHAD KB &copy; 2026 SACHAAN TECHSOL. All rights reserved. | Contact: 0300-2566358</p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
