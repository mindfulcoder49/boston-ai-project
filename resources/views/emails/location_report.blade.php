@component('mail::message')
# Hello,

Here's your daily report for {{ $location->address }}:

{{-- Use the "prose" class for better typography. --}}
<div class="prose">
{!! nl2br(e($report)) !!}
</div>

@component('mail::button', ['url' => url('/profile')])
Update Your Location Settings
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent