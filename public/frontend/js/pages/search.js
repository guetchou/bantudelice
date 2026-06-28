(() => {
    'use strict';

    const body = document.body;
    if (!body.classList.contains('bd-search-page')) return;

    const sidebar = document.getElementById('searchFiltersPanel');
    const backdrop = document.getElementById('searchFiltersBackdrop');
    const openButton = document.getElementById('searchFiltersOpen');
    const closeButton = document.getElementById('searchFiltersClose');
    const filterForm = document.getElementById('searchFilterForm');
    const mobileSort = document.getElementById('searchMobileSort');
    const desktopSort = document.getElementById('searchSort');
    const mainForm = document.getElementById('catalogSearchForm');
    const liveRegion = document.getElementById('searchLiveRegion');
    const inertRegions = [
        document.querySelector('.modern-header'),
        document.querySelector('.search-hero'),
        document.querySelector('.search-mobile-toolbar'),
        document.querySelector('.search-results'),
        document.querySelector('.modern-footer')
    ].filter(Boolean);
    let previousFocus = null;

    const focusableSelector = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])'
    ].join(',');

    function isMobileDrawer() {
        return window.matchMedia('(max-width: 991px)').matches;
    }

    function setPageInert(isInert) {
        inertRegions.forEach((region) => {
            region.inert = isInert;
        });
    }

    function setExpanded(isOpen) {
        const isHidden = !isOpen && isMobileDrawer();
        openButton?.setAttribute('aria-expanded', String(isOpen));
        sidebar?.setAttribute('aria-hidden', String(isHidden));

        if (!sidebar) return;
        sidebar.inert = isHidden;

        if (isOpen && isMobileDrawer()) {
            sidebar.setAttribute('role', 'dialog');
            sidebar.setAttribute('aria-modal', 'true');
        } else {
            sidebar.removeAttribute('role');
            sidebar.removeAttribute('aria-modal');
        }
    }

    function openFilters() {
        if (!sidebar || !isMobileDrawer()) return;
        previousFocus = document.activeElement;
        sidebar.classList.add('is-open');
        backdrop?.classList.add('is-visible');
        body.classList.add('search-filters-open');
        setPageInert(true);
        setExpanded(true);
        window.setTimeout(() => closeButton?.focus(), 40);
    }

    function closeFilters({ restoreFocus = true } = {}) {
        if (!sidebar) return;
        sidebar.classList.remove('is-open');
        backdrop?.classList.remove('is-visible');
        body.classList.remove('search-filters-open');
        setPageInert(false);
        setExpanded(false);
        if (restoreFocus && previousFocus instanceof HTMLElement) {
            previousFocus.focus();
        }
    }

    function trapFocus(event) {
        if (event.key !== 'Tab' || !sidebar?.classList.contains('is-open') || !isMobileDrawer()) return;
        const focusable = Array.from(sidebar.querySelectorAll(focusableSelector))
            .filter((element) => element.offsetParent !== null);
        if (!focusable.length) return;

        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    function announce(message) {
        if (!liveRegion) return;
        liveRegion.textContent = '';
        window.setTimeout(() => {
            liveRegion.textContent = message;
        }, 30);
    }

    function setSubmitting(form) {
        const button = form?.querySelector('button[type="submit"]');
        if (!button || button.disabled) return;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        const label = button.querySelector('[data-submit-label]');
        if (label) label.textContent = 'Recherche…';
        announce('Recherche en cours');
    }

    function applyImageFallback(image) {
        const fallback = image.getAttribute('data-image-fallback');
        if (!fallback || image.dataset.fallbackApplied === '1') return;
        image.dataset.fallbackApplied = '1';
        image.src = fallback;
    }

    openButton?.addEventListener('click', openFilters);
    closeButton?.addEventListener('click', () => closeFilters());
    backdrop?.addEventListener('click', () => closeFilters());

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sidebar?.classList.contains('is-open')) {
            closeFilters();
            return;
        }
        trapFocus(event);
    });

    window.addEventListener('resize', () => {
        if (!sidebar) return;
        if (!isMobileDrawer()) {
            closeFilters({ restoreFocus: false });
            sidebar.setAttribute('aria-hidden', 'false');
            sidebar.inert = false;
        } else if (!sidebar.classList.contains('is-open')) {
            sidebar.setAttribute('aria-hidden', 'true');
            sidebar.inert = true;
        }
    });

    mobileSort?.addEventListener('change', () => {
        if (!filterForm || !desktopSort) return;
        desktopSort.value = mobileSort.value;
        setSubmitting(filterForm);
        filterForm.submit();
    });

    filterForm?.addEventListener('submit', () => setSubmitting(filterForm));
    mainForm?.addEventListener('submit', () => setSubmitting(mainForm));

    document.querySelectorAll('[data-image-fallback]').forEach((image) => {
        image.addEventListener('error', () => applyImageFallback(image));
        if (image.complete && image.naturalWidth === 0) {
            applyImageFallback(image);
        }
    });

    setExpanded(Boolean(sidebar?.classList.contains('is-open')));
})();
