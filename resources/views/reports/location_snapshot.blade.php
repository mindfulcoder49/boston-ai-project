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
            --accent: #9a031e;
            --home: #0f766e;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            background: #dfe7eb;
        }

        #snapshot-root {
            width: 100vw;
            height: 100vh;
        }

        #map {
            width: 100vw;
            height: 100vh;
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
