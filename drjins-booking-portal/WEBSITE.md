# Clinic website

Run `php artisan serve --host=127.0.0.1 --port=8001`. The home page is `/`; the booking form is `/book-appointment`. The clinic-system API remains under `/api/appointments`.

Keep this server running while using the clinic system. Its `BOOKING_API_URL` must point to `http://127.0.0.1:8001/api` for local use, and both apps must have the same nonempty `BOOKING_API_TOKEN`. Running the portal on another port will leave the clinic unable to sync. In a separate terminal in `../drjins-clinic-system`, run `php artisan bookings:sync` to verify the connection. The clinic appointment page also syncs bookings when opened and refreshed. Choose the Upcoming or All filter for future appointments; the default Today filter excludes them.

Booking requires the guardian's name, relationship, address and mobile number, the child's name, birth date and sex, an available date and time, and consent. Validation errors preserve the entered details. The confirmation button explains missing details or schedule selection, shows submission progress, and redirects to a success notification after saving.

The site uses local CSS, SVG artwork, and vendored Bootstrap 5.3.3 assets in `public/vendor/bootstrap`. No Node build or external CDN connection is needed for these pages. Bootstrap's MIT license notice is retained in both asset files.

## Clinic content

Edit `config/clinic.php` for the clinic name, address, services and appointment slots. The user-provided phone number is the default. Optional `.env` settings are `CLINIC_PHONE`, `CLINIC_EMAIL` and `APP_TIMEZONE` (defaults to `Asia/Manila`). After configuration changes run `php artisan config:clear`.

Child checkup is the confirmed service. Follow-up and growth/development services are explicitly labeled samples and lead to an inquiry, not a confirmed service booking. No product inventory or pricing has been supplied; the Products page offers product inquiries.

## Appointments

The form offers the next 14 days. Past times, closed dates, invalid times, and occupied slots are rejected by the server. A cache lock serializes submissions for the same slot. Preserve a shared lock-capable cache store if deploying multiple application servers.

Staff status updates use the same slot lock. The API returns HTTP 409 if an update would reclaim an occupied slot or reopen a completed visit. The clinic must resolve the conflict rather than repeatedly approving that appointment. A lock timeout on the booking form returns an error while preserving the entered details.

The clinic sync now stores the guardian and child intake fields and pre-fills the patient registration form. Reported allergies and health notes are shown for staff review; they are not automatically converted into clinician-confirmed medical alerts. The clinic migration `2026_09_24_000010_add_intake_to_online_appointments` must be applied before using this sync version.

Successful bookings retain the existing Approved status. Confirmation details are accessible in the creating browser session; other visitors must use their reference and booking mobile number on the status page. Appointment changes and cancellations are handled by clinic staff through the existing clinic workflow. Print controls use the browser print dialog.

## Contact inquiries

Run `php artisan migrate` to create the inquiries table. The contact form validates and stores inquiries, returns an inquiry reference, and does not send email or SMS. To view inquiries on the server:

```powershell
php artisan clinic:inquiries
php artisan clinic:inquiries --limit=50
```

Inquiries are not yet displayed inside the separate clinic-system dashboard. The website tells visitors to call for prompt follow-up.

## Verification

Run `php artisan test` for page rendering, booking and duplicate-slot validation, appointment access checks, and contact inquiry storage. Tests use an isolated in-memory SQLite database.
