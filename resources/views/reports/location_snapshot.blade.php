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
        * {
            box-sizing: border-box;
        }

        html,
        body,
        #snapshot-root,
        #map {
            margin: 0;
            width: 100vw;
            height: 100vh;
        }

        body {
            overflow: hidden;
            background: #dfe7eb;
        }

        .marker-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 3px solid var(--marker-stroke, #ffffff);
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.22);
            background: var(--marker-fill, #475569);
            color: var(--marker-text, #ffffff);
            font-family: Arial, sans-serif;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1;
        }

        .marker-badge .badge-label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .marker-badge.shape-home {
            border-radius: 18px;
        }

        .marker-badge.shape-circle {
            border-radius: 999px;
        }

        .marker-badge.shape-rounded-square {
            border-radius: 14px;
        }

        .marker-badge.shape-square {
            border-radius: 8px;
        }

        .marker-badge.shape-pill {
            border-radius: 999px;
        }

        .marker-badge.shape-bevel {
            border-radius: 16px 6px 16px 6px;
        }

        .marker-badge.shape-tag {
            border-radius: 6px 16px 6px 16px;
        }

        .marker-badge.shape-diamond {
            border-radius: 9px;
            transform: rotate(45deg);
        }

        .marker-badge.shape-diamond .badge-label {
            transform: rotate(-45deg);
        }
    </style>
</head>
<body>
    <div id="snapshot-root" data-ready="0">
        <div id="map"></div>
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
                        --marker-fill:${marker.fill_color};
                        --marker-stroke:${marker.stroke_color};
                        --marker-text:${marker.text_color};
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

        const markReady = () => {
            root.setAttribute('data-ready', '1');
        };

        tileLayer.on('load', () => window.setTimeout(markReady, 150));
        window.setTimeout(markReady, 2500);
    </script>
</body>
</html>
