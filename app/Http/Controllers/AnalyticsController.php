<?php

namespace App\Http\Controllers;

use App\Models\GradeLevel;
use App\Models\SchoolSetting;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Services\AnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        protected AnalyticsService $analyticsService
    ) {}

    public function index(Request $request)
    {
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();
        $selectedYearId = $request->input('school_year_id')
            ?? SchoolYear::active()->value('id')
            ?? $schoolYears->first()?->id;

        $selectedYear = $selectedYearId ? SchoolYear::with('lapses')->find($selectedYearId) : null;
        $lapses = $selectedYear ? $selectedYear->lapses()->orderBy('number')->get() : collect();

        $lapseId = $request->filled('lapse_id') && $request->input('lapse_id') !== 'all'
            ? (int) $request->input('lapse_id')
            : null;

        $gradeLevelId = $request->filled('grade_level_id') && $request->input('grade_level_id') !== 'all'
            ? (int) $request->input('grade_level_id')
            : null;

        $sectionId = $request->filled('section_id') && $request->input('section_id') !== 'all'
            ? (int) $request->input('section_id')
            : null;

        $gradeLevels = GradeLevel::orderBy('order_num')->get();

        $sectionsQuery = Section::where('school_year_id', $selectedYearId)
            ->with('gradeLevel')
            ->join('grade_levels', 'sections.grade_level_id', '=', 'grade_levels.id')
            ->select('sections.*')
            ->orderBy('grade_levels.order_num')
            ->orderBy('sections.name');

        if ($gradeLevelId) {
            $sectionsQuery->where('sections.grade_level_id', $gradeLevelId);
        }
        $sections = $sectionsQuery->get();

        $metrics = $selectedYearId
            ? $this->analyticsService->getMetrics($selectedYearId, $lapseId, $gradeLevelId, $sectionId)
            : [];

        return Inertia::render('Analytics/Index', [
            'schoolYears' => $schoolYears,
            'selectedYearId' => (int) $selectedYearId,
            'lapses' => $lapses,
            'selectedLapseId' => $lapseId,
            'gradeLevels' => $gradeLevels,
            'selectedGradeLevelId' => $gradeLevelId,
            'sections' => $sections,
            'selectedSectionId' => $sectionId,
            'metrics' => $metrics,
        ]);
    }

    /**
     * Exporta la sábana de rendimiento de materias a CSV/Excel.
     */
    public function exportExcel(Request $request): StreamedResponse
    {
        $schoolYearId = (int) $request->input('school_year_id', SchoolYear::active()->value('id'));
        $lapseId = $request->filled('lapse_id') && $request->input('lapse_id') !== 'all' ? (int) $request->input('lapse_id') : null;
        $gradeLevelId = $request->filled('grade_level_id') && $request->input('grade_level_id') !== 'all' ? (int) $request->input('grade_level_id') : null;
        $sectionId = $request->filled('section_id') && $request->input('section_id') !== 'all' ? (int) $request->input('section_id') : null;

        $schoolYear = SchoolYear::find($schoolYearId);
        $metrics = $this->analyticsService->getMetrics($schoolYearId, $lapseId, $gradeLevelId, $sectionId);

        $filename = 'reporte_estadistico_' . ($schoolYear?->name ?? 'sga') . '_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($metrics, $schoolYear) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 for Excel compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header summary
            fputcsv($handle, ['SGA - SISTEMA DE GESTIÓN ACADÉMICA']);
            fputcsv($handle, ['REPORTE ESTADÍSTICO DE RENDIMIENTO ACADÉMICO']);
            fputcsv($handle, ['Año Escolar:', $schoolYear?->name ?? 'Todos']);
            fputcsv($handle, ['Fecha de generación:', date('d/m/Y H:i')]);
            fputcsv($handle, []);

            // KPIs
            fputcsv($handle, ['RESUMEN GENERAL']);
            fputcsv($handle, ['Total Estudiantes Evaluados', $metrics['kpis']['total_students']]);
            fputcsv($handle, ['Total Calificaciones', $metrics['kpis']['total_grades']]);
            fputcsv($handle, ['Promedio General del Plantel', $metrics['kpis']['overall_average']]);
            fputcsv($handle, ['Tasa de Aprobación (%)', $metrics['kpis']['pass_rate'] . '%']);
            fputcsv($handle, ['Tasa de Reprobación (%)', $metrics['kpis']['fail_rate'] . '%']);
            fputcsv($handle, []);

            // Subjects ranking table
            fputcsv($handle, ['RENDIMIENTO DETALLADO POR MATERIA']);
            fputcsv($handle, ['Código', 'Materia', 'Evaluaciones', 'Aprobados', 'Reprobados', '% Aprobación', '% Reprobación', 'Promedio']);

            foreach ($metrics['subjects'] as $sub) {
                fputcsv($handle, [
                    $sub['code'],
                    $sub['name'],
                    $sub['evaluated'],
                    $sub['passed'],
                    $sub['failed'],
                    $sub['pass_rate'] . '%',
                    $sub['fail_rate'] . '%',
                    number_format($sub['average'], 2),
                ]);
            }

            fputcsv($handle, []);

            // Distribution
            fputcsv($handle, ['DISTRIBUCIÓN DE CALIFICACIONES']);
            fputcsv($handle, ['Escala / Rango', 'Cantidad', 'Porcentaje']);
            foreach ($metrics['histogram']['labels'] as $idx => $label) {
                fputcsv($handle, [
                    $label,
                    $metrics['histogram']['counts'][$idx],
                    $metrics['histogram']['percentages'][$idx] . '%',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Exporta el informe estadístico consolidado a PDF.
     */
    public function exportPdf(Request $request)
    {
        $schoolYearId = (int) $request->input('school_year_id', SchoolYear::active()->value('id'));
        $lapseId = $request->filled('lapse_id') && $request->input('lapse_id') !== 'all' ? (int) $request->input('lapse_id') : null;
        $gradeLevelId = $request->filled('grade_level_id') && $request->input('grade_level_id') !== 'all' ? (int) $request->input('grade_level_id') : null;
        $sectionId = $request->filled('section_id') && $request->input('section_id') !== 'all' ? (int) $request->input('section_id') : null;

        $schoolYear = SchoolYear::find($schoolYearId);
        $lapse = $lapseId ? \App\Models\Lapse::find($lapseId) : null;
        $metrics = $this->analyticsService->getMetrics($schoolYearId, $lapseId, $gradeLevelId, $sectionId);

        $school = [
            'name' => SchoolSetting::get('school_name', 'U.E. Colegio'),
            'code' => SchoolSetting::get('dea_code', 'DEA-0000'),
            'logo_path' => SchoolSetting::get('logo_path'),
        ];

        $pdf = Pdf::loadView('pdf.analytics_report', [
            'school' => $school,
            'schoolYear' => $schoolYear,
            'lapse' => $lapse,
            'metrics' => $metrics,
            'generatedAt' => date('d/m/Y H:i'),
        ])->setPaper('letter', 'portrait');

        $filename = 'analisis_estadistico_' . ($schoolYear?->name ?? 'sga') . '.pdf';
        return $pdf->stream($filename);
    }
}
