<?php

namespace App\Http\Controllers;

use App\Actions\Grades\CalculateAverageAction;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Section;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $activeYear = SchoolYear::active()->first();
        $gradeLevelId = $request->input('grade_level_id');
        $sectionId = $request->input('section_id');

        $sections = [];
        $enrollments = [];
        $levels = [];

        if ($activeYear) {
            $levels = \App\Models\GradeLevel::orderBy('order_num')->get();
            
            $sectionsQuery = Section::where('school_year_id', $activeYear->id)
                ->with('gradeLevel')
                ->join('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
                ->select('sections.*')
                ->orderBy('grade_levels.order_num')
                ->orderBy('sections.name');

            if ($gradeLevelId) {
                $sectionsQuery->where('grade_level_id', $gradeLevelId);
            }

            $sections = $sectionsQuery->get();
            
            if ($sectionId) {
                $enrollments = Enrollment::where('section_id', $sectionId)
                    ->with('student')
                    ->get();
            }
        }

        return Inertia::render('Reports/Index', [
            'sections' => $sections,
            'activeYear' => $activeYear,
            'levels' => $levels,
            'enrollments' => $enrollments,
            'filters' => [
                'grade_level_id' => $gradeLevelId,
                'section_id' => $sectionId,
            ],
        ]);
    }

    public function downloadReportCard(Request $request, Enrollment $enrollment, CalculateAverageAction $calcAverage)
    {
        // Aseguramos que el usuario tenga permiso
        Gate::authorize('reports.generate');

        $enrollment->load([
            'student',
            'section.gradeLevel',
            'schoolYear',
            'grades.lapse',
            'grades.subject',
            'attendances',
        ]);

        $subjects = $enrollment->section->gradeLevel->subjects;

        // Calcular promedios finales y generales
        $finalGrades = [];
        foreach ($subjects as $subject) {
            $finalGrades[$subject->id] = $calcAverage->forSubject($enrollment, $subject);
        }
        $overallAverage = $calcAverage->overall($enrollment);

        // Contar inasistencias
        $absences = $enrollment->attendances->where('status', \App\Enums\AttendanceStatus::ABSENT)->count();

        $pdf = Pdf::loadView('pdf.report_card', [
            'enrollment' => $enrollment,
            'subjects' => $subjects,
            'finalGrades' => $finalGrades,
            'overallAverage' => $overallAverage,
            'absences' => $absences,
            'lapses' => $enrollment->schoolYear->lapses,
        ]);

        return $pdf->download("Boleta_{$enrollment->student->cedula}_{$enrollment->schoolYear->name}.pdf");
    }
}
