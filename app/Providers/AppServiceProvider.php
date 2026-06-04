<?php

namespace App\Providers;

use App\Models\Applicant;
use App\Models\ApplicantDegree;
use App\Models\ApplicantDocument;
use App\Models\Education;
use App\Models\Employee;
use App\Models\EmployeePositionHistory;
use App\Models\Government;
use App\Models\Interviewer;
use App\Models\LeaveApplication;
use App\Models\License;
use App\Models\LoadsUpload;
use App\Models\OpenPosition;
use App\Models\PayslipUpload;
use App\Models\Resignation;
use App\Models\Salary;
use App\Models\User;
use App\Support\ActivityChangeLogger;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        foreach ($this->activityTrackedModels() as $modelClass) {
            $modelClass::created(fn ($model) => ActivityChangeLogger::created($model));
            $modelClass::updated(fn ($model) => ActivityChangeLogger::updated($model));
            $modelClass::deleted(fn ($model) => ActivityChangeLogger::deleted($model));
        }
    }

    private function activityTrackedModels(): array
    {
        return [
            User::class,
            Employee::class,
            Applicant::class,
            ApplicantDegree::class,
            ApplicantDocument::class,
            Education::class,
            Government::class,
            License::class,
            Salary::class,
            OpenPosition::class,
            Interviewer::class,
            LeaveApplication::class,
            Resignation::class,
            PayslipUpload::class,
            LoadsUpload::class,
            EmployeePositionHistory::class,
        ];
    }
}
