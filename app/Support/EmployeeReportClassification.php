<?php

namespace App\Support;

class EmployeeReportClassification
{
    public static function gender(object $user): ?string
    {
        foreach ([$user->employee?->sex, $user->applicant?->sex] as $value) {
            $gender = match (strtolower(trim((string) $value))) {
                'm', 'male' => 'male',
                'f', 'fm', 'female' => 'female',
                default => null,
            };
            if ($gender !== null) return $gender;
        }
        return null;
    }

}
