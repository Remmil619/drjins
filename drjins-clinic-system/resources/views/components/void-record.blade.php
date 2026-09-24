@if($record->voided_at)
<div class="small text-danger mt-2">Voided: {{ $record->void_reason }}</div>
@else
<details class="mt-3 correction-action"><summary class="small text-muted">Correct or void this entry</summary>
<form method="POST" action="{{ route('records.void',[$patient,$type,$record->id]) }}" class="d-flex flex-wrap gap-2 mt-2">@csrf
<label class="visually-hidden" for="void-{{ $type }}-{{ $record->id }}">Reason</label><input id="void-{{ $type }}-{{ $record->id }}" name="void_reason" class="form-control" placeholder="Reason for voiding (original stays in history)" required minlength="5" maxlength="500"><button class="btn btn-outline-danger btn-sm">Void entry</button>
</form></details>
@endif
