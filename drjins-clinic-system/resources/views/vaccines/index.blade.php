@extends('layouts.app')

@section('content')
    <section class="page-header">
        <div class="container">
            <span class="eyebrow">MANAGEMENT</span>
            <h1>Vaccine management</h1>
            <p class="mb-0">Manage clinic availability, dose schedules, and prices.</p>
        </div>
    </section>

    <div class="container vaccine-management-page">
        @include('components.feedback')

        <div class="vaccine-management-note mb-4">
            <strong>Clinic vaccine list</strong>
            <span>Available vaccines appear in patient vaccination forms. Select a vaccine below to edit its schedule, price, or availability.</span>
        </div>

        <details class="card clinic-card vaccine-add-card mb-4" @if(old('adding_vaccine')) open @endif>
            <summary class="vaccine-add-summary">
                <span class="vaccine-add-icon" aria-hidden="true">+</span>
                <span><strong>Add a vaccine</strong><small>Create a vaccine and set its initial schedule and price.</small></span>
            </summary>
            <form method="POST" action="{{ route('vaccines.store') }}" class="row g-3 vaccine-add-form">
                @csrf
                <input type="hidden" name="adding_vaccine" value="1">
                <div class="col-md-6">
                    <label for="vaccine-name" class="form-label">Vaccine name</label>
                    <input id="vaccine-name" name="vaccine_name" class="form-control" maxlength="150" required value="{{ old('adding_vaccine') ? old('vaccine_name') : '' }}" placeholder="For example, BCG">
                </div>
                <div class="col-md-3">
                    <label for="vaccine-dose-count" class="form-label">Number of doses</label>
                    <select id="vaccine-dose-count" name="dose_count" class="form-select" data-dose-count-select>
                        @foreach([1, 2, 3, 4] as $count)
                            <option value="{{ $count }}" @selected(old('dose_count', 1) == $count)>{{ $count }} {{ $count === 1 ? 'dose' : 'doses' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="vaccine-dose-interval" class="form-label">Interval between doses</label>
                    <div class="input-group">
                        <input id="vaccine-dose-interval" name="dose_interval_months" type="number" min="1" max="24" class="form-control" value="{{ old('dose_interval_months') }}" placeholder="e.g. 2" data-dose-interval>
                        <span class="input-group-text">months</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="vaccine-new-price" class="form-label">Price per dose <span class="fw-normal text-muted">(optional)</span></label>
                    <div class="input-group">
                        <span class="input-group-text">PHP</span>
                        <input id="vaccine-new-price" name="price" type="number" step="0.01" min="0" max="9999999.99" class="form-control" value="{{ old('adding_vaccine') ? old('price') : '' }}" placeholder="0.00">
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="vaccine-new-availability" class="form-label">Clinic availability</label>
                    <select id="vaccine-new-availability" name="available" class="form-select">
                        <option value="0" @selected(old('available', '0') == '0')>Unavailable</option>
                        <option value="1" @selected(old('available') == '1')>Available</option>
                    </select>
                </div>
                <div class="col-md-5 d-flex align-items-end">
                    <button class="btn btn-primary">Add vaccine</button>
                </div>
            </form>
        </details>

        <section class="card clinic-card vaccine-list-card">
            <div class="card-body p-4">
                <form data-auto-filter method="GET" action="{{ route('vaccines.index') }}" class="auto-filter-bar row g-3 mb-4 align-items-end vaccine-filter-form">
                    <div class="col-md-7">
                        <label for="vaccine-search" class="form-label">Search vaccines</label>
                        <input id="vaccine-search" name="search" class="form-control" value="{{ $search }}" maxlength="150" placeholder="Search by vaccine name">
                    </div>
                    <div class="col-md-3">
                        <label for="vaccine-filter" class="form-label">Availability</label>
                        <select id="vaccine-filter" name="availability" class="form-select">
                            @foreach(['all' => 'All vaccines', 'available' => 'Available', 'unavailable' => 'Unavailable'] as $value => $label)
                                <option value="{{ $value }}" @selected($availability === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex">
                        <a href="{{ route('vaccines.index') }}" class="btn btn-outline-secondary vaccine-reset-button">Reset filters</a>
                    </div>
                </form>

                <div class="vaccine-list-heading">
                    <div>
                        <h2 class="h5 mb-1">Clinic vaccines</h2>
                        <p class="text-muted small mb-0">{{ $vaccines->total() }} {{ $vaccines->total() === 1 ? 'vaccine' : 'vaccines' }}</p>
                    </div>
                    <span class="vaccine-list-hint">Select a row to manage its settings</span>
                </div>

                <div class="vaccine-list">
                    @forelse($vaccines as $vaccine)
                        @php
                            $editingPrice = (string) old('vaccine_price_id') === (string) $vaccine->id;
                            $editingSchedule = (string) old('vaccine_schedule_id') === (string) $vaccine->id;
                            $editingAvailability = (string) old('vaccine_availability_id') === (string) $vaccine->id;
                        @endphp
                        <details class="vaccine-item" @if($editingPrice || $editingSchedule || $editingAvailability || (string) session('open_vaccine') === (string) $vaccine->id) open @endif>
                            <summary class="vaccine-item-summary">
                                <span class="vaccine-item-name">{{ $vaccine->name }}</span>
                                <span class="vaccine-item-facts">
                                    <span>{{ $vaccine->dose_count }} {{ $vaccine->dose_count === 1 ? 'dose' : 'doses' }}</span>
                                    <span class="vaccine-fact-divider" aria-hidden="true">·</span>
                                    <span>{{ $vaccine->dose_count > 1 && $vaccine->dose_interval_months ? 'Every ' . $vaccine->dose_interval_months . ' ' . ($vaccine->dose_interval_months === 1 ? 'month' : 'months') : ($vaccine->dose_count > 1 ? 'Interval not set' : 'Single dose') }}</span>
                                    <span class="vaccine-fact-divider" aria-hidden="true">·</span>
                                    <span>{{ $vaccine->price_centavos === null ? 'Price not set' : 'PHP ' . number_format($vaccine->price_centavos / 100, 2) . ' per dose' }}</span>
                                </span>
                                <span class="badge vaccine-status {{ $vaccine->available ? 'is-available' : 'is-unavailable' }}">{{ $vaccine->available ? 'Available' : 'Unavailable' }}</span>
                                <span class="vaccine-item-action">Manage</span>
                            </summary>

                            <div class="vaccine-item-content">
                                <div class="vaccine-settings-grid">
                                    <form method="POST" action="{{ route('vaccines.update', $vaccine) }}" class="vaccine-setting-panel">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="vaccine_schedule_id" value="{{ $vaccine->id }}">
                                        <h3>Dose schedule</h3>
                                        <p>Choose how many doses are given and the interval between doses.</p>
                                        <div class="vaccine-setting-fields">
                                            <div>
                                                <label for="dose-count-{{ $vaccine->id }}" class="form-label">Number of doses</label>
                                                <select id="dose-count-{{ $vaccine->id }}" name="dose_count" class="form-select" data-dose-count-select>
                                                    @foreach([1, 2, 3, 4] as $count)
                                                        <option value="{{ $count }}" @selected(($editingSchedule ? old('dose_count') : $vaccine->dose_count) == $count)>{{ $count }} {{ $count === 1 ? 'dose' : 'doses' }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label for="dose-interval-{{ $vaccine->id }}" class="form-label">Interval between doses</label>
                                                <div class="input-group">
                                                    <input id="dose-interval-{{ $vaccine->id }}" name="dose_interval_months" type="number" min="1" max="24" class="form-control" value="{{ $editingSchedule ? old('dose_interval_months') : $vaccine->dose_interval_months }}" placeholder="e.g. 2" data-dose-interval>
                                                    <span class="input-group-text">months</span>
                                                </div>
                                            </div>
                                        </div>
                                        <button class="btn btn-outline-primary mt-3">Save schedule</button>
                                    </form>

                                    <form method="POST" action="{{ route('vaccines.update', $vaccine) }}" class="vaccine-setting-panel">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="vaccine_price_id" value="{{ $vaccine->id }}">
                                        <h3>Price per dose</h3>
                                        <p>Set the clinic price charged for each dose.</p>
                                        <label for="price-{{ $vaccine->id }}" class="form-label">Price (PHP)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">PHP</span>
                                            <input id="price-{{ $vaccine->id }}" name="price" type="number" min="0" max="9999999.99" step="0.01" class="form-control" required value="{{ $editingPrice ? old('price') : ($vaccine->price_centavos === null ? '' : number_format($vaccine->price_centavos / 100, 2, '.', '')) }}" placeholder="0.00">
                                        </div>
                                        <button class="btn btn-outline-primary mt-3" aria-label="Save price for {{ $vaccine->name }}">Save price</button>
                                    </form>

                                    <div class="vaccine-setting-panel vaccine-availability-panel">
                                        <h3>Clinic availability</h3>
                                        <p>Only available vaccines can be selected in patient records.</p>
                                        <form method="POST" action="{{ route('vaccines.update', $vaccine) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="vaccine_availability_id" value="{{ $vaccine->id }}">
                                            <input type="hidden" name="available" value="{{ $vaccine->available ? 0 : 1 }}">
                                            <button class="btn {{ $vaccine->available ? 'btn-outline-secondary' : 'btn-primary' }}">{{ $vaccine->available ? 'Mark unavailable' : 'Mark available' }}</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </details>
                    @empty
                        <div class="vaccine-empty-state">
                            <strong>No vaccines found</strong>
                            <span>Try another search or change the availability filter.</span>
                        </div>
                    @endforelse
                </div>

                {{ $vaccines->links() }}
            </div>
        </section>
    </div>

    <script>
        document.querySelectorAll('.vaccine-item').forEach(item => {
            item.addEventListener('toggle', () => {
                if (!item.open) return;
                document.querySelectorAll('.vaccine-item[open]').forEach(other => {
                    if (other !== item) other.open = false;
                });
            });
        });

        document.querySelectorAll('[data-dose-count-select]').forEach(select => {
            const group = select.closest('form');
            const interval = group?.querySelector('[data-dose-interval]');
            if (!interval) return;
            const updateInterval = () => {
                const multipleDoses = Number(select.value) > 1;
                interval.disabled = !multipleDoses;
                interval.required = multipleDoses;
                if (!multipleDoses) interval.value = '';
            };
            select.addEventListener('change', updateInterval);
            updateInterval();
        });
    </script>
@endsection
