<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicLoad;
use App\Models\Grade;
use App\Models\Lapse;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CouncilAdjustmentController extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware
{
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

        $grade = Grade::findOrFail($validated['grade_id']);
        $grade->update(['council_adjustment' => $validated['council_adjustment']]);

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
            Grade::where('id', $change['grade_id'])
                ->update(['council_adjustment' => $change['council_adjustment']]);
        }

        return redirect()->back()->with('success', 'Ajustes guardados correctamente.');
    }
}
