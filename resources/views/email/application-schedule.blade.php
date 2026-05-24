@php
    use Carbon\Carbon;

    $dateValue = $interview->date ?? null;
    $timeValue = $interview->time ?? null;

    try {
        $dateLabel = $dateValue ? Carbon::parse($dateValue)->format('F d, Y') : 'To be confirmed';
    } catch (\Throwable $exception) {
        $dateLabel = (string) $dateValue;
    }

    try {
        $timeLabel = $timeValue ? Carbon::parse($timeValue)->format('h:i A') : 'To be confirmed';
    } catch (\Throwable $exception) {
        $timeLabel = (string) $timeValue;
    }

    $duration = trim((string) ($interview->duration ?? ''));
    $interviewers = trim((string) ($interview->interviewers ?? ''));
    $emailLink = trim((string) ($interview->email_link ?? ''));
    $meetingUrl = trim((string) ($interview->url ?? ''));
    $notes = trim((string) ($interview->notes ?? ''));
    $applicant = $interview->applicant ?? null;
    $firstName = trim((string) data_get($applicant, 'first_name', ''));
    $middleName = trim((string) data_get($applicant, 'middle_name', ''));
    $lastName = trim((string) data_get($applicant, 'last_name', ''));
    $name = trim(preg_replace('/\s+/', ' ', trim($firstName.' '.$middleName.' '.$lastName)));
    $statusUrl = route('guest.application');
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Interview Schedule</title>
</head>
<body style="margin:0; padding:0; background:#f3f6f8; color:#1f2937; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6f8; margin:0; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:660px; background:#ffffff; border:1px solid #e2e8f0; border-radius:18px; overflow:hidden;">
                    <tr>
                        <td style="background:#2563eb; padding:28px 32px; color:#ffffff;">
                            <p style="margin:0 0 8px; font-size:12px; font-weight:700; letter-spacing:.08em; text-transform:uppercase;">HRIS Recruitment</p>
                            <h1 style="margin:0; font-size:26px; line-height:1.25; font-weight:800;">Interview Schedule</h1>
                            <p style="margin:10px 0 0; font-size:14px; line-height:1.6; color:#dbeafe;">You have been scheduled for an application interview.</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 18px; font-size:15px; line-height:1.7;">Hello{{ $name !== '' ? ' '.$name : '' }},</p>

                            <p style="margin:0 0 22px; font-size:15px; line-height:1.7;">
                                Your interview schedule has been confirmed. Please review the details below and prepare to join on time.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate; border-spacing:0; border:1px solid #e2e8f0; border-radius:14px; overflow:hidden;">
                                <tr>
                                    <td style="padding:16px 18px; background:#eff6ff; border-bottom:1px solid #dbeafe; font-size:13px; font-weight:800; color:#1d4ed8; width:34%;">Date</td>
                                    <td style="padding:16px 18px; background:#ffffff; border-bottom:1px solid #e2e8f0; font-size:15px; font-weight:700; color:#0f172a;">{{ $dateLabel }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 18px; background:#eff6ff; border-bottom:1px solid #dbeafe; font-size:13px; font-weight:800; color:#1d4ed8;">Time</td>
                                    <td style="padding:16px 18px; background:#ffffff; border-bottom:1px solid #e2e8f0; font-size:15px; font-weight:700; color:#0f172a;">{{ $timeLabel }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 18px; background:#eff6ff; border-bottom:1px solid #dbeafe; font-size:13px; font-weight:800; color:#1d4ed8;">Duration</td>
                                    <td style="padding:16px 18px; background:#ffffff; border-bottom:1px solid #e2e8f0; font-size:14px; color:#0f172a;">{{ $duration !== '' ? $duration : 'To be confirmed' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 18px; background:#eff6ff; font-size:13px; font-weight:800; color:#1d4ed8;">Interviewer</td>
                                    <td style="padding:16px 18px; background:#ffffff; font-size:14px; color:#0f172a;">{{ $interviewers !== '' ? $interviewers : 'To be assigned' }}</td>
                                </tr>
                            </table>

                            @if($meetingUrl !== '' || $emailLink !== '')
                                <table role="presentation" cellspacing="0" cellpadding="0" style="margin:26px 0 0;">
                                    <tr>
                                        @if($meetingUrl !== '')
                                            <td style="border-radius:12px; background:#2563eb;">
                                                <a href="{{ $meetingUrl }}" style="display:inline-block; padding:13px 20px; color:#ffffff; text-decoration:none; font-size:14px; font-weight:800;">
                                                    Open Meeting Link
                                                </a>
                                            </td>
                                        @endif

                                        @if($meetingUrl !== '' && $emailLink !== '')
                                            <td style="width:12px;"></td>
                                        @endif

                                        @if($emailLink !== '')
                                            <td style="border-radius:12px; background:#0f766e;">
                                                <a href="mailto:{{ $emailLink }}" style="display:inline-block; padding:13px 20px; color:#ffffff; text-decoration:none; font-size:14px; font-weight:800;">
                                                    Email Contact
                                                </a>
                                            </td>
                                        @endif
                                    </tr>
                                </table>
                            @endif

                            @if($meetingUrl !== '')
                                <p style="margin:18px 0 0; font-size:13px; line-height:1.6; color:#475569; word-break:break-word;">
                                    Meeting URL: <a href="{{ $meetingUrl }}" style="color:#2563eb;">{{ $meetingUrl }}</a>
                                </p>
                            @endif

                            @if($emailLink !== '')
                                <p style="margin:8px 0 0; font-size:13px; line-height:1.6; color:#475569; word-break:break-word;">
                                    Contact email: <a href="mailto:{{ $emailLink }}" style="color:#0f766e;">{{ $emailLink }}</a>
                                </p>
                            @endif

                            @if($notes !== '')
                                <div style="margin:26px 0 0; padding:18px; background:#f8fafc; border-left:4px solid #2563eb; border-radius:12px;">
                                    <p style="margin:0 0 6px; font-size:13px; font-weight:800; color:#0f172a;">Notes</p>
                                    <p style="margin:0; font-size:14px; line-height:1.7; color:#334155;">{{ $notes }}</p>
                                </div>
                            @endif

                            <div style="margin:26px 0 0; padding:18px; background:#f8fafc; border-radius:12px;">
                                <p style="margin:0; font-size:14px; line-height:1.7; color:#334155;">
                                    Please be available before the scheduled time and check your application status page for any updates.
                                </p>
                            </div>

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:24px 0 0;">
                                <tr>
                                    <td style="border-radius:12px; background:#0f172a;">
                                        <a href="{{ $statusUrl }}" style="display:inline-block; padding:13px 20px; color:#ffffff; text-decoration:none; font-size:14px; font-weight:800;">
                                            Check Application Status
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:28px 0 0; font-size:14px; line-height:1.7; color:#475569;">
                                Thank you,<br>
                                <strong style="color:#0f172a;">HRIS Recruitment Team</strong>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 32px; background:#f8fafc; border-top:1px solid #e2e8f0;">
                            <p style="margin:0; font-size:12px; line-height:1.6; color:#64748b;">
                                This is an automated notification. Please do not reply directly to this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
