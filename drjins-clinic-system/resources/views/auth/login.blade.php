<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Staff Sign In | Dr. Jn's Children's Clinic</title>
    <link href="{{ asset('css/login.css') }}" rel="stylesheet">
</head>
<body>
<main class="login-shell">
    <section class="login-panel">
        <div class="login-card">
            <div class="brand">
                <span class="brand-mark" aria-hidden="true">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none">
                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                </span>
                <span>
                    Dr. Jn's Children's Clinic
                    <small>Clinic Management System</small>
                </span>
            </div>

            <h1>Welcome back</h1>
            <p class="intro">Sign in with your authorized staff account to access clinic records and appointments.</p>

            @if (session('status'))
                <div class="notice" role="status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login.store') }}">
                @csrf

                <div class="field">
                    <label for="email">Email address</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="username"
                        autofocus
                        required
                        class="@error('email') invalid @enderror"
                        aria-describedby="email-error"
                    >
                    @error('email')
                        <p class="error" id="email-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="@error('password') invalid @enderror"
                        aria-describedby="password-error"
                    >
                    @error('password')
                        <p class="error" id="password-error">{{ $message }}</p>
                    @enderror
                </div>

                <label class="remember">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
                    Keep me signed in on this device
                </label>

                <button class="submit" type="submit">Sign in securely</button>
            </form>

            <div class="privacy-note">
                <span aria-hidden="true">&#128274;</span>
                <span>This is a private system for authorized clinic personnel. Activity may be recorded for security and patient-data protection.</span>
            </div>
        </div>
    </section>

    <aside class="clinic-panel" aria-label="Private clinic system information">
        <div class="clinic-message">
            <div class="private-badge"><span aria-hidden="true">&#9679;</span> Authorized staff only</div>
            <h2>Care starts with information you can trust.</h2>
            <p>One secure workspace for managing appointments, patient records, and the clinic's daily operations.</p>
        </div>
    </aside>
</main>
</body>
</html>

