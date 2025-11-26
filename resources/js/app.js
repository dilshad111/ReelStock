import { createApp } from 'vue';
import axios from 'axios';

axios.defaults.baseURL = 'http://127.0.0.1:8000';

const PERMISSION_KEY_MAP = {
    dashboard: 'dashboard',
    suppliers: 'supplier',
    qualities: null,
    receipts: 'reel_receipt',
    issues: 'reel_issue',
    returns: 'reel_issue',
    'monthly-consumption': 'monthly_consumption',
    'reel-stock': 'reel_stock',
    'reel-receipt': 'reel_receipt_report',
    'monthly-closing': 'monthly_closing',
    cartons: 'cartons',
    customers: 'cartons',
    'sketch-generator': 'cartons',
    reports: null,
    users: null,
    'user-rights': null
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
    'returns',
    'monthly-consumption',
    'reel-stock',
    'reel-receipt',
    'monthly-closing',
    'cartons',
    'customers',
    'sketch-generator',
    'reports',
    'users',
    'user-rights'
];

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
            permissionsLoaded: false
        };

    },

    mounted() {
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
    },

    methods: {
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
                this.currentView = this.getFirstPermittedView();
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
                this.currentView = this.getFirstPermittedView();
            }).catch(error => {
                console.error('Error loading permissions:', error);
                this.permissions = createEmptyPermissions();
                this.permissionsLoaded = true;
                this.currentView = this.getFirstPermittedView();
            });
        },
        logout() {
            this.user = null;
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            delete axios.defaults.headers.common['Authorization'];
            this.permissions = createEmptyPermissions();
            this.permissionsLoaded = false;
            this.currentView = 'dashboard';
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
        setView(view) {
            if (!this.user) {
                this.currentView = view;
                return;
            }
            if (!this.permissionsLoaded && PERMISSION_KEY_MAP[view]) {
                return;
            }
            if (this.canView(view)) {
                this.currentView = view;
            } else {
                const fallback = this.getFirstPermittedView();
                this.currentView = fallback;
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
import ReelReturnComponent from './components/ReelReturnComponent.vue';
import LoginComponent from './components/LoginComponent.vue';
import MonthlyConsumptionReportComponent from './components/MonthlyConsumptionReportComponent.vue';
import ReelStockReportComponent from './components/ReelStockReportComponent.vue';
import ReelReceiptReportComponent from './components/ReelReceiptReportComponent.vue';
import MonthlyClosingReportComponent from './components/MonthlyClosingReportComponent.vue';
import UserComponent from './components/UserComponent.vue';
import UserRightsComponent from './components/UserRightsComponent.vue';
import CartonsComponent from './components/CartonsComponent.vue';
import CustomerComponent from './components/CustomerComponent.vue';
import SketchGeneratorComponent from './components/SketchGeneratorComponent.vue';
import ReportsComponent from './components/ReportsComponent.vue';
import ThemeSelectorComponent from './components/ThemeSelectorComponent.vue';
import ScrollToTopComponent from './components/ScrollToTopComponent.vue';
import SetupComponent from './components/SetupComponent.vue';

app.component('supplier-component', SupplierComponent);
app.component('paper-quality-component', PaperQualityComponent);
app.component('dashboard-component', DashboardComponent);
app.component('reel-receipt-component', ReelReceiptComponent);
app.component('reel-issue-component', ReelIssueComponent);
app.component('reel-return-component', ReelReturnComponent);
app.component('monthly-consumption-report-component', MonthlyConsumptionReportComponent);
app.component('reel-stock-report-component', ReelStockReportComponent);
app.component('reel-receipt-report-component', ReelReceiptReportComponent);
app.component('monthly-closing-report-component', MonthlyClosingReportComponent);
app.component('user-component', UserComponent);
app.component('user-rights-component', UserRightsComponent);
app.component('cartons-component', CartonsComponent);
app.component('customer-component', CustomerComponent);
app.component('sketch-generator-component', SketchGeneratorComponent);
app.component('login-component', LoginComponent);
app.component('reports-component', ReportsComponent);
app.component('theme-selector-component', ThemeSelectorComponent);
app.component('scroll-to-top-component', ScrollToTopComponent);
app.component('setup-component', SetupComponent);

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
