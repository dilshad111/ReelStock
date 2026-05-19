import axios from 'axios';
import { ElMessage } from 'element-plus';

const apiClient = axios.create({
    baseURL: 'http://192.168.10.47:8000/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

import { registerDateInterceptor } from '../utils/dateInterceptor';

apiClient.interceptors.request.use((config) => {
    const token = localStorage.getItem('token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

registerDateInterceptor(apiClient);

apiClient.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response) {
            const status = error.response.status;
            if (status === 401) {
                ElMessage.error('Session expired. Please log in again.');
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                window.location.reload();
            } else if (status === 422) {
                // Validation errors handled locally
            } else {
                ElMessage.error(error.response.data?.message || 'An error occurred.');
            }
        }
        return Promise.reject(error);
    }
);

export default apiClient;
