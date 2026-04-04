<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Recent Daily Maps</title>
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        crossorigin=""
    >
    <style>
        :root {
            color-scheme: light;
            --page-bg: #edf2f7;
            --card-bg: #ffffff;
            --card-border: #d7dde5;
            --text-primary: #0f172a;
            --text-secondary: #475569;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: linear-gradient(180deg, #edf2f7 0%, #e2e8f0 100%);
            color: var(--text-primary);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        .page-shell {
            max-width: 1120px;
            margin: 0 auto;
            padding: 32px 16px 56px;
        }

        .page-header {
            margin-bottom: 28px;
            padding: 28px;
            border: 1px solid var(--card-border);
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.86);
            backdrop-filter: blur(10px);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        }

        .page-title {
            margin: 0 0 10px;
            font-size: clamp(30px, 5vw, 44px);
            line-height: 1.05;
            letter-spacing: -0.04em;
        }

        .page-subtitle {
            margin: 0;
            font-size: clamp(18px, 2.2vw, 21px);
            line-height: 1.7;
            color: var(--text-secondary);
        }

        .daily-grid {
            display: grid;
            gap: 24px;
        }

        .daily-card {
            border: 1px solid var(--card-border);
            border-radius: 24px;
            background: var(--card-bg);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .daily-header {
            padding: 24px 24px 8px;
        }

        .daily-title {
            margin: 0;
            font-size: clamp(24px, 3vw, 30px);
            line-height: 1.15;
            letter-spacing: -0.03em;
        }

        .daily-summary {
            margin-top: 10px;
            font-size: 18px;
            line-height: 1.7;
            color: var(--text-secondary);
        }

        .map-frame {
            margin: 0 24px 18px;
            border: 1px solid var(--card-border);
            border-radius: 20px;
            overflow: hidden;
            background: #dfe7eb;
        }

        .daily-map {
            width: 100%;
            height: 420px;
        }

        .incident-list {
            padding: 0 24px 24px;
        }

        .incident-intro,
        .quiet-day,
        .omitted-note {
            font-size: 18px;
            line-height: 1.7;
            color: var(--text-secondary);
        }

        .incident-items {
            display: grid;
            gap: 14px;
            margin-top: 14px;
        }

        .incident-item {
            display: grid;
            grid-template-columns: 72px minmax(0, 1fr);
            gap: 14px;
            align-items: start;
        }

        .incident-copy {
            min-width: 0;
        }

        .incident-headline {
            margin: 0;
            font-size: 20px;
            line-height: 1.45;
            font-weight: 700;
            color: var(--text-primary);
        }

        .incident-meta,
        .incident-status {
            margin: 4px 0 0;
            font-size: 18px;
            line-height: 1.7;
            color: var(--text-secondary);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            line-height: 1;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.14);
        }

        .badge.shape-circle { width: 46px; height: 46px; border-radius: 999px; }
        .badge.shape-rounded-square { width: 46px; height: 46px; border-radius: 14px; }
        .badge.shape-square { width: 46px; height: 46px; border-radius: 8px; }
        .badge.shape-pill { width: 58px; height: 42px; border-radius: 999px; }
        .badge.shape-bevel { width: 58px; height: 42px; border-radius: 16px 6px 16px 6px; }
        .badge.shape-tag { width: 58px; height: 42px; border-radius: 6px 16px 6px 16px; }
        .badge.shape-diamond { width: 40px; height: 40px; border-radius: 8px; transform: rotate(45deg); }
        .badge .badge-label { display: inline-flex; align-items: center; justify-content: center; }
        .badge.shape-diamond .badge-label { transform: rotate(-45deg); }

        .marker-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.22);
            font-family: Arial, sans-serif;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1;
        }

        .marker-badge.shape-home { width: 56px; height: 56px; border-radius: 18px; }
        .marker-badge.shape-circle { width: 48px; height: 48px; border-radius: 999px; }
        .marker-badge.shape-rounded-square { width: 48px; height: 48px; border-radius: 14px; }
        .marker-badge.shape-square { width: 48px; height: 48px; border-radius: 8px; }
        .marker-badge.shape-pill { width: 60px; height: 44px; border-radius: 999px; }
        .marker-badge.shape-bevel { width: 60px; height: 44px; border-radius: 16px 6px 16px 6px; }
        .marker-badge.shape-tag { width: 60px; height: 44px; border-radius: 6px 16px 6px 16px; }
        .marker-badge.shape-diamond { width: 46px; height: 46px; border-radius: 9px; transform: rotate(45deg); }
        .marker-badge .badge-label { display: inline-flex; align-items: center; justify-content: center; }
        .marker-badge.shape-diamond .badge-label { transform: rotate(-45deg); }

        @media (max-width: 720px) {
            .page-shell {
                padding: 18px 12px 36px;
            }

            .page-header,
            .daily-header,
            .incident-list {
                padding-left: 18px;
                padding-right: 18px;
            }

            .map-frame {
                margin-left: 18px;
                margin-right: 18px;
            }

            .daily-map {
                height: 320px;
            }

            .incident-item {
                grid-template-columns: 64px minmax(0, 1fr);
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="page-shell">
        <header class="page-header">
            <h1 class="page-title">Recent Daily Maps</h1>
            <p class="page-subtitle">
                {{ $location->address }} · showing the newest day and the prior {{ max($days - 1, 0) }} days within {{ number_format($radius, 2) }} miles.
            </p>
        </header>

        <div class="daily-grid">
            @foreach ($snapshots as $index => $snapshot)
                @php
                    $incidents = $snapshot['incidents'] ?? [];
                    $selectedPoints = (int) ($snapshot['selected_points'] ?? 0);
                    $recentPoints = (int) ($snapshot['recent_points_in_window'] ?? 0);
                    $omittedPoints = (int) ($snapshot['omitted_points'] ?? 0);
                @endphp
                <section class="daily-card">
                    <div class="daily-header">
                        <h2 class="daily-title">{{ $snapshot['window']['display'] ?? 'Recent activity' }}</h2>
                        <div class="daily-summary">
                            @if ($selectedPoints > 0)
                                Showing {{ $selectedPoints }} of {{ $recentPoints }} nearby incident{{ $recentPoints === 1 ? '' : 's' }}.
                            @else
                                Quiet day. No nearby incidents were found in the selected radius.
                            @endif
                        </div>
                    </div>

                    <div class="map-frame">
                        <div class="daily-map" id="daily-map-{{ $index }}"></div>
                    </div>

                    <div class="incident-list">
                        @if (!empty($incidents))
                            <div class="incident-intro">Numbered badges in the map match the incidents below.</div>
                            <div class="incident-items">
                                @foreach ($incidents as $incident)
                                    <div class="incident-item">
                                        <div>
                                            <span
                                                class="badge shape-{{ $incident['shape'] ?? 'rounded-square' }}"
                                                style="
                                                    color: {{ $incident['text_color'] ?? '#ffffff' }};
                                                    background: {{ $incident['fill_color'] ?? '#475569' }};
                                                    border: 3px solid {{ $incident['stroke_color'] ?? '#ffffff' }};
                                                "
                                            >
                                                <span class="badge-label">{{ $incident['label'] }}</span>
                                            </span>
                                        </div>
                                        <div class="incident-copy">
                                            <p class="incident-headline">{{ $incident['headline'] }}</p>
                                            <p class="incident-meta">
                                                {{ $incident['category_label'] ?? $incident['type'] }}
                                                · {{ $incident['display_date'] }}
                                                @if (!empty($incident['address']))
                                                    · {{ $incident['address'] }}
                                                @endif
                                                · {{ number_format((float) $incident['distance_miles'], 2) }} miles from home
                                            </p>
                                            @if (!empty($incident['status']) || !empty($incident['identifier']))
                                                <p class="incident-status">
                                                    @if (!empty($incident['status']))
                                                        Status: {{ $incident['status'] }}
                                                    @endif
                                                    @if (!empty($incident['status']) && !empty($incident['identifier']))
                                                        ·
                                                    @endif
                                                    @if (!empty($incident['identifier']))
                                                        ID: {{ $incident['identifier'] }}
                                                    @endif
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if ($omittedPoints > 0)
                                <p class="omitted-note">{{ $omittedPoints }} additional incident{{ $omittedPoints === 1 ? '' : 's' }} happened that day but were not shown on the map.</p>
                            @endif
                        @else
                            <p class="quiet-day">The map for this day shows only the home marker.</p>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <script>
        const snapshots = {{ Illuminate\Support\Js::from($snapshots) }};

        const geometryForMarker = marker => {
            switch (marker.shape) {
                case 'home':
                    return { width: 56, height: 56 };
                case 'pill':
                case 'bevel':
                case 'tag':
                    return { width: 60, height: 44 };
                case 'diamond':
                    return { width: 46, height: 46 };
                default:
                    return { width: 48, height: 48 };
            }
        };

        const makeIcon = marker => {
            const geometry = geometryForMarker(marker);
            const html = `
                <div
                    class="marker-badge shape-${marker.shape}"
                    style="
                        width:${geometry.width}px;
                        height:${geometry.height}px;
                        color:${marker.text_color};
                        background:${marker.fill_color};
                        border:3px solid ${marker.stroke_color};
                    "
                >
                    <span class="badge-label">${marker.label}</span>
                </div>
            `;

            return L.divIcon({
                className: 'snapshot-div-icon',
                html,
                iconSize: [geometry.width, geometry.height],
                iconAnchor: [geometry.width / 2, geometry.height / 2],
            });
        };

        const applyIncidentOffsets = markers => {
            const groups = new Map();

            markers.forEach(marker => {
                const key = `${Number(marker.latitude).toFixed(6)}:${Number(marker.longitude).toFixed(6)}`;
                if (!groups.has(key)) {
                    groups.set(key, []);
                }

                groups.get(key).push(marker);
            });

            return Array.from(groups.values()).flatMap(group => {
                if (group.length === 1) {
                    return group.map(marker => ({
                        ...marker,
                        displayLatitude: marker.latitude,
                        displayLongitude: marker.longitude,
                    }));
                }

                return group.map((marker, index) => {
                    const radiusMeters = 14 + Math.max(group.length - 2, 0) * 4;
                    const angle = (-Math.PI / 2) + ((2 * Math.PI * index) / group.length);
                    const metersPerDegreeLat = 111320;
                    const metersPerDegreeLng = Math.max(
                        Math.cos((marker.latitude * Math.PI) / 180) * 111320,
                        1
                    );

                    return {
                        ...marker,
                        displayLatitude: marker.latitude + ((radiusMeters * Math.sin(angle)) / metersPerDegreeLat),
                        displayLongitude: marker.longitude + ((radiusMeters * Math.cos(angle)) / metersPerDegreeLng),
                    };
                });
            });
        };

        const renderSnapshotMap = (snapshot, elementId) => {
            const container = document.getElementById(elementId);
            const map = L.map(container, {
                zoomControl: false,
                attributionControl: false,
                scrollWheelZoom: false,
            });

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
            }).addTo(map);

            const bounds = [];
            const home = snapshot.markers.find(marker => marker.kind === 'home');
            const incidents = applyIncidentOffsets(snapshot.markers.filter(marker => marker.kind === 'incident'));

            if (home) {
                const homeLatLng = [home.latitude, home.longitude];
                bounds.push(homeLatLng);

                L.marker(homeLatLng, { icon: makeIcon(home) })
                    .addTo(map)
                    .bindTooltip(home.title, { direction: 'top' });
            }

            incidents.forEach(marker => {
                const incidentLatLng = [marker.displayLatitude, marker.displayLongitude];
                bounds.push(incidentLatLng);

                if (home) {
                    L.polyline([[home.latitude, home.longitude], incidentLatLng], {
                        color: marker.line_color || '#475569',
                        weight: 3,
                        opacity: 0.65,
                        dashArray: '6 8',
                    }).addTo(map);
                }

                L.marker(incidentLatLng, { icon: makeIcon(marker) })
                    .addTo(map)
                    .bindTooltip(`${marker.label}. ${marker.title}`, { direction: 'top' });
            });

            if (bounds.length > 1) {
                map.fitBounds(bounds, {
                    padding: [18, 18],
                    maxZoom: 18,
                });
            } else if (home) {
                map.setView([home.latitude, home.longitude], 17);
            } else {
                map.setView([42.3601, -71.0589], 12);
            }
        };

        snapshots.forEach((snapshot, index) => {
            renderSnapshotMap(snapshot, `daily-map-${index}`);
        });
    </script>
</body>
</html>
