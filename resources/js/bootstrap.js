import _ from 'lodash';
import axios from 'axios';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Ziggy } from 'ziggy-js';

// Set Axios defaults
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// Set CSRF token for Axios
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found');
}

// Make createInertiaApp available globally
window.createInertiaApp = createInertiaApp;
window.resolvePageComponent = resolvePageComponent;
window.createRoot = createRoot;

// Make Ziggy available globally
window.Ziggy = Ziggy;

// Set the Ziggy route helper
window.route = (name, params, absolute) => {
    return route(name, params, absolute, Ziggy);
};

// Add a response interceptor
window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 419) {
            // Session expired, refresh the page
            window.location.reload();
        }
        return Promise.reject(error);
    }
);

// Make lodash available globally
window._ = _;
