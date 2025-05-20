import './bootstrap';
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { Toaster } from 'react-hot-toast';

// Import layouts
import AppLayout from './Layouts/AppLayout';

// Resolve page components
const resolveComponent = (name) => {
    const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
    const page = pages[`./Pages/${name}.jsx`];
    
    // Set default layout for dashboard pages
    if (name.startsWith('Dashboard') && page) {
        page.default.layout = page.default.layout || ((page) => <AppLayout>{page}</AppLayout>);
    }
    
    return page;
};

// Set app name in global scope
window.appName = 'Asset Management System For NEEPCO LTD';

// Create the Inertia app
createInertiaApp({
    title: (title) => title ? `${title} - ${window.appName}` : window.appName,
    resolve: resolveComponent,
    setup({ el, App, props }) {
        createRoot(el).render(
            <>
                <App {...props} />
                <Toaster position="bottom-right" />
            </>
        );
    },
    progress: {
        color: '#4B5563',
    },
});
