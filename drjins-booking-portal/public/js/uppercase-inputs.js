(() => {
    function normalize(field) {
        if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) || field.disabled || field.readOnly || field.dataset.uppercase === 'false') return;
        if (field instanceof HTMLInputElement && !['text', 'search'].includes(field.type)) return;
        const start = field.selectionStart, end = field.selectionEnd;
        const value = field.value;
        const upper = value.toUpperCase();
        if (value === upper) return;
        field.value = upper;
        if (start !== null) field.setSelectionRange(value.slice(0, start).toUpperCase().length, value.slice(0, end).toUpperCase().length);
    }
    document.addEventListener('input', event => { if (!event.isComposing) normalize(event.target); });
    document.addEventListener('compositionend', event => normalize(event.target));
    document.addEventListener('change', event => normalize(event.target));
    document.addEventListener('focusout', event => normalize(event.target));
    window.addEventListener('pageshow', () => document.querySelectorAll('input, textarea').forEach(normalize));
    document.addEventListener('submit', event => event.target.querySelectorAll('input, textarea').forEach(normalize), true);
    document.querySelectorAll('input, textarea').forEach(normalize);
})();
