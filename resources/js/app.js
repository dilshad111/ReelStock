import { createApp } from 'vue';
import axios from 'axios';

axios.defaults.baseURL = 'http://192.168.10.47:8000';

const PERMISSION_KEY_MAP = {
    dashboard: 'dashboard',
    suppliers: 'supplier',
    qualities: null,
    receipts: 'reel_receipt',
    issues: 'reel_issue',
    'return-supplier': 'reel_issue',
    'monthly-consumption': 'monthly_consumption',
    'reel-stock': 'reel_stock',
    'reel-receipt': 'reel_receipt_report',
    'monthly-closing': 'monthly_closing',
    cartons: 'cartons',
    customers: 'cartons',
    'sketch-generator': 'cartons',
    reports: null,
    users: null,
    'user-rights': null,
    'audit-log': null,
    'best-ui': null
};

const PERMISSION_KEYS = [
    'dashboard',
    'supplier',
    'reel_receipt',
    'reel_issue',
    'monthly_consumption',
    'reel_stock',
    'reel_receipt_report',
    'monthly_closing',
    'cartons'
];

const VIEW_ORDER = [
    'dashboard',
    'suppliers',
    'qualities',
    'receipts',
    'issues',
    'return-supplier',
    'monthly-consumption',
    'reel-stock',
    'reel-receipt',
    'monthly-closing',
    'cartons',
    'customers',
    'sketch-generator',
    'reports',
    'users',
    'user-rights',
    'audit-log',
    'best-ui'
];

const VIEW_TO_ROUTE_SEGMENT = Object.freeze({
    dashboard: 'dashboard',
    suppliers: 'suppliers',
    qualities: 'qualities',
    receipts: 'receipts',
    issues: 'issues',
    'return-supplier': 'return-supplier',
    'monthly-consumption': 'monthly-consumption',
    'reel-stock': 'reel-stock',
    'reel-receipt': 'reel-receipt',
    'monthly-closing': 'monthly-closing',
    cartons: 'cartons',
    customers: 'customers',
    'sketch-generator': 'sketch-generator',
    reports: 'reports',
    users: 'users',
    'user-rights': 'user-rights',
    'audit-log': 'audit-log',
    'best-ui': 'best-ui',
    setup: 'setup'
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
            idleTimeout: 3600000 // 1 hour in milliseconds
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
            if (!this.user || this.user.role?.name === 'Admin' || this.user.email === 'superadmin@qc.com') {
                this.permissions = createFullPermissions();
                this.permissionsLoaded = true;
                if (!this.applyInitialRouteView({ replace: true })) {
                    this.setView(this.getFirstPermittedView(), { replace: true });
                }
                return;
            }
            axios.get(`/api/user-permissions/${this.user.id}`).then(response => {
                this.permissions = createEmptyPermissions();
                response.data.forEach(perm => {
                    if (this.permissions[perm.menu]) {
                        this.permissions[perm.menu] = {
                            can_view: !!perm.can_view,
                            can_edit: !!perm.can_edit,
                            can_see_amounts: !!perm.can_see_amounts
                        };
                    }
                });
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
            if (this.user && (this.user.role?.name === 'Admin' || this.user.email === 'superadmin@qc.com')) {
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
        }
    }

});

import SupplierComponent from './components/SupplierComponent.vue';
import PaperQualityComponent from './components/PaperQualityComponent.vue';
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
import CartonsComponent from './components/CartonsComponent.vue';
import CustomerComponent from './components/CustomerComponent.vue';
import SketchGeneratorComponent from './components/SketchGeneratorComponent.vue';
import ReportsComponent from './components/ReportsComponent.vue';
import ThemeSelectorComponent from './components/ThemeSelectorComponent.vue';
import ScrollToTopComponent from './components/ScrollToTopComponent.vue';
import SetupComponent from './components/SetupComponent.vue';
import BestUiShowcaseComponent from './components/BestUiShowcaseComponent.vue';

app.component('supplier-component', SupplierComponent);
app.component('paper-quality-component', PaperQualityComponent);
app.component('dashboard-component', DashboardComponent);
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
app.component('cartons-component', CartonsComponent);
app.component('customer-component', CustomerComponent);
app.component('sketch-generator-component', SketchGeneratorComponent);
app.component('login-component', LoginComponent);
app.component('reports-component', ReportsComponent);
app.component('theme-selector-component', ThemeSelectorComponent);
app.component('scroll-to-top-component', ScrollToTopComponent);
app.component('setup-component', SetupComponent);
app.component('best-ui-showcase-component', BestUiShowcaseComponent);

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

app.mount('#app');
