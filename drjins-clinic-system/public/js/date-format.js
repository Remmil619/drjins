(() => {
    const pad = value => String(value).padStart(2, '0');

    const toDisplay = value => {
        const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || '');
        return match ? `${match[3]}/${match[2]}/${match[1]}` : '';
    };

    const toIso = value => {
        const match = /^\s*(\d{1,2})[/-](\d{1,2})[/-](\d{4})\s*$/.exec(value || '');
        if (!match) return '';

        const day = Number(match[1]);
        const month = Number(match[2]);
        const year = Number(match[3]);
        const candidate = new Date(Date.UTC(year, month - 1, day));

        return candidate.getUTCFullYear() === year
            && candidate.getUTCMonth() === month - 1
            && candidate.getUTCDate() === day
            ? `${year}-${pad(month)}-${pad(day)}`
            : '';
    };

    const pretty = iso => {
        const date = toIso(toDisplay(iso));
        if (!date) return iso;
        const [, year, month, day] = /^(\d{4})-(\d{2})-(\d{2})$/.exec(date);
        return `${day}/${month}/${year}`;
    };

    const enhance = input => {
        if (input.dataset.dateFormatReady || input.type !== 'date') return;
        input.dataset.dateFormatReady = 'true';

        const id = input.id || `date-field-${Math.random().toString(36).slice(2)}`;
        input.id = id;
        const display = document.createElement('input');
        display.type = 'text';
        display.id = `${id}-display`;
        display.className = input.className;
        display.placeholder = 'DD/MM/YYYY';
        display.autocomplete = 'off';
        display.inputMode = 'numeric';
        display.setAttribute('aria-describedby', `${id}-format-hint`);
        display.setAttribute('aria-label', input.getAttribute('aria-label') || 'Date, day/month/year');
        display.required = input.required;
        display.value = toDisplay(input.value);

        const label = input.id && document.querySelector(`label[for="${CSS.escape(id)}"]`);
        if (label) label.htmlFor = display.id;

        const wrapper = document.createElement('div');
        wrapper.className = 'date-format-control';
        input.parentNode.insertBefore(wrapper, input);
        const row = document.createElement('div');
        row.className = 'date-format-row';
        row.append(display);
        const picker = document.createElement('button');
        picker.type = 'button';
        picker.className = 'date-format-picker';
        picker.setAttribute('aria-label', 'Choose date from calendar');
        picker.textContent = '\u{1F4C5}';
        picker.addEventListener('click', () => {
            if (typeof input.showPicker === 'function') input.showPicker();
            else display.focus();
        });
        row.append(picker);
        wrapper.append(row, input);

        const hint = document.createElement('small');
        hint.id = `${id}-format-hint`;
        hint.className = 'date-format-hint';
        hint.textContent = 'Enter as day/month/year (DD/MM/YYYY).';
        wrapper.append(hint);

        const constraints = { min: input.min, max: input.max };
        input.removeAttribute('min');
        input.removeAttribute('max');
        input.required = false;
        input.tabIndex = -1;
        input.setAttribute('aria-hidden', 'true');
        input.classList.add('date-format-source');

        const validationMessage = raw => {
            const iso = toIso(raw);
            if (raw && !iso) return 'Enter a valid date as DD/MM/YYYY.';
            if (display.required && !raw) return 'Enter a date as DD/MM/YYYY.';
            if (iso && constraints.min && iso < constraints.min) return `Enter a date on or after ${pretty(constraints.min)}.`;
            if (iso && constraints.max && iso > constraints.max) return `Enter a date on or before ${pretty(constraints.max)}.`;
            return '';
        };

        let syncingFromDisplay = false;
        const syncFromDisplay = () => {
            const raw = display.value.trim();
            const iso = toIso(raw);
            display.setCustomValidity(validationMessage(raw));
            input.value = iso;
            if (raw && !iso) return;
            syncingFromDisplay = true;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            syncingFromDisplay = false;
        };

        display.addEventListener('input', event => {
            event.stopPropagation();
            syncFromDisplay();
        });
        input.addEventListener('change', () => {
            if (syncingFromDisplay) return;
            display.value = toDisplay(input.value);
            display.setCustomValidity(validationMessage(display.value.trim()));
        });

        const form = input.form;
        if (form && !form.dataset.dateFormatSubmitReady) {
            form.dataset.dateFormatSubmitReady = 'true';
            form.addEventListener('submit', event => {
                const invalid = [...form.querySelectorAll('.date-format-control input[type="text"]')]
                    .find(field => !field.checkValidity());
                if (invalid) {
                    event.preventDefault();
                    invalid.reportValidity();
                    invalid.focus();
                }
            }, true);
            form.addEventListener('reset', () => setTimeout(() => {
                form.querySelectorAll('.date-format-control').forEach(control => {
                    const source = control.querySelector('.date-format-source');
                    const visible = control.querySelector('input[type="text"]');
                    if (source && visible) {
                        visible.value = toDisplay(source.value);
                        visible.setCustomValidity('');
                    }
                });
            }));
        }
    };

    const initialize = () => document.querySelectorAll('input[type="date"]').forEach(enhance);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
    else initialize();
})();
