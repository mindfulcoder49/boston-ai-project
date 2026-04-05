<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial Ended</title>
    <style>
        body, table, td, div, p, a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        .email-shell {
            width: 680px;
            max-width: 680px;
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
                    <tr>
                        <td class="email-padding" style="padding:0 28px 28px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; width:100%; border:1px solid #d7dde5; border-radius:18px; background:#f8fafc;">
                                <tr>
                                    <td style="padding:22px 22px 24px;">
                                        <div style="font-size:28px; line-height:1.15; font-weight:800; color:#0f172a;">
                                            Your trial ended
                                        </div>
                                        <div style="margin-top:12px; font-size:18px; line-height:1.75; color:#475569;">
                                            You are no longer receiving email reports from PublicDataWatch. Subscribe for 5 dollars a month to start receiving them again.
                                        </div>
                                        @if ($trialLocation)
                                            <div style="margin-top:10px; font-size:17px; line-height:1.7; color:#64748b;">
                                                The paused address was {{ $trialLocation->address }}.
                                            </div>
                                        @endif
                                        @if (!empty($subscriptionUrl))
                                            <div style="margin-top:18px;">
                                                <a href="{{ $subscriptionUrl }}" class="button-link">Subscribe for $5/month</a>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
