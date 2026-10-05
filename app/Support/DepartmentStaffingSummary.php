<?php

namespace App\Support;

use Illuminate\Support\Collection;

class DepartmentStaffingSummary
{
    public static function isHeadRank(?string $rank): bool
    {
        $normalized = strtolower(trim((string) $rank));
        $normalized = trim((string) preg_replace('/[^a-z0-9]+/', ' ', $normalized));

        return preg_match('/^(?:(?:asst|assistant)\s*)?(?:admin acad head|admin head|acad head|admin)(?:\s+(?:iii|ii|i|[1-3]))?$/', $normalized) === 1;
    }

    public static function isCoordinatorRank(?string $rank): bool
    {
        return preg_match('/^coordinator\s+(?:iii|ii|i|[1-3])$/i', trim((string) $rank)) === 1;
    }

    public static function isStaffRank(?string $rank): bool
    {
        $normalized = strtolower(trim((string) $rank));
        $normalized = trim((string) preg_replace('/[^a-z0-9]+/', ' ', $normalized));

        return preg_match('/^(?:school cler(?:c)?k (?:iii|ii|i|[1-3])|contractual other ranks|ujam)$/', $normalized) === 1;
    }

    public static function forEmployees(Collection $employees): Collection
    {
        return $employees->groupBy(function ($emp) {
            $department = (string) (($emp->department ?? '') ?: data_get($emp, 'employee.department') ?: data_get($emp, 'applicant.position.department'));
            $department = trim((string) preg_replace('/\s+/u', ' ', $department));
            $department = (string) preg_replace('/\s*-\s*/u', '-', $department);

            $department = mb_strtoupper($department);
            if (in_array(preg_replace('/\s*\/\s*/u', '/', $department), ['RESEARCH OFFICE/GAD', 'RESEARCH-OFFICE/GAD', 'RESEARCH/GAD', 'GAD/RESEARCH OFFICE', 'GAD', 'GENDER-AND-DEVELOPMENT', 'RESEARCH OFFICE', 'RESEARCH-OFFICE'], true)) {
                return 'RESEARCH';
            }
            if (in_array(preg_replace('/\s*\/\s*/u', '/', $department), ['NSTP/OSAS', 'OSAS'], true)) {
                return 'NSTP/OSAS';
            }
            if (in_array($department, ['CON', 'CON-OFFICE', 'CON OFFICE'], true)) {
                return 'CON';
            }

            if (in_array($department, ['LAW-OFFICE', 'LAW OFFICE', 'OFFICE OF THE CHAIRMAN', 'OFFICE OF THE CHAIRMAN & LAW/JD'], true)) {
                return 'OFFICE OF THE CHAIRMAN & LAW/JD';
            }

            if (in_array($department, ['GS_OFFICE', 'GS-OFFICE', 'GS OFFICE', 'GRADUATE SCHOOL'], true)) {
                return 'GRADUATE SCHOOL';
            }

            if (in_array($department, ['JANITORIAL', 'MAINTENANCE', 'MAINTENANCE & CARPENTRY', 'JANITORIAL & MAINTENANCE', 'JANITOR & MAINTENANCE'], true)) {
                return 'JANITOR & MAINTENANCE';
            }

            if (in_array($department, ['COLA', 'COLA-OFFICE', 'COLA OFFICE'], true)) {
                return 'COLA';
            }

            if (in_array($department, ['COHM', 'COHM-CULINARY LABORATORY', 'COHM CULINARY LABORATORY', 'COHM-OFFICE', 'COHM OFFICE'], true)) {
                return 'COHM';
            }

            if (in_array($department, ['COGE', 'COGE-OFFICE', 'COGE OFFICE'], true)) {
                return 'COGE';
            }

            if (in_array($department, ['COED', 'COED-OFFICE', 'COED OFFICE'], true)) {
                return 'COED';
            }

            if (in_array($department, ['COC', 'COC-LAB', 'COC LAB', 'COC-OFFICE', 'COC OFFICE'], true)) {
                return 'COC';
            }

            if (in_array($department, ['CIT', 'CIT-OFFICE', 'CIT OFFICE', 'COIT'], true)) {
                return 'COIT';
            }

            if (in_array($department, ['CABA', 'CABA-OFFICE', 'CABA OFFICE'], true)) {
                return 'CABA';
            }

            if (in_array($department, ['BEC', 'BEC-ELEM', 'BEC-SHS', 'BEC-JHS', 'BEC-STEC', 'BEC-STE', 'BEC-OFFICE'], true)) {
                return 'BEC';
            }

            if (in_array($department, ['TREASURY', "TREASURER'S OFFICE", 'ACCOUNTING', 'TREASURY & ACCOUNTING'], true)) {
                return 'TREASURY & ACCOUNTING';
            }

            return $department !== '' ? $department : 'Unassigned';
        })->map(fn ($members, $department) => self::forDepartment($members, $department));
    }

    public static function forDepartment(Collection $departmentEmployees, string $department): array
    {
    $employeeFlags = $departmentEmployees->map(function ($emp) {
      $jobRoleValue = trim((string) ($emp->job_role ?? ''));
      $positionFieldValue = trim((string) (data_get($emp, 'employee.position') ?? ($emp->position ?? data_get($emp, 'applicant.position.title') ?? '')));
      $positionTitle = trim((string) ($positionFieldValue !== '' ? $positionFieldValue : $jobRoleValue));
      $rankValue = trim((string) (data_get($emp, 'employee.rank') ?: data_get($emp, 'employee.classification') ?: ($emp->rank ?? '')));
      $jobTypeValue = trim((string) (data_get($emp, 'applicant.position.job_type') ?: data_get($emp, 'employee.job_type') ?: ($emp->job_type ?? '')));
      $classificationValue = trim((string) (data_get($emp, 'employee.classification') ?: data_get($emp, 'applicant.position.employment') ?: ($emp->classification ?? '')));
      $positionText = strtolower($positionTitle);
      $rankText = strtolower($rankValue);
      $jobTypeText = strtolower($jobTypeValue);
      $classificationText = strtolower($classificationValue);
      $classCode = strtoupper((string) preg_replace('/\s+/', '', (string) (data_get($emp, 'employee.class_code') ?: ($emp->class_code ?? ''))));
      $instructorClass = match ($classCode) {
        'T/FT', 'FT/T' => 'FT',
        'T/PT', 'PT/T' => 'PT',
        default => null,
      };
      $combinedRoleText = trim($positionText.' '.$rankText);
      $isNonTeachingJobType = str_contains($jobTypeText, 'non-teaching')
        || str_contains($jobTypeText, 'non teaching')
        || trim($jobTypeText) === 'nt';
      $isTeachingJobType = !$isNonTeachingJobType && (
        str_contains($jobTypeText, 'teaching')
        || str_contains($jobTypeText, 'faculty')
        || trim($jobTypeText) === 't'
      );

      $isCoordinator = self::isCoordinatorRank($rankValue);
      $isInstructorLike = str_contains($combinedRoleText, 'instructor')
        || str_contains($combinedRoleText, 'faculty')
        || str_contains($combinedRoleText, 'professor')
        || str_contains($combinedRoleText, 'proffesor')
        || str_contains($combinedRoleText, 'profesor')
        || str_contains($combinedRoleText, 'lecturer')
        || str_contains($combinedRoleText, 'teacher');
      $isInstructor = $classCode !== '' ? $instructorClass !== null : $isInstructorLike && !$isNonTeachingJobType;
      $isHead = self::isHeadRank($rankValue);

      return [
        'classification_text' => $classificationText,
        'instructor_class' => $instructorClass,
        'is_coordinator' => $isCoordinator,
        'is_instructor' => $isInstructor,
        'is_teaching_job_type' => $isTeachingJobType,
        'is_head' => $isHead,
        'is_staff' => self::isStaffRank($rankValue),
      ];
    })->values();

    $summary = [
      'department' => $department,
      'heads' => 0,
      'coordinator' => 0,
      'staff' => 0,
      'unclassified' => 0,
      'instructors_ft' => 0,
      'instructors_pt' => 0,
      'total' => 0,
      'is_teaching_department' => $employeeFlags->contains(function ($flags) {
        return (bool) ($flags['is_teaching_job_type'] ?? false);
      }),
    ];

    foreach ($employeeFlags as $flags) {
      if (($flags['instructor_class'] ?? null) === 'FT') {
        $summary['instructors_ft']++;
      } elseif (($flags['instructor_class'] ?? null) === 'PT') {
        $summary['instructors_pt']++;
      } elseif ($flags['is_head'] ?? false) {
        $summary['heads']++;
      } elseif ($flags['is_coordinator'] ?? false) {
        $summary['coordinator']++;
      } elseif ($flags['is_staff'] ?? false) {
        $summary['staff']++;
      } elseif ($flags['is_instructor'] ?? false) {
        if (($flags['instructor_class'] ?? null) === 'PT' || (($flags['instructor_class'] ?? null) === null && (str_contains($flags['classification_text'] ?? '', 'part-time') || str_contains($flags['classification_text'] ?? '', 'part time')))) {
          $summary['instructors_pt']++;
        } else {
          $summary['instructors_ft']++;
        }
      } else {
        $summary['unclassified']++;
      }

      $summary['total']++;
    }

    $summary['is_teaching_department'] = (bool) ($summary['is_teaching_department'] ?? false)
      || (int) ($summary['instructors_ft'] ?? 0) > 0
      || (int) ($summary['instructors_pt'] ?? 0) > 0;

    return $summary;
    }
}
