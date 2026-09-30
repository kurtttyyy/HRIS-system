<?php

namespace App\Support;

use Illuminate\Support\Collection;

class DepartmentStaffingSummary
{
    public static function forEmployees(Collection $employees): Collection
    {
        return $employees->groupBy(function ($emp) {
            return trim((string) (($emp->department ?? '') ?: data_get($emp, 'employee.department') ?: data_get($emp, 'applicant.position.department'))) ?: 'Unassigned';
        })->map(fn ($members, $department) => self::forDepartment($members, $department));
    }

    public static function forDepartment(Collection $departmentEmployees, string $department): array
    {
    $employeeFlags = $departmentEmployees->map(function ($emp) {
      $jobRoleValue = trim((string) ($emp->job_role ?? ''));
      $positionFieldValue = trim((string) (data_get($emp, 'employee.position') ?? ($emp->position ?? data_get($emp, 'applicant.position.title') ?? '')));
      $positionTitle = trim((string) ($positionFieldValue !== '' ? $positionFieldValue : $jobRoleValue));
      $rankValue = trim((string) (data_get($emp, 'employee.rank') ?: ($emp->rank ?? '')));
      $jobTypeValue = trim((string) (data_get($emp, 'applicant.position.job_type') ?: data_get($emp, 'employee.job_type') ?: ($emp->job_type ?? '')));
      $classificationValue = trim((string) (data_get($emp, 'employee.classification') ?: data_get($emp, 'applicant.position.employment') ?: ($emp->classification ?? '')));
      $jobRoleText = strtolower($jobRoleValue);
      $positionFieldText = strtolower($positionFieldValue);
      $positionText = strtolower($positionTitle);
      $rankText = strtolower($rankValue);
      $jobTypeText = strtolower($jobTypeValue);
      $classificationText = strtolower($classificationValue);
      $combinedRoleText = trim($positionText.' '.$rankText);
      $normalizedRoleText = preg_replace('/[^a-z0-9]+/i', ' ', $combinedRoleText);
      $normalizedRoleText = trim((string) preg_replace('/\s+/', ' ', (string) $normalizedRoleText));
      $serviceRecordRows = collect(is_array(data_get($emp, 'employee.service_record_rows')) ? data_get($emp, 'employee.service_record_rows') : []);
      $isNonTeachingJobType = str_contains($jobTypeText, 'non-teaching')
        || str_contains($jobTypeText, 'non teaching')
        || trim($jobTypeText) === 'nt';
      $isTeachingJobType = !$isNonTeachingJobType && (
        str_contains($jobTypeText, 'teaching')
        || str_contains($jobTypeText, 'faculty')
        || trim($jobTypeText) === 't'
      );

      $containsRoleKeyword = static function (string $needle) use ($combinedRoleText, $normalizedRoleText): bool {
        $needle = strtolower(trim($needle));
        if ($needle === '') {
          return false;
        }

        return str_contains($combinedRoleText, $needle) || str_contains($normalizedRoleText, str_replace('&', 'and', $needle));
      };
      $containsDeanKeyword = static function (?string $value): bool {
        $text = strtolower(trim((string) ($value ?? '')));
        if ($text === '') {
          return false;
        }

        $normalized = trim((string) preg_replace('/\s+/', ' ', (string) preg_replace('/[^a-z0-9]+/i', ' ', $text)));
        return str_contains($text, 'dean') || str_contains($normalized, 'dean');
      };
      $latestServiceRecordAction = $serviceRecordRows
        ->reverse()
        ->map(function ($row) use ($containsDeanKeyword) {
          $designation = trim((string) (data_get($row, 'designation') ?? ''));
          $remarks = trim((string) (data_get($row, 'remarks') ?? ''));
          $action = null;
          if (preg_match('/\bpromoted\b/i', $remarks) === 1) {
            $action = 'promoted';
          } elseif (preg_match('/\b(resigned|resign)\b/i', $remarks) === 1) {
            $action = 'resigned';
          }

          return [
            'has_content' => $designation !== '' || $remarks !== '',
            'matches_dean' => $containsDeanKeyword($designation) || $containsDeanKeyword($remarks),
            'action' => $action,
          ];
        })
        ->first(fn ($row) => $row['has_content'] ?? false);
      $hasActiveDeanServiceRecord = ($latestServiceRecordAction['matches_dean'] ?? false)
        && (($latestServiceRecordAction['action'] ?? null) !== 'resigned');

      $isCoordinator = str_contains($combinedRoleText, 'coordinator') || str_contains($combinedRoleText, 'coor');
      $isInstructorLike = str_contains($combinedRoleText, 'instructor')
        || str_contains($combinedRoleText, 'faculty')
        || str_contains($combinedRoleText, 'professor')
        || str_contains($combinedRoleText, 'proffesor')
        || str_contains($combinedRoleText, 'profesor')
        || str_contains($combinedRoleText, 'lecturer')
        || str_contains($combinedRoleText, 'teacher');
      $isInstructor = $isInstructorLike && !$isNonTeachingJobType;
      $isVicePresidentRole = preg_match('/\b(v\.?\s*p\.?|vice president)\b/i', $jobRoleValue) === 1;
      $isTeachingTopHeadRole =
        $containsRoleKeyword('dean')
        || $containsRoleKeyword('college dean')
        || $containsRoleKeyword('executive dean')
        || $containsRoleKeyword('associate dean')
        || $containsRoleKeyword('assistant dean')
        || $containsRoleKeyword('program head')
        || $containsRoleKeyword('department head')
        || $containsRoleKeyword('head')
        || $containsRoleKeyword('chairperson')
        || $containsRoleKeyword('chairman')
        || $containsRoleKeyword('department chair')
        || $containsRoleKeyword('program chair')
        || str_contains($combinedRoleText, 'chair ')
        || str_ends_with($combinedRoleText, ' chair');
      $isTeachingSubordinateRole =
        $containsRoleKeyword('vice dean')
        || $containsRoleKeyword('assistant dean')
        || $containsRoleKeyword('associate dean')
        || $containsRoleKeyword('coordinator')
        || $containsRoleKeyword('coor');
      $isNonTeachingHeadRole =
        $containsRoleKeyword('dean')
        || $containsRoleKeyword('legal counsel')
        || $containsRoleKeyword('director')
        || preg_match('/\b(o\.?\s*i\.?\s*c\.?|office in ?charge|office incharge)\b/i', $combinedRoleText) === 1
        || $containsRoleKeyword('school treasurer')
        || $containsRoleKeyword('school accountant')
        || $containsRoleKeyword('chief librarian')
        || $containsRoleKeyword('guidance counselor')
        || $containsRoleKeyword('guidance counsellor')
        || $containsRoleKeyword('focal person')
        || $containsRoleKeyword('coordinator')
        || $containsRoleKeyword('principal')
        || $containsRoleKeyword('building property custodian')
        || $containsRoleKeyword('building and property custodian')
        || $containsRoleKeyword('building & property custodian')
        || $containsRoleKeyword('supervisor');
      $isTeachingTrack = $isInstructor
        || str_contains($jobTypeText, 'teaching')
        || str_contains($jobTypeText, 'faculty')
        || $containsRoleKeyword('dean')
        || $containsRoleKeyword('college dean')
        || $containsRoleKeyword('executive dean')
        || $containsRoleKeyword('associate dean')
        || $containsRoleKeyword('assistant dean')
        || $containsRoleKeyword('vice dean')
        || $containsRoleKeyword('program head')
        || $containsRoleKeyword('department head')
        || $containsRoleKeyword('head')
        || $containsRoleKeyword('chairperson')
        || $containsRoleKeyword('chairman')
        || $containsRoleKeyword('department chair')
        || $containsRoleKeyword('program chair')
        || str_contains($combinedRoleText, 'chair ')
        || str_ends_with($combinedRoleText, ' chair');
      $isDirectLeadershipHead = $jobRoleText === 'president'
        || $positionFieldText === 'dean'
        || $isVicePresidentRole
        || $hasActiveDeanServiceRecord
        || $isTeachingTopHeadRole
        || $isNonTeachingHeadRole;
      $isHead = $isDirectLeadershipHead || (!$isCoordinator && !$isInstructor && (
        $containsRoleKeyword('head')
        || $containsRoleKeyword('chief')
        || $containsRoleKeyword('dean')
        || $containsRoleKeyword('director')
        || $containsRoleKeyword('president')
        || preg_match('/\b(v\.?\s*p\.?|vice president)\b/i', $combinedRoleText) === 1
        || $containsRoleKeyword('registrar')
        || $containsRoleKeyword('chairperson')
        || $containsRoleKeyword('chairman')
        || str_contains($combinedRoleText, 'chair ')
        || str_ends_with($combinedRoleText, ' chair')
        || $containsRoleKeyword('legal counsel')
        || preg_match('/\b(o\.?\s*i\.?\s*c\.?|office in ?charge|office incharge)\b/i', $combinedRoleText) === 1
        || $containsRoleKeyword('school treasurer')
        || $containsRoleKeyword('school accountant')
        || $containsRoleKeyword('chief librarian')
        || $containsRoleKeyword('guidance counselor')
        || $containsRoleKeyword('guidance counsellor')
        || $containsRoleKeyword('focal person')
        || $containsRoleKeyword('coordinator')
        || $containsRoleKeyword('principal')
        || $containsRoleKeyword('building property custodian')
        || $containsRoleKeyword('building and property custodian')
        || $containsRoleKeyword('building & property custodian')
        || $containsRoleKeyword('manager')
        || $containsRoleKeyword('supervisor')
      ));

      return [
        'classification_text' => $classificationText,
        'is_coordinator' => $isCoordinator,
        'is_instructor' => $isInstructor,
        'is_teaching_track' => $isTeachingTrack,
        'is_teaching_job_type' => $isTeachingJobType,
        'is_teaching_top_head' => $isTeachingTopHeadRole || $jobRoleText === 'president' || $isVicePresidentRole,
        'is_teaching_subordinate' => $isTeachingSubordinateRole,
        'is_non_teaching_head' => $isNonTeachingHeadRole,
        'is_head' => $isHead,
      ];
    })->values();

    $hasHigherTeachingHeadInDepartment = $employeeFlags->contains(function ($flags) {
      return ($flags['is_teaching_track'] ?? false) && ($flags['is_teaching_top_head'] ?? false);
    });

    $summary = [
      'department' => $department,
      'heads' => 0,
      'coordinator' => 0,
      'staff' => 0,
      'instructors_ft' => 0,
      'instructors_pt' => 0,
      'total' => 0,
      'is_teaching_department' => $employeeFlags->contains(function ($flags) {
        return (bool) ($flags['is_teaching_job_type'] ?? false);
      }),
    ];

    foreach ($employeeFlags as $flags) {
      $shouldDowngradeTeachingRoleToCoordinator = $hasHigherTeachingHeadInDepartment
        && ($flags['is_teaching_track'] ?? false)
        && ($flags['is_teaching_subordinate'] ?? false)
        && !($flags['is_non_teaching_head'] ?? false);

      if (($flags['is_head'] ?? false) && !$shouldDowngradeTeachingRoleToCoordinator) {
        $summary['heads']++;
      } elseif (($flags['is_coordinator'] ?? false) || $shouldDowngradeTeachingRoleToCoordinator) {
        $summary['coordinator']++;
      } elseif ($flags['is_instructor'] ?? false) {
        if (str_contains($flags['classification_text'] ?? '', 'part-time') || str_contains($flags['classification_text'] ?? '', 'part time')) {
          $summary['instructors_pt']++;
        } else {
          $summary['instructors_ft']++;
        }
      } else {
        $summary['staff']++;
      }

      $summary['total']++;
    }

    $summary['is_teaching_department'] = (bool) ($summary['is_teaching_department'] ?? false)
      || (int) ($summary['instructors_ft'] ?? 0) > 0
      || (int) ($summary['instructors_pt'] ?? 0) > 0;

    return $summary;
    }
}
