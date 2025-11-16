<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quality Cartons - ReelStock Inventory</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

        /* Sidebar styles */
        .main-content {
            padding: 20px;
            padding-top: 100px;
            min-height: 100vh;
            position: relative;
            z-index: 999;
        }

        .top-navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background-color: var(--navbar-bg);
        }
    </style>
    @vite(['resources/js/app.js'])
</head>
<body>
    <div id="app">
        <div v-if="!user">
            <login-component @logged-in="user = $event; checkAuth()"></login-component>
        </div>
        <div v-else>
            <nav class="navbar navbar-expand-lg navbar-dark top-navbar">
                <div class="container-fluid">
                    <a class="navbar-brand" href="#">
                        <img src="/images/quality-cartons-logo.svg" alt="Quality Cartons Logo" width="32" height="32" class="d-inline-block align-text-top me-2">
                        QUALITY CARTONS (PVT.) LTD.
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar" aria-controls="topNavbar" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="topNavbar">
                        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                            <li class="nav-item">
                                <button class="nav-link btn btn-link" :class="{ active: currentView === 'dashboard' }" @click="currentView = 'dashboard'">
                                    Dashboard
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link btn btn-link" :class="{ active: currentView === 'suppliers' }" @click="currentView = 'suppliers'">
                                    Suppliers
                                </button>
                            </li>
                            <li class="nav-item dropdown">
                                <button class="nav-link btn btn-link dropdown-toggle" id="paperDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    Paper
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="paperDropdown">
                                    <li><button class="dropdown-item" @click="currentView = 'qualities'">Paper Qualities</button></li>
                                    <li><button class="dropdown-item" @click="currentView = 'receipts'">Receipts</button></li>
                                    <li><button class="dropdown-item" @click="currentView = 'issues'">Issues</button></li>
                                    <li><button class="dropdown-item" @click="currentView = 'returns'">Returns</button></li>
                                </ul>
                            </li>
                            <li class="nav-item dropdown">
                                <button class="nav-link btn btn-link dropdown-toggle" id="reportsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    Reports
                                </button>
                                <ul class="dropdown-menu" aria-labelledby="reportsDropdown">
                                    <li><button class="dropdown-item" @click="currentView = 'monthly-consumption'">Monthly Consumption</button></li>
                                    <li><button class="dropdown-item" @click="currentView = 'reel-stock'">Reel Stock</button></li>
                                    <li><button class="dropdown-item" @click="currentView = 'reel-receipt'">Reel Receipt</button></li>
                                    <li><button class="dropdown-item" @click="currentView = 'monthly-closing'">Monthly Closing Stock</button></li>
                                </ul>
                            </li>
                            <li class="nav-item" v-if="user.role.name === 'Admin'">
                                <button class="nav-link btn btn-link" :class="{ active: currentView === 'users' }" @click="currentView = 'users'">
                                    Users
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
                <dashboard-component v-if="currentView === 'dashboard'" :user="user"></dashboard-component>
                <supplier-component v-else-if="currentView === 'suppliers'" :user="user"></supplier-component>
                <paper-quality-component v-else-if="currentView === 'qualities'" :user="user"></paper-quality-component>
                <reel-receipt-component v-else-if="currentView === 'receipts'" :user="user"></reel-receipt-component>
                <reel-issue-component v-else-if="currentView === 'issues'" :user="user"></reel-issue-component>
                <reel-return-component v-else-if="currentView === 'returns'" :user="user"></reel-return-component>
                <monthly-consumption-report-component v-else-if="currentView === 'monthly-consumption'" :user="user"></monthly-consumption-report-component>
                <reel-stock-report-component v-else-if="currentView === 'reel-stock'" :user="user"></reel-stock-report-component>
                <reel-receipt-report-component v-else-if="currentView === 'reel-receipt'" :user="user"></reel-receipt-report-component>
                <monthly-closing-report-component v-else-if="currentView === 'monthly-closing'" :user="user"></monthly-closing-report-component>
                <reports-component v-else-if="currentView === 'reports'" :user="user"></reports-component>
                <user-component v-else-if="currentView === 'users'" :user="user"></user-component>
                <!-- Add other components here -->
                <div v-else>
                    <h2>@{{ currentView.charAt(0).toUpperCase() + currentView.slice(1) }} Management</h2>
                    <p>Component for @{{ currentView }} will be added here.</p>
                    <p>Debug: Current view is "@{{ currentView }}"</p>
                </div>
            </div>
        </div>
    </div>
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">&copy; 2026 Sachaan Techsol. All rights reserved. | Contact: 0092 3002566358</p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
