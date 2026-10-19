<?php
namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Charge;
use App\Models\Consultation;
use App\Models\GrowthRecord;
use App\Models\Immunization;
use App\Models\OnlineAppointment;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\QueueEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PediatricOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->create());
    }

    private function patient(array $extra=[]): Patient
    {
        return Patient::create(array_merge(['patient_no'=>'PAT-'.Str::random(6),'first_name'=>'Test','last_name'=>'Child','birthdate'=>'2020-01-01','sex'=>'Female','guardian_name'=>'Guardian','contact_number'=>'09123456789','status'=>'Active'],$extra));
    }
    private function appointment(array $extra=[]): OnlineAppointment
    {
        return OnlineAppointment::create(array_merge(['appointment_no'=>'APT-'.Str::random(6),'guardian_name'=>'Guardian','child_name'=>'Test Child','contact_number'=>'09123456789','appointment_date'=>today()->toDateString(),'appointment_time'=>'10:00','status'=>'Approved'],$extra));
    }

    public function test_check_in_consultation_completes_queue_and_queues_portal_update(): void
    {
        Http::fake(['*'=>Http::response([],503)]);
        $patient=$this->patient();
        $appointment=$this->appointment();
        $this->post(route('queue.store'),['patient_id'=>$patient->id,'online_appointment_id'=>$appointment->id,'reason'=>'Checkup'])->assertSessionHasNoErrors();
        $entry=QueueEntry::firstOrFail();
        $this->assertSame($patient->id,$appointment->fresh()->patient_id);
        $this->post(route('queue.store'),['patient_id'=>$patient->id,'reason'=>'Duplicate'])->assertSessionHasErrors('patient_id');
        $this->patch(route('queue.update',$entry),['status'=>'in_consultation'])->assertSessionHasNoErrors();
        $payload=['visit_date'=>today()->toDateString(),'complaint'=>'Checkup','assessment'=>'Staff notes','request_key'=>(string)Str::uuid()];
        $this->post(route('consultations.store',$patient),$payload)->assertSessionHasNoErrors();
        $this->post(route('consultations.store',$patient),$payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('consultations',1);
        $this->assertSame('completed',$entry->fresh()->status);
        $this->assertSame('Completed',$appointment->fresh()->status);
        $this->assertTrue((bool)$appointment->fresh()->sync_pending);
        $this->patch(route('queue.update',$entry),['status'=>'waiting'])->assertSessionHasErrors('status');
    }

    public function test_walk_in_queue_rejects_wrong_patient_and_cancelled_booking(): void
    {
        $patient=$this->patient();
        $other=$this->patient();
        $appointment=$this->appointment(['patient_id'=>$other->id]);
        $this->post(route('queue.store'),['patient_id'=>$patient->id,'online_appointment_id'=>$appointment->id,'reason'=>'Checkup'])->assertSessionHasErrors('patient_id');
        $appointment->update(['patient_id'=>null,'status'=>'Cancelled']);
        $this->post(route('queue.store'),['patient_id'=>$patient->id,'online_appointment_id'=>$appointment->id,'reason'=>'Checkup'])->assertSessionHasErrors('online_appointment_id');
        $this->post(route('queue.store'),['patient_id'=>$patient->id,'reason'=>'Walk in'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('queue_entries',1);
    }

    public function test_growth_validation_and_patient_scoped_voiding(): void
    {
        $patient=$this->patient(); $other=$this->patient();
        $this->post(route('growth.store',$patient),['measured_on'=>'2019-01-01','weight_kg'=>-1,'height_cm'=>100])->assertSessionHasErrors(['measured_on','weight_kg']);
        $this->post(route('growth.store',$patient),['measured_on'=>today()->toDateString(),'weight_kg'=>'18.25','height_cm'=>'110.50','head_cm'=>50])->assertSessionHasNoErrors();
        $record=GrowthRecord::firstOrFail();
        $this->post(route('records.void',[$other,'growth',$record]),['void_reason'=>'Wrong measurement'])->assertNotFound();
        $this->post(route('records.void',[$patient,'growth',$record]),['void_reason'=>'Wrong measurement'])->assertSessionHasNoErrors();
        $this->assertNotNull($record->fresh()->voided_at);
        $this->assertDatabaseCount('growth_records',1);
        $this->assertDatabaseHas('audit_logs',['record_type'=>'GrowthRecord','record_id'=>$record->id,'action'=>'updated']);
    }

    public function test_vaccine_history_duplicate_dates_and_reminder_closure(): void
    {
        $patient=$this->patient();
        $payload=['vaccine'=>'Recorded vaccine','dose'=>'Dose 1','administered_on'=>today()->toDateString(),'source'=>'external_record','provider'=>'Recorded provider','next_due_on'=>today()->addMonth()->toDateString()];
        $this->post(route('immunizations.store',$patient),$payload+['expiry_date'=>'2019-01-01'])->assertSessionHasErrors('expiry_date');
        $this->post(route('immunizations.store',$patient),$payload)->assertSessionHasNoErrors();
        $this->post(route('immunizations.store',$patient),$payload)->assertSessionHasErrors('vaccine');
        $record=Immunization::firstOrFail();
        $this->get(route('reminders.index',['through'=>today()->addMonths(2)->toDateString()]))->assertOk()->assertSee('Recorded vaccine');
        $this->post(route('reminders.close',['immunization',$record]),['outcome'=>'Family arranged next visit'])->assertSessionHasNoErrors();
        $this->assertNotNull($record->fresh()->reminder_closed_at);
        $this->get(route('reminders.index',['through'=>today()->addMonths(2)->toDateString()]))->assertDontSee('Recorded vaccine');
    }

    public function test_money_is_exact_payments_are_idempotent_and_overpayment_is_rejected(): void
    {
        $patient=$this->patient();
        $payload=['description'=>'Consultation fee','charged_on'=>today()->toDateString(),'amount'=>'500.10','request_key'=>(string)Str::uuid()];
        $this->post(route('charges.store',$patient),$payload)->assertSessionHasNoErrors();
        $this->post(route('charges.store',$patient),$payload)->assertSessionHasNoErrors();
        $charge=Charge::firstOrFail();
        $this->assertSame(50010,$charge->amount_centavos);
        $this->assertDatabaseCount('charges',1);
        $payment=['paid_on'=>today()->toDateString(),'amount'=>'200.05','method'=>'Cash','request_key'=>(string)Str::uuid()];
        $this->post(route('payments.store',$charge),$payment)->assertSessionHasNoErrors();
        $this->post(route('payments.store',$charge),$payment)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('payments',1);
        $this->assertSame(30005,$charge->fresh()->balance_centavos);
        $this->post(route('payments.store',$charge),array_merge($payment,['amount'=>'300.06','request_key'=>(string)Str::uuid()]))->assertSessionHasErrors('amount');
        $this->post(route('billing.void',['charge',$charge]),['void_reason'=>'Correction requested'])->assertSessionHasErrors('void_reason');
        $this->post(route('billing.void',['payment',Payment::firstOrFail()]),['void_reason'=>'Incorrect payment entry'])->assertSessionHasNoErrors();
        $this->assertSame(50010,$charge->fresh()->balance_centavos);
        $this->post(route('billing.void',['charge',$charge]),['void_reason'=>'Incorrect charge entry'])->assertSessionHasNoErrors();
        $this->assertSame(0,$charge->fresh()->balance_centavos);
        $this->get(route('reports.index'))->assertOk()->assertSee('PHP 0.00');
    }

    public function test_non_cash_payment_requires_reference_and_zero_amount_is_invalid(): void
    {
        $patient=$this->patient();
        $this->post(route('charges.store',$patient),['description'=>'Fee','charged_on'=>today()->toDateString(),'amount'=>'0','request_key'=>(string)Str::uuid()])->assertSessionHasErrors('amount');
        $charge=Charge::create(['patient_id'=>$patient->id,'user_id'=>auth()->id(),'description'=>'Fee','charged_on'=>today()->toDateString(),'amount_centavos'=>50000,'request_key'=>(string)Str::uuid()]);
        $this->post(route('payments.store',$charge),['paid_on'=>today()->toDateString(),'amount'=>'10.00','method'=>'GCash','request_key'=>(string)Str::uuid()])->assertSessionHasErrors('reference');
    }

    public function test_patient_registration_does_not_complete_booking_or_duplicate_linked_patient(): void
    {
        $patient=$this->patient();
        $appointment=$this->appointment(['patient_id'=>$patient->id]);
        $payload=['appointment_no'=>$appointment->appointment_no,'first_name'=>'Test','last_name'=>'Child','guardian_name'=>'Guardian','contact_number'=>'09123456789','birthdate'=>'2020-01-01','sex'=>'Female','appointment_status'=>'register'];
        $this->post(route('appointments.savePatientDetails'),$payload)->assertSessionHasNoErrors()->assertRedirect(route('patients.show',$patient));
        $this->assertDatabaseCount('patients',1);
        $this->assertSame('Approved',$appointment->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_all_operations_pages_render_and_alerts_are_visible_on_each_patient_tab(): void
    {
        $patient=$this->patient();
        $this->post(route('alerts.store',$patient),['category'=>'Allergy','description'=>'Recorded allergy','reaction'=>'Recorded reaction'])->assertSessionHasNoErrors();
        foreach(['consultations','growth','immunizations','alerts','billing'] as $tab) {
            $this->get(route('patients.show',[$patient,'tab'=>$tab]))->assertOk()->assertSee('Recorded allergy');
        }
        foreach(['/queue','/billing','/follow-ups','/reports','/activity','/dashboard'] as $url) $this->get($url)->assertOk();
        $this->assertSame('2020-01-01',$patient->birthdate);
        $log=AuditLog::where('record_type','MedicalAlert')->firstOrFail();
        $this->assertStringNotContainsString('Recorded allergy',json_encode($log->changed_fields));
    }

    public function test_guest_cannot_read_operational_pages(): void
    {
        auth()->logout();
        foreach(['/queue','/billing','/follow-ups','/reports','/activity'] as $url) $this->get($url)->assertRedirect('/login');
    }
}
