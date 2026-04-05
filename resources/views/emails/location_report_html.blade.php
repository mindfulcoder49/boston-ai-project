@php
    $recentSnapshot = $recentMap['snapshot'] ?? null;
    $incidents = $recentSnapshot['incidents'] ?? [];
    $selectedPoints = (int) ($recentSnapshot['selected_points'] ?? 0);
    $recentPoints = (int) ($recentSnapshot['recent_points_in_window'] ?? 0);
    $omittedPoints = (int) ($recentSnapshot['omitted_points'] ?? 0);
    $windowDisplay = (string) ($recentSnapshot['window']['display'] ?? 'Most recent day');

    $badgeGeometry = function (array $incident): array {
        $shape = (string) ($incident['shape'] ?? 'rounded-square');

        return match ($shape) {
            'circle' => ['width' => 46, 'height' => 46, 'radius' => '999px', 'rotate' => true],
            'square' => ['width' => 46, 'height' => 46, 'radius' => '8px', 'rotate' => true],
            'pill' => ['width' => 58, 'height' => 42, 'radius' => '999px', 'rotate' => true],
            'bevel' => ['width' => 58, 'height' => 42, 'radius' => '16px 6px 16px 6px', 'rotate' => true],
            'tag' => ['width' => 58, 'height' => 42, 'radius' => '6px 16px 6px 16px', 'rotate' => true],
            'diamond' => ['width' => 40, 'height' => 40, 'radius' => '8px', 'rotate' => false],
            default => ['width' => 46, 'height' => 46, 'radius' => '14px', 'rotate' => true],
        };
    };

    $badgeOuterStyle = function (array $incident) use ($badgeGeometry): string {
        $geometry = $badgeGeometry($incident);
        $fill = (string) ($incident['fill_color'] ?? '#475569');
        $stroke = (string) ($incident['stroke_color'] ?? '#FFFFFF');

        $style = 'border-collapse:separate;border-spacing:0;display:inline-table;vertical-align:top;'
            . 'width:' . $geometry['width'] . 'px;'
            . 'height:' . $geometry['height'] . 'px;'
            . 'background:' . $fill . ';'
            . 'border:3px solid ' . $stroke . ';'
            . 'border-radius:' . $geometry['radius'] . ';'
            . 'box-shadow:0 8px 18px rgba(15,23,42,0.16);';

        if (($incident['shape'] ?? null) === 'diamond') {
            $style .= 'transform:rotate(45deg);';
        }

        return $style;
    };

    $badgeCellStyle = function (array $incident) use ($badgeGeometry): string {
        $geometry = $badgeGeometry($incident);
        $text = (string) ($incident['text_color'] ?? '#FFFFFF');

        return 'width:' . $geometry['width'] . 'px;'
            . 'height:' . $geometry['height'] . 'px;'
            . 'padding:0;'
            . 'text-align:center;'
            . 'vertical-align:middle;'
            . 'font-weight:800;'
            . 'font-size:18px;'
            . 'line-height:18px;'
            . 'color:' . $text . ';'
            . 'mso-line-height-rule:exactly;';
    };

    $badgeLabelStyle = function (array $incident): string {
        return ($incident['shape'] ?? null) === 'diamond'
            ? 'display:inline-block;line-height:1;transform:rotate(-45deg);'
            : 'display:inline-block;line-height:1;';
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Location Report</title>
    <style>
        body, table, td, div, p, li, a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        .email-shell {
            width: 680px;
            max-width: 680px;
        }

        .section-card {
            border: 1px solid #d7dde5;
            border-radius: 18px;
            background: #f8fafc;
        }

        .report-content,
        .report-content p,
        .report-content li {
            font-size: 18px;
            line-height: 1.75;
            color: #1f2937;
        }

        .report-content h2 {
            margin: 0 0 14px;
            font-size: 28px;
            line-height: 1.25;
            color: #0f172a;
        }

        .report-content h3 {
            margin: 22px 0 10px;
            font-size: 24px;
            line-height: 1.35;
            color: #0f172a;
        }

        .report-content h4 {
            margin: 18px 0 10px;
            font-size: 20px;
            line-height: 1.4;
            color: #0f172a;
        }

        .report-content ul,
        .report-content ol {
            padding-left: 22px;
        }

        .button-link {
            display: inline-block;
            padding: 14px 20px;
            border-radius: 14px;
            background: #0f172a;
            color: #ffffff !important;
            font-size: 17px;
            font-weight: 700;
            line-height: 1.2;
            text-decoration: none;
        }

        @media only screen and (max-width: 720px) {
            .email-shell {
                width: 100% !important;
                max-width: 100% !important;
            }

            .email-padding {
                padding-left: 18px !important;
                padding-right: 18px !important;
            }

            .report-content,
            .report-content p,
            .report-content li {
                font-size: 18px !important;
            }
        }
    </style>
</head>
<body style="margin:0; padding:24px 0; background:#edf2f7; color:#1f2937; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; width:100%;">
        <tr>
            <td align="center" class="email-padding" style="padding:0 12px;">
                <table role="presentation" class="email-shell" cellpadding="0" cellspacing="0" style="border-collapse:collapse; background:#ffffff; border:1px solid #d7dde5; border-radius:18px;">
                    <tr>
                        <td class="email-padding" style="padding:28px 28px 10px; font-size:28px; font-weight:800; color:#0f172a;">
                            PublicDataWatch
                        </td>
                    </tr>

                    @if (!empty($introNotice))
                        <tr>
                            <td class="email-padding" style="padding:0 28px 18px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; width:100%; border:1px solid #cbd5e1; border-radius:16px; background:#f8fafc;">
                                    <tr>
                                        <td style="padding:18px 20px;">
                                            <div style="font-size:22px; line-height:1.2; font-weight:800; color:#0f172a;">
                                                {{ $introNotice['headline'] }}
                                            </div>
                                            <div style="margin-top:8px; font-size:17px; line-height:1.7; color:#475569;">
                                                {{ $introNotice['body'] }}
                                            </div>
                                            @if (!empty($subscriptionUrl) && !empty($introNotice['cta_label']))
                                                <div style="margin-top:14px;">
                                                    <a href="{{ $subscriptionUrl }}" class="button-link">{{ $introNotice['cta_label'] }}</a>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    @if ($recentSnapshot)
                        <tr>
                            <td class="email-padding" style="padding:0 28px 12px; font-size:18px; line-height:1.7; color:#475569;">
                                Most recent map for {{ $windowDisplay }} around {{ $location->address }}.
                            </td>
                        </tr>

                        <tr>
                            <td class="email-padding" style="padding:0 28px 22px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="section-card" style="border-collapse:collapse; width:100%;">
                                    <tr>
                                        <td style="padding:20px 20px 8px;">
                                            <div style="font-size:24px; line-height:1.2; font-weight:800; color:#0f172a;">
                                                {{ $windowDisplay }}
                                            </div>
                                            <div style="margin-top:8px; font-size:18px; line-height:1.7; color:#475569;">
                                                @if ($selectedPoints > 0)
                                                    Showing {{ $selectedPoints }} of {{ $recentPoints }} nearby incident{{ $recentPoints === 1 ? '' : 's' }} within {{ number_format((float) ($recentSnapshot['radius_miles'] ?? 0.25), 2) }} miles.
                                                @else
                                                    No nearby incidents were found within {{ number_format((float) ($recentSnapshot['radius_miles'] ?? 0.25), 2) }} miles.
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                    @if (!empty($recentMap['path']) && is_string($recentMap['path']) && is_file($recentMap['path']))
                                        <tr>
                                            <td style="padding:0 20px 16px;">
                                                <img
                                                    src="{{ $message->embed($recentMap['path']) }}"
                                                    alt="Incident map for {{ $windowDisplay }} near {{ $location->address }}"
                                                    style="display:block; width:100%; height:auto; border:1px solid #d7dde5; border-radius:16px;"
                                                >
                                            </td>
                                        </tr>
                                    @endif

                                    <tr>
                                        <td style="padding:0 20px 20px;">
                                            @if (!empty($incidents))
                                                <div style="margin-bottom:14px; font-size:17px; line-height:1.7; color:#475569;">
                                                    Numbered badges in the map match the incidents below.
                                                </div>

                                                @foreach ($incidents as $incident)
                                                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; width:100%; margin-bottom:14px;">
                                                        <tr>
                                                            <td width="72" valign="top" style="padding-right:12px;">
                                                                <table role="presentation" cellpadding="0" cellspacing="0" style="{{ $badgeOuterStyle($incident) }}">
                                                                    <tr>
                                                                        <td align="center" valign="middle" style="{{ $badgeCellStyle($incident) }}">
                                                                            <span style="{{ $badgeLabelStyle($incident) }}">{{ $incident['label'] }}</span>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                            <td valign="top" style="font-size:18px; line-height:1.7; color:#1f2937;">
                                                                <div style="font-weight:700; color:#0f172a;">
                                                                    {{ $incident['headline'] }}
                                                                </div>
                                                                @if (!empty($incident['detail']))
                                                                    <div style="margin-top:2px; color:#334155;">
                                                                        {{ $incident['detail'] }}
                                                                    </div>
                                                                @endif
                                                                <div style="color:#475569;">
                                                                    {{ $incident['category_label'] ?? $incident['type'] }}
                                                                    · {{ $incident['display_date'] }}
                                                                    @if (!empty($incident['address']))
                                                                        · {{ $incident['address'] }}
                                                                    @endif
                                                                    · {{ number_format((float) $incident['distance_miles'], 2) }} miles from home
                                                                </div>
                                                                @if (!empty($incident['status']) || !empty($incident['identifier']))
                                                                    <div style="color:#64748b;">
                                                                        @if (!empty($incident['status']))
                                                                            Status: {{ $incident['status'] }}
                                                                        @endif
                                                                        @if (!empty($incident['status']) && !empty($incident['identifier']))
                                                                            ·
                                                                        @endif
                                                                        @if (!empty($incident['identifier']))
                                                                            ID: {{ $incident['identifier'] }}
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    </table>
                                                @endforeach

                                                @if ($omittedPoints > 0)
                                                    <div style="font-size:17px; line-height:1.7; color:#64748b;">
                                                        {{ $omittedPoints }} additional incident{{ $omittedPoints === 1 ? '' : 's' }} happened that day but were not shown on the map.
                                                    </div>
                                                @endif
                                            @else
                                                <div style="font-size:18px; line-height:1.7; color:#475569;">
                                                    Quiet day. The map for this day shows only the home marker.
                                                </div>
                                            @endif

                                            @if (!empty($publicMapsUrl))
                                                <div style="margin-top:18px;">
                                                    <a href="{{ $publicMapsUrl }}" class="button-link">View Daily Maps For The Last 7 Days</a>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @elseif (!empty($publicMapsUrl))
                        <tr>
                            <td class="email-padding" style="padding:0 28px 22px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="section-card" style="border-collapse:collapse; width:100%;">
                                    <tr>
                                        <td style="padding:20px;">
                                            <div style="font-size:18px; line-height:1.7; color:#475569; margin-bottom:16px;">
                                                The newest-day map could not be embedded in this email.
                                            </div>
                                            <a href="{{ $publicMapsUrl }}" class="button-link">View Daily Maps For The Last 7 Days</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td class="email-padding" style="padding:4px 28px 28px;">
                            <div style="font-size:24px; line-height:1.2; font-weight:800; color:#0f172a; margin-bottom:12px;">
                                Narrative Summary
                            </div>
                            <div class="report-content">
                                {!! Illuminate\Support\Str::markdown($report) !!}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
