<x-landing::site-layout :json-ld="$jsonLd">
    <main>
        <section class="hero" aria-labelledby="hero-title">
            <p class="kicker"><span class="mono">POST {{ $apiPrefix }}/natal-chart</span></p>
            <hr class="hero-rule" aria-hidden="true">
            <h1 id="hero-title">The sky, computed.</h1>
            <hr class="hero-rule" aria-hidden="true">
            <p class="lede">Swiss Ephemeris as a JSON API. Natal charts, houses, eclipses — positions to the arcsecond.</p>
            <p class="hero-cta">
                <a href="{{ url('/#playground') }}" data-select="natal-chart">Ask the sky</a>
                <span aria-hidden="true">·</span>
                <a href="{{ url('/#endpoints') }}">Read the API</a>
            </p>
            <p class="dateline" aria-label="Product facts">
                Swiss Ephemeris 2.10
                <span aria-hidden="true">·</span>
                1800–2399 CE
                <span aria-hidden="true">·</span>
                Astrodienst-verified
                <span aria-hidden="true">·</span>
                9 endpoints
            </p>
        </section>

        <section class="instruments" aria-labelledby="instruments-title">
            <div class="section-head">
                <p class="eyebrow">Capabilities</p>
                <h2 id="instruments-title">Three instruments. One engine.</h2>
            </div>
            <div class="instrument-grid">
                <a class="plate" href="{{ url('/#playground') }}" data-select="natal-chart">
                    <p class="mono plate-path">POST {{ $apiPrefix }}/natal-chart</p>
                    <h3>Natal chart</h3>
                    <p>Thirteen bodies, houses, declinations, and pairwise aspects in one envelope.</p>
                </a>
                <a class="plate" href="{{ url('/#playground') }}" data-select="houses">
                    <p class="mono plate-path">POST {{ $apiPrefix }}/houses</p>
                    <h3>Houses &amp; ayanamsa</h3>
                    <p>Placidus through Whole Sign, plus sidereal modes from Lahiri to Fagan–Bradley.</p>
                </a>
                <a class="plate" href="{{ url('/#playground') }}" data-select="eclipses-solar">
                    <p class="mono plate-path">POST {{ $apiPrefix }}/eclipses/*</p>
                    <h3>Eclipses</h3>
                    <p>Search forward or back from any UTC moment for solar and lunar maxima.</p>
                </a>
            </div>
        </section>

        <section class="playground" id="playground" aria-labelledby="playground-title">
            <div class="section-head">
                <p class="eyebrow">Playground</p>
                <h2 id="playground-title">Ask the sky.</h2>
                <p class="section-copy">Nine live <span class="mono">POST</span>s under <span class="mono">{{ $apiPrefix }}</span>. Same origin, same envelope. Edit the JSON and run it.</p>
            </div>
            <div class="play">
                <div class="play-tabs" role="tablist" aria-label="API endpoints">
                    @foreach ($endpoints as $endpoint)
                        <button
                            type="button"
                            class="play-tab{{ $endpoint->id === 'natal-chart' ? ' is-active' : '' }}"
                            role="tab"
                            id="tab-{{ $endpoint->id }}"
                            aria-selected="{{ $endpoint->id === 'natal-chart' ? 'true' : 'false' }}"
                            aria-controls="play-stage"
                            data-endpoint="{{ $endpoint->id }}"
                        >
                            <span class="play-tab-title">{{ $endpoint->title }}</span>
                            <span class="mono play-tab-path">{{ $endpoint->publicPath() }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="play-stage" id="play-stage" role="tabpanel" aria-labelledby="tab-natal-chart">
                    <p class="play-summary" id="play-summary"></p>
                    <div class="play-split">
                        <div class="play-pane">
                            <div class="pane-head">
                                <span class="mono">Request</span>
                                <button type="button" class="btn-text" id="copy-curl">Copy curl</button>
                            </div>
                            <label class="sr-only" for="play-body">JSON request body</label>
                            <textarea id="play-body" class="mono" spellcheck="false"></textarea>
                            <button type="button" class="btn btn-primary play-run" id="play-run">Run request</button>
                        </div>
                        <div class="play-pane">
                            <div class="pane-head">
                                <span class="mono">Response</span>
                                <span class="mono play-status" id="play-status">idle</span>
                            </div>
                            <pre class="mono play-out" id="play-out" tabindex="0">{
  "ok": true,
  "data": {}
}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="docs" id="docs" aria-labelledby="docs-title">
            <div class="section-head">
                <p class="eyebrow">Accuracy</p>
                <h2 id="docs-title">Arcsecond parity with Astrodienst.</h2>
                <p class="section-copy">
                    Moments are Universal Time. There is no timezone conversion — pass wall-clock UT, or convert local birth time first.
                    Julian Day is UT (not TT); a ~1-minute delta-T offset versus some printed sheets does not move the positions.
                    Ephemeris files cover 1800–2399 CE. Envelope: <span class="mono">{ ok, data }</span> or <span class="mono">{ ok, error }</span> at HTTP 422.
                    Call <span class="mono">POST {{ $apiPrefix }}/…</span>.
                </p>
            </div>
            <div class="almanac" role="table" aria-label="Verified natal sample">
                <div class="almanac-head" role="row">
                    <span role="columnheader">Body</span>
                    <span role="columnheader">Longitude</span>
                    <span role="columnheader">House</span>
                    <span role="columnheader">Declination</span>
                </div>
                <div role="row"><span>Sun</span><span class="mono">Pisces 12°29′35″</span><span class="mono">11</span><span class="mono">6°52′21″ S</span></div>
                <div role="row"><span>Moon</span><span class="mono">Scorpio 24°31′55″</span><span class="mono">6</span><span class="mono">—</span></div>
                <div role="row"><span>Asc</span><span class="mono">—</span><span class="mono">1</span><span class="mono">21°20′14″ N</span></div>
                <div role="row"><span>MC</span><span class="mono">—</span><span class="mono">10</span><span class="mono">18°28′15″ S</span></div>
                <p class="almanac-note mono">1994-03-03T07:35 UT · 46n55 / 35e00 · Placidus · Sid.Time 20:38:25</p>
            </div>
        </section>

        <section class="endpoints" id="endpoints" aria-labelledby="endpoints-title">
            <div class="section-head">
                <p class="eyebrow">Reference</p>
                <h2 id="endpoints-title">Nine endpoints. All POST.</h2>
                <p class="section-copy">Every route lives under <span class="mono">{{ $apiPrefix }}</span>.</p>
            </div>
            <div class="endpoint-table" role="table" aria-label="API endpoint index">
                <div class="endpoint-row endpoint-row-head" role="row">
                    <span role="columnheader">Path</span>
                    <span role="columnheader">Returns</span>
                </div>
                @foreach ($endpoints as $endpoint)
                    <a class="endpoint-row" role="row" href="{{ url('/#playground') }}" data-select="{{ $endpoint->id }}">
                        <span class="mono" role="cell">POST {{ $endpoint->publicPath() }}</span>
                        <span role="cell">{{ $endpoint->summary }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    </main>

    <x-slot:scripts>
        <script type="application/json" id="landing-data">@json($landingData)</script>
    </x-slot:scripts>
</x-landing::site-layout>
