(() => {
    const popup = document.getElementById('clinicWelcome');
    if (!popup || !window.bootstrap?.Modal) return;

    let trigger;
    document.addEventListener('click', event => {
        const link = event.target.closest('a[href^="tel:"]');
        if (!link || popup.contains(link) || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        trigger = link;
        bootstrap.Modal.getOrCreateInstance(popup).show();
    });
    popup.addEventListener('hidden.bs.modal', () => trigger?.focus());
})();
