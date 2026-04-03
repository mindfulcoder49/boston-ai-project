<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Location Report</title>
</head>
<body style="margin:0; padding:24px 0; background:#edf2f7; color:#1f2937; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; width:100%;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="border-collapse:collapse; width:640px; max-width:640px; background:#ffffff; border:1px solid #d7dde5; border-radius:16px;">
                    <tr>
                        <td style="padding:28px 28px 12px; font-size:24px; font-weight:700; color:#0f172a;">
                            PublicDataWatch
                        </td>
                    </tr>
                    @if (!empty($mapImagePath) && is_string($mapImagePath) && is_file($mapImagePath))
                        <tr>
                            <td style="padding:0 28px 16px;">
                                <img
                                    src="{{ $message->embed($mapImagePath) }}"
                                    alt="Recent incident map near {{ $location->address }}"
                                    style="display:block; width:100%; max-width:584px; height:auto; border:1px solid #d7dde5; border-radius:14px;"
                                >
                            </td>
                        </tr>
                    @endif
                    @if (!empty($mapSnapshot['incidents']))
                        <tr>
                            <td style="padding:0 28px 20px; font-size:15px; line-height:1.6; color:#1f2937;">
                                <div style="font-size:18px; font-weight:700; color:#0f172a; margin-bottom:10px;">
                                    Incidents Shown On The Map
                                </div>
                                <div style="margin-bottom:12px; color:#475569;">
                                    Numbered markers in the image correspond to the incidents below.
                                </div>
                                <ol style="margin:0; padding-left:22px;">
                                    @foreach ($mapSnapshot['incidents'] as $incident)
                                        <li style="margin-bottom:12px;">
                                            <div style="font-weight:700; color:#0f172a;">
                                                {{ $incident['label'] }}. {{ $incident['headline'] }}
                                            </div>
                                            <div style="color:#475569;">
                                                {{ $incident['display_date'] }}
                                                @if (!empty($incident['address']))
                                                    · {{ $incident['address'] }}
                                                @endif
                                                · {{ number_format((float) $incident['distance_miles'], 2) }} miles from home
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                            </td>
                        </tr>
                    @endif
                    <tr>
                        <td style="padding:0 28px 28px; font-size:16px; line-height:1.6; color:#1f2937;">
                            {!! Illuminate\Support\Str::markdown($report) !!}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
