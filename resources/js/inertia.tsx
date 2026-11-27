import '../css/app.css';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';

class ErrorBoundary extends React.Component<{ children: React.ReactNode }, { error: Error | null }> {
    constructor(props: { children: React.ReactNode }) {
        super(props);
        this.state = { error: null };
    }
    static getDerivedStateFromError(error: Error) {
        return { error };
    }
    render() {
        if (this.state.error) {
            return React.createElement('div', { style: { padding: '2rem', color: 'red' } },
                React.createElement('h1', null, 'React Error'),
                React.createElement('pre', null, this.state.error.message),
                React.createElement('pre', null, this.state.error.stack)
            );
        }
        return this.props.children;
    }
}

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
        root.render(React.createElement(ErrorBoundary, null, React.createElement(App, props)));
    },
    progress: {
        color: '#DC2626',
    },
});