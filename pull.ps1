
# Stop if any command fails
$ErrorActionPreference = "Stop"

Write-Host "=== Pulling latest GitHub updates ===" -ForegroundColor Cyan
git pull

Write-Host "`n=== Updating Dr. JN's Booking Portal ===" -ForegroundColor Yellow
Set-Location "drjins-booking-portal"

php artisan migrate:fresh --seed
php artisan route:clear
php artisan route:cache

Write-Host "`n=== Updating Dr. JN's Clinic System ===" -ForegroundColor Yellow
Set-Location "../drjins-clinic-system"

php artisan migrate:fresh --seed
php artisan route:clear
php artisan route:cache

Write-Host "`n=== ALL SYSTEMS UPDATED SUCCESSFULLY ===" -ForegroundColor Green
