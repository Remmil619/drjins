<fieldset class="row g-3 mb-3"><legend class="h6">Address</legend>
@foreach(['barangay'=>'Brgy.', 'municipality'=>'Municipality', 'province'=>'Province'] as $field=>$label)
<div class="col-md-4"><label for="address-{{ $field }}" class="form-label">{{ $label }}</label><input id="address-{{ $field }}" name="{{ $field }}" class="form-control" maxlength="150" value="{{ old($field, $patient->$field ?? '') }}" autocomplete="{{ $field==='province'?'address-level1':($field==='municipality'?'address-level2':'address-level3') }}"></div>
@endforeach
<div class="col-12"><label for="previous-address" class="form-label">Previously recorded address</label><textarea id="previous-address" name="address" class="form-control" rows="2" readonly>{{ old('address', $patient->address ?? '') }}</textarea><small class="text-muted">Use the fields above to update the address.</small></div>
</fieldset>