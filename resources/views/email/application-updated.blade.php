@php
    $status = trim((string) ($review->application_status ?? 'Updated'));
    $firstName = trim((string) ($review->first_name ?? ''));
    $middleName = trim((string) ($review->middle_name ?? ''));
    $lastName = trim((string) ($review->last_name ?? ''));
    $name = trim(preg_replace('/\s+/', ' ', trim($firstName.' '.$middleName.' '.$lastName)));
    $positionTitle = data_get($review, 'position.position_name')
        ?? data_get($review, 'position.title')
        ?? data_get($review, 'openPosition.position_name')
        ?? data_get($review, 'openPosition.title')
        ?? ($review->position_applied ?? $review->position ?? null);
    $statusKey = strtolower($status);
    $isPassingDocument = str_contains($statusKey, 'passing document');
    $isHired = str_contains($statusKey, 'hired');
    $reportDate = $review->date_hired
        ? \Illuminate\Support\Carbon::parse($review->date_hired)->format('F d, Y')
        : null;
    $statusColor = match (true) {
        str_contains($statusKey, 'hired') => '#047857',
        str_contains($statusKey, 'reject') => '#be123c',
        str_contains($statusKey, 'interview') => '#2563eb',
        str_contains($statusKey, 'review') => '#7c3aed',
        default => '#0f766e',
    };
    $statusBackground = match (true) {
        str_contains($statusKey, 'hired') => '#d1fae5',
        str_contains($statusKey, 'reject') => '#ffe4e6',
        str_contains($statusKey, 'interview') => '#dbeafe',
        str_contains($statusKey, 'review') => '#ede9fe',
        default => '#ccfbf1',
    };
    $statusUrl = !empty($review->tracking_number)
        ? route('guest.application', ['application_lookup' => $review->tracking_number])
        : route('guest.application');
    $registerUrl = route('register');
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Status Update</title>
</head>
<body style="margin:0; padding:0; background:#f3f6f8; color:#1f2937; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6f8; margin:0; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; background:#ffffff; border:1px solid #e2e8f0; border-radius:18px; overflow:hidden;">
                    <tr>
                        <td style="background:#0f766e; padding:28px 32px; color:#ffffff;">
                            <p style="margin:0 0 8px; font-size:12px; font-weight:700; letter-spacing:.08em; text-transform:uppercase;">HRIS Recruitment</p>
                            <h1 style="margin:0; font-size:26px; line-height:1.25; font-weight:800;">{{ $isHired ? 'Congratulations, You Are Hired' : 'Application Status Update' }}</h1>
                            <p style="margin:10px 0 0; font-size:14px; line-height:1.6; color:#d9f7ef;">
                                {{ $isHired ? 'Your application has been approved for employment.' : 'There has been a new update on your submitted application.' }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 18px; font-size:15px; line-height:1.7;">Hello{{ $name !== '' ? ' '.$name : '' }},</p>

                            <p style="margin:0 0 20px; font-size:15px; line-height:1.7;">
                                @if($isHired)
                                    Congratulations. You have been hired for the position below. Please report or start work on the date listed and create your employee account to access the HR portal.
                                @elseif($isPassingDocument)
                                    Your application has moved to the document passing stage. Please prepare and submit the remaining requirements to the HR Office for verification.
                                @else
                                    Your application status has been updated. Please review the latest status below and check the website for any additional instructions.
                                @endif
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate; border-spacing:0; border:1px solid #e2e8f0; border-radius:14px; overflow:hidden;">
                                <tr>
                                    <td style="padding:16px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:13px; font-weight:700; color:#475569; width:38%;">Current Status</td>
                                    <td style="padding:16px 18px; background:#ffffff; border-bottom:1px solid #e2e8f0;">
                                        <span style="display:inline-block; padding:8px 14px; border-radius:999px; background:{{ $statusBackground }}; color:{{ $statusColor }}; font-size:13px; font-weight:800;">
                                            {{ $status }}
                                        </span>
                                    </td>
                                </tr>

                                @if(!empty($positionTitle))
                                    <tr>
                                        <td style="padding:16px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:13px; font-weight:700; color:#475569;">Position</td>
                                        <td style="padding:16px 18px; background:#ffffff; border-bottom:1px solid #e2e8f0; font-size:14px; color:#0f172a;">{{ $positionTitle }}</td>
                                    </tr>
                                @endif

                                @if($isHired && $reportDate)
                                    <tr>
                                        <td style="padding:16px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0; font-size:13px; font-weight:700; color:#475569;">Report / Start Date</td>
                                        <td style="padding:16px 18px; background:#ffffff; border-bottom:1px solid #e2e8f0; font-size:14px; font-weight:800; color:#047857;">{{ $reportDate }}</td>
                                    </tr>
                                @endif

                                <tr>
                                    <td style="padding:16px 18px; background:#f8fafc; font-size:13px; font-weight:700; color:#475569;">Updated On</td>
                                    <td style="padding:16px 18px; background:#ffffff; font-size:14px; color:#0f172a;">
                                        {{ optional($review->updated_at)->format('F d, Y h:i A') ?? now()->format('F d, Y h:i A') }}
                                    </td>
                                </tr>
                            </table>

                            @if($isHired)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:28px 0; border-collapse:separate; border-spacing:0; background:#ecfdf5; border:1px solid #86efac; border-radius:16px; overflow:hidden;">
                                    <tr>
                                        <td style="padding:22px;">
                                            <p style="margin:0 0 8px; font-size:12px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#047857;">Welcome Aboard</p>
                                            <h2 style="margin:0 0 10px; font-size:21px; line-height:1.35; color:#064e3b;">You may create your employee account now</h2>
                                            <p style="margin:0; font-size:14px; line-height:1.7; color:#14532d;">
                                                Your account registration is now available. Create your account using the same email address from your application, then report to the HR Office or start work on your scheduled date.
                                            </p>

                                            @if($reportDate)
                                                <div style="margin-top:16px; padding:14px 16px; background:#ffffff; border:1px solid #bbf7d0; border-radius:12px;">
                                                    <p style="margin:0; font-size:12px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#047857;">Report / Start Date</p>
                                                    <p style="margin:6px 0 0; font-size:18px; font-weight:800; color:#0f172a;">{{ $reportDate }}</p>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            @elseif($isPassingDocument)
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:28px 0; border-collapse:separate; border-spacing:0; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:16px; overflow:hidden;">
                                    <tr>
                                        <td style="padding:20px 22px;">
                                            <p style="margin:0 0 8px; font-size:12px; font-weight:800; letter-spacing:.08em; text-transform:uppercase; color:#047857;">Action Required</p>
                                            <h2 style="margin:0 0 10px; font-size:20px; line-height:1.35; color:#064e3b;">Submit your remaining requirements to the HR Office</h2>
                                            <p style="margin:0; font-size:14px; line-height:1.7; color:#14532d;">
                                                Bring or submit the required documents requested by HR. Your application will continue after the HR Office receives and verifies the requirements.
                                            </p>

                                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:16px; border-collapse:collapse;">
                                                <tr>
                                                    <td style="padding:10px 0; border-top:1px solid #bbf7d0; font-size:13px; line-height:1.6; color:#166534;">
                                                        <strong style="color:#065f46;">Reminder:</strong> Use the same email address from your application when checking your status online.
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:10px 0 0; border-top:1px solid #bbf7d0; font-size:13px; line-height:1.6; color:#166534;">
                                                        <strong style="color:#065f46;">Next step:</strong> Check the Application Status page for any listed document instructions, then coordinate with the HR Office.
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            @else
                                <div style="margin:28px 0; padding:18px; background:#f8fafc; border-left:4px solid {{ $statusColor }}; border-radius:12px;">
                                    <p style="margin:0; font-size:14px; line-height:1.7; color:#334155;">
                                        For complete details, open the Application Status page using the same email address you used when submitting your application.
                                    </p>
                                </div>
                            @endif

                            <table role="presentation" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="border-radius:12px; background:#0f766e;">
                                        <a href="{{ $isHired ? $registerUrl : $statusUrl }}" style="display:inline-block; padding:13px 20px; color:#ffffff; text-decoration:none; font-size:14px; font-weight:800;">
                                            {{ $isHired ? 'Create Employee Account' : ($isPassingDocument ? 'View Required Documents' : 'Check Application Status') }}
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
