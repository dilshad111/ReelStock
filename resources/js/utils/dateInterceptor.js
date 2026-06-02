import { ElMessage } from 'element-plus';
import axios from 'axios';

const DATE_RANGE_PAIRS = [
    { from: 'date_from', to: 'date_to', fromLabel: 'From Date', toLabel: 'To Date' },
    { from: 'from_date', to: 'to_date', fromLabel: 'From Date', toLabel: 'To Date' },
    { from: 'start_date', to: 'end_date', fromLabel: 'Start Date', toLabel: 'End Date' },
    { from: 'from', to: 'to', fromLabel: 'From Date', toLabel: 'To Date' }
];

const parseStrictIsoDate = (value) => {
    if (typeof value !== 'string') return null;
    const trimmed = value.trim();
    if (!/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) return null;

    const [yearRaw, monthRaw, dayRaw] = trimmed.split('-');
    const year = Number(yearRaw);
    const month = Number(monthRaw);
    const day = Number(dayRaw);

    if (!Number.isInteger(year) || !Number.isInteger(month) || !Number.isInteger(day)) {
        return null;
    }

    const dt = new Date(Date.UTC(year, month - 1, day));
    if (
        dt.getUTCFullYear() !== year ||
        dt.getUTCMonth() + 1 !== month ||
        dt.getUTCDate() !== day
    ) {
        return null;
    }

    return dt;
};

const showDateValidationError = (message) => {
    try {
        ElMessage.error(message);
    } catch (e) {
        alert(message);
    }
};

const getValidationError = (payload, pair) => {
    const fromValue = payload?.[pair.from];
    const toValue = payload?.[pair.to];

    if (fromValue) {
        const fromDate = parseStrictIsoDate(String(fromValue));
        if (!fromDate) {
            return `Invalid ${pair.fromLabel}. Please select a valid calendar date.`;
        }
    }

    if (toValue) {
        const toDate = parseStrictIsoDate(String(toValue));
        if (!toDate) {
            return `Invalid ${pair.toLabel}. Please select a valid calendar date.`;
        }
    }

    if (fromValue && toValue) {
        const fromDate = parseStrictIsoDate(String(fromValue));
        const toDate = parseStrictIsoDate(String(toValue));
        if (fromDate && toDate && fromDate.getTime() > toDate.getTime()) {
            return `${pair.fromLabel} cannot be greater than ${pair.toLabel}.`;
        }
    }

    return null;
};

const extractUrlPayload = (url) => {
    if (!url) return {};
    try {
        const urlObj = new URL(url, window.location.origin);
        return Object.fromEntries(urlObj.searchParams.entries());
    } catch (e) {
        return {};
    }
};

const extractDataPayload = (data) => {
    if (!data) return {};
    if (typeof data === 'string') {
        try {
            return JSON.parse(data);
        } catch (e) {
            return {};
        }
    }
    if (typeof data === 'object') {
        return data;
    }
    return {};
};

const validateRequestDates = (config) => {
    const payloads = [
        extractUrlPayload(config.url),
        config.params || {},
        extractDataPayload(config.data)
    ];

    for (const payload of payloads) {
        for (const pair of DATE_RANGE_PAIRS) {
            const error = getValidationError(payload, pair);
            if (error) return error;
        }
    }

    return null;
};

export const registerDateInterceptor = (axiosInstance) => {
    axiosInstance.interceptors.request.use((config) => {
        const error = validateRequestDates(config);
        if (error) {
            showDateValidationError(error);
            return Promise.reject(new axios.Cancel(error));
        }
        return config;
    }, (error) => Promise.reject(error));
};

