(() => {
    const modal = document.getElementById('consultation-wizard');
    if (!modal) return;
    document.body.append(modal);
    const form = modal.querySelector('form');
    const steps = [...form.querySelectorAll('[data-wizard-step]')];
    const overview = document.getElementById('panel-consultations');
    const next = modal.querySelector('[data-wizard-next]');
    const back = modal.querySelector('[data-wizard-back]');
    const save = modal.querySelector('[data-wizard-save]');
    const checkbox = form.querySelector('#include-vaccination');
    const vaccination = form.querySelector('#workflow-vaccination');
    const source = form.querySelector('#workflow-source');
    const fee = form.querySelector('#workflow-vaccination-fee');
    const consultationFee = form.querySelector('#workflow-consultation-fee');
    let step = 0;
    let saving = false;
    function showStep(index) {
        step = index;
        steps.forEach((section, i) => { section.hidden = i !== index; });
        const title = ['Consultation', 'Vaccination', 'Billing & Fees'][index];
        modal.querySelector('#wizard-title').textContent = title;
        modal.querySelector('[data-wizard-progress]').textContent = `Step ${index + 1} of 3 � ${title}`;
        next.hidden = index === 2;
        save.hidden = index !== 2;
        back.textContent = index ? 'Back' : (overview ? 'Back to overview' : 'Back to queue');
        modal.querySelector('.modal-body').scrollTop = 0;
    }
    function total() {
        const amount = Number(consultationFee.value || 0) + (checkbox.checked && source.value === 'clinic' ? Number(fee.value || 0) : 0);
        form.querySelector('[data-workflow-total]').textContent = `Visit fees: PHP ${amount.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }
    function vaccineState() {
        vaccination.disabled = !checkbox.checked;
        vaccination.hidden = !checkbox.checked;
        const clinicDose = checkbox.checked && source.value === 'clinic';
        form.querySelector('#workflow-vaccination-fee-row').hidden = !clinicDose;
        fee.required = clinicDose;
        if (!clinicDose) fee.value = '0.00';
        total();
    }
    function invalid(section) {
        return [...section.querySelectorAll('input, select, textarea')].find(input => !input.disabled && !input.checkValidity());
    }
    next.addEventListener('click', () => {
        const input = invalid(steps[step]);
        if (input) { input.reportValidity(); return; }
        showStep(Math.min(2, step + 1));
    });
    back.addEventListener('click', () => {
        if (step) { showStep(step - 1); return; }
        if (overview) {
            modal.addEventListener('hidden.bs.modal', () => bootstrap.Modal.getOrCreateInstance(overview).show(), {once: true});
            bootstrap.Modal.getOrCreateInstance(modal).hide();
        } else {
            bootstrap.Modal.getOrCreateInstance(modal).hide();
        }
    });
    document.querySelectorAll('[data-open-workflow]').forEach(button => {
        button.addEventListener('click', () => {
            showStep(Number(button.dataset.openWorkflow));
            const open = () => bootstrap.Modal.getOrCreateInstance(modal).show();
            if (overview?.classList.contains('show')) {
                overview.addEventListener('hidden.bs.modal', open, {once: true});
                bootstrap.Modal.getOrCreateInstance(overview).hide();
            } else open();
        });
    });
    checkbox.addEventListener('change', vaccineState);
    source.addEventListener('change', vaccineState);
    form.querySelector('#workflow-vaccine').addEventListener('change', event => {
        const option = [...form.querySelectorAll('#workflow-vaccine-prices [data-name]')].find(option => option.dataset.name.toLowerCase() === event.target.value.trim().toLowerCase());
        fee.value = option?.dataset.price || '';
        vaccineState();
    });
    consultationFee.addEventListener('input', total);
    fee.addEventListener('input', total);
    form.addEventListener('submit', event => {
        for (let index = 0; index < steps.length; index++) {
            const input = invalid(steps[index]);
            if (input) { event.preventDefault(); showStep(index); input.reportValidity(); return; }
        }
        if (step !== 2) { event.preventDefault(); showStep(step + 1); return; }
        if (saving) { event.preventDefault(); return; }
        saving = true;
        save.disabled = true;
        save.textContent = 'Saving�';
    });
    modal.addEventListener('hidden.bs.modal', () => {
        document.querySelector('[data-bs-target="#panel-consultations"], [data-bs-target="#consultation-wizard"]')?.focus();
    });
    vaccineState();
    showStep(0);
    if (modal.dataset.autoWizard === 'true') bootstrap.Modal.getOrCreateInstance(modal).show();
})();
