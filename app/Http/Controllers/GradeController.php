<?php

namespace App\Http\Controllers;

use App\Actions\Grades\StoreGradeAction;
use App\DTOs\GradeDTO;
use App\Http\Requests\StoreGradeRequest;
use App\Models\AcademicLoad;
use App\Models\Enrollment;
use App\Models\Lapse;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();
        
        $selectedYearId = $request->input('school_year_id');
        
        if ($selectedYearId) {
            $selectedYear = SchoolYear::with('lapses')->findOrFail($selectedYearId);
        } else {
            $selectedYear = SchoolYear::active()->with('lapses')->first();
        }

        if (!$selectedYear) {
            return Inertia::render('Grades/Index', [
                'loads' => [], 
                'activeYear' => null,
                'schoolYears' => $schoolYears
            ]);
        }

        // Si es docente, solo mostrar su carga académica en ese año
        if ($user->hasRole('Docente')) {
            $loads = AcademicLoad::where('teacher_id', $user->id)
                ->where('school_year_id', $selectedYear->id)
                ->with(['subject', 'section.gradeLevel'])
                ->get();
        } else {
            $loads = AcademicLoad::where('school_year_id', $selectedYear->id)
                ->with(['subject', 'section.gradeLevel', 'teacher'])
                ->get();
        }

        return Inertia::render('Grades/Index', [
            'loads' => $loads,
            'activeYear' => $selectedYear,
            'lapses' => $selectedYear->lapses,
            'schoolYears' => $schoolYears,
        ]);
    }

    public function datagrid(Request $request, Section $section, Subject $subject, Lapse $lapse)
    {
        $enrollments = Enrollment::where('section_id', $section->id)
            ->where('school_year_id', $section->school_year_id)
            ->active()
            ->with([
                'student',
                'grades' => fn($q) => $q->where('subject_id', $subject->id)
                                         ->where('lapse_id', $lapse->id),
            ])
            ->get()
            ->sortBy('student.last_name');

        return Inertia::render('Grades/DataGrid', [
            'section' => $section->load('gradeLevel'),
            'subject' => $subject,
            'lapse' => $lapse,
            'enrollments' => $enrollments->values(),
        ]);
    }

    public function update(StoreGradeRequest $request, StoreGradeAction $action)
    {
        $lapse = Lapse::findOrFail($request->input('lapse_id'));
        
        if (!$lapse->is_open) {
            return back()->withErrors(['message' => 'No se pueden cargar notas en un lapso cerrado.']);
        }

        $dto = GradeDTO::fromRequest($request);
        $action->execute($dto);

        return back()->with('success', 'Nota guardada correctamente.');
    }

    public function batchUpdate(Request $request, StoreGradeAction $action)
    {
        $request->validate([
            'changes' => ['required', 'array'],
            'changes.*.enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'changes.*.subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'changes.*.lapse_id' => ['required', 'integer', 'exists:lapses,id'],
            'changes.*.score' => ['required', 'numeric', 'min:1', 'max:20'],
        ]);

        // Validar que el lapso del primer cambio esté abierto
        $first = $request->input('changes')[0];
        $lapse = Lapse::findOrFail($first['lapse_id']);

        if (!$lapse->is_open) {
            return back()->withErrors(['message' => 'No se pueden cargar notas en un lapso cerrado.']);
        }

        foreach ($request->input('changes') as $change) {
            $dto = GradeDTO::fromArray($change);
            $action->execute($dto);
        }

        return back()->with('success', 'Notas guardadas correctamente.');
    }
}
