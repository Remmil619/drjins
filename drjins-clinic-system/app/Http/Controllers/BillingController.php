<?php
namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Charge;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillingController extends Controller
{
    private function centavos(string $amount): int
    {
        [$whole,$fraction] = array_pad(explode('.',$amount,2),2,'0');
        $value = ((int)$whole * 100) + (int)str_pad($fraction,2,'0');
        if ($value < 1) throw ValidationException::withMessages(['amount'=>'Amount must be greater than zero.']);
        return $value;
    }
    public function index(Request $request)
    {
        $filters=$request->validate(['search'=>'nullable|string|max:100','status'=>'nullable|in:all,unpaid,paid,void']);
        $search=$filters['search'] ?? '';
        $status=$filters['status'] ?? 'unpaid';
        $charges=Charge::with(['patient','payments'])->withSum(['payments as collected'=>fn($q)=>$q->whereNull('voided_at')],'amount_centavos')
            ->when($search,fn($q)=>$q->whereHas('patient',fn($p)=>$p->where('patient_no','like',"%$search%")->orWhere('first_name','like',"%$search%")->orWhere('last_name','like',"%$search%")))
            ->when($status==='void',fn($q)=>$q->whereNotNull('voided_at'))
            ->when(in_array($status,['unpaid','paid']),fn($q)=>$q->whereNull('voided_at')->whereRaw('amount_centavos '.($status==='paid'?'=':'>').' (select coalesce(sum(payments.amount_centavos),0) from payments where payments.charge_id = charges.id and payments.voided_at is null)'))
            ->latest()->paginate(20)->withQueryString();
        return view('billing.index',compact('charges','search','status'));
    }
    public function charge(Request $request, Patient $patient)
    {
        $data=$request->validate(['description'=>'required|string|max:255','charged_on'=>['required','date_format:Y-m-d','before_or_equal:today','after_or_equal:'.$patient->birthdate],'amount'=>['required','regex:/^\d{1,7}(\.\d{1,2})?$/'],'request_key'=>'required|uuid']);
        $amount=$this->centavos($data['amount']);
        DB::transaction(function() use($patient,$request,$data,$amount) {
            Patient::whereKey($patient->id)->lockForUpdate()->firstOrFail();
            $existing=Charge::where('request_key',$data['request_key'])->first();
            if ($existing) {
                if ($existing->voided_at || $existing->patient_id !== $patient->id || $existing->amount_centavos !== $amount || $existing->description !== $data['description'] || $existing->charged_on !== $data['charged_on']) throw ValidationException::withMessages(['request_key'=>'This form was already submitted with different details or was voided. Reload the page.']);
                return;
            }
            Charge::create(collect($data)->except('amount')->all()+['patient_id'=>$patient->id,'user_id'=>$request->user()->id,'amount_centavos'=>$amount]);
        });
        return back()->with('success','Charge saved.');
    }
    public function payment(Request $request, Charge $charge)
    {
        $data=$request->validate(['paid_on'=>'required|date_format:Y-m-d|before_or_equal:today|after_or_equal:'.$charge->charged_on,'amount'=>['required','regex:/^\d{1,7}(\.\d{1,2})?$/'],'method'=>'required|in:Cash,GCash,Bank transfer,Card','reference'=>'nullable|required_unless:method,Cash|string|max:150','request_key'=>'required|uuid']);
        $amount=$this->centavos($data['amount']);
        DB::transaction(function() use($charge,$data,$amount,$request) {
            $charge=Charge::whereKey($charge->id)->lockForUpdate()->firstOrFail();
            $existing=Payment::where('request_key',$data['request_key'])->first();
            if ($existing) {
                if ($existing->voided_at || $existing->charge_id !== $charge->id || $existing->amount_centavos !== $amount || $existing->method !== $data['method'] || $existing->paid_on !== $data['paid_on'] || $existing->reference !== ($data['reference'] ?? null)) throw ValidationException::withMessages(['request_key'=>'This payment form was already submitted with different details or was voided. Reload the page.']);
                return;
            }
            if ($charge->voided_at || $amount > $charge->balance_centavos) throw ValidationException::withMessages(['amount'=>'Payment exceeds the outstanding balance or the charge was voided.']);
            Payment::create(collect($data)->except('amount')->all()+['charge_id'=>$charge->id,'user_id'=>$request->user()->id,'amount_centavos'=>$amount]);
        });
        return back()->with('success','Payment recorded.');
    }
    public function void(Request $request, string $type, int $record)
    {
        $data=$request->validate(['void_reason'=>'required|string|min:5|max:500']);
        abort_unless(in_array($type,['charge','payment']),404);
        DB::transaction(function() use($type,$record,$data) {
            $entry=($type==='charge'?Charge::class:Payment::class)::findOrFail($record);
            $charge=Charge::whereKey($type==='charge'?$entry->id:$entry->charge_id)->lockForUpdate()->firstOrFail();
            $entry->refresh();
            if ($entry->voided_at || ($type==='charge' && $charge->paid_centavos > 0)) throw ValidationException::withMessages(['void_reason'=>'Void active payments first, or refresh an already voided record.']);
            $entry->update($data+['voided_at'=>now()]);
        });
        return back()->with('success','Entry voided with its history retained.');
    }
}
