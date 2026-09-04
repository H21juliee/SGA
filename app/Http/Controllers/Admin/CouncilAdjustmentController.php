<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicLoad;
use App\Models\Grade;
use App\Models\Lapse;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CouncilAdjustmentController extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware
{
    use LogsActivity;
    public static function middleware(): array
    {
        return [
        new \Illuminate\Routing\Controllers\Middleware('permission:council.view', only: ['index']),
        new \Illuminate\Routing\Controllers\Middleware('permission:council.manage', only: ['update']),
        new \Illuminate\Routing\Controllers\Middleware('permission:council.batch_update', only: ['batchUpdate']),
        ];
    }
    public function index(Request $request)
    {

        $activeYear = SchoolYear::active()->first();
        $gradeLevelId = $request->input('grade_level_id');
        $sectionId    = $request->input('section_id');
        $subjectId    = $request->input('subject_id');
        $lapseId      = $request->input('lapse_id');

        $sections = $activeYear
            ? Section::where('school_year_id', $activeYear->id)->with('gradeLevel')->get()
            : collect();

        $subjects = collect();
        $lapses   = $activeYear ? $activeYear->lapses : collect();
        $rows     = collect();

        $resolvedGradeLevelId = $gradeLevelId;
        if (!$resolvedGradeLevelId && $sectionId) {
            $resolvedGradeLevelId = Section::find($sectionId)?->grade_level_id;
        }

        if ($resolvedGradeLevelId) {
            $subjects = Subject::where('grade_level_id', $resolvedGradeLevelId)
                ->where('grading_type', 'numeric')
            ->get();
        }

        if ($sectionId && $subjectId && $lapseId) {
            $rows = Grade::where('subject_id', $subjectId)
                ->where('lapse_id', $lapseId)
                ->whereHas('enrollment', fn($q) => $q->where('section_id', $sectionId))
                ->with('enrollment.student')
                ->get()
                ->map(fn($grade) => [
                    'grade_id'          => $grade->id,
                    'student_name'      => $grade->enrollment->student->full_name,
                    'student_cedula'    => $grade->enrollment->student->cedula ?? '—',
                    'score'             => (float) $grade->score,
                    'council_adjustment'=> (int) $grade->council_adjustment,
                    'definitive'        => $grade->definitive,
                ]);
        }

        return Inertia::render('Admin/CouncilAdjustments/Index', [
            'activeYear' => $activeYear,
            'sections'   => $sections,
            'subjects'   => $subjects,
            'lapses'     => $lapses,
            'rows'       => $rows,
            'filters' => [
                'grade_level_id' => $resolvedGradeLevelId,
                'section_id'     => $sectionId,
                'subject_id'     => $subjectId,
                'lapse_id'       => $lapseId,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'grade_id'           => 'required|exists:grades,id',
            'council_adjustment' => 'required|integer|min:-5|max:5',
        ]);

        $grade = Grade::with('enrollment.schoolYear', 'lapse')->findOrFail($validated['grade_id']);
        $schoolYear = $grade->enrollment?->schoolYear;

        if ($schoolYear && ($schoolYear->is_closed || !$schoolYear->is_active)) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'No se pueden realizar ajustes de consejo en un año escolar cerrado o inactivo.'], 422);
            }
            return redirect()->back()->withErrors(['message' => 'No se pueden realizar ajustes de consejo en un año escolar cerrado o inactivo.']);
        }

        $oldAdj = $grade->council_adjustment;
        $grade->update(['council_adjustment' => $validated['council_adjustment']]);

        // Cargar datos del alumno para la descripción
        $grade->load('enrollment.student', 'subject', 'lapse');
        $studentName = $grade->enrollment->student->full_name ?? 'Desconocido';
        $subjectName = $grade->subject->name ?? 'Desconocida';
        $lapseName   = $grade->lapse->name ?? 'Desconocido';

        $this->auditLog('consejo', 'council_updated',
            "Ajuste de consejo para {$studentName} — {$subjectName} ({$lapseName}): {$oldAdj} → {$validated['council_adjustment']}",
            $grade,
            ['old' => ['council_adjustment' => $oldAdj], 'new' => ['council_adjustment' => $validated['council_adjustment']]]
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Ajuste guardado.',
                'council_adjustment' => $grade->council_adjustment,
            ]);
        }

        return redirect()->back()->with('success', 'Ajuste guardado.');
    }

    public function batchUpdate(Request $request)
    {
        $validated = $request->validate([
            'changes'                    => 'required|array',
            'changes.*.grade_id'         => 'required|exists:grades,id',
            'changes.*.council_adjustment' => 'required|integer|min:-5|max:5',
        ]);

        foreach ($validated['changes'] as $change) {
            $grade = Grade::with('enrollment.schoolYear', 'lapse')->where('id', $change['grade_id'])->first();
            if (!$grade) continue;

            $schoolYear = $grade->enrollment?->schoolYear;
            if ($schoolYear && ($schoolYear->is_closed || !$schoolYear->is_active)) {
                continue;
            }

            $oldAdj = $grade->council_adjustment;
            $grade->update(['council_adjustment' => $change['council_adjustment']]);

            $grade->load('enrollment.student', 'subject', 'lapse');
            $studentName = $grade->enrollment->student->full_name ?? 'Desconocido';
            $subjectName = $grade->subject->name ?? 'Desconocida';
            $lapseName   = $grade->lapse->name ?? 'Desconocido';

            $this->auditLog('consejo', 'council_updated',
                "Ajuste de consejo para {$studentName} — {$subjectName} ({$lapseName}): {$oldAdj} → {$change['council_adjustment']}",
                $grade,
                ['old' => ['council_adjustment' => $oldAdj], 'new' => ['council_adjustment' => $change['council_adjustment']]]
            );
        }

        return redirect()->back()->with('success', 'Ajustes guardados correctamente.');
    }
}
