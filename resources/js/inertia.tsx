import '../css/app.css';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';

createInertiaApp({
    title: (title) => `${title} - EataLyft`,
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.tsx', { eager: true });
        const page = pages[`./Pages/${name}.tsx`];
        if (!page) throw new Error(`Page not found: ${name}`);
        return (page as any).default || page;
    },
    setup({ el, App, props }) {
        const root = createRoot(el);
        root.render(React.createElement(App, props));
    },
    progress: {
        color: '#DC2626',
    },
});