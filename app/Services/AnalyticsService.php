<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeLevel;
use App\Models\Lapse;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Obtiene el consolidado de métricas estadísticas e indicadores clave.
     */
    public function getMetrics(int $schoolYearId, ?int $lapseId = null, ?int $gradeLevelId = null, ?int $sectionId = null): array
    {
        $schoolYear = SchoolYear::with('lapses')->find($schoolYearId);
        if (!$schoolYear) {
            return $this->getEmptyMetrics();
        }

        // Base query for grades scoped to this school year
        $gradesQuery = Grade::query()
            ->join('lapses', 'grades.lapse_id', '=', 'lapses.id')
            ->join('enrollments', 'grades.enrollment_id', '=', 'enrollments.id')
            ->join('sections', 'enrollments.section_id', '=', 'sections.id')
            ->join('subjects', 'grades.subject_id', '=', 'subjects.id')
            ->where('lapses.school_year_id', $schoolYearId)
            ->where('enrollments.school_year_id', $schoolYearId)
            ->where('subjects.grading_type', 'numeric');

        if ($lapseId) {
            $gradesQuery->where('grades.lapse_id', $lapseId);
        }

        if ($sectionId) {
            $gradesQuery->where('enrollments.section_id', $sectionId);
        } elseif ($gradeLevelId) {
            $gradesQuery->where('sections.grade_level_id', $gradeLevelId);
        }

        // We compute definitive grade: LEAST(20, GREATEST(1, grades.score + COALESCE(grades.council_adjustment, 0)))
        $definitiveExpr = 'LEAST(20, GREATEST(1, grades.score + COALESCE(grades.council_adjustment, 0)))';

        // 1. KPI Aggregates
        $kpiAgg = (clone $gradesQuery)
            ->selectRaw("
                COUNT(*) as total_grades,
                AVG($definitiveExpr) as overall_average,
                SUM(CASE WHEN $definitiveExpr >= 9.5 THEN 1 ELSE 0 END) as passed_count,
                SUM(CASE WHEN $definitiveExpr < 9.5 THEN 1 ELSE 0 END) as failed_count,
                COUNT(DISTINCT enrollments.student_id) as total_students
            ")
            ->first();

        $totalGrades = (int) ($kpiAgg->total_grades ?? 0);
        $overallAvg = $totalGrades > 0 ? round((float) $kpiAgg->overall_average, 2) : 0.0;
        $passedCount = (int) ($kpiAgg->passed_count ?? 0);
        $failedCount = (int) ($kpiAgg->failed_count ?? 0);
        $totalStudents = (int) ($kpiAgg->total_students ?? 0);

        $passRate = $totalGrades > 0 ? round(($passedCount / $totalGrades) * 100, 1) : 0.0;
        $failRate = $totalGrades > 0 ? round(($failedCount / $totalGrades) * 100, 1) : 0.0;

        // If no grades, get student count directly from enrollments
        if ($totalStudents === 0) {
            $enrollmentQuery = Enrollment::where('school_year_id', $schoolYearId)->active();
            if ($sectionId) {
                $enrollmentQuery->where('section_id', $sectionId);
            } elseif ($gradeLevelId) {
                $enrollmentQuery->whereHas('section', fn($q) => $q->where('grade_level_id', $gradeLevelId));
            }
            $totalStudents = $enrollmentQuery->count();
        }

        // 2. Grade Distribution (Histogram)
        $histogramAgg = (clone $gradesQuery)
            ->selectRaw("
                SUM(CASE WHEN $definitiveExpr < 9.5 THEN 1 ELSE 0 END) as deficiente,
                SUM(CASE WHEN $definitiveExpr >= 9.5 AND $definitiveExpr < 13.5 THEN 1 ELSE 0 END) as regular,
                SUM(CASE WHEN $definitiveExpr >= 13.5 AND $definitiveExpr < 17.5 THEN 1 ELSE 0 END) as bueno,
                SUM(CASE WHEN $definitiveExpr >= 17.5 THEN 1 ELSE 0 END) as excelente
            ")
            ->first();

        $histDef = (int) ($histogramAgg->deficiente ?? 0);
        $histReg = (int) ($histogramAgg->regular ?? 0);
        $histBue = (int) ($histogramAgg->bueno ?? 0);
        $histExc = (int) ($histogramAgg->excelente ?? 0);

        $histogram = [
            'labels' => ['01 - 09 (Deficiente)', '10 - 13 (Regular)', '14 - 17 (Bueno)', '18 - 20 (Excelente)'],
            'counts' => [$histDef, $histReg, $histBue, $histExc],
            'percentages' => [
                $totalGrades > 0 ? round(($histDef / $totalGrades) * 100, 1) : 0,
                $totalGrades > 0 ? round(($histReg / $totalGrades) * 100, 1) : 0,
                $totalGrades > 0 ? round(($histBue / $totalGrades) * 100, 1) : 0,
                $totalGrades > 0 ? round(($histExc / $totalGrades) * 100, 1) : 0,
            ],
        ];

        // 3. Subjects Ranking (Performance by Subject)
        $subjectsData = (clone $gradesQuery)
            ->selectRaw("
                subjects.id as subject_id,
                subjects.name as subject_name,
                subjects.code as subject_code,
                COUNT(*) as evaluated_count,
                SUM(CASE WHEN $definitiveExpr >= 9.5 THEN 1 ELSE 0 END) as passed_count,
                SUM(CASE WHEN $definitiveExpr < 9.5 THEN 1 ELSE 0 END) as failed_count,
                AVG($definitiveExpr) as average_score
            ")
            ->groupBy('subjects.id', 'subjects.name', 'subjects.code')
            ->orderByRaw("SUM(CASE WHEN $definitiveExpr < 9.5 THEN 1 ELSE 0 END) DESC")
            ->get()
            ->map(function ($row) {
                $eval = (int) $row->evaluated_count;
                $passed = (int) $row->passed_count;
                $failed = (int) $row->failed_count;
                $passR = $eval > 0 ? round(($passed / $eval) * 100, 1) : 0.0;
                $failR = $eval > 0 ? round(($failed / $eval) * 100, 1) : 0.0;
                $avg = round((float) $row->average_score, 2);

                return [
                    'id' => $row->subject_id,
                    'name' => $row->subject_name,
                    'code' => $row->subject_code,
                    'evaluated' => $eval,
                    'passed' => $passed,
                    'failed' => $failed,
                    'pass_rate' => $passR,
                    'fail_rate' => $failR,
                    'average' => $avg,
                ];
            });

        // Critical Subject (subject with highest fail rate having at least 1 evaluation)
        $criticalSubject = $subjectsData->sortByDesc('fail_rate')->first();

        // 4. Performance by Section
        $sectionsData = (clone $gradesQuery)
            ->selectRaw("
                sections.id as section_id,
                sections.name as section_name,
                sections.grade_level_id,
                COUNT(*) as evaluated_count,
                SUM(CASE WHEN $definitiveExpr >= 9.5 THEN 1 ELSE 0 END) as passed_count,
                AVG($definitiveExpr) as average_score,
                COUNT(DISTINCT enrollments.student_id) as students_count
            ")
            ->groupBy('sections.id', 'sections.name', 'sections.grade_level_id')
            ->get();

        // Load grade level names
        $gradeLevelsMap = GradeLevel::pluck('name', 'id');

        $sectionsComparison = $sectionsData->map(function ($s) use ($gradeLevelsMap) {
            $eval = (int) $s->evaluated_count;
            $passed = (int) $s->passed_count;
            $lvlName = $gradeLevelsMap[$s->grade_level_id] ?? '';
            return [
                'id' => $s->section_id,
                'name' => trim("$lvlName {$s->section_name}"),
                'level' => $lvlName,
                'average' => round((float) $s->average_score, 2),
                'pass_rate' => $eval > 0 ? round(($passed / $eval) * 100, 1) : 0.0,
                'students_count' => (int) $s->students_count,
            ];
        })->values();

        // 5. Lapses Trend (Evolution across Lapses 1, 2, 3)
        $lapsesTrend = [];
        foreach ($schoolYear->lapses as $lapse) {
            $lapseQuery = Grade::query()
                ->join('enrollments', 'grades.enrollment_id', '=', 'enrollments.id')
                ->join('sections', 'enrollments.section_id', '=', 'sections.id')
                ->join('subjects', 'grades.subject_id', '=', 'subjects.id')
                ->where('grades.lapse_id', $lapse->id)
                ->where('subjects.grading_type', 'numeric');

            if ($sectionId) {
                $lapseQuery->where('enrollments.section_id', $sectionId);
            } elseif ($gradeLevelId) {
                $lapseQuery->where('sections.grade_level_id', $gradeLevelId);
            }

            $lAgg = $lapseQuery->selectRaw("
                COUNT(*) as total,
                AVG($definitiveExpr) as avg_score,
                SUM(CASE WHEN $definitiveExpr >= 9.5 THEN 1 ELSE 0 END) as passed
            ")->first();

            $lTotal = (int) ($lAgg->total ?? 0);
            $lPassed = (int) ($lAgg->passed ?? 0);

            $lapsesTrend[] = [
                'id' => $lapse->id,
                'name' => $lapse->name,
                'is_open' => (bool) $lapse->is_open,
                'total_evaluations' => $lTotal,
                'average' => $lTotal > 0 ? round((float) $lAgg->avg_score, 2) : null,
                'pass_rate' => $lTotal > 0 ? round(($lPassed / $lTotal) * 100, 1) : null,
            ];
        }

        // 6. Historical Comparison across all School Years
        $historicalComparison = [];
        $allYears = SchoolYear::orderBy('start_date', 'asc')->get();
        foreach ($allYears as $y) {
            $yAgg = Grade::query()
                ->join('lapses', 'grades.lapse_id', '=', 'lapses.id')
                ->join('subjects', 'grades.subject_id', '=', 'subjects.id')
                ->where('lapses.school_year_id', $y->id)
                ->where('subjects.grading_type', 'numeric')
                ->selectRaw("
                    COUNT(*) as total,
                    AVG($definitiveExpr) as avg_score,
                    SUM(CASE WHEN $definitiveExpr >= 9.5 THEN 1 ELSE 0 END) as passed
                ")->first();

            $yTotal = (int) ($yAgg->total ?? 0);
            $yPassed = (int) ($yAgg->passed ?? 0);

            $historicalComparison[] = [
                'id' => $y->id,
                'name' => $y->name,
                'is_active' => (bool) $y->is_active,
                'total_evaluations' => $yTotal,
                'average' => $yTotal > 0 ? round((float) $yAgg->avg_score, 2) : null,
                'pass_rate' => $yTotal > 0 ? round(($yPassed / $yTotal) * 100, 1) : null,
                'total_students' => Enrollment::where('school_year_id', $y->id)->count(),
            ];
        }

        return [
            'kpis' => [
                'total_students' => $totalStudents,
                'total_grades' => $totalGrades,
                'overall_average' => $overallAvg,
                'pass_rate' => $passRate,
                'fail_rate' => $failRate,
                'passed_count' => $passedCount,
                'failed_count' => $failedCount,
                'critical_subject' => $criticalSubject ? [
                    'name' => $criticalSubject['name'],
                    'fail_rate' => $criticalSubject['fail_rate'],
                    'average' => $criticalSubject['average'],
                ] : null,
            ],
            'pass_fail' => [
                'passed' => $passedCount,
                'failed' => $failedCount,
                'pass_rate' => $passRate,
                'fail_rate' => $failRate,
            ],
            'histogram' => $histogram,
            'subjects' => $subjectsData->values()->toArray(),
            'sections' => $sectionsComparison->toArray(),
            'lapses_trend' => $lapsesTrend,
            'historical' => $historicalComparison,
        ];
    }

    private function getEmptyMetrics(): array
    {
        return [
            'kpis' => [
                'total_students' => 0,
                'total_grades' => 0,
                'overall_average' => 0.0,
                'pass_rate' => 0.0,
                'fail_rate' => 0.0,
                'passed_count' => 0,
                'failed_count' => 0,
                'critical_subject' => null,
            ],
            'pass_fail' => [
                'passed' => 0,
                'failed' => 0,
                'pass_rate' => 0.0,
                'fail_rate' => 0.0,
            ],
            'histogram' => [
                'labels' => ['01 - 09 (Deficiente)', '10 - 13 (Regular)', '14 - 17 (Bueno)', '18 - 20 (Excelente)'],
                'counts' => [0, 0, 0, 0],
                'percentages' => [0, 0, 0, 0],
            ],
            'subjects' => [],
            'sections' => [],
            'lapses_trend' => [],
            'historical' => [],
        ];
    }
}
