<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UppercaseText
{
    public function handle(Request $request, Closure $next)
    {
        // Only human-entered text; never change passwords, enum values, or API payloads.
        foreach (['address', 'allergies', 'barangay', 'municipality', 'province', 'child_first_name', 'child_middle_name', 'child_name', 'child_surname', 'current_medications', 'description', 'emergency_contact_name', 'first_name', 'guardian_address', 'guardian_barangay', 'guardian_first_name', 'guardian_middle_name', 'guardian_municipality', 'guardian_name', 'guardian_province', 'guardian_surname', 'last_name', 'medical_notes', 'message', 'middle_name', 'name', 'notes', 'outcome', 'reaction', 'reason', 'reason_for_visit', 'reference', 'search', 'suffix', 'vaccine_name', 'void_reason', 'complaint', 'assessment', 'prescription'] as $field) {
            $value = $request->input($field);
            if (is_string($value)) {
                $request->merge([
                    $field => mb_strtoupper($value, 'UTF-8'),
                ]);
            }
        }

        return $next($request);
    }
}
