@php
    $recentSnapshot = $recentMap['snapshot'] ?? null;
    $incidents = $recentSnapshot['incidents'] ?? [];
    $selectedPoints = (int) ($recentSnapshot['selected_points'] ?? 0);
    $recentPoints = (int) ($recentSnapshot['recent_points_in_window'] ?? 0);
    $omittedPoints = (int) ($recentSnapshot['omitted_points'] ?? 0);
@endphp
@if ($recentSnapshot)
Most recent day map: {{ $recentSnapshot['window']['display'] ?? 'Most recent day' }}
@if ($selectedPoints > 0)
Showing {{ $selectedPoints }} of {{ $recentPoints }} nearby incident{{ $recentPoints === 1 ? '' : 's' }} within {{ number_format((float) ($recentSnapshot['radius_miles'] ?? 0.25), 2) }} miles.
@else
No nearby incidents were found within {{ number_format((float) ($recentSnapshot['radius_miles'] ?? 0.25), 2) }} miles.
@endif

@if (!empty($incidents))
@foreach ($incidents as $incident)
{{ $incident['label'] }}. [{{ $incident['category_label'] ?? $incident['type'] }}] {{ $incident['headline'] }}
{{ $incident['display_date'] }}@if (!empty($incident['address'])) · {{ $incident['address'] }}@endif · {{ number_format((float) $incident['distance_miles'], 2) }} miles from home
@if (!empty($incident['status']) || !empty($incident['identifier']))
@if (!empty($incident['status']))Status: {{ $incident['status'] }}@endif@if (!empty($incident['status']) && !empty($incident['identifier'])) · @endif@if (!empty($incident['identifier']))ID: {{ $incident['identifier'] }}@endif
@endif

@endforeach
@if ($omittedPoints > 0)
{{ $omittedPoints }} additional incident{{ $omittedPoints === 1 ? '' : 's' }} happened that day but were not shown on the map.

@endif
@else
Quiet day. The map for this day shows only the home marker.

@endif
@endif

@if (!empty($publicMapsUrl))
View daily maps for the last 7 days:
{{ $publicMapsUrl }}

@endif

{{ $report }}

PublicDataWatch
