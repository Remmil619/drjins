(() => {
    const fields = new Set([
        "address",
        "allergies",
        "barangay",
        "municipality",
        "province",
        "child_first_name",
        "child_middle_name",
        "child_name",
        "child_surname",
        "current_medications",
        "description",
        "emergency_contact_name",
        "first_name",
        "guardian_address",
        "guardian_barangay",
        "guardian_first_name",
        "guardian_middle_name",
        "guardian_municipality",
        "guardian_name",
        "guardian_province",
        "guardian_surname",
        "last_name",
        "medical_notes",
        "message",
        "middle_name",
        "name",
        "notes",
        "outcome",
        "reaction",
        "reason",
        "reason_for_visit",
        "reference",
        "search",
        "suffix",
        "vaccine_name",
        "void_reason",
        "complaint",
        "assessment",
        "prescription",
    ]);
    function normalize(field) {
        if (
            !(
                field instanceof HTMLInputElement ||
                field instanceof HTMLTextAreaElement
            ) ||
            !fields.has(field.name) ||
            field.disabled ||
            field.readOnly
        )
            return;
        if (
            field instanceof HTMLInputElement &&
            !["text", "search"].includes(field.type)
        )
            return;
        const start = field.selectionStart,
            end = field.selectionEnd;
        const value = field.value;
        const upper = value.toUpperCase();
        if (value === upper) return;
        field.value = upper;
        if (start !== null)
            field.setSelectionRange(
                value.slice(0, start).toUpperCase().length,
                value.slice(0, end).toUpperCase().length,
            );
    }
    document.addEventListener("input", (event) => {
        if (!event.isComposing) normalize(event.target);
    });
    document.addEventListener("compositionend", (event) =>
        normalize(event.target),
    );
    document.addEventListener("change", (event) => normalize(event.target));
    document.addEventListener(
        "submit",
        (event) =>
            event.target.querySelectorAll("input, textarea").forEach(normalize),
        true,
    );
    document.querySelectorAll("input, textarea").forEach(normalize);
})();
