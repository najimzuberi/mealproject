<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Meal Master') }} — Organizational MEAL Platform</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600|source-serif-4:600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="mm-landing antialiased">
    <section class="mm-hero" aria-label="Meal Master introduction">
        <div class="mm-hero__media" aria-hidden="true">
            <img
                class="mm-hero__image"
                src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=2400&q=80"
                alt=""
                width="2400"
                height="1600"
            >
            <div class="mm-hero__veil"></div>
        </div>

        <header class="mm-hero__nav">
            <a href="{{ url('/') }}" class="mm-brand-mark" aria-label="Meal Master home">
                <span class="mm-brand-mark__glyph" aria-hidden="true"></span>
                <span class="mm-brand-mark__text">Meal Master</span>
            </a>
        </header>

        <div class="mm-hero__content">
            <p class="mm-hero__brand">Meal Master</p>
            <h1 class="mm-hero__headline">Evidence that organizations can trust.</h1>
            <p class="mm-hero__lede">
                One secure platform from strategy to verified results — private by organization, governed by role, ready for accountable MEAL work.
            </p>
            <div class="mm-hero__actions">
                @auth
                    <a href="{{ url('/dashboard') }}" class="mm-btn mm-btn--primary">Open dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="mm-btn mm-btn--primary">Sign in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="mm-btn mm-btn--ghost">Create account</a>
                    @endif
                @endauth
            </div>
        </div>
    </section>
</body>
</html>
