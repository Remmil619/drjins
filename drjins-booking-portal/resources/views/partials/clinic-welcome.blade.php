@if(config('clinic.phone'))
<div class="modal fade clinic-welcome" id="clinicWelcome" tabindex="-1" aria-labelledby="clinicWelcomeTitle" aria-describedby="clinicWelcomeDescription" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <span class="eyebrow mb-0">LITTLE PATIENTS. BIG HEARTS.</span>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close clinic introduction"></button>
            </div>
            <div class="modal-body text-center">
                <div class="welcome-art" aria-hidden="true">
                    <span class="welcome-spark welcome-spark-one">✦</span>
                    <img src="{{ asset('images/clinic-care.svg') }}" width="280" height="180" alt="">
                    <span class="welcome-spark welcome-spark-two">♡</span>
                </div>
                <span class="welcome-greeting">Hello, families! <span class="welcome-wave" aria-hidden="true">👋</span></span>
                <h2 id="clinicWelcomeTitle">Welcome to<br>{{ config('clinic.name') }}</h2>
                <p id="clinicWelcomeDescription" class="mt-3">A caring place for your child's next checkup. We're here to help you plan your visit and answer your questions.</p>
                <div class="welcome-location">{{ config('clinic.address') }}</div>
                <div class="welcome-contact mt-4">
                    <span class="small text-muted">Let's talk about your visit</span>
                    <div class="welcome-phone mt-2">Contact number: {{ config('clinic.phone') }}</div>
                    <a class="btn btn-outline-primary w-100 mt-2" href="{{ route('booking.create') }}">Book an appointment &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
