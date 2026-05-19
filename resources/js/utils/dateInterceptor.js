import { ElMessage } from 'element-plus';
import axios from 'axios';

export const validateDates = (from, to, fromLabel = 'From Date', toLabel = 'To Date', config) => {
    if (from && to && from > to) {
        const errorMsg = `${fromLabel} cannot be greater than ${toLabel}.`;
        
        try {
            ElMessage.error(errorMsg);
        } catch (e) {
            alert(errorMsg);
        }
        
        // Cancel the request using the standard CancelToken
        config.cancelToken = new axios.CancelToken((cancel) => {
            cancel(errorMsg);
        });
    }
};

export const registerDateInterceptor = (axiosInstance) => {
    axiosInstance.interceptors.request.use((config) => {
        // Check URL string query parameters
        if (config.url) {
            try {
                const urlObj = new URL(config.url, window.location.origin);
                const searchParams = urlObj.searchParams;
                
                const date_from = searchParams.get('date_from');
                const date_to = searchParams.get('date_to');
                if (date_from && date_to) {
                    validateDates(date_from, date_to, 'From Date', 'To Date', config);
                }
                
                const from_date = searchParams.get('from_date');
                const to_date = searchParams.get('to_date');
                if (from_date && to_date) {
                    validateDates(from_date, to_date, 'From Date', 'To Date', config);
                }
                
                const start_date = searchParams.get('start_date');
                const end_date = searchParams.get('end_date');
                if (start_date && end_date) {
                    validateDates(start_date, end_date, 'Start Date', 'End Date', config);
                }
            } catch (e) {
                // Ignore potential relative URL parsing errors
            }
        }

        // Check query params (GET)
        if (config.params) {
            if (config.params.date_from && config.params.date_to) {
                validateDates(config.params.date_from, config.params.date_to, 'From Date', 'To Date', config);
            }
            if (config.params.from_date && config.params.to_date) {
                validateDates(config.params.from_date, config.params.to_date, 'From Date', 'To Date', config);
            }
            if (config.params.start_date && config.params.end_date) {
                validateDates(config.params.start_date, config.params.end_date, 'Start Date', 'End Date', config);
            }
        }

        // Check payload data (POST/PUT)
        if (config.data) {
            let payload = config.data;
            if (typeof payload === 'string') {
                try {
                    payload = JSON.parse(payload);
                } catch (e) {}
            }
            if (payload && typeof payload === 'object') {
                if (payload.date_from && payload.date_to) {
                    validateDates(payload.date_from, payload.date_to, 'From Date', 'To Date', config);
                }
                if (payload.from_date && payload.to_date) {
                    validateDates(payload.from_date, payload.to_date, 'From Date', 'To Date', config);
                }
                if (payload.start_date && payload.end_date) {
                    validateDates(payload.start_date, payload.end_date, 'Start Date', 'End Date', config);
                }
            }
        }

        return config;
    }, (error) => {
        return Promise.reject(error);
    });
};
