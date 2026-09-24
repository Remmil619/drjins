<div class="row g-4">
@foreach(config('clinic.services') as $slug => $service)
<div class="col-md-4"><article class="service-card"><span class="icon-box" aria-hidden="true">{{ $service['icon'] }}</span><h3>{{ $service['name'] }}</h3>@if($service['sample'])<span class="badge text-bg-light border mb-3">Sample service</span>@endif<p>{{ $service['description'] }}</p><a href="{{ $service['sample'] ? route('contact', ['topic' => 'Service inquiry']) : route('booking.create', ['service' => $slug]) }}">{{ $service['sample'] ? 'Ask about this service' : 'Book a checkup' }} &rarr;</a></article></div>
@endforeach
</div>
