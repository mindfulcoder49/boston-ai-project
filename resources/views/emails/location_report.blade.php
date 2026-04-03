@component('mail::message')

@if (!empty($mapImagePath) && is_string($mapImagePath) && is_file($mapImagePath))
<img
    src="{{ $message->embed($mapImagePath) }}"
    alt="Recent incident map near {{ $location->address }}"
    style="display:block; width:100%; max-width:640px; height:auto; margin:0 auto 20px; border-radius:14px; border:1px solid #d7dde5;"
>
@endif

{{ $report }}

{{ config('app.name') }}
@endcomponent
