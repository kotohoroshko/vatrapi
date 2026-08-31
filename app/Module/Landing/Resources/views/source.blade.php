<x-landing::site-layout
    title="Source — vatrapi"
    description="Download the Corresponding Source for this AGPL-3.0 service."
>
    <main class="legal">
        <p class="kicker"><span class="mono">AGPL §13</span></p>
        <h1>Corresponding Source</h1>
        <p class="lede">This service is free software under the GNU Affero General Public License v3. Anyone who uses it over a network may receive the Corresponding Source at no charge.</p>
        <div class="legal-actions">
            <a class="btn btn-primary" href="{{ route('landing.source.archive') }}">Download source archive</a>
        </div>
        @if ($sourceRepositoryUrl)
            <p>Public repository: <a href="{{ $sourceRepositoryUrl }}">{{ $sourceRepositoryUrl }}</a></p>
        @endif
        <p>
            The archive is a snapshot of this running tree: application source plus the Swiss Ephemeris C library under
            <span class="mono">Docker/sweph/</span>.
            It excludes secrets and generated directories (<span class="mono">.env</span>, <span class="mono">vendor</span>, <span class="mono">storage</span>).
        </p>
        <p>
            Also see the
            <a rel="license" href="{{ route('landing.license') }}">GNU Affero GPL</a>
            and
            <a href="{{ route('landing.notice') }}">copyright notices</a>.
        </p>
    </main>
</x-landing::site-layout>
