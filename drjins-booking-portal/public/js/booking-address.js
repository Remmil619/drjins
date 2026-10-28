(() => {
    const script = document.currentScript;
    const province = document.getElementById('guardian_province');
    const municipality = document.getElementById('guardian_municipality');
    const barangay = document.getElementById('guardian_barangay');
    const status = document.getElementById('addressStatus');
    const retry = document.getElementById('retryAddress');
    let directory;

    function populate(select, rows, placeholder, previous = '') {
        select.replaceChildren(new Option(placeholder, ''));
        rows.sort((a, b) => a.name.localeCompare(b.name)).forEach(row => {
            const option = new Option(row.name, row.name);
            option.dataset.code = row.code || '';
            option.selected = row.name.toUpperCase() === previous.toUpperCase();
            select.add(option);
        });
        select.disabled = rows.length === 0;
    }

    function updateMunicipalities(previous = '') {
        const code = province.selectedOptions[0]?.dataset.code;
        const rows = directory.municipalities.filter(row => code === 'NCR'
            ? row.regionCode === '130000000'
            : !!code && row.provinceCode === code);
        populate(municipality, rows, code ? 'Select municipality / city' : 'Select a province first', previous);
        populate(barangay, [], 'Select a municipality first');
    }

    function updateBarangays(previous = '') {
        const code = municipality.selectedOptions[0]?.dataset.code;
        const rows = directory.barangays.filter(row => !!code && (row.cityCode === code || row.municipalityCode === code));
        populate(barangay, rows, code ? 'Select barangay' : 'Select a municipality first', previous);
    }

    province.addEventListener('change', () => updateMunicipalities());
    municipality.addEventListener('change', () => updateBarangays());

    async function load() {
        retry.hidden = true;
        province.setCustomValidity('Please wait for the address directory to load.');
        status.textContent = 'Loading address options…';
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);
        try {
            const response = await fetch(script.dataset.addressUrl, { signal: controller.signal });
            if (!response.ok) throw new Error('Address directory unavailable');
            directory = await response.json();
            populate(province, [...directory.provinces, { code: 'NCR', name: 'Metro Manila' }], 'Select province', province.dataset.selected);
            updateMunicipalities(municipality.dataset.selected);
            updateBarangays(barangay.dataset.selected);
            province.setCustomValidity('');
            status.textContent = 'Select your province, municipality or city, then barangay.';
        } catch {
            province.replaceChildren(new Option('Address options unavailable', ''));
            province.setCustomValidity('Please retry loading the address options.');
            status.textContent = 'Could not load addresses. Please retry.';
            retry.hidden = false;
        } finally {
            clearTimeout(timeout);
        }
    }

    retry.addEventListener('click', load);
    load();
})();
