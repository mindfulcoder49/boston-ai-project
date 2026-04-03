@if (!empty($mapSnapshot['incidents']))
Incidents shown on the map

@foreach ($mapSnapshot['incidents'] as $incident)
{{ $incident['label'] }}. {{ $incident['headline'] }}
{{ $incident['display_date'] }}@if (!empty($incident['address'])) · {{ $incident['address'] }}@endif · {{ number_format((float) $incident['distance_miles'], 2) }} miles from home

@endforeach
@endif

{{ $report }}

PublicDataWatch
