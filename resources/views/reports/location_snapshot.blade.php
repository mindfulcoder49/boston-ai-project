<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Location Report Snapshot</title>
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        crossorigin=""
    >
    <style>
        :root {
            color-scheme: light;
            --canvas: #f4efe5;
            --ink: #14213d;
            --accent: #9a031e;
            --accent-soft: #f7c6ce;
            --panel: #fffdf8;
            --muted: #6b7280;
            --border: #d9d0c3;
            --home: #0f766e;
            --home-soft: #c8f2ee;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Georgia, "Times New Roman", serif;
            background: linear-gradient(145deg, #f7f3ea 0%, #efe7d6 100%);
            color: var(--ink);
        }

        #snapshot-root {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
            min-height: 100vh;
        }

        .map-pane {
            position: relative;
            padding: 28px 0 28px 28px;
        }

        #map {
            height: calc(100vh - 56px);
            border: 1px solid rgba(20, 33, 61, 0.12);
            border-radius: 28px;
            box-shadow: 0 20px 50px rgba(20, 33, 61, 0.12);
            overflow: hidden;
        }

        .sidebar {
            padding: 28px 28px 28px 20px;
        }

        .panel {
            height: calc(100vh - 56px);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 18px;
            background: rgba(255, 253, 248, 0.92);
            border: 1px solid rgba(20, 33, 61, 0.08);
            border-radius: 28px;
            box-shadow: 0 16px 44px rgba(20, 33, 61, 0.08);
            padding: 24px;
        }

        .eyebrow {
            margin: 0;
            font-size: 12px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--accent);
        }

        h1 {
            margin: 0;
            font-size: 34px;
            line-height: 1.05;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
        }

        .summary-card {
            background: var(--canvas);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 12px 14px;
        }

        .summary-card strong {
            display: block;
            font-size: 24px;
            margin-bottom: 4px;
        }

        .summary-card span {
            color: var(--muted);
            font-size: 13px;
        }

        .policy,
        .empty-state,
        .source-note {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.45;
        }

        .incident-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            overflow: auto;
            padding-right: 4px;
        }

        .incident {
            border: 1px solid var(--border);
            border-radius: 18px;
            background: white;
            padding: 14px 16px;
        }

        .incident-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }

        .incident-label {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            background: var(--accent-soft);
            color: var(--accent);
        }

        .incident-type {
            font-size: 12px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
        }

        .incident h2 {
            margin: 0;
            font-size: 19px;
            line-height: 1.2;
        }

        .incident-meta {
            display: grid;
            gap: 6px;
            margin-top: 10px;
            color: var(--ink);
            font-size: 14px;
        }

        .incident-meta span {
            color: var(--muted);
        }

        .counts {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .count-chip {
            border-radius: 999px;
            border: 1px solid var(--border);
            padding: 6px 10px;
            font-size: 12px;
            color: var(--muted);
            background: white;
        }

        .home-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 8px 12px;
            border-radius: 999px;
            border: 1px solid rgba(15, 118, 110, 0.18);
            background: var(--home-soft);
            color: var(--home);
            font-size: 13px;
            font-weight: 700;
        }

        .source-note {
            margin-top: auto;
            font-size: 12px;
        }

        .marker-badge {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 2px solid rgba(255, 255, 255, 0.85);
            box-shadow: 0 6px 16px rgba(20, 33, 61, 0.18);
            font-family: Arial, sans-serif;
            font-weight: 700;
        }

        .marker-badge.incident {
            background: var(--accent);
            color: white;
        }

        .marker-badge.home {
            background: var(--home);
            color: white;
            width: 40px;
            height: 40px;
        }

        @media (max-width: 1100px) {
            #snapshot-root {
                grid-template-columns: 1fr;
            }

            .map-pane,
            .sidebar {
                padding: 20px;
            }

            #map,
            .panel {
                height: auto;
                min-height: 540px;
            }
        }
    </style>
</head>
<body>
    <div id="snapshot-root" data-ready="0">
        <section class="map-pane">
            <div id="map"></div>
        </section>
        <aside class="sidebar">
            <div class="panel">
                <p class="eyebrow">Paid Report Map Preview</p>
                <h1>{{ $snapshot['location']['label'] }}</h1>
                <p class="home-pill">Home marker H</p>
                <p class="policy">
                    {{ $snapshot['selection_policy'] }}
                </p>
                <div class="summary">
                    <div class="summary-card">
                        <strong>{{ $snapshot['selected_points'] }}</strong>
                        <span>incidents shown on the image</span>
                    </div>
                    <div class="summary-card">
                        <strong>{{ $snapshot['recent_points_in_window'] }}</strong>
                        <span>incidents found in the last {{ $snapshot['window']['days'] }} day(s)</span>
                    </div>
                </div>
                <div class="counts">
                    @foreach ($snapshot['counts_by_date'] as $count)
                        <div class="count-chip">{{ $count['date'] }} · {{ $count['count'] }}</div>
                    @endforeach
                </div>

                @if ($snapshot['empty'])
                    <p class="empty-state">
                        No incidents were found in this window. The screenshot still shows the saved home location so report generation can fail visibly instead of silently.
                    </p>
                @endif

                <div class="incident-list">
                    @foreach ($snapshot['incidents'] as $incident)
                        <article class="incident">
                            <div class="incident-head">
                                <div class="incident-label">{{ $incident['label'] }}</div>
                                <div class="incident-type">{{ $incident['type'] }}</div>
                            </div>
                            <h2>{{ $incident['headline'] }}</h2>
                            <div class="incident-meta">
                                <div><span>Recorded:</span> {{ $incident['display_date'] }}</div>
                                <div><span>Distance:</span> {{ number_format($incident['distance_miles'], 2) }} miles from home</div>
                                @if ($incident['address'])
                                    <div><span>Address:</span> {{ $incident['address'] }}</div>
                                @endif
                                @if ($incident['status'])
                                    <div><span>Status:</span> {{ $incident['status'] }}</div>
                                @endif
                                @if ($incident['identifier'])
                                    <div><span>Reference:</span> {{ $incident['identifier'] }}</div>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

                <p class="source-note">
                    Snapshot window: {{ $snapshot['window']['display'] }}. Radius: {{ number_format($snapshot['radius_miles'], 2) }} miles.
                    @if ($snapshot['omitted_points'] > 0)
                        {{ $snapshot['omitted_points'] }} additional incident(s) are intentionally omitted from the image but still counted.
                    @endif
                    Base map © OpenStreetMap contributors.
                </p>
            </div>
        </aside>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        const snapshot = {{ Illuminate\Support\Js::from($snapshot) }};
        const root = document.getElementById('snapshot-root');
        const map = L.map('map', {
            zoomControl: false,
            attributionControl: false,
            scrollWheelZoom: false,
        });

        const tileLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
        }).addTo(map);

        const bounds = [];
        const home = snapshot.markers.find(marker => marker.kind === 'home');
        const incidents = snapshot.markers.filter(marker => marker.kind === 'incident');

        const makeIcon = marker => L.divIcon({
            className: 'snapshot-div-icon',
            html: `<div class="marker-badge ${marker.kind}">${marker.label}</div>`,
            iconSize: marker.kind === 'home' ? [40, 40] : [34, 34],
            iconAnchor: marker.kind === 'home' ? [20, 40] : [17, 34],
        });

        if (home) {
            const homeLatLng = [home.latitude, home.longitude];
            bounds.push(homeLatLng);
            L.marker(homeLatLng, { icon: makeIcon(home) })
                .addTo(map)
                .bindTooltip(home.title, { direction: 'top' });
        }

        incidents.forEach(marker => {
            const incidentLatLng = [marker.latitude, marker.longitude];
            bounds.push(incidentLatLng);

            if (home) {
                L.polyline([[home.latitude, home.longitude], incidentLatLng], {
                    color: '#9a031e',
                    weight: 2,
                    opacity: 0.5,
                    dashArray: '4 8',
                }).addTo(map);
            }

            L.marker(incidentLatLng, { icon: makeIcon(marker) })
                .addTo(map)
                .bindTooltip(`${marker.label}. ${marker.title}`, { direction: 'top' });
        });

        if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [48, 48] });
        } else if (home) {
            map.setView([home.latitude, home.longitude], 15);
        } else {
            map.setView([42.3601, -71.0589], 12);
        }

        const markReady = () => {
            root.setAttribute('data-ready', '1');
        };

        tileLayer.on('load', markReady);
        window.setTimeout(markReady, 2500);
    </script>
</body>
</html>
