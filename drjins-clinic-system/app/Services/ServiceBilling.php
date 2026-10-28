<?php

namespace App\Services;

use App\Models\Charge;
use App\Models\Consultation;
use App\Models\Immunization;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ServiceBilling
{
    // Called within the clinical record transaction, with the patient locked.
    public function consultation(Consultation $visit): void
    {
        $active = Consultation::where('patient_id', $visit->patient_id)->where('visit_date', $visit->visit_date)->whereNull('voided_at')->exists();
        $this->sync('consultation:'.$visit->patient_id.':'.$visit->visit_date, $visit->patient_id, $visit->visit_date, 'CONSULTATION FEE', $active, 50000);
    }

    public function vaccination(Immunization $record): void
    {
        $this->sync('vaccination:'.$record->id, $record->patient_id, $record->administered_on,
            'VACCINATION FEE - '.$record->vaccine.' ('.$record->dose.')',
            ! $record->voided_at && $record->source === 'clinic',
            \App\Models\ClinicVaccine::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($record->vaccine))])->value('price_centavos'));
    }

    private function sync(string $key, int $patientId, string $date, string $description, bool $active, ?int $price): void
    {
        $charge = Charge::where('service_key', $key)->lockForUpdate()->first();
        if (! $charge) {
            if ($active) {
                Charge::create(['service_key' => $key, 'patient_id' => $patientId, 'user_id' => auth()->id(),
                    'charged_on' => $date, 'description' => $description, 'amount_centavos' => $price ?? 0,
                    'price_pending' => $price === null, 'request_key' => (string) Str::uuid()]);
            }
            return;
        }
        if ($charge->voided_at) {
            if ($active && $charge->void_reason === 'Linked service was voided or marked as an external vaccination.') {
                $charge->update(['voided_at' => null, 'void_reason' => null, 'description' => $description,
                    'charged_on' => $date, 'amount_centavos' => $price ?? 0, 'price_pending' => $price === null]);
            }
            return;
        }
        if (! $active || $charge->description !== $description || $charge->charged_on !== $date) {
            if ($charge->paid_centavos > 0) {
                throw ValidationException::withMessages(['billing' => 'This service has payments. Resolve its billing payments before changing or voiding the service.']);
            }
            if (! $active) {
                $charge->update(['voided_at' => now(), 'void_reason' => 'Linked service was voided or marked as an external vaccination.']);
            } else {
                $charge->update(['description' => $description, 'charged_on' => $date,
                    'amount_centavos' => $price ?? 0, 'price_pending' => $price === null]);
            }
        } elseif ($active && $charge->price_pending && $price !== null) {
            $charge->update(['amount_centavos' => $price, 'price_pending' => false]);
        }
    }
}
