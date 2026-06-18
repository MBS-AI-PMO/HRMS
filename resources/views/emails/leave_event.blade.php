<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject }}</title>
</head>

<body style="margin:0;padding:0;background:#f4f2f8;font-family:'Segoe UI',Arial,Helvetica,sans-serif;color:#1f2937;-webkit-text-size-adjust:100%;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        {{ $headline }} — {{ $employeeName }} ({{ $startDate }} - {{ $endDate }})
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f2f8;padding:32px 14px;">
        <tr>
            <td align="center">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0"
                    style="max-width:640px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e8e2f2;box-shadow:0 12px 32px rgba(124,92,196,0.10);">

                    {{-- Brand bar --}}
                    <tr>
                        <td style="padding:24px 32px 20px;background:#ffffff;border-bottom:4px solid #7c5cc4;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="left" style="vertical-align:middle;">
                                        @if (!empty($logoUrl) || !empty($logoEmbed))
                                            <img src="{{ $logoUrl ?: $logoEmbed }}" alt="{{ $companyName }}"
                                                width="260"
                                                style="display:block;width:260px;max-width:260px;height:auto;min-height:64px;border:0;outline:none;">
                                        @else
                                            <p style="margin:0;font-size:18px;font-weight:700;color:#7c5cc4;">{{ $companyName }}</p>
                                        @endif
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <span style="display:inline-block;padding:6px 12px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:#5f459f;background:rgba(124,92,196,0.10);">
                                            {{ $siteTitle }}
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Headline --}}
                    <tr>
                        <td style="padding:28px 32px 8px;background:linear-gradient(180deg,rgba(124,92,196,0.07) 0%,#ffffff 100%);">
                            <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#7c5cc4;">
                                {{ $isWfh ? __('Work From Home') : __('Leave Management') }}
                            </p>
                            <h1 style="margin:0;font-size:26px;line-height:1.25;font-weight:700;color:#2d2640;">
                                {{ $headline }}
                            </h1>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:8px 32px 28px;">
                            <p style="margin:0 0 10px;font-size:15px;line-height:1.65;color:#374151;">
                                {{ __('Hello') }} <strong>{{ $recipientName ?? __('Team Member') }}</strong>,
                            </p>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.65;color:#6b7280;">
                                {{ $intro }}
                            </p>

                            {{-- Status + date strip --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                style="margin-bottom:18px;background:rgba(124,92,196,0.06);border:1px solid rgba(124,92,196,0.14);border-radius:12px;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="vertical-align:middle;">
                                                    <span style="display:inline-block;padding:7px 14px;border-radius:999px;font-size:12px;font-weight:700;color:{{ $statusColor }};background:{{ $statusBg }};">
                                                        {{ $statusLabel }}
                                                    </span>
                                                </td>
                                                <td align="right" style="vertical-align:middle;font-size:13px;color:#5f459f;font-weight:600;">
                                                    {{ $startDate }} &rarr; {{ $endDate }}
                                                    @if (!empty($totalDays) && $totalDays !== '—')
                                                        <span style="color:#9ca3af;font-weight:500;">&nbsp;·&nbsp;{{ $totalDays }} {{ __('days') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- Details card --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                style="border:1px solid #ece7f5;border-radius:12px;overflow:hidden;margin-bottom:26px;">
                                <tr>
                                    <td style="padding:14px 18px;background:#7c5cc4;color:#ffffff;font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;">
                                        {{ __('Request Summary') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            @php
                                                $rows = [
                                                    [__('Company'), $companyName, true],
                                                    [__('Employee'), $employeeName, true],
                                                    [__('Department'), $departmentName, false],
                                                    [__('Type'), $leaveTypeName, false],
                                                ];
                                                if (!empty($decisionBy) && !empty($decisionLabel)) {
                                                    $rows[] = [$decisionLabel, $decisionBy, true];
                                                }
                                            @endphp
                                            @foreach ($rows as $index => $row)
                                                <tr>
                                                    <td style="padding:13px 18px;font-size:12px;color:#8b7da8;width:36%;background:{{ $index % 2 === 0 ? '#faf8fd' : '#ffffff' }};border-top:1px solid #f0ebf8;">
                                                        {{ $row[0] }}
                                                    </td>
                                                    <td style="padding:13px 18px;font-size:14px;color:#1f2937;{{ $row[2] ? 'font-weight:700;' : '' }}background:{{ $index % 2 === 0 ? '#faf8fd' : '#ffffff' }};border-top:1px solid #f0ebf8;">
                                                        {{ $row[1] }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                            @if (!empty($leaveReason))
                                                <tr>
                                                    <td colspan="2" style="padding:14px 18px;background:#faf8fd;border-top:1px solid #f0ebf8;">
                                                        <p style="margin:0 0 6px;font-size:12px;color:#8b7da8;font-weight:600;">{{ __('Reason') }}</p>
                                                        <p style="margin:0;font-size:14px;line-height:1.55;color:#374151;">{{ $leaveReason }}</p>
                                                    </td>
                                                </tr>
                                            @endif
                                            @if (!empty($remarks))
                                                <tr>
                                                    <td colspan="2" style="padding:14px 18px;background:#ffffff;border-top:1px solid #f0ebf8;">
                                                        <p style="margin:0 0 6px;font-size:12px;color:#8b7da8;font-weight:600;">{{ __('Remarks') }}</p>
                                                        <p style="margin:0;font-size:14px;line-height:1.55;color:#374151;">{{ $remarks }}</p>
                                                    </td>
                                                </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            {{-- CTA (bulletproof button for Gmail/Outlook) --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:10px;">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center">
                                            <tr>
                                                <td align="center" bgcolor="#7c5cc4"
                                                    style="border-radius:10px;background-color:#7c5cc4;mso-padding-alt:14px 36px;">
                                                    <a href="{{ $actionUrl }}"
                                                        style="display:inline-block;padding:15px 36px;font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:15px;font-weight:700;line-height:1.2;color:#ffffff !important;text-decoration:none;border-radius:10px;background-color:#7c5cc4;border:1px solid #5f459f;">
                                                        <span style="color:#ffffff !important;">{{ $actionLabel }}</span>
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:16px 0 0;font-size:12px;line-height:1.65;color:#9ca3af;text-align:center;">
                                {{ __('If the button does not work, copy and paste this link into your browser:') }}<br>
                                <a href="{{ $actionUrl }}" style="color:#7c5cc4;text-decoration:none;word-break:break-all;">{{ $actionUrl }}</a>
                            </p>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding:22px 32px 28px;background:#faf8fd;border-top:1px solid #ece7f5;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="vertical-align:top;">
                                        <p style="margin:0 0 4px;font-size:14px;font-weight:700;color:#5f459f;">{{ $companyName }}</p>
                                        @if (!empty($companyEmail))
                                            <p style="margin:0 0 3px;font-size:12px;color:#6b7280;">{{ __('Email') }}: {{ $companyEmail }}</p>
                                        @endif
                                        @if (!empty($companyWebsite))
                                            <p style="margin:0;font-size:12px;color:#6b7280;">
                                                {{ __('Website') }}:
                                                <a href="{{ $companyWebsite }}" style="color:#7c5cc4;text-decoration:none;">{{ $companyWebsite }}</a>
                                            </p>
                                        @endif
                                    </td>
                                    <td align="right" style="vertical-align:bottom;">
                                        <p style="margin:0;font-size:11px;color:#9ca3af;line-height:1.5;">
                                            &copy; {{ $year }} {{ $siteTitle }}<br>{{ __('All rights reserved.') }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            @if (!empty($footerText))
                                <p style="margin:14px 0 0;font-size:11px;color:#9ca3af;line-height:1.5;border-top:1px solid #ece7f5;padding-top:12px;">
                                    {{ $footerText }}
                                </p>
                            @endif
                        </td>
                    </tr>
                </table>

                <p style="margin:16px 0 0;font-size:11px;color:#9ca3af;text-align:center;">
                    {{ __('This is an automated notification from') }} {{ $siteTitle }}.
                </p>
            </td>
        </tr>
    </table>
</body>

</html>
