import { defineStore } from 'pinia';
import inventoryApi from '../api/inventoryApi';

export const useReelReceiptStore = defineStore('reelReceipt', {
    state: () => ({
        receipts: [],
        isLoading: false,
        error: null,
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 50,
            total: 0
        },
        filters: {
            reel_no: '',
            quality_id: '',
            supplier_id: '',
            date_from: '',
            date_to: ''
        }
    }),
    actions: {
        async fetchReceipts(page = 1) {
            this.isLoading = true;
            this.error = null;
            try {
                const params = {
                    page,
                    ...this.filters
                };
                const response = await inventoryApi.getReelReceipts(params);
                // Based on Laravel pagination response
                this.receipts = response.data.data;
                this.pagination = {
                    current_page: response.data.current_page,
                    last_page: response.data.last_page,
                    per_page: response.data.per_page,
                    total: response.data.total
                };
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to fetch receipts';
            } finally {
                this.isLoading = false;
            }
        },
        async saveReceipt(data) {
            this.isLoading = true;
            this.error = null;
            try {
                await inventoryApi.storeReelReceipt(data);
                await this.fetchReceipts(this.pagination.current_page);
                return true;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to save receipt';
                return false;
            } finally {
                this.isLoading = false;
            }
        },
        setFilters(newFilters) {
            this.filters = { ...this.filters, ...newFilters };
            this.fetchReceipts(1); // Reset to page 1 on filter change
        },
        clearFilters() {
            this.filters = {
                reel_no: '',
                quality_id: '',
                supplier_id: '',
                date_from: '',
                date_to: ''
            };
            this.fetchReceipts(1);
        },
        setupRealtimeListener() {
            if (window.Echo) {
                window.Echo.channel('inventory')
                    .listen('.inventory.updated', (event) => {
                        console.log('Real-time inventory update received:', event);
                        if (event.type === 'receipt_created' || event.type === 'receipt_updated' || event.type === 'receipt_deleted') {
                            this.fetchReceipts(this.pagination.current_page);
                        }
                    });
            }
        }
    }
});
