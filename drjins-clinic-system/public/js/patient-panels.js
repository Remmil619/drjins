(() => {
    const closeAfterSave = document.currentScript?.dataset.closeAfterSave === 'true';
    const openConsultation = document.currentScript?.dataset.openConsultation === 'true';
    const panels = [...document.querySelectorAll('[data-patient-panel]')];
    const current = new URL(window.location.href);
    const requested = panels.find(panel => current.searchParams.has(panel.dataset.pageKey || panel.dataset.section + '_page'))
        || panels.find(panel => panel.dataset.section === current.searchParams.get('tab'))
        || (openConsultation ? document.getElementById('panel-consultations') : null)
        || panels.find(panel => panel.dataset.autoOpen === 'true');

    document.querySelectorAll('[data-entry-section]').forEach(card => {
        card.addEventListener('toggle', () => {
            if (!card.open || !card.closest('.modal.show')) return;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', card.dataset.entrySection);
            history.replaceState(null, '', url);
        });
    });

    panels.forEach(panel => {
        // Keep the popup outside cards whose styles can constrain its positioning.
        document.body.append(panel);
        panel.addEventListener('shown.bs.modal', () => {
            if (panel.id === 'panel-consultations') {
                const section = ['immunizations', 'billing'].find(name =>
                    current.searchParams.get('tab') === name || current.searchParams.has(name + '_page'));
                if (section) panel.querySelector('#section-' + section)?.scrollIntoView({ block: 'start' });
            }
            const manager = panel.querySelector('#vaccine-manager[data-restore="true"]');
            if (manager) {
                manager.scrollIntoView({ block: 'start', behavior: 'instant' });
                manager.dataset.restore = 'false';
            }
        });
        panel.addEventListener('show.bs.modal', event => {
            if (event.relatedTarget && panel.id === 'panel-consultations') {
                panel.querySelectorAll('[data-entry-section]').forEach(card => { card.open = false; });
            }
            const url = new URL(window.location.href);
            url.searchParams.set('tab', panel.dataset.section);
            url.hash = '';
            history.replaceState(null, '', url);
        });
        panel.addEventListener('hidden.bs.modal', () => {
            const url = new URL(window.location.href);
            url.searchParams.delete('tab');
            panels.forEach(item => url.searchParams.delete(item.dataset.pageKey || item.dataset.section + '_page'));
            ['immunizations_page', 'billing_page', 'growth_page'].forEach(key => url.searchParams.delete(key));
            history.replaceState(null, '', url);
            document.querySelector(`[data-bs-target="#${panel.id}"]`)?.focus({ preventScroll: true });
        });
    });
    if (document.getElementById('consultation-wizard')?.dataset.autoWizard === 'true') return;
    if (closeAfterSave) {
        // A successful redirect should show the updated record, not reopen its form.
        const url = new URL(window.location.href);
        url.searchParams.delete('tab');
        panels.forEach(panel => url.searchParams.delete(panel.dataset.pageKey || panel.dataset.section + '_page'));
        url.searchParams.delete('growth_page');
        url.searchParams.delete('immunizations_page');
        url.searchParams.delete('billing_page');
        url.hash = '';
        history.replaceState(null, '', url);
        document.querySelectorAll('details[open]').forEach(details => {
            if (details.querySelector('form')) details.open = false;
        });
    } else if (requested) {
        bootstrap.Modal.getOrCreateInstance(requested).show();
    }
})();
