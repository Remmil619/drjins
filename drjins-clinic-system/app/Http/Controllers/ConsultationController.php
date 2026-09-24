<?php
namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Consultation;
use App\Models\QueueEntry;
use App\Models\OnlineAppointment;
use App\Services\BookingSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsultationController extends Controller
{
    public function store(Request $request, Patient $patient)
    {
        $values=$request->validate([
            'visit_date'=>['required','date_format:Y-m-d','before_or_equal:today','after_or_equal:'.$patient->birthdate],
            'complaint'=>'required|string|max:5000','assessment'=>'required|string|max:5000',
            'plan'=>'nullable|string|max:5000','prescription'=>'nullable|string|max:5000',
            'follow_up_date'=>'nullable|date_format:Y-m-d|after:visit_date',
            'request_key'=>'nullable|uuid',
        ]);
        $appointment=DB::transaction(function() use($patient,$values,$request) {
            Patient::whereKey($patient->id)->lockForUpdate()->firstOrFail();
            if (!empty($values['request_key'])) {
                $existing=Consultation::where('request_key',$values['request_key'])->first();
                if ($existing) {
                    $same = $existing->patient_id === $patient->id && !$existing->voided_at;
                    foreach (['visit_date', 'complaint', 'assessment', 'plan', 'prescription', 'follow_up_date'] as $field) {
                        $same = $same && $existing->$field === ($values[$field] ?? null);
                    }
                    if (!$same) throw ValidationException::withMessages(['request_key'=>'This form was already submitted with different details or was voided. Reload the patient record before adding a new consultation.']);
                    return null;
                }
            }
            $queue=QueueEntry::where('patient_id',$patient->id)->whereDate('visit_date',$values['visit_date'])->whereIn('status',['waiting','in_consultation'])->lockForUpdate()->first();
            $patient->consultations()->create($values+['user_id'=>$request->user()->id,'queue_entry_id'=>$queue?->id]);
            if (!$queue) return null;
            $queue->update(['status'=>'completed','completed_at'=>now()]);
            if (!$queue->online_appointment_id) return null;
            $booking=OnlineAppointment::whereKey($queue->online_appointment_id)->lockForUpdate()->firstOrFail();
            $booking->update(['status'=>'Completed','staff_response'=>'Your clinic consultation has been completed.','responded_at'=>now(),'sync_pending'=>true]);
            return $booking;
        });
        $message='Consultation saved.';
        if ($appointment && !app(BookingSync::class)->push($appointment)) $message.=' Portal status is queued for retry.';
        return redirect()->route('patients.show',[$patient,'tab'=>'consultations'])->with('success',$message);
    }
}
