<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Meal Master') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=ibm-plex-sans:400,500,600|source-serif-4:600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="mm-auth antialiased">
    <div class="mm-auth__shell">
        <aside class="mm-auth__stage" aria-hidden="true">
            <img
                class="mm-auth__stage-image"
                src="https://images.unsplash.com/photo-1556761175-5973dc0f32e7?auto=format&fit=crop&w=1600&q=80"
                alt=""
                width="1600"
                height="2000"
            >
            <div class="mm-auth__stage-veil"></div>
            <div class="mm-auth__stage-copy">
                <p class="mm-auth__stage-brand">Meal Master</p>
                <p class="mm-auth__stage-line">Secure access for monitoring, evaluation, and learning teams.</p>
            </div>
        </aside>

        <main class="mm-auth__panel">
            <div class="mm-auth__panel-inner">
                <a href="{{ url('/') }}" class="mm-brand-mark mm-brand-mark--compact">
                    <span class="mm-brand-mark__glyph" aria-hidden="true"></span>
                    <span class="mm-brand-mark__text">Meal Master</span>
                </a>

                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
