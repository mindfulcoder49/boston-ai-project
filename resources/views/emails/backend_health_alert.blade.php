<x-mail::message>
# Backend Health Alert

The backend health evaluator found the following active issues:

@foreach ($alerts as $alert)
## {{ $alert['title'] }}

Severity: `{{ $alert['severity'] }}`

{{ $alert['message'] }}

@endforeach

Review the backend admin dashboard for the latest operational state.
</x-mail::message>
