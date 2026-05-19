import './bootstrap';
import { createApp } from 'vue';
import axios from 'axios';

axios.defaults.baseURL = 'http://192.168.10.47:8000';

const PERMISSION_KEY_MAP = {
    dashboard: 'dashboard',
    suppliers: 'suppliers',
    receipts: 'receipts',
    qualities: 'qualities',
    'paper-colors': 'qualities',
    issues: 'issues',
    'return-supplier': 'return-supplier',
    'monthly-consumption': 'monthly-consumption',
    'reel-stock': 'reel-stock',
    'reel-receipt': 'reel-receipt',
    'monthly-closing': 'monthly-closing',
    'reel-stock-count': 'reel-stock-count',
    'usage-intelligence': 'usage-intelligence',
    'management-dashboard': 'management-dashboard',
    reports: null,
    users: 'users',
    'user-rights': 'user-rights',
    'audit-log': 'audit-log',
    'old-reels': 'old-reels',
    'best-ui': null,
    'stock-alerts': 'stock-alerts',
    'reconciliation': 'reconciliation',
    'customers': 'customers',
    'transporters': 'transporters',
    'vehicles': 'vehicles',
    'vehicle-types': 'vehicle-types',
    'cartage-rates': 'cartage-rates',
    'cartage': 'cartage',
    'cartage-list': 'cartage-list',
    'cartage-increment': 'cartage-increment',
    'cartage-increment-history': 'cartage-increment-history',
    'cartage-report': 'cartage-report',
    'setup': 'setup',
    'transport-dashboard': 'transport-dashboard',
    'fg-products': 'fg-products',
    'fg-receipts': 'fg-receipts',
    'fg-dispatches': 'fg-dispatches',
    'fg-reports': 'fg-reports',
    'fg-inventory-email': 'fg-inventory-email',
    'fg-dashboard': 'fg-dashboard',
    'approve_cartage': 'approve_cartage',
    'qc-inspection': 'qc-inspection',
    'rm-dashboard': 'rm-dashboard',
    'rm-items': 'rm-items',
    'rm-receipts': 'rm-receipts',
    'rm-consumptions': 'rm-consumptions',
    'rm-reports': 'rm-reports',
    'unit-of-measures': 'rm-items',
    'job-cards': 'job-cards',
    'production-dashboard': 'production-dashboard'
};

const PERMISSION_KEYS = [
    'dashboard',
    'suppliers',
    'qualities',
    'paper-colors',
    'receipts',
    'issues',
    'return-supplier',
    'monthly-consumption',
    'reel-stock',
    'reel-receipt',
    'monthly-closing',
    'reel-stock-count',
    'usage-intelligence',
    'management-dashboard',
    'customers',
    'transporters',
    'vehicles',
    'vehicle-types',
    'cartage-rates',
    'cartage',
    'cartage-list',
    'cartage-increment',
    'cartage-increment-history',
    'cartage-report',
    'approve_cartage',
    'setup',
    'users',
    'user-rights',
    'audit-log',
    'transport-dashboard',
    'fg-products',
    'fg-receipts',
    'fg-dispatches',
    'fg-reports',
    'fg-inventory-email',
    'fg-dashboard',
    'old-reels',
    'stock-alerts',
    'reconciliation',
    'qc-inspection',
    'rm-dashboard',
    'rm-items',
    'rm-receipts',
    'rm-consumptions',
    'rm-reports',
    'job-cards',
    'production-dashboard'
];

const VIEW_ORDER = [
    'dashboard',
    'transport-dashboard',
    'management-dashboard',
    'suppliers',
    'qualities',
    'receipts',
    'issues',
    'return-supplier',
    'monthly-consumption',
    'reel-stock',
    'reel-receipt',
    'monthly-closing',
    'reel-stock-count',
    'usage-intelligence',
    'reports',
    'users',
    'user-rights',
    'audit-log',
    'best-ui',
    'old-reels',
    'stock-alerts',
    'reconciliation',
    'customers',
    'transporters',
    'vehicles',
    'vehicle-types',
    'cartage-rates',
    'cartage-increment',
    'cartage-increment-history',
    'cartage',
    'cartage-list',
    'cartage-report',
    'fg-dashboard',
    'fg-products',
    'fg-receipts',
    'fg-dispatches',
    'fg-reports',
    'fg-inventory-email',
    'qc-inspection',
    'rm-dashboard',
    'rm-items',
    'rm-receipts',
    'rm-consumptions',
    'rm-reports',
    'unit-of-measures',
    'job-cards',
    'production-dashboard',
    'profile'
];

const VIEW_TO_ROUTE_SEGMENT = Object.freeze({
    dashboard: 'dashboard',
    'transport-dashboard': 'transport-dashboard',
    'management-dashboard': 'management-dashboard',
    suppliers: 'suppliers',
    qualities: 'qualities',
    'paper-colors': 'qualities',
    receipts: 'receipts',
    issues: 'issues',
    'return-supplier': 'return-supplier',
    'monthly-consumption': 'monthly-consumption',
    'reel-stock': 'reel-stock',
    'reel-receipt': 'reel-receipt',
    'monthly-closing': 'monthly-closing',
    'reel-stock-count': 'reel-stock-count',
    'usage-intelligence': 'usage-intelligence',
    reports: 'reports',
    users: 'users',
    'user-rights': 'user-rights',
    'audit-log': 'audit-log',
    'cartage-report': 'cartage-report',
    'best-ui': 'best-ui',
    setup: 'setup',
    'old-reels': 'old-reels',
    'stock-alerts': 'stock-alerts',
    'reconciliation': 'reconciliation',
    'customers': 'customers',
    'transporters': 'transporters',
    'vehicles': 'vehicles',
    'vehicle-types': 'vehicle-types',
    'cartage-rates': 'cartage-rates',
    'cartage-increment': 'cartage-increment',
    'cartage-increment-history': 'cartage-increment-history',
    'cartage': 'cartage',
    'cartage-list': 'cartage-list',
    'fg-dashboard': 'fg-dashboard',
    'fg-products': 'fg-products',
    'fg-receipts': 'fg-receipts',
    'fg-dispatches': 'fg-dispatches',
    'fg-reports': 'fg-reports',
    'fg-inventory-email': 'fg-inventory-email',
    'qc-inspection': 'qc-inspection',
    'rm-dashboard': 'rm-dashboard',
    'rm-items': 'rm-items',
    'rm-receipts': 'rm-receipts',
    'rm-consumptions': 'rm-consumptions',
    'rm-reports': 'rm-reports',
    'unit-of-measures': 'unit-of-measures',
    'job-cards': 'job-cards',
    'production-dashboard': 'production-dashboard',
    profile: 'profile'
});

const VALID_VIEWS = new Set(Object.keys(VIEW_TO_ROUTE_SEGMENT));
const DASHBOARD_VIEW = 'dashboard';

const normalizePathname = path => {
    if (!path) {
        return '/';
    }
    if (path === '/') {
        return '/';
    }
    const trimmed = path.replace(/\/+$/, '');
    if (trimmed === '') {
        return '/';
    }
    return trimmed.startsWith('/') ? trimmed : `/${trimmed}`;
};

const resolveViewFromPath = pathname => {
    if (!pathname) {
        return DASHBOARD_VIEW;
    }
    const trimmed = pathname.replace(/\/+$/, '');
    if (trimmed === '' || trimmed === '/') {
        return DASHBOARD_VIEW;
    }
    const segments = trimmed.split('/').filter(Boolean);
    for (let i = segments.length - 1; i >= 0; i--) {
        const segment = segments[i];
        if (segment === 'login') {
            return DASHBOARD_VIEW;
        }
        if (VALID_VIEWS.has(segment)) {
            return segment;
        }
    }
    return DASHBOARD_VIEW;
};

const computeBasePath = (pathname, view) => {
    if (!pathname) {
        return '';
    }
    const trimmed = pathname.replace(/\/+$/, '');
    if (trimmed === '' || trimmed === '/') {
        return '';
    }
    if (trimmed === '/login') {
        return '';
    }
    if (trimmed.endsWith('/login')) {
        const withoutLogin = trimmed.slice(0, -'/login'.length);
        if (withoutLogin === '') {
            return '';
        }
        return withoutLogin.startsWith('/') ? withoutLogin : `/${withoutLogin}`;
    }
    const segment = VIEW_TO_ROUTE_SEGMENT[view];
    if (segment) {
        const suffix = `/${segment}`;
        if (trimmed.endsWith(suffix)) {
            const base = trimmed.slice(0, trimmed.length - suffix.length);
            if (base === '') {
                return '';
            }
            return base.startsWith('/') ? base : `/${base}`;
        }
    }
    return trimmed.startsWith('/') ? trimmed : `/${trimmed}`;
};

const buildPathForView = (basePath, view) => {
    const segment = VIEW_TO_ROUTE_SEGMENT[view] || VIEW_TO_ROUTE_SEGMENT[DASHBOARD_VIEW];
    const cleanedBase = basePath ? basePath.replace(/\/+$/, '') : '';
    const combined = `${cleanedBase}/${segment}`.replace(/\/{2,}/g, '/');
    if (combined === '') {
        return '/';
    }
    return combined.startsWith('/') ? combined : `/${combined}`;
};

const createEmptyPermissions = () => {
    const perms = {};
    PERMISSION_KEYS.forEach(key => {
        perms[key] = { can_view: false, can_edit: false, can_see_amounts: false };
    });
    return perms;
};

const createFullPermissions = () => {
    const perms = {};
    PERMISSION_KEYS.forEach(key => {
        perms[key] = { can_view: true, can_edit: true, can_see_amounts: true };
    });
    return perms;
};

const app = createApp({

    data() {

        return {
            currentView: 'dashboard',
            user: null,
            token: localStorage.getItem('token'),
            permissions: createEmptyPermissions(),
            permissionsLoaded: false,
            initialRouteView: null,
            basePath: '',
            idleTimer: null,
            idleTimeout: 3600000, // 1 hour in milliseconds
            isSidebarCollapsed: false,
            currentTime: new Date().toLocaleString(),
            triggeredCount: 0,
            pendingCartageCount: 0
        };

    },

    mounted() {
        this.initialRouteView = resolveViewFromPath(window.location.pathname);
        this.basePath = computeBasePath(window.location.pathname, this.initialRouteView);
        if (this.initialRouteView && this.initialRouteView !== this.currentView) {
            this.currentView = this.initialRouteView;
        }
        window.addEventListener('popstate', this.handlePopState);
        try {
            this.user = JSON.parse(localStorage.getItem('user')) || null;
        } catch (e) {
            this.user = null;
        }
        if (this.token) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${this.token}`;
            this.checkAuth();
        } else if (this.user) {
            this.logout(); // Token cleared, logout
        }

        this.startIdleTimer();
        window.addEventListener('mousemove', this.resetIdleTimer);
        window.addEventListener('keydown', this.resetIdleTimer);
        window.addEventListener('mousedown', this.resetIdleTimer);
        window.addEventListener('scroll', this.resetIdleTimer);

        if (this.user) {
            this.fetchTriggeredCount();
            this.fetchPendingCartageCount();
        }

        // Periodic check for stock alerts (every 2 minutes)
        setInterval(() => {
            if (this.user) {
                this.fetchTriggeredCount();
                this.fetchPendingCartageCount();
            }
        }, 120000);

        // Live time updater
        setInterval(() => {
            this.currentTime = new Date().toLocaleString('en-US', {
                weekday: 'short',
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
        }, 1000);
    },

    beforeUnmount() {
        window.removeEventListener('popstate', this.handlePopState);
        window.removeEventListener('mousemove', this.resetIdleTimer);
        window.removeEventListener('keydown', this.resetIdleTimer);
        window.removeEventListener('mousedown', this.resetIdleTimer);
        window.removeEventListener('scroll', this.resetIdleTimer);
        if (this.idleTimer) clearTimeout(this.idleTimer);
    },

    methods: {
        normalizeView(view) {
            if (typeof view !== 'string') {
                return DASHBOARD_VIEW;
            }
            return VALID_VIEWS.has(view) ? view : DASHBOARD_VIEW;
        },
        getViewFromRoute() {
            return resolveViewFromPath(window.location.pathname);
        },
        updateRoute(view, replace = false) {
            const targetPath = buildPathForView(this.basePath, view);
            const normalizedTarget = normalizePathname(targetPath);
            const currentPath = normalizePathname(window.location.pathname);
            if (currentPath === normalizedTarget) {
                if (replace) {
                    window.history.replaceState({ view }, '', normalizedTarget);
                    this.basePath = computeBasePath(normalizedTarget, view);
                }
                return;
            }
            const method = replace ? 'replaceState' : 'pushState';
            window.history[method]({ view }, '', normalizedTarget);
            this.basePath = computeBasePath(normalizedTarget, view);
        },
        applyInitialRouteView(options = {}) {
            if (!this.initialRouteView) {
                return false;
            }
            const view = this.normalizeView(this.initialRouteView);
            this.initialRouteView = null;
            const replace = Object.prototype.hasOwnProperty.call(options, 'replace') ? options.replace : true;
            this.setView(view, { replace, skipRoute: true });
            return true;
        },
        handlePopState() {
            const path = window.location.pathname;
            const view = resolveViewFromPath(path);
            this.basePath = computeBasePath(path, view);
            this.initialRouteView = null;
            this.setView(view, { replace: true, skipRoute: true });
        },
        checkAuth() {
            axios.get('/api/user').then(response => {
                this.user = response.data;
                this.fetchPermissions();
            }).catch(() => {
                this.logout();
            });
        },
        fetchPermissions() {
            if (!this.user || this.user.email === 'superadmin@qc.com') {
                this.permissions = createFullPermissions();
                this.user.permissions = this.permissions;
                this.permissionsLoaded = true;
                if (!this.applyInitialRouteView({ replace: true })) {
                    this.setView(this.getFirstPermittedView(), { replace: true });
                }
                return;
            }
            axios.get(`/api/user-permissions/${this.user.id}`).then(response => {
                // If NO permissions are found AND user is Admin, give full access as a fallback
                // This ensures existing Admins don't lose access until they are configured.
                if (response.data.length === 0 && this.user.role?.name === 'Admin') {
                    this.permissions = createFullPermissions();
                } else {
                    this.permissions = createEmptyPermissions();
                    response.data.forEach(perm => {
                        if (this.permissions[perm.menu]) {
                            this.permissions[perm.menu] = {
                                can_view: !!perm.can_view,
                                can_add: !!perm.can_add,
                                can_edit: !!perm.can_edit,
                                can_delete: !!perm.can_delete,
                                can_see_amounts: !!perm.can_see_amounts
                            };
                        }
                    });
                }
                this.user.permissions = this.permissions;
                this.permissionsLoaded = true;
                if (!this.applyInitialRouteView({ replace: true })) {
                    this.setView(this.getFirstPermittedView(), { replace: true });
                }
            }).catch(error => {
                console.error('Error loading permissions:', error);
                this.permissions = createEmptyPermissions();
                this.permissionsLoaded = true;
                if (!this.applyInitialRouteView({ replace: true })) {
                    this.setView(this.getFirstPermittedView(), { replace: true });
                }
            });
        },
        startIdleTimer() {
            if (this.idleTimer) clearTimeout(this.idleTimer);
            if (this.user) {
                this.idleTimer = setTimeout(() => {
                    alert('You have been logged out due to 1 hour of inactivity.');
                    this.logout();
                }, this.idleTimeout);
            }
        },
        resetIdleTimer() {
            this.startIdleTimer();
        },
        logout() {
            this.user = null;
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            delete axios.defaults.headers.common['Authorization'];
            this.permissions = createEmptyPermissions();
            this.permissionsLoaded = false;
            this.currentView = DASHBOARD_VIEW;
            this.initialRouteView = DASHBOARD_VIEW;
            const loginBase = this.basePath ? this.basePath.replace(/\/+$/, '') : '';
            const loginPath = `${loginBase}/login`.replace(/\/{2,}/g, '/');
            const normalizedLoginPath = loginPath.startsWith('/') ? loginPath : `/${loginPath}`;
            window.history.replaceState({}, '', normalizedLoginPath);
        },
        canView(view) {
            if (this.user?.email === 'superadmin@qc.com') {
                return true;
            }
            const permKey = PERMISSION_KEY_MAP[view];
            if (!permKey) {
                return true;
            }
            if (!this.permissionsLoaded) {
                return false;
            }
            const permission = this.permissions[permKey];
            return permission ? permission.can_view : false;
        },
        canSeeAmounts(view) {
            const permKey = PERMISSION_KEY_MAP[view];
            if (!permKey) {
                return true;
            }
            if (!this.permissionsLoaded) {
                return false;
            }
            // Only superadmin bypasses the granular permissions here
            // Admin role will follow the permission object which was populated in fetchPermissions
            if (this.user?.email === 'superadmin@qc.com') {
                return true;
            }
            const permission = this.permissions[permKey];
            return permission ? permission.can_see_amounts : false;
        },
        setView(view, options = {}) {
            const { replace = false, skipRoute = false } = options;
            const targetView = this.normalizeView(view);

            if (!this.user) {
                this.currentView = targetView;
                if (!skipRoute) {
                    this.updateRoute(targetView, replace);
                }
                return;
            }
            if (!this.permissionsLoaded && PERMISSION_KEY_MAP[targetView]) {
                return;
            }

            let nextView = targetView;
            if (!this.canView(targetView)) {
                nextView = this.getFirstPermittedView();
            }

            this.currentView = nextView;
            if (!skipRoute) {
                this.updateRoute(nextView, replace);
            }
        },
        getFirstPermittedView() {
            for (const view of VIEW_ORDER) {
                if (view === 'users' || view === 'user-rights') {
                    continue;
                }
                if (this.canView(view)) {
                    return view;
                }
            }
            return 'dashboard';
        },
        fetchTriggeredCount() {
            axios.get('/api/stock-alerts/triggered').then(response => {
                this.triggeredCount = response.data.length;
            }).catch(error => {
                console.error('Error fetching triggered alerts:', error);
            });
        },
        fetchPendingCartageCount() {
            if (!this.user) return;
            axios.get('/api/cartage-bills/pending-count').then(response => {
                this.pendingCartageCount = response.data.count;
            }).catch(error => {
                console.error('Error fetching pending cartage count:', error);
            });
        }
    }

});

import SupplierComponent from './components/SupplierComponent.vue';
import PaperQualityComponent from './components/PaperQualityComponent.vue';
import PaperColorComponent from './components/PaperColorComponent.vue';
import DashboardComponent from './components/DashboardComponent.vue';
import ReelReceiptComponent from './components/ReelReceiptComponent.vue';
import ReelIssueComponent from './components/ReelIssueComponent.vue';
import ReelReturnSupplierComponent from './components/ReelReturnSupplierComponent.vue';
import LoginComponent from './components/LoginComponent.vue';
import MonthlyConsumptionReportComponent from './components/MonthlyConsumptionReportComponent.vue';
import ReelStockReportComponent from './components/ReelStockReportComponent.vue';
import ReelReceiptReportComponent from './components/ReelReceiptReportComponent.vue';
import MonthlyClosingReportComponent from './components/MonthlyClosingReportComponent.vue';
import UserComponent from './components/UserComponent.vue';
import UserRightsComponent from './components/UserRightsComponent.vue';
import AuditLogComponent from './components/AuditLogComponent.vue';
import ReportsComponent from './components/ReportsComponent.vue';
import ScrollToTopComponent from './components/ScrollToTopComponent.vue';
import SetupComponent from './components/SetupComponent.vue';
import BestUiShowcaseComponent from './components/BestUiShowcaseComponent.vue';

import ProfileComponent from './components/ProfileComponent.vue';
import ReelStockCountReportComponent from './components/ReelStockCountReportComponent.vue';
import UsageIntelligenceReportComponent from './components/UsageIntelligenceReportComponent.vue';

import OldReelsReportComponent from './components/OldReelsReportComponent.vue';
import StockAlertComponent from './components/StockAlertComponent.vue';
import ReconciliationComponent from './components/ReconciliationComponent.vue';
import CustomerComponent from './components/CustomerComponent.vue';
import TransporterComponent from './components/TransporterComponent.vue';
import VehicleComponent from './components/VehicleComponent.vue';
import VehicleTypeComponent from './components/VehicleTypeComponent.vue';
import CartageRateComponent from './components/CartageRateComponent.vue';
import CartageRateIncrementComponent from './components/CartageRateIncrementComponent.vue';
import CartageIncrementHistoryComponent from './components/CartageIncrementHistoryComponent.vue';
import CartageBillingComponent from './components/CartageBillingComponent.vue';
import CartageReportComponent from './components/CartageReportComponent.vue';

import TransportDashboardComponent from './components/TransportDashboardComponent.vue';

import ProductComponent from './components/ProductComponent.vue';
import FGReceiptComponent from './components/FGReceiptComponent.vue';
import FGDispatchComponent from './components/FGDispatchComponent.vue';
import FGReportComponent from './components/FGReportComponent.vue';
import FGInventoryEmailComponent from './components/FGInventoryEmailComponent.vue';
import FGDashboardComponent from './components/FGDashboardComponent.vue';
import QcInspectionComponent from './components/QcInspectionComponent.vue';

import RMItemComponent from './components/RMItemComponent.vue';
import RMReceiptComponent from './components/RMReceiptComponent.vue';
import RMConsumptionComponent from './components/RMConsumptionComponent.vue';
import RMDashboardComponent from './components/RMDashboardComponent.vue';
import RMReportComponent from './components/RMReportComponent.vue';
import UnitOfMeasureComponent from './components/UnitOfMeasureComponent.vue';
import JobCardComponent from './components/JobCardComponent.vue';
import ProductionDashboardComponent from './components/ProductionDashboardComponent.vue';
import ThemeSelectorComponent from './components/ThemeSelectorComponent.vue';

app.component('supplier-component', SupplierComponent);
app.component('paper-quality-component', PaperQualityComponent);
app.component('paper-color-component', PaperColorComponent);
app.component('dashboard-component', DashboardComponent);
app.component('transport-dashboard-component', TransportDashboardComponent);
app.component('reel-receipt-component', ReelReceiptComponent);
app.component('reel-issue-component', ReelIssueComponent);
app.component('reel-return-supplier-component', ReelReturnSupplierComponent);
app.component('monthly-consumption-report-component', MonthlyConsumptionReportComponent);
app.component('reel-stock-report-component', ReelStockReportComponent);
app.component('reel-receipt-report-component', ReelReceiptReportComponent);
app.component('monthly-closing-report-component', MonthlyClosingReportComponent);
app.component('user-component', UserComponent);
app.component('user-rights-component', UserRightsComponent);
app.component('audit-log-component', AuditLogComponent);
app.component('login-component', LoginComponent);
app.component('reports-component', ReportsComponent);
app.component('scroll-to-top-component', ScrollToTopComponent);
app.component('setup-component', SetupComponent);
app.component('reel-stock-count-report-component', ReelStockCountReportComponent);
app.component('usage-intelligence-report-component', UsageIntelligenceReportComponent);
app.component('best-ui-showcase-component', BestUiShowcaseComponent);
app.component('profile-component', ProfileComponent);
app.component('old-reels-report-component', OldReelsReportComponent);
app.component('stock-alert-component', StockAlertComponent);
app.component('reconciliation-component', ReconciliationComponent);
app.component('customer-component', CustomerComponent);
app.component('transporter-component', TransporterComponent);
app.component('vehicle-component', VehicleComponent);
app.component('vehicle-type-component', VehicleTypeComponent);
app.component('cartage-rate-component', CartageRateComponent);
app.component('cartage-rate-increment-component', CartageRateIncrementComponent);
app.component('cartage-increment-history-component', CartageIncrementHistoryComponent);
app.component('cartage-billing-component', CartageBillingComponent);
app.component('cartage-report-component', CartageReportComponent);
app.component('product-component', ProductComponent);
app.component('fg-receipt-component', FGReceiptComponent);
app.component('fg-dispatch-component', FGDispatchComponent);
app.component('fg-report-component', FGReportComponent);
app.component('fg-inventory-email-component', FGInventoryEmailComponent);
app.component('fg-dashboard-component', FGDashboardComponent);
app.component('qc-inspection-component', QcInspectionComponent);

app.component('rm-item-component', RMItemComponent);
app.component('rm-receipt-component', RMReceiptComponent);
app.component('rm-consumption-component', RMConsumptionComponent);
app.component('rm-dashboard-component', RMDashboardComponent);
app.component('rm-report-component', RMReportComponent);
app.component('unit-of-measure-component', UnitOfMeasureComponent);
app.component('job-card-component', JobCardComponent);
app.component('production-dashboard-component', ProductionDashboardComponent);
app.component('theme-selector-component', ThemeSelectorComponent);

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// Object.entries(import.meta.glob('./**/*.vue', { eager: true })).forEach(([path, definition]) => {
//     app.component(path.split('/').pop().replace(/\.\w+$/, ''), definition.default);
// });
import ElementPlus from 'element-plus';
import 'element-plus/dist/index.css';
import { createPinia } from 'pinia';

const pinia = createPinia();
app.use(pinia);
app.use(ElementPlus);

app.mount('#app');
