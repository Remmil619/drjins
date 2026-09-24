<?php
namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Charge;
use App\Models\Consultation;
use App\Models\Immunization;
use App\Models\Payment;
use App\Models\QueueEntry;
use Illuminate\Http\Request;

class OperationsController extends Controller
{
    public function reminders(Request $request)
    {
        $filters=$request->validate(['through'=>'nullable|date_format:Y-m-d']);
        $through=$filters['through'] ?? today()->addDays(30)->toDateString();
        $consultations=Consultation::with('patient')->whereNull('voided_at')->whereNull('follow_up_closed_at')->whereDate('follow_up_date','<=',$through)->reorder()->orderBy('follow_up_date')->paginate(15,['*'],'consultation_page')->withQueryString();
        $vaccines=Immunization::with('patient')->whereNull('voided_at')->whereNull('reminder_closed_at')->whereDate('next_due_on','<=',$through)->orderBy('next_due_on')->paginate(15,['*'],'vaccine_page')->withQueryString();
        return view('operations.reminders',compact('through','consultations','vaccines'));
    }
    public function reports(Request $request)
    {
        $data=$request->validate(['from'=>'nullable|date_format:Y-m-d','to'=>'nullable|date_format:Y-m-d|after_or_equal:from']);
        $from=$data['from'] ?? today()->startOfMonth()->toDateString();
        $to=$data['to'] ?? today()->toDateString();
        if ($to < $from) throw \Illuminate\Validation\ValidationException::withMessages(['to'=>'End date must be on or after start date.']);
        $visits=QueueEntry::whereBetween('visit_date',[$from,$to])->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total','status');
        $consultations=Consultation::whereNull('voided_at')->whereBetween('visit_date',[$from,$to])->count();
        $vaccines=Immunization::whereNull('voided_at')->where('source','clinic')->whereBetween('administered_on',[$from,$to])->count();
        $charged=Charge::whereNull('voided_at')->whereBetween('charged_on',[$from,$to])->sum('amount_centavos');
        $collected=Payment::whereNull('voided_at')->whereBetween('paid_on',[$from,$to])->sum('amount_centavos');
        $outstanding=Charge::whereNull('voided_at')->sum('amount_centavos')-Payment::whereNull('voided_at')->whereHas('charge',fn($q)=>$q->whereNull('voided_at'))->sum('amount_centavos');
        return view('operations.reports',compact('from','to','visits','consultations','vaccines','charged','collected','outstanding'));
    }
    public function audit(Request $request)
    {
        $request->validate(['type'=>'nullable|in:Patient,OnlineAppointment,QueueEntry,Consultation,GrowthRecord,Immunization,MedicalAlert,Charge,Payment']);
        $logs=AuditLog::with('user')->when($request->type,fn($q)=>$q->where('record_type',$request->type))->orderByDesc('id')->paginate(30)->withQueryString();
        return view('operations.audit',compact('logs'));
    }
}
