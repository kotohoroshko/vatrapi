@props([
    'title' => 'vatrapi — Swiss Ephemeris JSON API',
    'description' => 'Swiss Ephemeris as a JSON API. Planetary positions, house cusps, natal charts, ayanamsa, fixed stars and eclipses — accurate to the arcsecond.',
    'jsonLd' => null,
])
<!DOCTYPE html>
<html lang="en" data-theme="night">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0c0c0d" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#fafaf9" media="(prefers-color-scheme: light)">
    <title>{{ $title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ route('landing.asset', ['file' => 'landing.css']) }}">
    <link rel="license" href="{{ route('landing.license') }}">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Crect width='20' height='20' rx='4' fill='%230c0c0d'/%3E%3Ccircle cx='10' cy='10' r='5.5' fill='none' stroke='%23ececee' stroke-width='1.3'/%3E%3Ccircle cx='14' cy='6.2' r='2' fill='%23ff5f33'/%3E%3C/svg%3E">
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('vatrapi-theme');
                if (!theme && window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
                    theme = 'day';
                }
                if (theme === 'day') {
                    document.documentElement.setAttribute('data-theme', 'day');
                }
            } catch (e) {}
        })();
    </script>
    @if (is_array($jsonLd))
        <script type="application/ld+json">@json($jsonLd)</script>
    @endif
</head>
<body id="top">
    <header class="nav">
        <div class="container nav-inner">
            <a class="wordmark" href="{{ url('/#top') }}" aria-label="vatrapi">
                <svg class="mark" viewBox="0 0 20 20" aria-hidden="true">
                    <circle cx="10" cy="10" r="6.5" fill="none" stroke="currentColor" stroke-width="1.4"/>
                    <circle class="mark-body" cx="14.6" cy="5.4" r="2.3"/>
                </svg>
                <x-landing::wordmark-name />
            </a>
            <nav class="nav-links" aria-label="Primary">
                <a href="{{ url('/#endpoints') }}">Endpoints</a>
                <a href="{{ url('/#playground') }}">Playground</a>
                <a href="{{ url('/#docs') }}">Accuracy</a>
                <a href="{{ route('landing.source') }}">Source</a>
            </nav>
            <div class="nav-actions">
                <button type="button" class="icon-btn" id="theme-toggle" aria-pressed="false" aria-label="Switch to light theme">
                    <svg class="icon-moon" viewBox="0 0 16 16" aria-hidden="true"><path d="M13.5 9.6A5.8 5.8 0 0 1 6.4 2.5a5.8 5.8 0 1 0 7.1 7.1Z" fill="none" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/></svg>
                    <svg class="icon-sun" viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="3" fill="none" stroke="currentColor" stroke-width="1.3"/><path d="M8 1v1.6M8 13.4V15M1 8h1.6M13.4 8H15M3 3l1.1 1.1M11.9 11.9 13 13M3 13l1.1-1.1M11.9 4.1 13 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                </button>
                <a class="btn btn-primary btn-sm" href="{{ url('/#playground') }}" data-select="natal-chart">Open playground</a>
            </div>
        </div>
    </header>

    {{ $slot }}

    <footer class="footer">
        <div class="container footer-inner">
            <a class="wordmark" href="{{ url('/#top') }}" aria-label="vatrapi">
                <svg class="mark" viewBox="0 0 20 20" aria-hidden="true">
                    <circle cx="10" cy="10" r="6.5" fill="none" stroke="currentColor" stroke-width="1.4"/>
                    <circle class="mark-body" cx="14.6" cy="5.4" r="2.3"/>
                </svg>
                <x-landing::wordmark-name />
            </a>
            <p>Free software under the GNU Affero GPL v3. Built on Swiss Ephemeris 2.10. All times are UT.</p>
            <nav aria-label="Footer">
                <a href="{{ route('landing.source') }}">Source</a>
                <a rel="license" href="{{ route('landing.license') }}">License</a>
                <a href="{{ route('landing.notice') }}">Notice</a>
            </nav>
        </div>
    </footer>

    {{ $scripts ?? '' }}
    <script src="{{ route('landing.asset', ['file' => 'landing.js']) }}" defer></script>
</body>
</html>
