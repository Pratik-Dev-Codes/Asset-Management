import _ from 'lodash';
import axios from 'axios';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

// Ziggy is loaded from CDN and available globally
const Ziggy = window.Ziggy || {};

// Set Axios defaults
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// Function to set CSRF token from meta tag or window.Laravel
function setCsrfToken() {
    // Try to get CSRF token from meta tag
    const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
    
    if (csrfToken && csrfToken.content) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
    } 
    // Fallback to window.Laravel.csrfToken if available
    else if (window.Laravel && window.Laravel.csrfToken) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = window.Laravel.csrfToken;
    } else {
        console.warn('CSRF token not found. Some features may not work correctly.');
    }
}

// Set initial CSRF token
setCsrfToken();

// Make createInertiaApp available globally
window.createInertiaApp = createInertiaApp;
window.resolvePageComponent = resolvePageComponent;
window.createRoot = createRoot;

// Make Ziggy available globally
window.Ziggy = Ziggy;

// Set the Ziggy route helper
window.route = function(name, params, absolute) {
    return route(name, params, absolute, Ziggy);
};

// Add a request interceptor to ensure CSRF token is set for each request
window.axios.interceptors.request.use(
    config => {
        // Ensure CSRF token is set before each request
        const csrfToken = document.head.querySelector('meta[name="csrf-token"]');
        if (csrfToken && csrfToken.content) {
            config.headers['X-CSRF-TOKEN'] = csrfToken.content;
        } else if (window.Laravel && window.Laravel.csrfToken) {
            config.headers['X-CSRF-TOKEN'] = window.Laravel.csrfToken;
        }
        
        // Ensure we're sending the correct headers
        config.headers['X-Requested-With'] = 'XMLHttpRequest';
        config.withCredentials = true;
        
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

// Add a response interceptor to handle common errors
window.axios.interceptors.response.use(
    response => response,
    error => {
        const { status } = error.response || {};
        
        // Handle session expiration (419) or unauthorized (401) errors
        if (status === 419 || status === 401) {
            // If we're not already on the login page, redirect to login
            if (!window.location.pathname.includes('login')) {
                window.location.href = '/login';
            }
        }
        
        // Handle token mismatch (419)
        if (status === 419) {
            // Optionally, you could refresh the CSRF token here
            setCsrfToken();
        }
        
        return Promise.reject(error);
    }
);

// Make lodash available globally
window._ = _;
