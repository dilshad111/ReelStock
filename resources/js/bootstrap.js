import 'bootstrap';
import { ElMessage } from 'element-plus';

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import { registerDateInterceptor } from './utils/dateInterceptor';

// Global request interceptor to validate date range filters across the entire application
registerDateInterceptor(window.axios);

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST ?? `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
    wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

// Global scroll-to-top handler for all edit actions across the entire software
document.addEventListener('click', (event) => {
    const target = event.target;
    if (!target) return;

    // Find closest interactive element
    const interactiveEl = target.closest('button, a, .el-button, .el-dropdown-item, [role="button"]');
    if (!interactiveEl) return;

    const text = (interactiveEl.innerText || interactiveEl.textContent || '').trim().toLowerCase();
    const title = (interactiveEl.getAttribute('title') || '').toLowerCase();
    const ariaLabel = (interactiveEl.getAttribute('aria-label') || '').toLowerCase();
    const className = (interactiveEl.className || '').toLowerCase();
    
    // Check if the clicked element is an Edit button or contains edit indicators
    const isEdit = 
        text === 'edit' || 
        text.includes('edit ') || 
        text === 'edit entry' || 
        title === 'edit' || 
        title.includes('edit ') || 
        ariaLabel === 'edit' || 
        className.includes('btn-warning') ||
        className.includes('edit-btn') ||
        target.closest('.bi-pencil') ||
        target.closest('.bi-pencil-fill') ||
        target.closest('.el-icon-edit') ||
        interactiveEl.querySelector('.bi-pencil') ||
        interactiveEl.querySelector('.bi-pencil-fill');

    if (isEdit) {
        // Wait briefly to allow Vue state changes / DOM rendering of edit forms
        setTimeout(() => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }, 100);
    }
}, true); // capturing phase ensures it captures clicks before elements are removed from DOM
