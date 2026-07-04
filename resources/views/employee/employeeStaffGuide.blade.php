<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Guide - Northeastern College</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body { transition: margin-left 0.3s ease; }
        main { transition: margin-left 0.3s ease; }
        aside:not(:hover) ~ main { margin-left: 4rem; }
        aside:hover ~ main { margin-left: 14rem; }

        #employee-staff-guide-page .employee-staff-guide-reveal {
            opacity: 1;
            transform: translateY(0);
            will-change: opacity, transform;
        }

        #employee-staff-guide-page .employee-staff-guide-reveal.is-scroll-animated {
            animation: employee-staff-guide-scroll-fade-up 0.72s cubic-bezier(0.22, 1, 0.36, 1) both;
            animation-delay: var(--employee-staff-guide-delay, 0ms);
        }

        @keyframes employee-staff-guide-scroll-fade-up {
            0% { opacity: 0; transform: translateY(26px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        #employee-staff-guide-page .employee-staff-guide-card-motion {
            transition:
                transform 0.25s ease,
                box-shadow 0.25s ease,
                border-color 0.25s ease;
        }

        #employee-staff-guide-page .employee-staff-guide-card-motion:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.12);
        }

        #employee-staff-guide-page .employee-staff-guide-icon-pop {
            opacity: 0;
            transform: scale(0.86) rotate(-4deg);
            transition:
                opacity 0.55s ease,
                transform 0.55s cubic-bezier(0.22, 1, 0.36, 1);
            transition-delay: var(--employee-staff-guide-delay, 120ms);
        }

        #employee-staff-guide-page .is-visible .employee-staff-guide-icon-pop,
        #employee-staff-guide-page .employee-staff-guide-icon-pop.is-visible {
            opacity: 1;
            transform: scale(1) rotate(0deg);
        }

        @media (prefers-reduced-motion: reduce) {
            #employee-staff-guide-page .employee-staff-guide-reveal,
            #employee-staff-guide-page .employee-staff-guide-icon-pop,
            #employee-staff-guide-page .employee-staff-guide-card-motion {
                opacity: 1;
                transform: none;
                transition: none;
                animation: none;
                will-change: auto;
            }
        }
    </style>
</head>
<body class="bg-slate-100">

<div class="flex min-h-screen">
    @include('components.employeeSideBar')

    <main class="flex-1 ml-16 transition-all duration-300">
        <div id="employee-staff-guide-page" class="space-y-8 p-4 md:p-8">
            @php
                $systemOverview = [
                    [
                        'icon' => 'fa-users',
                        'title' => 'What the system is for',
                        'text' => 'The HRIS helps staff access employee records, attendance summaries, leave requests, payslips, required documents, notifications, and HR communication in one employee portal.',
                    ],
                    [
                        'icon' => 'fa-exchange',
                        'title' => 'How information moves',
                        'text' => 'Administrators maintain records such as employee details, attendance uploads, required documents, leave approvals, payslips, and resignation status. Staff review those updates from the employee side.',
                    ],
                    [
                        'icon' => 'fa-check-circle',
                        'title' => 'What staff should do',
                        'text' => 'Staff should keep profile details accurate, complete document requirements, file requests correctly, monitor notifications, and use communication for HR follow-ups.',
                    ],
                ];

                $guideSections = [
                    [
                        'id' => 'system-overview',
                        'number' => '01',
                        'short' => 'Overview',
                        'title' => 'System Overview',
                        'subtitle' => 'Understand the purpose of the employee side before using each module.',
                        'icon' => 'fa-sitemap',
                        'details' => 'The employee side is the staff-facing part of the HRIS. It does not replace HR, but it helps employees see the records and updates connected to their account without waiting for manual follow-ups. Staff can review information, submit forms, upload requirements, and communicate with the office using the portal.',
                        'manual' => [
                            'Purpose' => 'This HRIS is designed to centralize employee-related processes. Instead of keeping attendance concerns, leave requests, payslips, document requirements, and messages in separate manual channels, the system gives staff one place to check their own records and submit needed actions.',
                            'What staff can do' => 'Staff can view their dashboard, check profile and employment details, upload required documents, file leave or official business forms, review attendance summaries, view released payslips, send messages, read notifications, and submit resignation requests when applicable.',
                            'What HR or admin controls' => 'Administrators control employee approval, employee records, attendance uploads, required document setup, leave request decisions, payslip uploads, resignation status updates, and some department-head access. If staff see wrong information, the correction usually needs HR or administrator review.',
                            'Important reminder' => 'The system should be treated as the official employee-side record viewer. Staff should regularly check it because new updates may appear after HR uploads files, approves requests, scans payslips, or changes account records.',
                        ],
                        'steps' => [
                            ['title' => 'Know the main purpose', 'text' => 'The employee side is made for staff self-service. It lets employees check HR records, attendance, leave, payslips, documents, messages, notifications, and resignation records from one account.'],
                            ['title' => 'Understand the employee and admin connection', 'text' => 'Administrators manage many records that appear in the employee portal. This includes attendance files, payslip files, required document lists, employee status, leave decisions, and resignation status. Staff view these records and submit needed responses.'],
                            ['title' => 'Know what staff can update or submit', 'text' => 'Staff can upload documents, file leave or official business forms, send communication messages, and submit resignation details. Other records, such as approval status or payroll data, are updated by administrators.'],
                            ['title' => 'Use the guide as a daily reference', 'text' => 'Follow the sections below whenever you need to know where to go, what to check, or what action to take inside the system.'],
                        ],
                        'tips' => [
                            'Staff use the employee portal for self-service tasks.',
                            'Admins control many records that appear on the employee side.',
                            'Use the sidebar labels Main and Help to navigate faster.',
                        ],
                    ],
                    [
                        'id' => 'login-dashboard',
                        'number' => '02',
                        'short' => 'Login',
                        'title' => 'Login and Dashboard Overview',
                        'subtitle' => 'Start every session from the dashboard and review the alerts that need attention.',
                        'icon' => 'fa-dashboard',
                        'details' => 'The dashboard is the first workspace after login. It summarizes the most important employee information, including leave balance, attendance status, weekly attendance, upcoming events, account alerts, and quick links to common tasks.',
                        'manual' => [
                            'Purpose' => 'The dashboard is the main landing page for staff. It gives a quick summary of the employee account so the user can immediately see if something needs attention.',
                            'What staff should check first' => 'Staff should look at the account alerts, leave balance, attendance rate, current attendance status, and weekly attendance records. If an alert appears, it usually means there is a pending item such as missing documents, rejected leave, unread messages, or attendance follow-up.',
                            'How quick actions work' => 'Quick actions are shortcut buttons that open the most used employee pages: Leave Requests, Documents, Payslips, and Communication or Directory. They are provided so staff do not need to search through the sidebar for common tasks.',
                            'Example use case' => 'If a staff member logs in and sees a missing document alert, they should click the alert or open Documents, check the missing document list, upload the correct file, and then return to the dashboard later to confirm the alert is cleared.',
                        ],
                        'steps' => [
                            ['title' => 'Sign in to your employee account', 'text' => 'Open the login page, enter your registered employee email and password, then continue to the employee dashboard. Only approved employee accounts can access the employee side.'],
                            ['title' => 'Check the dashboard cards', 'text' => 'Review the month, attendance status, available leave, attendance rate, upcoming events, and payment hub. These cards give a quick picture of your current HR records.'],
                            ['title' => 'Use quick actions', 'text' => 'The dashboard includes quick links for Leave, Documents, Payslip, and Directory. Use them when you want to open common employee tasks faster.'],
                            ['title' => 'Open account alerts', 'text' => 'If the dashboard shows missing documents, rejected leave, unread messages, attendance follow-ups, or other alerts, open the linked page and resolve the item.'],
                        ],
                        'tips' => [
                            'Use the sidebar to move between employee pages.',
                            'The bell icon opens the notification center.',
                            'The profile menu contains My Profile and Logout.',
                        ],
                    ],
                    [
                        'id' => 'profile-password',
                        'number' => '03',
                        'short' => 'Profile',
                        'title' => 'Profile and Account Details',
                        'subtitle' => 'Confirm your personal and employment information before submitting requests.',
                        'icon' => 'fa-user',
                        'details' => 'The profile page shows employee identity and employment-related information connected to the account. These details help HR verify leave forms, documents, payroll records, communication records, and employee reports.',
                        'manual' => [
                            'Purpose' => 'The profile page helps staff verify the personal and employment information connected to their account. This information supports HR validation when processing requests and reviewing employee records.',
                            'What staff should review' => 'Staff should check their full name, email, contact number, address, employee ID, position, department, and other employment-related details. These values should match the records used by HR.',
                            'What to do if information is wrong' => 'If any profile information is outdated or incorrect, staff should not create duplicate accounts or submit unrelated forms. They should contact HR through Communication or directly ask HR to update the record.',
                            'Why accuracy matters' => 'Incorrect profile details can cause confusion when matching attendance records, payslips, leave requests, uploaded documents, or department records. Keeping profile information accurate helps reduce delays.',
                        ],
                        'steps' => [
                            ['title' => 'Open My Profile', 'text' => 'Click the user icon in the page header, then choose My Profile. Some pages also include a profile shortcut in the upper section.'],
                            ['title' => 'Review personal information', 'text' => 'Check your full name, position, department, contact number, address, employee ID, and related employee details. These are used when HR reviews requests and records.'],
                            ['title' => 'Confirm employment information', 'text' => 'Review the department and position shown on your account. If your role or department changed recently, verify that the system already reflects the update.'],
                            ['title' => 'Report corrections to HR', 'text' => 'If the system shows outdated or incorrect records, send a message through Communication or contact HR directly. Do not submit repeated forms just to correct profile data.'],
                        ],
                        'tips' => [
                            'Use accurate profile details when filing leave or other forms.',
                            'Your employee records are used for HR validation.',
                            'Do not share your account password with other users.',
                        ],
                    ],
                    [
                        'id' => 'documents',
                        'number' => '04',
                        'short' => 'Documents',
                        'title' => 'Document Submission',
                        'subtitle' => 'Upload missing 201 file requirements and organize records in folders.',
                        'icon' => 'fa-folder-open',
                        'details' => 'The Document Center is used for employee 201 file completion and personal file organization. It shows uploaded files, created folders, missing requirements, completion percentage, latest upload, and admin notices.',
                        'manual' => [
                            'Purpose' => 'The Documents page is where staff complete their employee 201 file requirements. It also works as a personal document center where staff can organize uploaded files into folders.',
                            'Missing document list' => 'The missing document list tells the staff which required files still need to be submitted. This list may be based on default requirements or specific requirements assigned by the administrator.',
                            'Upload process' => 'When uploading, staff should type the document name based on the missing requirement, choose a folder if they want to organize it immediately, attach the file, and save. The system stores the file under the employee document records.',
                            'Folders and organization' => 'Folders help staff group files such as IDs, certificates, clearances, or supporting documents. Files can be moved to folders using the document options or drag-and-drop folder targets.',
                            'Common mistake to avoid' => 'Staff should avoid uploading a file with a vague name like “document” or “file”. The document name should match the requirement, such as “Transcript Of Records” or “Personal Data Sheet”, so HR can validate it easily.',
                        ],
                        'steps' => [
                            ['title' => 'Open Documents', 'text' => 'Use the sidebar or dashboard quick action to open the Document Center. The top section shows totals for all documents, folders, missing requirements, and completion.'],
                            ['title' => 'Check missing documents', 'text' => 'Use the Missing Document list as your upload guide. If HR assigned a specific list, follow that list before uploading optional or extra files.'],
                            ['title' => 'Upload the file', 'text' => 'Enter the document name based on the missing requirement, choose a folder if needed, attach the file, then click Save Document. The upload becomes part of your employee document records.'],
                            ['title' => 'Review latest upload and admin notice', 'text' => 'After saving, check the Latest Upload area and read any Admin Notice shown in the form. This helps confirm that the file was received and that no instruction was missed.'],
                            ['title' => 'Organize saved files', 'text' => 'Create folders, move files using the menu or drag-and-drop, or keep documents in Unfiled until you organize them later. Remove files only when you are sure they are incorrect or unnecessary.'],
                        ],
                        'tips' => [
                            'Accepted uploads are PDF, XLSX, DOC, and DOCX files up to 5MB.',
                            'Name uploads based on the missing document label.',
                            'Review admin notices before submitting files.',
                        ],
                    ],
                    [
                        'id' => 'leave-payslip',
                        'number' => '05',
                        'short' => 'Leave',
                        'title' => 'Leave Requests and Official Business',
                        'subtitle' => 'Review leave credits, submit the updated forms, download a Word copy, and track approval status.',
                        'icon' => 'fa-calendar-check-o',
                        'details' => 'The updated Leave Requests page combines leave balances, monthly request totals, request history, the Leave Application form, and the Official Business / Official Time form. Staff can complete a request, submit it for review, and download its Word copy without leaving the page.',
                        'manual' => [
                            'Leave dashboard' => 'The summary cards show available Vacation, Sick, and Other Leave credits, their used amounts, and the total days used for the selected month. The request counters show how many requests are pending, approved, or rejected.',
                            'Month filter and history' => 'Choose a month and select Apply Month Filter to refresh the dashboard and My Leave History. Each history card shows the request type, number of days, inclusive dates, details, and current status.',
                            'Leave application form' => 'Use Leave Application for vacation, sick, or another leave type. Enter the working days requested and check the automatically calculated inclusive dates, applied credits, ending balances, and days with or without pay before continuing.',
                            'Sick Leave attachment' => 'Selecting Sick Leave displays a required Medical Certificate / Receipt upload. Attach a PDF, JPG, JPEG, PNG, WEBP, HEIC, or HEIF file no larger than 15 MB; the request cannot be submitted without it.',
                            'Official business or official time form' => 'Use Official Business / Official Time only for work-related activities. Enter the type, working days, purpose, venue, and dates. These requests are treated as work with pay and do not consume vacation or sick leave credits.',
                            'Submit and download' => 'Select Download Word Form after reviewing the form. For a Leave Application, this saves the request as Pending and then downloads a Word copy. Keep the downloaded copy for signatures or other HR requirements.',
                            'Approval flow' => 'HR or the administrator reviews submitted requests. Monitor My Leave History and Notifications because a Pending request may later be Approved or Rejected.',
                        ],
                        'steps' => [
                            ['title' => 'Review the Leave Desk', 'text' => 'Check available vacation, sick, and other leave credits, days already used, and the request totals for the selected month.'],
                            ['title' => 'Use the month filter', 'text' => 'Choose a month and select Apply Month Filter to review the balances, usage, and request records for that period.'],
                            ['title' => 'Choose the correct form', 'text' => 'Use Leave Application for vacation, sick, or other leave credits. Use Official Business / Official Time when the request is work-related and not a regular leave credit request.'],
                            ['title' => 'Complete and verify the form', 'text' => 'Check the employee details, leave or business type, working days, automatically filled inclusive dates, purpose or supporting details, and calculated credit balances.'],
                            ['title' => 'Attach Sick Leave proof', 'text' => 'For Sick Leave, upload the required medical certificate or receipt as a supported PDF or image file up to 15 MB.'],
                            ['title' => 'Submit and keep a copy', 'text' => 'Select Download Word Form. Wait while the Leave Application is saved, then keep the downloaded Word copy for any required signatures or HR follow-up.'],
                            ['title' => 'Monitor request history', 'text' => 'Return to My Leave History or Notifications to check whether the submitted request is pending, approved, or rejected.'],
                            ['title' => 'Follow up when needed', 'text' => 'If a request is rejected or needs attention, contact HR before correcting and filing a new request.'],
                        ],
                        'tips' => [
                            'Selecting Sick Leave makes the medical certificate or receipt mandatory.',
                            'The form recalculates applied credits and remaining balances from the requested days.',
                            'Downloading the Leave Application Word form also submits the request as Pending.',
                            'Official Business / Official Time is work with pay and does not deduct regular leave credits.',
                        ],
                    ],
                    [
                        'id' => 'attendance',
                        'number' => '06',
                        'short' => 'Attendance',
                        'title' => 'Attendance Monitoring',
                        'subtitle' => 'Use the dashboard to review weekly attendance, late minutes, absences, and missing logs.',
                        'icon' => 'fa-clock-o',
                        'details' => 'Attendance information is displayed mainly on the dashboard. It helps staff review whether attendance records are complete and whether there are possible issues such as absence, tardiness, holiday attendance, or missing time logs.',
                        'manual' => [
                            'Purpose' => 'Attendance monitoring lets staff review their attendance records after HR or the administrator uploads attendance data. It gives staff visibility into their weekly logs and attendance rate.',
                            'What the dashboard shows' => 'The dashboard can show attendance status, attendance percentage, present days, total counted days, and weekly morning and afternoon time logs.',
                            'Possible attendance issues' => 'The system may identify absences, late minutes, missing time logs, or incomplete entries. These items may also appear as notifications or account alerts.',
                            'How staff should respond' => 'If staff believe an attendance record is incorrect, they should contact HR or the assigned office with the date and expected correction. The staff side is for viewing and follow-up, while correction is usually handled by HR or admin.',
                            'Important note' => 'Attendance results depend on the uploaded attendance file and matching employee information. If employee ID or name matching is incorrect, HR may need to review the source record.',
                        ],
                        'steps' => [
                            ['title' => 'Open the Dashboard', 'text' => 'The dashboard shows attendance rate, current attendance status, and this week\'s attendance records.'],
                            ['title' => 'Review daily logs', 'text' => 'Check morning and afternoon time ranges to confirm if your attendance entries are complete. Missing logs may show as incomplete or may affect the attendance summary.'],
                            ['title' => 'Understand attendance status', 'text' => 'The system can show present, absent, tardy, no data, holiday, or missing-log related states depending on uploaded attendance records.'],
                            ['title' => 'Watch for attendance alerts', 'text' => 'The system can show absences, tardiness, or missing logs as notifications or dashboard alerts. These alerts are follow-up reminders.'],
                            ['title' => 'Report concerns early', 'text' => 'If the attendance shown is wrong, contact HR or the assigned office so the record can be reviewed. Include the date and the expected correction when reporting.'],
                        ],
                        'tips' => [
                            'Attendance data depends on uploaded biometric or attendance records.',
                            'Missing time logs can affect attendance summaries.',
                            'Dashboard attendance is for review and follow-up.',
                        ],
                    ],
                    [
                        'id' => 'payslips',
                        'number' => '07',
                        'short' => 'Payslips',
                        'title' => 'Payslips and Payroll Records',
                        'subtitle' => 'Review released payroll records and confirm pay details.',
                        'icon' => 'fa-file-text-o',
                        'details' => 'The Payslips page displays payroll records after administrator upload and scanning. Staff can check pay period, pay date, gross pay, deductions, other income, and net pay, then open detailed payslip advice when available.',
                        'manual' => [
                            'Purpose' => 'The Payslips page gives staff access to payroll records released through the system. It helps employees review salary details without asking HR for every payslip copy.',
                            'What staff can see' => 'Staff can see the pay period, pay date, release status, gross pay, deductions, other income, and net pay. When records are available, staff can open a detailed payslip advice view.',
                            'Where data comes from' => 'Payslip records appear after the administrator uploads and scans payroll files. If no payslip is shown, it may mean the payroll file has not been uploaded, scanned, or matched to the employee account yet.',
                            'How to review a payslip' => 'Staff should compare the pay date, employee ID, employee name, earnings, deductions, and net pay. If something looks incorrect, they should contact HR or payroll with the exact pay period and item being questioned.',
                            'Common reminder' => 'Payslip details should be reviewed carefully, especially deductions and net pay. The system displays the records, but payroll corrections are handled by HR or the payroll office.',
                        ],
                        'steps' => [
                            ['title' => 'Open Payslips', 'text' => 'Use the sidebar to open the Payslips page and view released payroll records. If no payslip appears, it may not have been uploaded or scanned yet.'],
                            ['title' => 'Check the payroll snapshot', 'text' => 'Review gross pay, deductions, other income, net pay, pay period, pay date, and release status. This gives a quick payroll summary.'],
                            ['title' => 'Open recent payslips', 'text' => 'Use the Recent Payslips list to select a specific payroll record. The latest release is usually shown near the top.'],
                            ['title' => 'View detailed payslip advice', 'text' => 'Open a payslip record to see the detailed breakdown of earnings, deductions, and net pay. Use this section when verifying payroll components.'],
                            ['title' => 'Clarify payroll concerns', 'text' => 'If an amount looks incorrect, contact HR or payroll with the pay date, pay period, and item you want reviewed.'],
                        ],
                        'tips' => [
                            'Payslips appear after admin upload and scanning.',
                            'Use the latest pay date to confirm the correct payroll record.',
                            'Older payroll records may be available in the payslip history list.',
                        ],
                    ],
                    [
                        'id' => 'communication',
                        'number' => '08',
                        'short' => 'Messages',
                        'title' => 'Communication and Notifications',
                        'subtitle' => 'Use messages and notifications to follow HR updates and employee concerns.',
                        'icon' => 'fa-comments',
                        'details' => 'Communication is the employee-side message area. Notifications are the system-generated follow-up list for records that changed or need attention. Together, these tools help staff avoid missing important HR updates.',
                        'manual' => [
                            'Purpose' => 'Communication and Notifications help staff stay updated. Communication is used for direct messages, while Notifications show system updates related to employee records.',
                            'Communication page' => 'The Communication page lets staff find people or offices, open conversations, read messages, and send replies. It is useful for HR questions, document concerns, leave follow-ups, payroll clarification, and account record issues.',
                            'Notification center' => 'The Notification page collects recent updates such as leave request status, payslip availability, attendance follow-ups, and resignation updates. Staff should treat it as a follow-up checklist.',
                            'Unread counts' => 'The sidebar may show a badge beside Communication when there are unread messages. Staff should open Communication to read and respond so important concerns are not missed.',
                            'Good communication practice' => 'When sending a message, staff should include clear details such as date, request type, document name, pay period, or attendance date. This helps HR understand and resolve the concern faster.',
                        ],
                        'steps' => [
                            ['title' => 'Open Notifications', 'text' => 'Use the bell icon or sidebar notification link to view leave, payslip, attendance, and resignation updates. Notifications help identify records you should review.'],
                            ['title' => 'Use Communication', 'text' => 'Open Communication to read messages and send replies or concerns to HR or assigned personnel. Use this for questions about documents, leave, payroll, or account details.'],
                            ['title' => 'Search or select a contact', 'text' => 'The communication area can show available people or offices. Choose the correct contact before sending a message so the concern reaches the right receiver.'],
                            ['title' => 'Track unread messages', 'text' => 'Unread message counts appear beside Communication in the sidebar. Open the page to review new replies and keep conversations updated.'],
                            ['title' => 'Use notifications as your follow-up list', 'text' => 'Notifications help you see recent changes to leave, payslip, attendance, documents, and resignation records.'],
                        ],
                        'tips' => [
                            'Unread message counts appear beside Communication.',
                            'Notifications help you track records that changed recently.',
                            'Logout after using a shared device.',
                        ],
                    ],
                    [
                        'id' => 'resignation-more',
                        'number' => '09',
                        'short' => 'Other',
                        'title' => 'Resignation and Department Head Tools',
                        'subtitle' => 'Use special pages only when they apply to your role or employment status.',
                        'icon' => 'fa-ellipsis-h',
                        'details' => 'The Resignation page is for formal resignation submissions and status tracking. Department head tools are role-based pages that appear only when the employee account is approved as a department head.',
                        'manual' => [
                            'Purpose' => 'The Resignation page is used only for formal resignation processing. It gives staff a place to submit resignation details and monitor status updates from HR or the administrator.',
                            'Before submitting' => 'Staff should review the resignation details carefully before submitting. This type of request may affect employment records, account status, and HR processing.',
                            'Status tracking' => 'After submission, staff can return to the Resignation page or Notifications to check updates. Status may remain pending until the administrator reviews and updates the request.',
                            'Department head tools' => 'Some employees may see a More menu if their account is approved as a department head. This may include Employee Hierarchy and Evaluation pages for department-related viewing.',
                            'Access rule' => 'Regular staff may not see department-head tools. If a department head cannot see the More menu, HR or the administrator may need to check the account access setting.',
                        ],
                        'steps' => [
                            ['title' => 'Use Resignation only when needed', 'text' => 'Open the Resignation page only if you need to submit formal resignation details. Review the information before submitting because HR will use it for processing.'],
                            ['title' => 'Check resignation status', 'text' => 'After submitting, return to the Resignation page or Notifications to see updates from the administrator. Status changes help you know whether the request is still pending, approved, or completed.'],
                            ['title' => 'Open More if you are a department head', 'text' => 'Approved department heads may see Employee Hierarchy and Evaluation under the More menu. Regular employee accounts may not see these options.'],
                            ['title' => 'Review hierarchy and evaluation pages', 'text' => 'Use these pages to view department structure and evaluation-related information when your account has access.'],
                        ],
                        'tips' => [
                            'The More menu appears only for approved department heads.',
                            'Resignation requests are reviewed by the administrator.',
                            'Use Communication if you need clarification before submitting resignation.',
                        ],
                    ],
                ];
            @endphp

            <section class="relative overflow-hidden rounded-[2rem] border border-emerald-950/40 bg-gradient-to-br from-slate-950 via-emerald-950 to-emerald-800 text-white shadow-2xl">
                <div class="absolute -bottom-20 left-12 h-56 w-56 rounded-full border border-white/15"></div>
                <div class="absolute -bottom-28 left-44 h-72 w-72 rounded-full border border-white/10"></div>
                <div class="relative p-6 md:p-8">
                    <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-[11px] font-black uppercase tracking-[0.18em] text-emerald-100">
                        <i class="fa fa-sitemap"></i>
                        System Documentation
                    </div>
                    <h1 class="mt-5 text-4xl font-black tracking-tight md:text-5xl">Staff Guide</h1>
                    <p class="mt-3 max-w-3xl text-sm font-medium leading-6 text-emerald-50 md:text-base">
                        Complete employee-side documentation for how the HRIS works, what each staff module is for, and what actions employees should take from login to daily HR follow-ups.
                    </p>

                    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="border-white/15 sm:border-r">
                            <p class="text-3xl font-black">{{ count($guideSections) }}</p>
                            <p class="mt-1 text-xs font-black uppercase tracking-[0.18em] text-emerald-100">Procedures</p>
                        </div>
                        <div class="border-white/15 sm:border-r">
                            <p class="text-3xl font-black">12</p>
                            <p class="mt-1 text-xs font-black uppercase tracking-[0.18em] text-emerald-100">Pages Covered</p>
                        </div>
                        <div>
                            <p class="text-3xl font-black">Staff</p>
                            <p class="mt-1 text-xs font-black uppercase tracking-[0.18em] text-emerald-100">Role Required</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                @foreach ($systemOverview as $item)
                    <article class="rounded-[1.75rem] border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-6 shadow-sm">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/20">
                            <i class="fa {{ $item['icon'] }} text-lg"></i>
                        </div>
                        <h2 class="mt-5 text-lg font-black text-slate-900">{{ $item['title'] }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $item['text'] }}</p>
                    </article>
                @endforeach
            </section>

            <section class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-400 xl:w-24">Jump To</p>
                    <div class="flex flex-1 flex-wrap gap-3">
                        @foreach ($guideSections as $section)
                            <a href="#{{ $section['id'] }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-black text-slate-700 transition hover:border-emerald-200 hover:bg-emerald-50 hover:text-emerald-700">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-700 text-[11px] text-white">
                                    <i class="fa {{ $section['icon'] }}"></i>
                                </span>
                                <span class="text-[10px] font-black text-slate-400">{{ $section['number'] }}</span>
                                <span>{{ $section['short'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="space-y-6">
                @foreach ($guideSections as $section)
                    @php
                        $procedureLayout = $loop->index % 4;
                    @endphp
                    <article id="{{ $section['id'] }}" class="overflow-hidden rounded-[2rem] border border-emerald-200 bg-white shadow-sm">
                        <div class="relative overflow-hidden bg-gradient-to-br from-slate-950 via-emerald-950 to-emerald-700 px-6 py-5 text-white">
                            <div class="relative flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-2xl font-black">
                                        {{ $section['number'] }}
                                    </div>
                                    <div>
                                        <h2 class="text-2xl font-black tracking-tight">{{ $section['title'] }}</h2>
                                        <p class="mt-1 text-sm font-medium leading-6 text-emerald-50">{{ $section['subtitle'] }}</p>
                                    </div>
                                </div>
                                <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-2xl">
                                    <i class="fa {{ $section['icon'] }}"></i>
                                </div>
                            </div>
                        </div>

                        @if ($procedureLayout === 0)
                            <div class="grid gap-6 p-6 lg:grid-cols-[1fr_340px]">
                                <div class="space-y-6">
                                    @if (!empty($section['details'] ?? ''))
                                        <div class="rounded-[1.5rem] border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-5">
                                            <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">How this part works</p>
                                            <p class="mt-3 text-sm leading-7 text-slate-700">{{ $section['details'] }}</p>
                                        </div>
                                    @endif

                                    @if (!empty($section['manual'] ?? []))
                                        <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
                                            <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Detailed Explanation</p>
                                            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                                                @foreach ($section['manual'] as $manualTitle => $manualText)
                                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                                        <div class="flex items-start gap-3">
                                                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                                                <i class="fa {{ $section['icon'] }} text-xs"></i>
                                                            </span>
                                                            <div>
                                                                <h3 class="text-sm font-black text-slate-900">{{ $manualTitle }}</h3>
                                                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $manualText }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <div class="relative space-y-6">
                                        <div class="absolute bottom-2 left-[15px] top-2 w-px bg-slate-200"></div>
                                        @foreach ($section['steps'] as $stepIndex => $step)
                                            <div class="relative flex gap-5">
                                                <div class="z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 border-emerald-600 bg-white text-sm font-black text-emerald-700">
                                                    {{ $stepIndex + 1 }}
                                                </div>
                                                <div class="pb-1">
                                                    <h3 class="text-base font-black text-slate-900">
                                                        <i class="fa fa-check-circle mr-2 text-emerald-600"></i>{{ $step['title'] }}
                                                    </h3>
                                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $step['text'] }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <aside class="h-fit rounded-[1.5rem] border border-emerald-100 bg-emerald-50 p-5">
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Quick Notes</p>
                                    <ul class="mt-4 space-y-3">
                                        @foreach ($section['tips'] as $tip)
                                            <li class="flex gap-3 text-sm leading-6 text-slate-700">
                                                <span class="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-[10px] text-white">
                                                    <i class="fa fa-check"></i>
                                                </span>
                                                <span>{{ $tip }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </aside>
                            </div>
                        @elseif ($procedureLayout === 1)
                            <div class="space-y-6 p-6">
                                @if (!empty($section['details'] ?? ''))
                                    <div class="rounded-[1.75rem] border border-emerald-100 bg-emerald-50 p-6">
                                        <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">How this part works</p>
                                        <p class="mt-3 text-sm leading-7 text-slate-700">{{ $section['details'] }}</p>
                                    </div>
                                @endif

                                <div class="grid grid-cols-1 gap-4 xl:grid-cols-[0.9fr_1.1fr]">
                                    <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                                        <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Detailed Explanation</p>
                                        <div class="mt-4 space-y-3">
                                            @foreach (($section['manual'] ?? []) as $manualTitle => $manualText)
                                                <div class="rounded-2xl bg-white p-4 shadow-sm">
                                                    <div class="flex items-start gap-3">
                                                        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                                            <i class="fa {{ $section['icon'] }} text-xs"></i>
                                                        </span>
                                                        <div>
                                                            <h3 class="text-sm font-black text-slate-900">{{ $manualTitle }}</h3>
                                                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $manualText }}</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                        @foreach ($section['steps'] as $stepIndex => $step)
                                            <div class="rounded-[1.5rem] border border-emerald-100 bg-white p-5 shadow-sm">
                                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-700 text-sm font-black text-white">{{ $stepIndex + 1 }}</span>
                                                <h3 class="mt-4 text-base font-black text-slate-900">
                                                    <i class="fa fa-check-circle mr-2 text-emerald-600"></i>{{ $step['title'] }}
                                                </h3>
                                                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $step['text'] }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="rounded-[1.5rem] border border-emerald-100 bg-gradient-to-r from-emerald-50 to-white p-5">
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Quick Notes</p>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($section['tips'] as $tip)
                                            <span class="inline-flex items-center gap-2 rounded-full bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-sm">
                                                <i class="fa fa-check text-emerald-700"></i>
                                                {{ $tip }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @elseif ($procedureLayout === 2)
                            <div class="grid gap-6 p-6 lg:grid-cols-[320px_1fr]">
                                <aside class="space-y-4">
                                    <div class="rounded-[1.5rem] border border-emerald-100 bg-emerald-50 p-5">
                                        <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">Quick Notes</p>
                                        <ul class="mt-4 space-y-3">
                                            @foreach ($section['tips'] as $tip)
                                                <li class="flex gap-3 text-sm leading-6 text-slate-700">
                                                    <span class="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-[10px] text-white">
                                                        <i class="fa fa-check"></i>
                                                    </span>
                                                    <span>{{ $tip }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>

                                    @if (!empty($section['details'] ?? ''))
                                        <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm">
                                            <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">How this part works</p>
                                            <p class="mt-3 text-sm leading-7 text-slate-700">{{ $section['details'] }}</p>
                                        </div>
                                    @endif
                                </aside>

                                <div class="space-y-5">
                                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                        @foreach (($section['manual'] ?? []) as $manualTitle => $manualText)
                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                                <div class="flex items-start gap-3">
                                                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                                        <i class="fa {{ $section['icon'] }} text-xs"></i>
                                                    </span>
                                                    <div>
                                                        <h3 class="text-sm font-black text-slate-900">{{ $manualTitle }}</h3>
                                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $manualText }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="rounded-[1.5rem] border border-slate-200 bg-white p-5">
                                        <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Procedure Flow</p>
                                        <div class="mt-4 space-y-4">
                                            @foreach ($section['steps'] as $stepIndex => $step)
                                                <div class="flex gap-4 rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4">
                                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-700 text-sm font-black text-white">{{ $stepIndex + 1 }}</span>
                                                    <div>
                                                        <h3 class="text-base font-black text-slate-900">
                                                            <i class="fa fa-check-circle mr-2 text-emerald-600"></i>{{ $step['title'] }}
                                                        </h3>
                                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $step['text'] }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="space-y-6 p-6">
                                <div class="grid gap-4 lg:grid-cols-[1fr_1fr]">
                                    @if (!empty($section['details'] ?? ''))
                                        <div class="rounded-[1.75rem] border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-6">
                                            <p class="text-xs font-black uppercase tracking-[0.18em] text-emerald-700">How this part works</p>
                                            <p class="mt-3 text-sm leading-7 text-slate-700">{{ $section['details'] }}</p>
                                        </div>
                                    @endif

                                    <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-6">
                                        <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Quick Notes</p>
                                        <div class="mt-4 grid gap-3">
                                            @foreach ($section['tips'] as $tip)
                                                <div class="flex gap-3 rounded-2xl bg-white p-3 text-sm leading-6 text-slate-700 shadow-sm">
                                                    <i class="fa fa-check-circle mt-1 text-emerald-700"></i>
                                                    <span>{{ $tip }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Detailed Explanation</p>
                                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                                        @foreach (($section['manual'] ?? []) as $manualTitle => $manualText)
                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                                <div class="flex items-start gap-3">
                                                    <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                                        <i class="fa {{ $section['icon'] }} text-xs"></i>
                                                    </span>
                                                    <div>
                                                        <h3 class="text-sm font-black text-slate-900">{{ $manualTitle }}</h3>
                                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $manualText }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    @foreach ($section['steps'] as $stepIndex => $step)
                                        <div class="rounded-[1.5rem] border border-emerald-100 bg-gradient-to-br from-white to-emerald-50 p-5 shadow-sm">
                                            <div class="flex items-center gap-3">
                                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-700 text-sm font-black text-white">{{ $stepIndex + 1 }}</span>
                                                <h3 class="text-base font-black text-slate-900">
                                                    <i class="fa fa-check-circle mr-2 text-emerald-600"></i>{{ $step['title'] }}
                                                </h3>
                                            </div>
                                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $step['text'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </article>
                @endforeach
            </section>
        </div>
    </main>
</div>

<script>
    const initEmployeeStaffGuideAnimation = () => {
        const page = document.getElementById('employee-staff-guide-page');
        if (!page) return;

        const topSections = Array.from(page.children).filter((child) => child.tagName === 'SECTION');
        topSections.forEach((section, index) => {
            section.classList.add('employee-staff-guide-reveal');
            section.style.setProperty('--employee-staff-guide-delay', `${Math.min(index * 80, 240)}ms`);
        });

        page.querySelectorAll('section:nth-of-type(2) article').forEach((card, index) => {
            card.classList.add('employee-staff-guide-card-motion', 'employee-staff-guide-reveal');
            card.style.setProperty('--employee-staff-guide-delay', `${120 + (index * 60)}ms`);
        });

        page.querySelectorAll('section:nth-of-type(3) a').forEach((link, index) => {
            link.classList.add('employee-staff-guide-card-motion');
            link.style.setProperty('--employee-staff-guide-delay', `${Math.min(120 + (index * 20), 320)}ms`);
        });

        page.querySelectorAll('section:nth-of-type(4) > article').forEach((article, index) => {
            article.classList.add('employee-staff-guide-card-motion', 'employee-staff-guide-reveal');
            article.style.setProperty('--employee-staff-guide-delay', `${Math.min(80 + ((index % 4) * 60), 260)}ms`);
        });

        page.querySelectorAll('[class*="rounded-"]').forEach((card) => {
            if (!card.classList.contains('employee-staff-guide-reveal')) {
                card.classList.add('employee-staff-guide-card-motion');
            }
        });

        page.querySelectorAll('.fa').forEach((icon) => {
            const iconBox = icon.closest('.flex[class*="h-"], span[class*="h-"]');
            if (iconBox) {
                iconBox.classList.add('employee-staff-guide-icon-pop', 'is-visible');
            }
        });

        const animatedItems = Array.from(page.querySelectorAll('.employee-staff-guide-reveal'));

        if (!('IntersectionObserver' in window)) {
            animatedItems.forEach((item) => item.classList.add('is-visible'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;

                entry.target.classList.add('is-scroll-animated', 'is-visible');
                observer.unobserve(entry.target);
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -35px 0px',
        });

        animatedItems.forEach((item) => observer.observe(item));
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEmployeeStaffGuideAnimation, { once: true });
    } else {
        initEmployeeStaffGuideAnimation();
    }

    const sidebar = document.querySelector('aside');
    const main = document.querySelector('main');

    if (sidebar && main) {
        sidebar.addEventListener('mouseenter', function() {
            main.classList.remove('ml-16');
            main.classList.add('ml-56');
        });

        sidebar.addEventListener('mouseleave', function() {
            main.classList.remove('ml-56');
            main.classList.add('ml-16');
        });
    }
</script>

</body>
</html>
