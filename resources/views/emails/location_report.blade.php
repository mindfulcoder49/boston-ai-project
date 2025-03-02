@component('mail::message')

<div class="prose">
# Hello,

Here's your daily report for {{ $location->address }}:

{{-- Use the "prose" class for better typography. --}}

{{ $report }}
</div>

@component('mail::button', ['url' => url('/profile')])
Update Your Location Settings
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent