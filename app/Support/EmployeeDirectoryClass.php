<?php

namespace App\Support;

final class EmployeeDirectoryClass
{
    public static function label($emp): string
    {
        $classValue = trim((string) (data_get($emp, 'employee.classification') ?: data_get($emp, 'applicant.position.employment') ?: ($emp->classification ?? '')));
        $employmentCode = match (strtolower($classValue)) {
          'full-time', 'full time' => 'FT',
          'part-time', 'part time' => 'PT',
          default => (str_contains(strtolower($classValue), 'part') ? 'PT' : (str_contains(strtolower($classValue), 'full') || str_contains(strtolower($classValue), 'probationary') || str_contains(strtolower($classValue), 'permanent') ? 'FT' : '')),
        };
        $jobTypeValue = trim((string) (data_get($emp, 'applicant.position.job_type') ?: data_get($emp, 'employee.job_type') ?: ($emp->job_type ?? '')));
        $normalizedJobType = strtolower($jobTypeValue);
        $jobTypeCode = match (true) {
          in_array(strtoupper($jobTypeValue), ['NT'], true),
          str_contains($normalizedJobType, 'non-teaching'),
          str_contains($normalizedJobType, 'non teaching') => 'NT',
          in_array(strtoupper($jobTypeValue), ['T'], true),
          str_contains($normalizedJobType, 'teaching'),
          str_contains($normalizedJobType, 'teacher'),
          str_contains($normalizedJobType, 'faculty') => 'T',
          default => $jobTypeValue,
        };
        $classDisplay = collect([$employmentCode, $jobTypeCode])->filter(fn ($value) => trim((string) $value) !== '')->implode('/');
        return trim((string) (data_get($emp, 'employee.class_code') ?: ($emp->class_code ?? null) ?: $classDisplay));
    }
}
