<div class="row g-4">
@foreach(config('clinic.services') as $slug => $service)
<div class="col-md-8 col-lg-6"><article class="service-card"><span class="icon-box" aria-hidden="true">{{ $service['icon'] }}</span><h3>{{ $service['name'] }}</h3><p>{{ $service['description'] }}</p><a href="{{ route('booking.create', ['service' => $slug]) }}">Book a checkup &rarr;</a></article></div>
@endforeach
</div>
