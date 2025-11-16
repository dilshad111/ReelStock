import { createApp } from 'vue';
import axios from 'axios';

axios.defaults.baseURL = 'http://127.0.0.1:8000';

const app = createApp({

    data() {

        return {

            currentView: 'dashboard',
            user: null,
            token: localStorage.getItem('token'),

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
            }).catch(() => {
                this.logout();
            });
        },
        logout() {
            this.user = null;
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            delete axios.defaults.headers.common['Authorization'];
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
import ReportsComponent from './components/ReportsComponent.vue';
import ThemeSelectorComponent from './components/ThemeSelectorComponent.vue';

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
app.component('login-component', LoginComponent);
app.component('reports-component', ReportsComponent);
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

app.mount('#app');
