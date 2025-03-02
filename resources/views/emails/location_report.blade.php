@component('mail::message')


# Hello,

Here's your daily report for {{ $location->address }}:

{{ $report }}

@component('mail::button', ['url' => url('/profile')])
Update Your Location Settings
@endcomponent

Thanks,

{{ config('app.name') }}
@endcomponent