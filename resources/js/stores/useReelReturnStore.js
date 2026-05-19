import { defineStore } from 'pinia';
import inventoryApi from '../api/inventoryApi';

export const useReelReturnStore = defineStore('reelReturn', {
    state: () => ({
        returns: [],
        isLoading: false,
        error: null,
        filters: {
            returned_to: ''
        }
    }),
    actions: {
        async fetchReturns() {
            this.isLoading = true;
            this.error = null;
            try {
                const params = {
                    ...this.filters
                };
                const response = await inventoryApi.getReelReturns(params);
                // Reel returns are not paginated in the backend currently
                this.returns = response.data;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to fetch returns';
            } finally {
                this.isLoading = false;
            }
        },
        async saveReturn(data) {
            this.isLoading = true;
            this.error = null;
            try {
                await inventoryApi.storeReelReturn(data);
                await this.fetchReturns();
                return true;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to save return';
                throw err;
            } finally {
                this.isLoading = false;
            }
        },
        async updateReturn(id, data) {
            this.isLoading = true;
            this.error = null;
            try {
                await inventoryApi.updateReelReturn(id, data);
                await this.fetchReturns();
                return true;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to update return';
                throw err;
            } finally {
                this.isLoading = false;
            }
        },
        async deleteReturn(id) {
            this.isLoading = true;
            this.error = null;
            try {
                await inventoryApi.deleteReelReturn(id);
                await this.fetchReturns();
                return true;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to delete return';
                throw err;
            } finally {
                this.isLoading = false;
            }
        },
        setFilters(newFilters) {
            this.filters = { ...this.filters, ...newFilters };
            this.fetchReturns();
        },
        clearFilters() {
            this.filters = {
                returned_to: ''
            };
            this.fetchReturns();
        },
        setupRealtimeListener() {
            if (window.Echo) {
                window.Echo.channel('inventory')
                    .listen('.inventory.updated', (event) => {
                        if (event.type === 'return_created' || event.type === 'return_updated' || event.type === 'return_deleted' || event.type === 'issue_created') {
                            // issue_created might trigger an auto-return
                            this.fetchReturns();
                        }
                    });
            }
        }
    }
});
