import { defineStore } from 'pinia';
import axios from 'axios';

export const useDashboardStore = defineStore('dashboard', {
    state: () => ({
        operationalData: null,
        managementData: null,
        transportData: null,
        loading: {
            operational: false,
            management: false,
            transport: false
        },
        error: null,
        timeRange: 30,
        lastUpdated: null
    }),
    
    actions: {
        setTimeRange(range) {
            this.timeRange = range;
        },

        async fetchOperationalDashboard() {
            this.loading.operational = true;
            this.error = null;
            try {
                const response = await axios.get(`/api/dashboard?range=${this.timeRange}`);
                this.operationalData = response.data;
                this.lastUpdated = response.data.last_updated;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to fetch operational dashboard';
                console.error(err);
            } finally {
                this.loading.operational = false;
            }
        },

        async fetchManagementDashboard() {
            this.loading.management = true;
            this.error = null;
            try {
                const response = await axios.get(`/api/management-dashboard?range=${this.timeRange}`);
                this.managementData = response.data;
                this.lastUpdated = response.data.last_updated;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to fetch management dashboard';
                console.error(err);
            } finally {
                this.loading.management = false;
            }
        },

        async fetchTransportDashboard() {
            this.loading.transport = true;
            this.error = null;
            try {
                const response = await axios.get(`/api/transport-dashboard?range=${this.timeRange}`);
                this.transportData = response.data;
                this.lastUpdated = response.data.last_updated;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to fetch transport dashboard';
                console.error(err);
            } finally {
                this.loading.transport = false;
            }
        },

        setupRealtimeListener() {
            if (window.Echo) {
                window.Echo.channel('inventory')
                    .listen('.inventory.updated', (event) => {
                        // Refresh active dashboard data
                        this.fetchOperationalDashboard();
                        // If management data is already loaded, refresh it too
                        if (this.managementData) {
                            this.fetchManagementDashboard();
                        }
                    });
            }
        }
    }
});
