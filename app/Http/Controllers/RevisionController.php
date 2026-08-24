<?php

namespace App\Http\Controllers;

use App\Actions\Grades\CalculateAverageAction;
use App\Models\AcademicLoad;
use App\Models\Enrollment;
use App\Models\RevisionGrade;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RevisionController extends Controller
{
    /**
     * Muestra la pantalla principal de Revisiones con las cargas académicas.
     */
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
            return Inertia::render('Revisions/Index', [
                'loads' => [],
                'activeYear' => null,
                'schoolYears' => $schoolYears,
            ]);
        }

        // Verificar que TODOS los lapsos estén cerrados para habilitar revisiones
        $allLapsesClosed = $selectedYear->lapses->every(fn($l) => !$l->is_open);

        // Si es docente, solo mostrar su carga académica
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

        return Inertia::render('Revisions/Index', [
            'loads' => $loads,
            'activeYear' => $selectedYear,
            'schoolYears' => $schoolYears,
            'allLapsesClosed' => $allLapsesClosed,
        ]);
    }

    /**
     * Muestra el DataGrid de revisión para una sección/materia específica.
     * Solo muestra estudiantes que aplazaron la materia (promedio < 9.5 en los 3 lapsos).
     */
    public function datagrid(Request $request, Section $section, Subject $subject, CalculateAverageAction $calcAverage)
    {
        $enrollments = Enrollment::where('section_id', $section->id)
            ->where('school_year_id', $section->school_year_id)
            ->active()
            ->with([
                'student',
                'grades' => fn($q) => $q->where('subject_id', $subject->id),
                'revisionGrades' => fn($q) => $q->where('subject_id', $subject->id),
            ])
            ->get()
            ->sortBy('student.last_name');

        // Filtrar solo los estudiantes que aplazaron esta materia
        $failedEnrollments = $enrollments->filter(function ($enrollment) use ($subject, $calcAverage) {
            $finalGrade = $calcAverage->forSubject($enrollment, $subject);
            return $finalGrade === null || $finalGrade < 9.5;
        });

        // Verificar si el año está cerrado (revisiones bloqueadas)
        $schoolYear = SchoolYear::find($section->school_year_id);
        $isClosed = $schoolYear->is_closed ?? false;

        return Inertia::render('Revisions/DataGrid', [
            'section' => $section->load('gradeLevel'),
            'subject' => $subject,
            'enrollments' => $failedEnrollments->values(),
            'isClosed' => $isClosed,
        ]);
    }

    /**
     * Guarda/actualiza una nota de revisión individual.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'enrollment_id' => 'required|integer|exists:enrollments,id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'score' => 'required|numeric|min:1|max:20',
        ]);

        $enrollment = Enrollment::findOrFail($validated['enrollment_id']);
        $schoolYear = SchoolYear::findOrFail($enrollment->school_year_id);

        if ($schoolYear->is_closed) {
            return back()->withErrors(['message' => 'El año escolar ya fue cerrado. No se pueden cargar revisiones.']);
        }

        $status = $validated['score'] >= 9.5 ? 'approved' : 'failed';

        RevisionGrade::updateOrCreate(
            [
                'enrollment_id' => $validated['enrollment_id'],
                'subject_id' => $validated['subject_id'],
            ],
            [
                'score' => $validated['score'],
                'status' => $status,
                'evaluated_at' => now()->toDateString(),
            ]
        );

        return back()->with('success', 'Nota de revisión guardada correctamente.');
    }

    /**
     * Guarda/actualiza múltiples notas de revisión.
     */
    public function batchUpdate(Request $request)
    {
        $request->validate([
            'changes' => ['required', 'array'],
            'changes.*.enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'changes.*.subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'changes.*.score' => ['required', 'numeric', 'min:1', 'max:20'],
        ]);

        // Validar que el año no esté cerrado
        $first = $request->input('changes')[0];
        $enrollment = Enrollment::findOrFail($first['enrollment_id']);
        $schoolYear = SchoolYear::findOrFail($enrollment->school_year_id);

        if ($schoolYear->is_closed) {
            return back()->withErrors(['message' => 'El año escolar ya fue cerrado. No se pueden cargar revisiones.']);
        }

        foreach ($request->input('changes') as $change) {
            $status = $change['score'] >= 9.5 ? 'approved' : 'failed';

            RevisionGrade::updateOrCreate(
                [
                    'enrollment_id' => $change['enrollment_id'],
                    'subject_id' => $change['subject_id'],
                ],
                [
                    'score' => $change['score'],
                    'status' => $status,
                    'evaluated_at' => now()->toDateString(),
                ]
            );
        }

        return back()->with('success', 'Notas de revisión guardadas correctamente.');
    }
}
