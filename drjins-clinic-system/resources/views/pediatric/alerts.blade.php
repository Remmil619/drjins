<div class="card clinic-card"><div class="card-body p-4"><h2 class="h5">Allergies & medical alerts</h2>
@forelse($records as $record)<article class="record-entry {{ $record->voided_at?'record-void':'' }}"><strong>{{ $record->category }}: {{ $record->description }}</strong><p class="preserve-lines small mb-1">{{ $record->reaction }}</p><small class="text-muted">Recorded {{ $record->created_at->format('M j, Y g:i A') }}</small>@include('components.void-record',['type'=>'alert'])</article>
@empty<p class="text-muted py-3">No allergy assessment or medical alerts recorded.</p>@endforelse
<details class="record-form mt-3" @if($errors->any()) open @endif><summary class="fw-semibold">+ Add patient alert</summary><form method="POST" action="{{ route('alerts.store',$patient) }}" class="mt-3">@csrf
<label for="category" class="form-label">Category</label><select id="category" name="category" class="form-select mb-3">@foreach(['Allergy','Medical condition','Other'] as $category)<option @selected(old('category')===$category)>{{ $category }}</option>@endforeach</select>
<label for="description" class="form-label">Allergen / condition / alert</label><input id="description" name="description" class="form-control mb-3" maxlength="500" required value="{{ old('description') }}">
<label for="reaction" class="form-label">Reaction / details</label><textarea id="reaction" name="reaction" class="form-control" maxlength="500">{{ old('reaction') }}</textarea><button class="btn btn-primary mt-3">Save alert</button></form></details>
</div></div>
