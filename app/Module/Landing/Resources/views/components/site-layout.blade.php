@props([
    'title' => 'vatrapi — The sky, computed.',
    'description' => 'Swiss Ephemeris as a JSON API. Natal charts, houses, eclipses — positions to the arcsecond.',
    'jsonLd' => null,
])
<!DOCTYPE html>
<html lang="en" data-theme="night">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $description }}">
    <title>{{ $title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,480;0,9..144,600;1,9..144,400;1,9..144,480&family=IBM+Plex+Mono:wght@400;500&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ route('landing.asset', ['file' => 'landing.css']) }}">
    <link rel="license" href="{{ route('landing.license') }}">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Ccircle cx='16' cy='16' r='13' fill='none' stroke='%23C9A36A' stroke-width='1.4'/%3E%3Cpath d='M6 16h20M16 6v20' stroke='%23C9A36A' stroke-width='.6' opacity='.5'/%3E%3Cpath d='M8 16a8 8 0 0 1 16 0' fill='none' stroke='%23C9A36A' stroke-width='1.2'/%3E%3C/svg%3E">
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('vatrapi-theme');
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
    <div class="grain" aria-hidden="true"></div>

    <header class="nav">
        <div class="nav-inner">
            <a class="wordmark" href="{{ url('/#top') }}" aria-label="vatrapi">
                <svg class="mark" viewBox="0 0 32 32" aria-hidden="true">
                    <circle cx="16" cy="16" r="13" fill="none" stroke="currentColor" stroke-width="1.4"/>
                    <path d="M6 16h20M16 6v20" stroke="currentColor" stroke-width=".6" opacity=".45"/>
                    <path d="M8 16a8 8 0 0 1 16 0" fill="none" stroke="currentColor" stroke-width="1.2"/>
                </svg>
                <x-landing::wordmark-name />
            </a>
            <nav class="nav-links" aria-label="Primary">
                <a href="{{ url('/#playground') }}">Playground</a>
                <a href="{{ url('/#endpoints') }}">API</a>
                <a href="{{ url('/#docs') }}">Docs</a>
                <a href="{{ route('landing.source') }}">Source</a>
            </nav>
            <div class="nav-actions">
                <button type="button" class="theme-toggle" id="theme-toggle" aria-pressed="false" aria-label="Switch to day theme">
                    <span class="theme-toggle-night" aria-hidden="true">Night</span>
                    <span class="theme-toggle-day" aria-hidden="true">Day</span>
                </button>
                <a class="btn btn-compact" href="{{ url('/#playground') }}" data-select="natal-chart">Ask the sky</a>
            </div>
        </div>
    </header>

    {{ $slot }}

    <footer class="footer">
        <div class="footer-inner">
            <a class="wordmark" href="{{ url('/#top') }}" aria-label="vatrapi">
                <svg class="mark" viewBox="0 0 32 32" aria-hidden="true">
                    <circle cx="16" cy="16" r="13" fill="none" stroke="currentColor" stroke-width="1.4"/>
                    <path d="M8 16a8 8 0 0 1 16 0" fill="none" stroke="currentColor" stroke-width="1.2"/>
                </svg>
                <x-landing::wordmark-name />
            </a>
            <p>Free software under the GNU Affero GPL v3. Swiss Ephemeris 2.10. Times are UTC.</p>
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
