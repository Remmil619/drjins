(() => {
    const closeAfterSave = document.currentScript?.dataset.closeAfterSave === 'true';
    const panels = [...document.querySelectorAll('[data-patient-panel]')];
    const current = new URL(window.location.href);
    const requested = panels.find(panel => current.searchParams.has(panel.dataset.pageKey || panel.dataset.section + '_page'))
        || panels.find(panel => panel.dataset.section === current.searchParams.get('tab'))
        || panels.find(panel => panel.dataset.autoOpen === 'true');

    panels.forEach(panel => {
        // Keep the popup outside cards whose styles can constrain its positioning.
        document.body.append(panel);
        panel.addEventListener('shown.bs.modal', () => {
            const manager = panel.querySelector('#vaccine-manager[data-restore="true"]');
            if (manager) {
                manager.scrollIntoView({ block: 'start', behavior: 'instant' });
                manager.dataset.restore = 'false';
            }
        });
        panel.addEventListener('show.bs.modal', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', panel.dataset.section);
            url.hash = '';
            history.replaceState(null, '', url);
        });
        panel.addEventListener('hidden.bs.modal', () => {
            const url = new URL(window.location.href);
            url.searchParams.delete('tab');
            panels.forEach(item => url.searchParams.delete(item.dataset.pageKey || item.dataset.section + '_page'));
            history.replaceState(null, '', url);
            document.querySelector(`[data-bs-target="#${panel.id}"]`)?.focus({ preventScroll: true });
        });
    });
    if (closeAfterSave) {
        // A successful redirect should show the updated record, not reopen its form.
        const url = new URL(window.location.href);
        url.searchParams.delete('tab');
        panels.forEach(panel => url.searchParams.delete(panel.dataset.pageKey || panel.dataset.section + '_page'));
        url.searchParams.delete('growth_page');
        url.hash = '';
        history.replaceState(null, '', url);
        document.querySelectorAll('details[open]').forEach(details => {
            if (details.querySelector('form')) details.open = false;
        });
    } else if (requested) {
        bootstrap.Modal.getOrCreateInstance(requested).show();
    }
})();
