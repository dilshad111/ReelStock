import apiClient from './axios';

export default {
    getReelReceipts(params) {
        return apiClient.get('/reel-receipts', { params });
    },
    storeReelReceipt(data) {
        return apiClient.post('/reel-receipts', data);
    },
    bulkStoreReelReceipt(data) {
        return apiClient.post('/reel-receipts/bulk', data);
    },
    updateReelReceipt(id, data) {
        return apiClient.put(`/reel-receipts/${id}`, data);
    },
    deleteReelReceipt(id) {
        return apiClient.delete(`/reel-receipts/${id}`);
    },

    // Reel Issues
    getReelIssues(params) {
        return apiClient.get('/reel-issues', { params });
    },
    storeReelIssue(data) {
        return apiClient.post('/reel-issues', data);
    },
    updateReelIssue(id, data) {
        return apiClient.put(`/reel-issues/${id}`, data);
    },
    deleteReelIssue(id) {
        return apiClient.delete(`/reel-issues/${id}`);
    },

    // Reel Returns
    getReelReturns(params) {
        return apiClient.get('/reel-returns', { params });
    },
    storeReelReturn(data) {
        return apiClient.post('/reel-returns', data);
    },
    updateReelReturn(id, data) {
        return apiClient.put(`/reel-returns/${id}`, data);
    },
    deleteReelReturn(id) {
        return apiClient.delete(`/reel-returns/${id}`);
    }
};
