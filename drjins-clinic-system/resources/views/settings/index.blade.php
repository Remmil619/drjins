@extends('layouts.app')

@section('content')
<section class="page-header"><div class="container"><span class="eyebrow">SYSTEM SETTINGS</span><h1>Theme</h1><p class="mb-0">Choose the color theme used across the clinic workspace.</p></div></section>
<div class="container">
    @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
    <form method="POST" action="{{ route('settings.theme') }}" class="card clinic-card p-4" style="max-width: 850px">@csrf @method('PATCH')
        <fieldset><legend class="h5 mb-3">System color theme</legend><div class="row g-3">
            @foreach(['green' => ['Clinic Green', '#183d43', '#147d78'], 'blue' => ['Calm Blue', '#18334f', '#3478a5'], 'violet' => ['Soft Violet', '#342b50', '#7655a6']] as $value => [$label, $sidebar, $accent])
                <div class="col-md-4"><label class="theme-choice w-100"><input type="radio" name="theme" value="{{ $value }}" @checked($theme === $value)><span class="theme-preview" style="--preview-sidebar:{{ $sidebar }};--preview-accent:{{ $accent }}"><i></i><b></b><em></em></span><strong>{{ $label }}</strong></label></div>
            @endforeach
        </div></fieldset><button class="btn btn-primary mt-4">Save theme</button>
    </form>
</div>
@endsection
