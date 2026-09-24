# Dr. Jn's Clinic System

Laravel staff workspace connected to the adjacent drjins-booking-portal application.

## Available workflows

- Authenticated dashboard and appointment monitoring.
- Portal appointments imported by reference; approve, decline, cancel, and request follow-up.
- Patient registration, search, editing, and appointment import with required demographics.
- Consultation history, staff attribution, care plans, prescription notes, follow-up dates, and printable patient records.
- Status changes saved locally before delivery. Failed deliveries remain pending and retry on synchronization.
- Portal-matched teal/mint styling with local Bootstrap assets.

## Local setup

Requires PHP 8.3+ and Composer dependencies. Run composer install, php artisan migrate, then php artisan serve --port=8000. Run the booking portal separately on port 8001.

Configure the clinic .env with APP_TIMEZONE=Asia/Manila, BOOKING_API_URL=http://127.0.0.1:8001/api, and BOOKING_API_TOKEN matching the portal's token. Keep tokens server-side. Use HTTPS when hosted. Each application keeps its own database.

Appointment monitoring refreshes every 15 seconds while open. Run php artisan schedule:work to synchronize without an open browser. On hosting, invoke php artisan schedule:run every minute. Manual sync: php artisan bookings:sync.

Existing staff accounts continue to work. Do not run the default sample seeder against the existing clinic database.

## Verification

Run php artisan test and php artisan view:cache. Tests cover authentication, patient import, patient editing, consultation saving, invalid API responses, and offline status delivery/retry. Tests use an isolated in-memory SQLite database.

## Operational boundaries

The existing portal API supports reading bookings and updating status. Follow-up status does not reserve a new time slot; arrange a new booking through the portal. Consultation and prescription notes are staff-entered and remain in the clinic database. SMS/email delivery, billing, inventory, and immunization modules are not included.

The migration adds a pending-sync flag and consultation records without dropping existing data. Browser visual review and production deployment checks remain necessary before rollout.
