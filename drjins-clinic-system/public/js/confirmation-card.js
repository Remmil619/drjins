(() => {
    const modalElement = document.getElementById('confirmationCard');
    if (!modalElement) return;
    const title = document.getElementById('confirmationCardTitle');
    const message = document.getElementById('confirmationCardMessage');
    const accept = document.getElementById('confirmationCardAccept');
    let pendingForm = null;
    let triggeringElement = null;

    const close = () => {
        modalElement.hidden = true;
        pendingForm = null;
        triggeringElement?.focus();
        triggeringElement = null;
    };

    document.addEventListener('submit', event => {
        const form = event.target.closest('form[data-confirm-message]');
        if (!form) return;

        if (form.dataset.confirmBypass === 'true') {
            delete form.dataset.confirmBypass;
            return;
        }

        event.preventDefault();
        pendingForm = form;
        triggeringElement = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
        title.textContent = form.dataset.confirmTitle || 'Please confirm';
        message.textContent = form.dataset.confirmMessage;
        accept.textContent = form.dataset.confirmAccept || 'Confirm';
        accept.classList.toggle('btn-danger', form.dataset.confirmDanger === 'true');
        accept.classList.toggle('btn-primary', form.dataset.confirmDanger !== 'true');
        modalElement.hidden = false;
        (modalElement.querySelector('[data-confirm-cancel]') || accept).focus();
    }, true);

    modalElement.querySelector('[data-confirm-cancel]').addEventListener('click', close);

    modalElement.addEventListener('click', event => {
        if (event.target === modalElement) close();
    });

    document.addEventListener('keydown', event => {
        if (modalElement.hidden) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
        }
        if (event.key === 'Tab') {
            const focusable = [...modalElement.querySelectorAll('button:not([disabled])')];
            if (event.shiftKey && document.activeElement === focusable[0]) {
                event.preventDefault();
                focusable.at(-1)?.focus();
            } else if (!event.shiftKey && document.activeElement === focusable.at(-1)) {
                event.preventDefault();
                focusable[0]?.focus();
            }
        }
    });

    accept.addEventListener('click', () => {
        const form = pendingForm;
        pendingForm = null;
        if (!form) return;

        modalElement.hidden = true;
        if (!form.isConnected || !form.reportValidity()) return;
        form.dataset.confirmBypass = 'true';
        form.requestSubmit();
        triggeringElement = null;
    });
})();
