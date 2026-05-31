<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function display_login(){
        return view('auth', array_merge(['mode' => 'login'], $this->authStats()));
    }

    public function display_register(){
        return view('auth', array_merge(['mode' => 'register'], $this->authStats()));
    }

    private function authStats(): array
    {
        $ratingStats = Applicant::query()
            ->whereNotNull('starRatings')
            ->get(['starRatings'])
            ->map(function ($applicant) {
                $value = (int) $applicant->starRatings;
                return ($value >= 1 && $value <= 5) ? $value : null;
            })
            ->filter()
            ->values();

        return [
            'companyRating' => $ratingStats->count() ? round((float) $ratingStats->avg(), 1) : null,
            'ratingCount' => $ratingStats->count(),
            'defaultAccounts' => [
                [
                    'label' => 'Main Admin',
                    'role' => 'Admin',
                    'email' => env('DEFAULT_ADMIN_EMAIL', 'kurtrobin20031118@gmail.com'),
                    'password' => env('DEFAULT_ADMIN_PASSWORD', 'Kurt12345'),
                ],
                [
                    'label' => 'Demo Admin',
                    'role' => 'Admin',
                    'email' => env('DEMO_ADMIN_EMAIL', 'demo.admin@example.com'),
                    'password' => env('DEMO_ADMIN_PASSWORD', 'Demo12345'),
                ],
                [
                    'label' => 'Employee One',
                    'role' => 'Employee',
                    'email' => 'employee.one@example.com',
                    'password' => 'Employee12345',
                ],
                [
                    'label' => 'Employee Two',
                    'role' => 'Employee',
                    'email' => 'employee.two@example.com',
                    'password' => 'Employee12345',
                ],
            ],
        ];
    }
}
