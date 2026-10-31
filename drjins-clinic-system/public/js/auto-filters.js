(() => {
    const focusKey = 'clinic-auto-filter-focus';
    const forms = [...document.querySelectorAll('form[data-auto-filter]')];
    forms.forEach(form => {
        let timer;
        let composing = false;
        let navigating = false;
        const status = document.createElement('span');
        status.className = 'auto-filter-status';
        status.setAttribute('role', 'status');
        status.setAttribute('aria-live', 'polite');
        status.textContent = 'Filters update automatically';
        form.append(status);
        function validRange() {
            const from = form.elements.namedItem('from');
            const to = form.elements.namedItem('to');
            if (from && to) {
                const invalid = from.value && to.value && from.value > to.value;
                to.setCustomValidity(invalid ? 'Choose an end date on or after the start date.' : '');
                if (invalid) status.textContent = 'Choose an end date on or after the start date.';
            }
            return form.checkValidity();
        }
        function apply(input) {
            clearTimeout(timer);
            if (composing || navigating || !validRange()) return;
            if (input?.matches('input[type="text"], input[type="search"], input:not([type])') && document.activeElement === input) {
                try {
                    sessionStorage.setItem(focusKey, JSON.stringify({path: location.pathname, name: input.name, start: input.selectionStart, end: input.selectionEnd}));
                } catch (_) { /* Filtering still works if storage is unavailable. */ }
            }
            form.requestSubmit();
        }
        form.addEventListener('submit', event => {
            clearTimeout(timer);
            if (navigating || !validRange()) { event.preventDefault(); return; }
            navigating = true;
            status.textContent = 'Updating results…';
            form.setAttribute('aria-busy', 'true');
        });
        form.addEventListener('compositionstart', () => { composing = true; clearTimeout(timer); });
        form.addEventListener('compositionend', event => { composing = false; timer = setTimeout(() => apply(event.target), 650); });
        form.addEventListener('input', event => {
            const input = event.target;
            if (!input.matches('input[type="text"], input[type="search"], input:not([type])')) return;
            clearTimeout(timer);
            if (!composing) timer = setTimeout(() => apply(input), 650);
        });
        form.addEventListener('change', event => {
            if (event.target.matches('select, input[type="date"], input[type="checkbox"], input[type="radio"]')) apply(event.target);
        });
        window.addEventListener('pageshow', () => { navigating = false; form.removeAttribute('aria-busy'); status.textContent = 'Filters update automatically'; });
    });
    try {
        const saved = JSON.parse(sessionStorage.getItem(focusKey) || 'null');
        sessionStorage.removeItem(focusKey);
        if (saved?.path === location.pathname) {
            const input = forms.map(form => form.elements.namedItem(saved.name)).find(Boolean);
            if (input) { input.focus({preventScroll: true}); input.setSelectionRange(saved.start, saved.end); }
        }
    } catch (_) { /* Focus restoration is optional. */ }
})();
