<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $activeYear = SchoolYear::active()->first();

        $stats = [];
        if ($activeYear) {
            $stats = [
                'total_students' => Student::active()->count(),
                'total_enrollments' => Enrollment::forSchoolYear($activeYear->id)->active()->count(),
                'school_year' => $activeYear->name,
                'open_lapses' => $activeYear->lapses()->where('is_open', true)->count(),
            ];
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'activeYear' => $activeYear,
        ]);
    }
}
