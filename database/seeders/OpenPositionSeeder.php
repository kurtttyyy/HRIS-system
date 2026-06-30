<?php

namespace Database\Seeders;

use App\Models\OpenPosition;
use Illuminate\Database\Seeder;

class OpenPositionSeeder extends Seeder
{
    public function run(): void
    {
        $applicationStart = now()->startOfDay();
        $applicationEnd = now()->addDays(90)->endOfDay();

        foreach ($this->positions() as $positionData) {
            $position = OpenPosition::withTrashed()->updateOrCreate(
                [
                    'title' => $positionData['title'],
                    'department' => $positionData['department'],
                ],
                $positionData + [
                    'one' => $applicationStart,
                    'two' => $applicationEnd,
                ]
            );

            if ($position->trashed()) {
                $position->restore();
            }
        }
    }

    private function positions(): array
    {
        return [
            [
                'title' => 'Clinical Instructor',
                'department' => 'College of Nursing',
                'employment' => 'Full-Time',
                'work_mode' => 'Onsite',
                'job_description' => 'Guide nursing students through classroom instruction, skills-laboratory activities, and supervised clinical exposure while promoting safe, ethical, and evidence-based patient care.',
                'responsibilities' => implode("\n", [
                    '- Prepare and deliver nursing lessons, demonstrations, and clinical conferences.',
                    '- Supervise students during laboratory and hospital rotations.',
                    '- Evaluate competencies, provide feedback, and maintain academic records.',
                    '- Coordinate clinical schedules and requirements with partner institutions.',
                    '- Support curriculum improvement and departmental quality initiatives.',
                ]),
                'requirements' => implode("\n", [
                    '- Bachelor of Science in Nursing; a master’s degree or ongoing graduate studies is preferred.',
                    '- Active Philippine Registered Nurse license.',
                    '- At least one year of relevant clinical nursing experience.',
                    '- Strong teaching, communication, documentation, and mentoring skills.',
                    '- Able to work onsite and travel to affiliated clinical facilities.',
                ]),
                'experience_level' => 'Mid',
                'location' => 'Santiago City Campus',
                'skills' => 'Clinical instruction, patient care, assessment, mentoring, lesson planning',
                'benifits' => 'Health coverage, paid leave, professional development, training support',
                'job_type' => 'Teaching',
            ],
            [
                'title' => 'Information Technology Support Specialist',
                'department' => 'Information Technology',
                'employment' => 'Full-Time',
                'work_mode' => 'Hybrid',
                'job_description' => 'Provide dependable technical assistance for faculty, staff, laboratories, offices, and institutional systems while helping maintain secure and reliable campus technology services.',
                'responsibilities' => implode("\n", [
                    '- Diagnose and resolve computer, printer, network, and software concerns.',
                    '- Install, configure, inventory, and maintain institutional devices.',
                    '- Assist users through the help desk and document completed support work.',
                    '- Monitor routine backups, endpoint protection, and system availability.',
                    '- Support account provisioning, classroom technology, and campus events.',
                ]),
                'requirements' => implode("\n", [
                    '- Bachelor’s degree in Information Technology, Computer Science, or a related field.',
                    '- Working knowledge of Windows, office applications, networking, and troubleshooting.',
                    '- Strong customer-service, communication, and documentation skills.',
                    '- Awareness of cybersecurity, privacy, and responsible access practices.',
                    '- Relevant certifications or prior help-desk experience are an advantage.',
                ]),
                'experience_level' => 'Junior',
                'location' => 'Santiago City Campus',
                'skills' => 'Technical support, networking, hardware, Windows, cybersecurity',
                'benifits' => 'Health coverage, paid leave, certification support, hybrid work schedule',
                'job_type' => 'Non-Teaching',
            ],
            [
                'title' => 'Guidance Counselor',
                'department' => 'Student Affairs',
                'employment' => 'Full-Time',
                'work_mode' => 'Onsite',
                'job_description' => 'Deliver accessible counseling, student-development, referral, and wellness services that support learners’ academic progress, personal growth, and overall well-being.',
                'responsibilities' => implode("\n", [
                    '- Conduct individual and group counseling sessions in a confidential setting.',
                    '- Develop student wellness, career guidance, and prevention programs.',
                    '- Maintain accurate confidential records and coordinate appropriate referrals.',
                    '- Collaborate with faculty, families, and administrators on student support plans.',
                    '- Assist with assessments, orientation, crisis response, and follow-up services.',
                ]),
                'requirements' => implode("\n", [
                    '- Bachelor’s or master’s degree in Guidance and Counseling, Psychology, or a related field.',
                    '- Registered Guidance Counselor license is preferred or required when applicable.',
                    '- Experience supporting adolescents or college students is an advantage.',
                    '- Excellent listening, case-management, communication, and ethical judgment.',
                    '- Demonstrated respect for confidentiality, diversity, and student welfare.',
                ]),
                'experience_level' => 'Mid',
                'location' => 'Santiago City Campus',
                'skills' => 'Counseling, case management, assessment, crisis support, student development',
                'benifits' => 'Health coverage, paid leave, wellness support, professional development',
                'job_type' => 'Non-Teaching',
            ],
            [
                'title' => 'College Librarian',
                'department' => 'Library Department',
                'employment' => 'Full-Time',
                'work_mode' => 'Onsite',
                'job_description' => 'Manage accessible, organized, and responsive library services that strengthen teaching, research, information literacy, and the academic experience of students and employees.',
                'responsibilities' => implode("\n", [
                    '- Organize, catalog, preserve, and circulate print and digital resources.',
                    '- Assist users with research, references, databases, and information literacy.',
                    '- Maintain library records, usage reports, acquisitions, and inventory controls.',
                    '- Develop orientations, reading programs, and faculty resource support.',
                    '- Enforce library policies while maintaining a welcoming learning environment.',
                ]),
                'requirements' => implode("\n", [
                    '- Bachelor of Library and Information Science or an equivalent degree.',
                    '- Active Professional Librarian license.',
                    '- Familiarity with library systems, cataloging standards, and online databases.',
                    '- Strong research, organization, communication, and service skills.',
                    '- School or academic library experience is an advantage.',
                ]),
                'experience_level' => 'Mid',
                'location' => 'Santiago City Campus',
                'skills' => 'Cataloging, research assistance, library systems, records, information literacy',
                'benifits' => 'Health coverage, paid leave, training support, professional development',
                'job_type' => 'Non-Teaching',
            ],
        ];
    }
}
