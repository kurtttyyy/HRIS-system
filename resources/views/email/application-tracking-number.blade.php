@php
    $firstName = trim((string) ($applicant->first_name ?? ''));
    $middleName = trim((string) ($applicant->middle_name ?? ''));
    $lastName = trim((string) ($applicant->last_name ?? ''));
    $name = trim(preg_replace('/\s+/', ' ', trim($firstName.' '.$middleName.' '.$lastName)));
    $positionTitle = data_get($applicant, 'position.title')
        ?? data_get($applicant, 'position.position_name')
        ?? 'your selected position';
    $trackingNumber = trim((string) ($applicant->tracking_number ?? ''));
    $statusUrl = route('guest.application');
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application Tracking Number</title>
</head>
<body style="margin:0; padding:0; background:#f3f6f8; color:#1f2937; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f6f8; margin:0; padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; background:#ffffff; border:1px solid #e2e8f0; border-radius:18px; overflow:hidden;">
                    <tr>
                        <td style="background:#047857; padding:28px 32px; color:#ffffff;">
                            <p style="margin:0 0 8px; font-size:12px; font-weight:700; letter-spacing:.08em; text-transform:uppercase;">HRIS Recruitment</p>
                            <h1 style="margin:0; font-size:26px; line-height:1.25; font-weight:800;">Application Received</h1>
                            <p style="margin:10px 0 0; font-size:14px; line-height:1.6; color:#dcfce7;">Use your tracking number to check your application status.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 18px; font-size:15px; line-height:1.7;">Hello{{ $name !== '' ? ' '.$name : '' }},</p>
                            <p style="margin:0 0 20px; font-size:15px; line-height:1.7;">
                                Your application for <strong>{{ $positionTitle }}</strong> has been submitted successfully.
                                Please keep this tracking number because it will be used to track your application status.
                            </p>

                            <div style="margin:24px 0; padding:22px; background:#ecfdf5; border:1px solid #86efac; border-radius:16px; text-align:center;">
                                <p style="margin:0 0 8px; font-size:12px; font-weight:800; color:#047857; letter-spacing:.08em; text-transform:uppercase;">Tracking Number</p>
                                <p style="margin:0; font-size:28px; line-height:1.2; font-weight:900; color:#064e3b; letter-spacing:.08em;">{{ $trackingNumber }}</p>
                            </div>

                            <p style="margin:0 0 24px; font-size:15px; line-height:1.7;">
                                To view updates, open Application Status on the website and enter either your email address or this tracking number.
                            </p>

                            <p style="margin:0 0 28px;">
                                <a href="{{ $statusUrl }}" style="display:inline-block; padding:13px 20px; background:#16a34a; color:#ffffff; border-radius:999px; text-decoration:none; font-size:14px; font-weight:800;">Check Application Status</a>
                            </p>

                            <p style="margin:0; font-size:13px; line-height:1.6; color:#64748b;">
                                If you did not submit this application, please ignore this email or contact the HR office.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
