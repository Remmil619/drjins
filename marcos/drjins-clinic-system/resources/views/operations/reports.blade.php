@extends('layouts.app')
@section('content')
<section class="page-header"><div class="container"><span class="eyebrow">CLINIC OPERATIONS</span><h1>Reports</h1><p class="mb-0">Activity and collections from records entered in the clinic system.</p></div></section>
<div class="container">@include('components.feedback')
<form class="d-flex flex-wrap gap-3 align-items-end mb-4" method="GET"><div><label for="from" class="form-label">From</label><input id="from" type="date" name="from" class="form-control" value="{{ $from }}"></div><div><label for="to" class="form-label">Through</label><input id="to" type="date" name="to" class="form-control" value="{{ $to }}"></div><button class="btn btn-primary">Generate</button><button type="button" class="btn btn-outline-primary" onclick="window.print()">Print report</button></form>
<p class="small text-muted">Period: {{ $from }} to {{ $to }}. Voided entries are excluded. Collections use payment dates and may relate to older charges.</p>
<div class="row g-3">@foreach(['Consultations recorded'=>$consultations,'Vaccines given at clinic'=>$vaccines,'Checked-in visits'=>$visits->sum(),'Completed visits'=>$visits['completed']??0] as $label=>$value)<div class="col-md-3 col-6"><div class="card clinic-card h-100"><div class="card-body p-4"><p class="small text-muted">{{ $label }}</p><div class="metric-value">{{ $value }}</div></div></div></div>@endforeach</div>
<div class="row g-3 mt-1">@foreach(['Charges in period'=>$charged,'Collections in period'=>$collected,'Current outstanding · all dates'=>$outstanding] as $label=>$value)<div class="col-md-4"><div class="card clinic-card"><div class="card-body p-4"><p class="small text-muted">{{ $label }}</p><strong class="h4">PHP {{ number_format($value/100,2) }}</strong></div></div></div>@endforeach</div>
<div class="card clinic-card mt-4"><div class="card-body p-4"><h2 class="h5">Queue outcomes</h2>@forelse($visits as $status=>$count)<p>{{ ucfirst(str_replace('_',' ',$status)) }}: <strong>{{ $count }}</strong></p>@empty<p class="text-muted mb-0">No queue entries for this period.</p>@endforelse</div></div>
</div>
@endsection
