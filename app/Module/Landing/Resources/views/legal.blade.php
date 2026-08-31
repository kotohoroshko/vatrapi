<x-landing::site-layout
    :title="$title"
    description="Legal text for vatrapi."
>
    <main class="legal">
        <p class="kicker"><span class="mono">AGPL-3.0</span></p>
        <h1>{{ $heading }}</h1>
        <p class="lede">{{ $lede }}</p>
        <p>
            <a href="{{ route('landing.source') }}">Corresponding Source</a>
            <span aria-hidden="true"> · </span>
            <a rel="license" href="{{ route('landing.license') }}">License</a>
            <span aria-hidden="true"> · </span>
            <a href="{{ route('landing.notice') }}">Notice</a>
        </p>
        <pre class="legal-doc mono">{{ $plaintext }}</pre>
    </main>
</x-landing::site-layout>
