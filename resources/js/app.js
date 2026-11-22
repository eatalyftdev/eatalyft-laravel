import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('layout-menu-toggle');
    const layoutMenu = document.getElementById('layout-menu');
    const contentBackdrop = document.querySelector('.content-backdrop');
    const layoutWrapper = document.querySelector('.layout-wrapper');

    if (menuToggle && layoutMenu) {
        menuToggle.addEventListener('click', () => {
            layoutMenu.classList.toggle('show');
            contentBackdrop?.classList.toggle('show');
            layoutWrapper?.classList.toggle('layout-menu-open');
        });
    }

    if (contentBackdrop) {
        contentBackdrop.addEventListener('click', () => {
            layoutMenu?.classList.remove('show');
            contentBackdrop.classList.remove('show');
            layoutWrapper?.classList.remove('layout-menu-open');
        });
    }

    const submenuItems = document.querySelectorAll('.menu-item-submenu > .menu-link');
    submenuItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const parent = item.parentElement;
            parent.classList.toggle('open');
        });
    });

    const activeItem = document.querySelector('.menu-item.active');
    if (activeItem) {
        let parent = activeItem.closest('.menu-item-submenu');
        while (parent) {
            parent.classList.add('open');
            parent = parent.parentElement?.closest('.menu-item-submenu');
        }
    }
});

window.switchCurrency = function(currency) {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (!csrfMeta) return;
    fetch('/currency/switch', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfMeta.getAttribute('content'),
        },
        body: JSON.stringify({ currency }),
    }).then(response => {
        if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
            return;
        }
        return response.json();
    }).then(data => {
        if (data && data.success) {
            location.reload();
        }
    }).catch(() => {
        // Silently ignore - endpoint may require authentication
    });
};

window.switchLanguage = function(locale) {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (!csrfMeta) return;
    fetch('/language/switch', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfMeta.getAttribute('content'),
        },
        body: JSON.stringify({ locale }),
    }).then(response => {
        if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
            return;
        }
        return response.json();
    }).then(data => {
        if (data && data.success) {
            location.reload();
        }
    }).catch(() => {
        // Silently ignore - endpoint may require authentication
    });
};

// Scroll reveal for elements with .ely-reveal class
if ('IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

    document.querySelectorAll('.ely-reveal').forEach(el => revealObserver.observe(el));
} else {
    // Fallback: show all reveal elements immediately if IntersectionObserver not supported
    document.querySelectorAll('.ely-reveal').forEach(el => {
        el.style.opacity = '1';
        el.style.transform = 'none';
    });
}