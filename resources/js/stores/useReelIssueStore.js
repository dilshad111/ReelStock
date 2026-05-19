import { defineStore } from 'pinia';
import inventoryApi from '../api/inventoryApi';

export const useReelIssueStore = defineStore('reelIssue', {
    state: () => ({
        issues: [],
        isLoading: false,
        error: null,
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 50,
            total: 0
        },
        filters: {
            search: '',
            date_from: '',
            date_to: ''
        }
    }),
    actions: {
        async fetchIssues(page = 1) {
            this.isLoading = true;
            this.error = null;
            try {
                const params = {
                    page,
                    ...this.filters
                };
                const response = await inventoryApi.getReelIssues(params);
                this.issues = response.data.data;
                this.pagination = {
                    current_page: response.data.current_page,
                    last_page: response.data.last_page,
                    per_page: response.data.per_page,
                    total: response.data.total
                };
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to fetch issues';
            } finally {
                this.isLoading = false;
            }
        },
        async saveIssue(data) {
            this.isLoading = true;
            this.error = null;
            try {
                await inventoryApi.storeReelIssue(data);
                await this.fetchIssues(this.pagination.current_page);
                return true;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to save issue';
                throw err;
            } finally {
                this.isLoading = false;
            }
        },
        async updateIssue(id, data) {
            this.isLoading = true;
            this.error = null;
            try {
                await inventoryApi.updateReelIssue(id, data);
                await this.fetchIssues(this.pagination.current_page);
                return true;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to update issue';
                throw err;
            } finally {
                this.isLoading = false;
            }
        },
        async deleteIssue(id) {
            this.isLoading = true;
            this.error = null;
            try {
                await inventoryApi.deleteReelIssue(id);
                await this.fetchIssues(this.pagination.current_page);
                return true;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to delete issue';
                throw err;
            } finally {
                this.isLoading = false;
            }
        },
        setFilters(newFilters) {
            this.filters = { ...this.filters, ...newFilters };
            this.fetchIssues(1); // Reset to page 1 on filter change
        },
        clearFilters() {
            this.filters = {
                search: '',
                date_from: '',
                date_to: ''
            };
            this.fetchIssues(1);
        },
        setupRealtimeListener() {
            if (window.Echo) {
                window.Echo.channel('inventory')
                    .listen('.inventory.updated', (event) => {
                        if (event.type === 'issue_created' || event.type === 'issue_updated' || event.type === 'issue_deleted') {
                            this.fetchIssues(this.pagination.current_page);
                        }
                    });
            }
        }
    }
});
