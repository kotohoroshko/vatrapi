<x-landing::site-layout :json-ld="$jsonLd">
    <main>
        <section class="container hero" aria-labelledby="hero-title">
            <div class="hero-copy">
                <h1 id="hero-title">Swiss Ephemeris as a JSON&nbsp;API</h1>
                <p class="lede">Planetary positions, house cusps, natal charts, ayanamsa, fixed stars and eclipses — computed by the Swiss Ephemeris C library on every request and returned as plain JSON.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="{{ url('/#playground') }}" data-select="natal-chart">Open playground</a>
                    <a class="btn btn-secondary" href="{{ url('/#endpoints') }}">API reference</a>
                </div>
                <p class="hero-meta">No API key. Free software under AGPL-3.0 — <a href="{{ route('landing.source') }}">get the source</a>.</p>
            </div>

            <figure class="snippet mono" aria-label="Example natal chart request and response">
                <div class="snippet-bar">
                    <span class="method">POST</span>
                    <span class="mono">{{ $apiPrefix }}/natal-chart</span>
                    <span class="snippet-status">200 OK</span>
                </div>
<pre><span class="tok-punct">{</span>
  <span class="tok-key">"moment"</span><span class="tok-punct">:</span> <span class="tok-str">"1994-03-03T07:35"</span><span class="tok-punct">,</span>
  <span class="tok-key">"latitude"</span><span class="tok-punct">:</span> <span class="tok-num">46.9167</span><span class="tok-punct">,</span>
  <span class="tok-key">"longitude"</span><span class="tok-punct">:</span> <span class="tok-num">35.0</span><span class="tok-punct">,</span>
  <span class="tok-key">"system"</span><span class="tok-punct">:</span> <span class="tok-str">"P"</span>
<span class="tok-punct">}</span></pre>
                <div class="snippet-label"><span>Response</span><span>abridged</span></div>
<pre><span class="tok-punct">{</span>
  <span class="tok-key">"ok"</span><span class="tok-punct">:</span> <span class="tok-lit">true</span><span class="tok-punct">,</span>
  <span class="tok-key">"data"</span><span class="tok-punct">: {</span>
    <span class="tok-key">"julianDay"</span><span class="tok-punct">:</span> <span class="tok-num">2449414.815972</span><span class="tok-punct">,</span>
    <span class="tok-key">"siderealTime"</span><span class="tok-punct">:</span> <span class="tok-num">20.640278</span><span class="tok-punct">,</span>
    <span class="tok-key">"positions"</span><span class="tok-punct">: [</span>
      <span class="tok-punct">{</span>
        <span class="tok-key">"planet"</span><span class="tok-punct">:</span> <span class="tok-str">"Sun"</span><span class="tok-punct">,</span>
        <span class="tok-key">"longitude"</span><span class="tok-punct">:</span> <span class="tok-num">342.493056</span><span class="tok-punct">,</span>
        <span class="tok-key">"declination"</span><span class="tok-punct">:</span> <span class="tok-num">-6.8725</span><span class="tok-punct">,</span>
        <span class="tok-key">"retrograde"</span><span class="tok-punct">:</span> <span class="tok-lit">false</span><span class="tok-punct">,</span>
        <span class="tok-key">"house"</span><span class="tok-punct">:</span> <span class="tok-num">11</span>
      <span class="tok-punct">},</span>
      <span class="tok-comment">// 12 more bodies</span>
    <span class="tok-punct">],</span>
    <span class="tok-key">"houses"</span><span class="tok-punct">: { … },</span>
    <span class="tok-key">"aspects"</span><span class="tok-punct">: [ … ]</span>
  <span class="tok-punct">}</span>
<span class="tok-punct">}</span></pre>
            </figure>
        </section>

        <div class="container">
            <dl class="facts" aria-label="Product facts">
                <div class="fact">
                    <dt class="mono">1800–2399</dt>
                    <dd>Years covered by the bundled ephemeris files (CE)</dd>
                </div>
                <div class="fact">
                    <dt class="mono">1″</dt>
                    <dd>Positions match Astrodienst to the arcsecond</dd>
                </div>
                <div class="fact">
                    <dt class="mono">{{ count($endpoints) }}</dt>
                    <dd>POST endpoints sharing one response envelope</dd>
                </div>
                <div class="fact">
                    <dt class="mono">UT</dt>
                    <dd>Universal Time in, Universal Time out</dd>
                </div>
            </dl>
        </div>

        <section class="container section" id="endpoints" aria-labelledby="endpoints-title">
            <div class="section-head">
                <h2 id="endpoints-title">Endpoints</h2>
                <p class="section-copy">Every route lives under <code>{{ $apiPrefix }}</code>, takes a JSON body and answers with <code>{ ok, data }</code>. Select a row to try it.</p>
            </div>
            <div class="endpoint-table" role="table" aria-label="API endpoint index">
                <div class="endpoint-row endpoint-row-head" role="row">
                    <span role="columnheader">Method</span>
                    <span role="columnheader">Path</span>
                    <span role="columnheader">Returns</span>
                    <span role="columnheader"><span class="sr-only">Try</span></span>
                </div>
                @foreach ($endpoints as $endpoint)
                    <a class="endpoint-row" role="row" href="{{ url('/#playground') }}" data-select="{{ $endpoint->id }}">
                        <span role="cell"><span class="method">POST</span></span>
                        <span class="mono endpoint-path" role="cell" title="POST {{ $endpoint->publicPath() }}"><span class="prefix">{{ $apiPrefix }}</span>{{ $endpoint->path }}</span>
                        <span class="endpoint-summary" role="cell">{{ $endpoint->summary }}</span>
                        <span class="endpoint-go" role="cell" aria-hidden="true">→</span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="container section" id="playground" aria-labelledby="playground-title">
            <div class="section-head">
                <h2 id="playground-title">Playground</h2>
                <p class="section-copy">Requests run live against this server — nothing is mocked. Pick an endpoint, edit the body and run it.</p>
            </div>
            <div class="console">
                <div class="console-side" role="tablist" aria-label="API endpoints" aria-orientation="vertical">
                    <p class="console-side-label" aria-hidden="true">Endpoints</p>
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
                            <span class="mono play-tab-path">{{ $endpoint->path }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="console-main" id="play-stage" role="tabpanel" aria-labelledby="tab-natal-chart">
                    <div class="console-bar">
                        <div class="console-url">
                            <span class="method">POST</span>
                            <span class="mono" id="play-path">{{ $apiPrefix }}/natal-chart</span>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" id="copy-curl">Copy curl</button>
                        <button type="button" class="btn btn-primary btn-sm" id="play-run">Run <kbd id="run-hint" aria-hidden="true">Ctrl ↵</kbd></button>
                    </div>
                    <p class="console-summary" id="play-summary"></p>
                    <div class="console-split">
                        <div class="play-pane">
                            <div class="pane-head">
                                <span>Body</span>
                                <span class="mono">application/json</span>
                            </div>
                            <label class="sr-only" for="play-body">JSON request body</label>
                            <textarea id="play-body" class="mono" spellcheck="false" autocomplete="off" autocapitalize="off"></textarea>
                        </div>
                        <div class="play-pane">
                            <div class="pane-head">
                                <span>Response</span>
                                <span class="mono play-status" id="play-status" aria-live="polite">idle</span>
                            </div>
                            <pre class="mono play-out is-empty" id="play-out" tabindex="0">Run the request to see the response.</pre>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="container section" id="docs" aria-labelledby="docs-title">
            <div class="accuracy">
                <div>
                    <h2 id="docs-title">Accuracy and conventions</h2>
                    <p class="section-copy">Calculations run in the Swiss Ephemeris 2.10 C library with its compressed planetary files — the same engine behind Astrodienst charts.</p>
                    <dl class="notes">
                        <div>
                            <dt>Time scale</dt>
                            <dd>Moments are Universal Time. There is no timezone conversion — convert local birth time to UT before sending.</dd>
                        </div>
                        <div>
                            <dt>Julian Day</dt>
                            <dd>Returned in UT, not TT. The ~1&nbsp;minute delta-T difference from some printed sheets does not move positions.</dd>
                        </div>
                        <div>
                            <dt>Coverage</dt>
                            <dd>1800–2399 CE — the range of the bundled <span class="mono">.se1</span> ephemeris files.</dd>
                        </div>
                        <div>
                            <dt>Errors</dt>
                            <dd>Validation failures return HTTP 422 with <code>{ ok: false, error }</code>.</dd>
                        </div>
                    </dl>
                </div>

                <div class="almanac" role="table" aria-label="Verified natal sample">
                    <div class="almanac-caption">
                        <strong>Reference chart</strong>
                        <span class="mono">1994-03-03 07:35 UT · 46°55′ N 35°00′ E · Placidus</span>
                    </div>
                    <div class="almanac-head" role="row">
                        <span role="columnheader">Body</span>
                        <span role="columnheader">Longitude</span>
                        <span role="columnheader" class="num">House</span>
                        <span role="columnheader" class="num">Declination</span>
                    </div>
                    <div role="row"><span role="cell">Sun</span><span role="cell" class="mono">Pisces 12°29′35″</span><span role="cell" class="mono num">11</span><span role="cell" class="mono num">6°52′21″ S</span></div>
                    <div role="row"><span role="cell">Moon</span><span role="cell" class="mono">Scorpio 24°31′55″</span><span role="cell" class="mono num">6</span><span role="cell" class="mono num nil">—</span></div>
                    <div role="row"><span role="cell">Asc</span><span role="cell" class="mono nil">—</span><span role="cell" class="mono num">1</span><span role="cell" class="mono num">21°20′14″ N</span></div>
                    <div role="row"><span role="cell">MC</span><span role="cell" class="mono nil">—</span><span role="cell" class="mono num">10</span><span role="cell" class="mono num">18°28′15″ S</span></div>
                    <p class="almanac-note">Identical to the Astrodienst chart for the same data, to the arcsecond. Sidereal time <span class="mono">20:38:25</span>.</p>
                </div>
            </div>
        </section>
    </main>

    <x-slot:scripts>
        <script type="application/json" id="landing-data">@json($landingData)</script>
    </x-slot:scripts>
</x-landing::site-layout>
