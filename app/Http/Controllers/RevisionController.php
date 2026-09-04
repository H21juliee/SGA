<?php

namespace App\Http\Controllers;

use App\Actions\Grades\CalculateAverageAction;
use App\Models\AcademicLoad;
use App\Models\Enrollment;
use App\Models\RevisionGrade;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use App\Traits\LogsActivity;
use App\Traits\ValidatesTeacherLoad;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RevisionController extends Controller
{
    use LogsActivity, ValidatesTeacherLoad;
    /**
     * Muestra la pantalla principal de Revisiones con las cargas académicas.
     */
    public function index(Request $request, CalculateAverageAction $calcAverage)
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
                'allLapsesClosed' => false,
            ]);
        }

        // Verificar que TODOS los lapsos estén cerrados para habilitar revisiones
        $allLapsesClosed = $selectedYear->lapses->isNotEmpty() && $selectedYear->lapses->every(fn($l) => !$l->is_open);

        // Si es docente, solo consultar su carga académica; si es directivo/admin, todas
        $loadsQuery = AcademicLoad::where('school_year_id', $selectedYear->id)
            ->with(['subject', 'section.gradeLevel', 'teacher']);

        if ($user->hasRole('Docente')) {
            $loadsQuery->where('teacher_id', $user->id);
        }

        $allLoads = $loadsQuery->get();

        // Obtener inscripciones no retiradas con sus calificaciones para este año escolar
        $enrollmentsBySection = Enrollment::where('school_year_id', $selectedYear->id)
            ->whereNotIn('status', [\App\Enums\EnrollmentStatus::WITHDRAWN])
            ->with(['grades'])
            ->get()
            ->groupBy('section_id');

        // Filtrar solo las materias que tienen estudiantes aplazados (promedio < 9.5)
        $filteredLoads = $allLoads->filter(function ($load) use ($enrollmentsBySection, $calcAverage) {
            if (!$load->subject || $load->subject->isQualitative()) {
                return false;
            }

            $sectionEnrollments = $enrollmentsBySection->get($load->section_id, collect());
            
            $failedCount = 0;
            foreach ($sectionEnrollments as $enrollment) {
                $finalGrade = $calcAverage->forSubject($enrollment, $load->subject);
                if ($finalGrade !== null && $finalGrade < 9.5) {
                    $failedCount++;
                }
            }

            $load->failed_students_count = $failedCount;
            $load->total_students_count = $sectionEnrollments->count();

            return $failedCount > 0;
        })->values();

        return Inertia::render('Revisions/Index', [
            'loads' => $filteredLoads,
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
        $this->authorizeLoad($section->id, $subject->id);

        $enrollments = Enrollment::where('section_id', $section->id)
            ->where('school_year_id', $section->school_year_id)
            ->whereNotIn('status', [\App\Enums\EnrollmentStatus::WITHDRAWN])
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

        // Verificar si el año está cerrado o no activo (revisiones bloqueadas en solo lectura)
        $schoolYear = SchoolYear::find($section->school_year_id);
        $isClosed = !$schoolYear || $schoolYear->is_closed || !$schoolYear->is_active;

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
        $this->authorizeLoad($enrollment->section_id, $validated['subject_id']);

        $schoolYear = SchoolYear::findOrFail($enrollment->school_year_id);

        if ($schoolYear->is_closed || !$schoolYear->is_active) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'El año escolar ya fue cerrado o es un periodo histórico. No se pueden modificar revisiones.'], 422);
            }
            return back()->withErrors(['message' => 'El año escolar ya fue cerrado o es un periodo histórico. No se pueden modificar revisiones.']);
        }

        $status = $validated['score'] >= 9.5 ? 'approved' : 'failed';

        $existing = RevisionGrade::where('enrollment_id', $validated['enrollment_id'])
            ->where('subject_id', $validated['subject_id'])->first();
        $oldScore = $existing?->score;

        $revision = RevisionGrade::updateOrCreate(
            ['enrollment_id' => $validated['enrollment_id'], 'subject_id' => $validated['subject_id']],
            ['score' => $validated['score'], 'status' => $status, 'evaluated_at' => now()->toDateString()]
        );

        $revision->load('enrollment.student', 'subject');
        $studentName = $revision->enrollment->student->full_name ?? 'Desconocido';
        $subjectName = $revision->subject->name ?? 'Desconocida';

        $this->auditLog('revisiones', 'revision_updated',
            "Nota de revisión para {$studentName} — {$subjectName}: {$oldScore} → {$validated['score']}",
            $revision,
            ['old' => ['score' => $oldScore], 'new' => ['score' => $validated['score']]]
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Nota de revisión guardada correctamente.',
                'score' => $validated['score'],
                'status' => $status,
            ]);
        }

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

        // Validar que el año no esté cerrado ni sea inactivo
        $first = $request->input('changes')[0];
        $enrollment = Enrollment::findOrFail($first['enrollment_id']);
        
        $this->authorizeLoad($enrollment->section_id, $first['subject_id']);

        $schoolYear = SchoolYear::findOrFail($enrollment->school_year_id);

        if ($schoolYear->is_closed || !$schoolYear->is_active) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'El año escolar ya fue cerrado o es un periodo histórico. No se pueden modificar revisiones.'], 422);
            }
            return back()->withErrors(['message' => 'El año escolar ya fue cerrado o es un periodo histórico. No se pueden modificar revisiones.']);
        }

        foreach ($request->input('changes') as $change) {
            $status = $change['score'] >= 9.5 ? 'approved' : 'failed';

            $existing = RevisionGrade::where('enrollment_id', $change['enrollment_id'])
                ->where('subject_id', $change['subject_id'])->first();
            $oldScore = $existing?->score;

            $revision = RevisionGrade::updateOrCreate(
                ['enrollment_id' => $change['enrollment_id'], 'subject_id' => $change['subject_id']],
                ['score' => $change['score'], 'status' => $status, 'evaluated_at' => now()->toDateString()]
            );

            $revision->load('enrollment.student', 'subject');
            $studentName = $revision->enrollment->student->full_name ?? 'Desconocido';
            $subjectName = $revision->subject->name ?? 'Desconocida';

            $this->auditLog('revisiones', 'revision_updated',
                "Nota de revisión para {$studentName} — {$subjectName}: {$oldScore} → {$change['score']}",
                $revision,
                ['old' => ['score' => $oldScore], 'new' => ['score' => $change['score']]]
            );
        }

        return back()->with('success', 'Notas de revisión guardadas correctamente.');
    }
}
