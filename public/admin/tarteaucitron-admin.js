// Back-office page of the tarteaucitron configuration (loaded by
// templates/admin/configuration/update/content/form/sections/general/tarteaucitron.html.twig).

// Keeps the active tab across the save redirect: the fragment rides on the form action.
// Waits for DOMContentLoaded: the admin bundle, which attaches Bootstrap's tab handlers, is
// loaded after this markup (synchronously, so before that event).
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('tarteaucitron-init-tabs');
    if (!root) {
        return;
    }
    const form = document.getElementById(root.dataset.formId);
    const remember = (tab) => {
        history.replaceState(null, '', '#tab-' + tab);
        if (form) {
            form.action = location.pathname + location.search + '#tab-' + tab;
        }
    };
    root.querySelectorAll('[data-bs-toggle="tab"]').forEach((button) => {
        button.addEventListener('shown.bs.tab', () => remember(button.dataset.tab));
    });
    const show = (tab) => {
        const button = tab ? root.querySelector('[data-tab="' + CSS.escape(tab) + '"]') : null;
        if (button) {
            button.click();
            remember(tab);
        }
    };
    const fromHash = () => (location.hash.startsWith('#tab-') ? location.hash.slice(5) : null);
    show(root.dataset.errorTab || fromHash());
    window.addEventListener('hashchange', () => show(fromHash()));
});

// Services column: details (hint, identifiers) stay folded until the service is enabled, each
// category header counts the enabled services, and a search box filters by name.
document.addEventListener('DOMContentLoaded', () => {
    const accordion = document.getElementById('tarteaucitron-services-accordion');
    if (!accordion) {
        return;
    }

    const refreshCount = (category) => {
        const enabled = category.querySelectorAll('[data-tac-service-toggle]:checked').length;
        category.querySelector('[data-tac-category-enabled]').textContent = enabled;
        const badge = category.querySelector('[data-tac-category-count]');
        badge.classList.toggle('text-bg-success', enabled > 0);
        badge.classList.toggle('text-bg-secondary', enabled === 0);
    };

    accordion.querySelectorAll('[data-tac-service-toggle]').forEach((toggle) => {
        toggle.addEventListener('change', () => {
            const service = toggle.closest('[data-tac-service]');
            const details = service.querySelector('[data-tac-service-details]');
            if (details) {
                details.classList.toggle('d-none', !toggle.checked);
                if (toggle.checked) {
                    details.querySelector('input:not([type=hidden]), textarea')?.focus();
                }
            }
            refreshCount(service.closest('[data-tac-service-category]'));
        });
    });

    const search = document.querySelector('[data-tac-service-search]');
    const noMatch = document.querySelector('[data-tac-service-no-match]');
    const normalize = (text) => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    search?.addEventListener('input', () => {
        const query = normalize(search.value);
        let matches = 0;
        accordion.querySelectorAll('[data-tac-service-category]').forEach((category) => {
            const categoryHit = normalize(category.querySelector('[data-tac-category-name]').textContent).includes(query);
            let categoryMatches = 0;
            category.querySelectorAll('[data-tac-service]').forEach((service) => {
                const hit = '' === query || categoryHit || normalize(service.querySelector('[data-tac-service-name]').textContent).includes(query);
                service.classList.toggle('d-none', !hit);
                categoryMatches += hit ? 1 : 0;
            });
            matches += categoryMatches;
            category.classList.toggle('d-none', '' !== query && 0 === categoryMatches);
            if ('' !== query && categoryMatches > 0) {
                category.querySelector('.accordion-collapse').classList.add('show');
                category.querySelector('.accordion-button').classList.remove('collapsed');
            }
        });
        noMatch.classList.toggle('d-none', '' === query || matches > 0);
    });
});
