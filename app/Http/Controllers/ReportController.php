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

    /**
     * Generate PDF with list of students for a given year and section.
     */
    public function printStudents($yearId, $sectionId)
    {
        Gate::authorize('reports.generate');

        $year = SchoolYear::findOrFail($yearId);
        $section = Section::findOrFail($sectionId);

        // Obtener los estudiantes inscritos en la sección mediante la tabla de matriculas (enrollments)
        $students = Enrollment::where('section_id', $sectionId)
            ->with('student')
            ->get()
            ->pluck('student')
            ->sortBy([['last_name', 'asc'], ['first_name', 'asc']])
            ->values();

        $pdf = Pdf::loadView('pdf.students_list', [
            'year' => $year,
            'section' => $section,
            'students' => $students,
        ]);

        $fileName = sprintf('%s_%s_%s.pdf', $year->name, $section->gradeLevel->name,$section->name);
        return $pdf->download($fileName);
    }

    
    public function downloadReportCard(Request $request, Enrollment $enrollment, CalculateAverageAction $calcAverage)
    {
        Gate::authorize('reports.generate');
        $bulletinData = $this->getBulletinData($enrollment, $calcAverage);
        $settings = \App\Models\SchoolSetting::allAsArray();

        $pdf = Pdf::loadView('pdf.report_card', [
            'bulletins' => [$bulletinData],
            'settings' => $settings,
        ])->setPaper('letter', 'portrait');

        return $pdf->download("Boleta_{$enrollment->student->cedula}_{$enrollment->schoolYear->name}.pdf");
    }

    public function downloadBatchReportCards(Request $request, Section $section, CalculateAverageAction $calcAverage)
    {
        Gate::authorize('reports.generate');
        
        $enrollments = Enrollment::where('section_id', $section->id)
            ->where('status', \App\Enums\EnrollmentStatus::ACTIVE)
            ->with(['student'])
            ->get()
            ->sortBy([['student.last_name', 'asc'], ['student.first_name', 'asc']])
            ->values();

        $bulletins = [];
        foreach ($enrollments as $enrollment) {
            $bulletins[] = $this->getBulletinData($enrollment, $calcAverage);
        }

        $settings = \App\Models\SchoolSetting::allAsArray();

        $pdf = Pdf::loadView('pdf.report_card', [
            'bulletins' => $bulletins,
            'settings' => $settings,
        ])->setPaper('letter', 'portrait');

        $fileName = sprintf('Boletas_Masivas_%s_%s.pdf', $section->gradeLevel->name, $section->name);
        return $pdf->download($fileName);
    }

    private function getBulletinData(Enrollment $enrollment, CalculateAverageAction $calcAverage)
    {
        $enrollment->load([
            'student',
            'section.gradeLevel.subjects',
            'schoolYear.lapses',
            'grades.lapse',
            'grades.subject',
            'attendances',
            'revisionGrades',
        ]);

        $lapses   = $enrollment->schoolYear->lapses->sortBy('order_num')->values();
        $subjects = $enrollment->section->gradeLevel->subjects->sortBy('name')->values();

        // Separar materias numéricas de cualitativas
        $numericSubjects     = $subjects->filter(fn($s) => $s->grading_type !== 'qualitative');
        $qualitativeSubjects = $subjects->filter(fn($s) => $s->grading_type === 'qualitative');

        // Construir la matriz de notas por materia/lapso
        $gradesMatrix = [];
        foreach ($numericSubjects as $subject) {
            $row = [];
            foreach ($lapses as $lapse) {
                $grade = $enrollment->grades
                    ->where('subject_id', $subject->id)
                    ->where('lapse_id', $lapse->id)
                    ->first();
                $row[$lapse->id] = $grade ? [
                    'score'              => (float) $grade->score,
                    'council_adjustment' => (int) $grade->council_adjustment,
                    'definitive'         => $grade->definitive,
                ] : null;
            }
            // Nota final de la materia (promedio de definitivas de cada lapso)
            $definitives = collect($row)->filter()->pluck('definitive');
            $finalGrade  = $definitives->isNotEmpty() ? round($definitives->avg(), 2) : null;

            // Nota de revisión si existe
            $revision = $enrollment->revisionGrades
                ->where('subject_id', $subject->id)
                ->first();

            $gradesMatrix[$subject->id] = [
                'lapses'     => $row,
                'final'      => $finalGrade,
                'revision'   => $revision ? (float) $revision->score : null,
                'is_pending' => $finalGrade !== null && $finalGrade < 10 && (!$revision || $revision->score < 10),
            ];
        }

        // Notas cualitativas (solo la del último lapso o la única registrada)
        $qualitativeGrades = [];
        foreach ($qualitativeSubjects as $subject) {
            $lastGrade = $enrollment->grades
                ->where('subject_id', $subject->id)
                ->sortByDesc('lapse_id')
                ->first();
            $qualitativeGrades[$subject->id] = $lastGrade?->score ?? '—';
        }

        // Inasistencias totales (lapses no tienen rango de fechas definido)
        $absencesByLapse = [];
        foreach ($lapses as $lapse) {
            $absencesByLapse[$lapse->id] = '—';
        }
        $totalAbsences = $enrollment->attendances
            ->filter(fn($att) => $att->status->value === 'absent')
            ->count();

        // Promedio general (solo numéricas)
        $overallAverage = $calcAverage->overall($enrollment);

        // Promedio por lapso
        $lapseAverages = [];
        foreach ($lapses as $lapse) {
            $lapseScores = collect($gradesMatrix)
                ->map(fn($m) => $m['lapses'][$lapse->id]['definitive'] ?? null)
                ->filter()
                ->values();
            $lapseAverages[$lapse->id] = $lapseScores->isNotEmpty() ? round($lapseScores->avg(), 2) : null;
        }

        return [
            'enrollment' => $enrollment,
            'lapses' => $lapses,
            'numericSubjects' => $numericSubjects,
            'qualitativeSubjects' => $qualitativeSubjects,
            'gradesMatrix' => $gradesMatrix,
            'qualitativeGrades' => $qualitativeGrades,
            'absencesByLapse' => $absencesByLapse,
            'totalAbsences' => $totalAbsences,
            'overallAverage' => $overallAverage,
            'lapseAverages' => $lapseAverages,
        ];
    }
}
