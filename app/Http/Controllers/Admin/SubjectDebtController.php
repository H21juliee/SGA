<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnrollmentType;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectDebt;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;

class SubjectDebtController extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware
{
    use LogsActivity;

    public static function middleware(): array
    {
        return [
            new \Illuminate\Routing\Controllers\Middleware('permission:students.edit'),
        ];
    }

    /**
     * Asigna una materia pendiente manualmente a un estudiante.
     */
    public function store(Request $request, Student $student)
    {
        $validated = $request->validate([
            'subject_id'            => 'required|exists:subjects,id',
            'origin_school_year_id' => 'nullable|exists:school_years,id',
            'status'                => 'required|in:pending,resolved',
            'score'                 => 'nullable|numeric|min:1|max:20',
            'moment'                => 'nullable|string|max:50',
            'acta_number'           => 'nullable|string|max:50',
            'notes'                 => 'nullable|string|max:1000',
        ]);

        // Verificar si ya tiene asignada esta materia como pendiente
        $existing = SubjectDebt::where('student_id', $student->id)
            ->where('subject_id', $validated['subject_id'])
            ->where('status', 'pending')
            ->first();

        if ($existing && $validated['status'] === 'pending') {
            return back()->with('error', 'El estudiante ya tiene esta materia registrada como pendiente.');
        }

        $activeYear = SchoolYear::active()->first();
        $activeEnrollment = null;

        if ($activeYear) {
            $activeEnrollment = Enrollment::where('student_id', $student->id)
                ->where('school_year_id', $activeYear->id)
                ->first();
        }

        $isResolved = $validated['status'] === 'resolved' || (isset($validated['score']) && $validated['score'] >= 10);
        $status = $isResolved ? 'resolved' : 'pending';

        $debt = SubjectDebt::create([
            'student_id'               => $student->id,
            'subject_id'               => $validated['subject_id'],
            'origin_school_year_id'    => $validated['origin_school_year_id'] ?? null,
            'origin_enrollment_id'     => null,
            'resolution_enrollment_id' => $isResolved && $activeEnrollment ? $activeEnrollment->id : null,
            'status'                   => $status,
            'score'                    => $validated['score'] ?? null,
            'moment'                   => $validated['moment'] ?? null,
            'acta_number'              => $validated['acta_number'] ?? null,
            'notes'                    => $validated['notes'] ?? null,
            'resolved_at'              => $isResolved ? now() : null,
        ]);

        // Si la deuda es pendiente y tiene inscripción activa, actualizar tipo a PENDING
        if ($status === 'pending' && $activeEnrollment) {
            $activeEnrollment->update(['enrollment_type' => EnrollmentType::PENDING]);
        }

        $subject = Subject::find($validated['subject_id']);
        $this->auditLog(
            'materias_pendientes',
            'created',
            "Asignó materia pendiente '{$subject->name}' a {$student->full_name}",
            $debt
        );

        return back()->with('success', "Materia pendiente '{$subject->name}' registrada exitosamente.");
    }

    /**
     * Actualiza el estado y calificación de una materia pendiente.
     */
    public function update(Request $request, SubjectDebt $subjectDebt)
    {
        $validated = $request->validate([
            'status'      => 'required|in:pending,resolved',
            'score'       => 'nullable|numeric|min:1|max:20',
            'moment'      => 'nullable|string|max:50',
            'acta_number' => 'nullable|string|max:50',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $activeYear = SchoolYear::active()->first();
        $activeEnrollment = null;

        if ($activeYear) {
            $activeEnrollment = Enrollment::where('student_id', $subjectDebt->student_id)
                ->where('school_year_id', $activeYear->id)
                ->first();
        }

        $isResolved = $validated['status'] === 'resolved' || (isset($validated['score']) && $validated['score'] >= 10);
        $status = $isResolved ? 'resolved' : 'pending';

        $subjectDebt->update([
            'status'                   => $status,
            'score'                    => $validated['score'] ?? $subjectDebt->score,
            'moment'                   => $validated['moment'] ?? $subjectDebt->moment,
            'acta_number'              => $validated['acta_number'] ?? $subjectDebt->acta_number,
            'notes'                    => $validated['notes'] ?? $subjectDebt->notes,
            'resolved_at'              => $isResolved ? ($subjectDebt->resolved_at ?? now()) : null,
            'resolution_enrollment_id' => $isResolved && $activeEnrollment ? $activeEnrollment->id : null,
        ]);

        // Verificar si le quedan otras deudas pendientes
        $hasOtherPending = SubjectDebt::where('student_id', $subjectDebt->student_id)
            ->where('id', '!=', $subjectDebt->id)
            ->where('status', 'pending')
            ->exists();

        if (!$hasOtherPending && $activeEnrollment && $status === 'resolved') {
            $activeEnrollment->update(['enrollment_type' => EnrollmentType::REGULAR]);
        } elseif ($status === 'pending' && $activeEnrollment) {
            $activeEnrollment->update(['enrollment_type' => EnrollmentType::PENDING]);
        }

        $subjectDebt->load(['student', 'subject']);
        $this->auditLog(
            'materias_pendientes',
            'updated',
            "Actualizó materia pendiente '{$subjectDebt->subject->name}' de {$subjectDebt->student->full_name} (Estado: {$status}, Nota: " . ($validated['score'] ?? '-') . ")",
            $subjectDebt
        );

        return back()->with('success', 'Materia pendiente actualizada exitosamente.');
    }

    /**
     * Elimina el registro de una materia pendiente (si fue creada por error).
     */
    public function destroy(SubjectDebt $subjectDebt)
    {
        $studentId = $subjectDebt->student_id;
        $subjectDebt->load(['student', 'subject']);
        $studentName = $subjectDebt->student->full_name ?? 'Estudiante';
        $subjectName = $subjectDebt->subject->name ?? 'Materia';

        $subjectDebt->delete();

        // Si ya no le quedan deudas pendientes, restaurar inscripción activa a REGULAR
        $hasPending = SubjectDebt::where('student_id', $studentId)
            ->where('status', 'pending')
            ->exists();

        $activeYear = SchoolYear::active()->first();
        if (!$hasPending && $activeYear) {
            $activeEnrollment = Enrollment::where('student_id', $studentId)
                ->where('school_year_id', $activeYear->id)
                ->first();
            if ($activeEnrollment && $activeEnrollment->enrollment_type === EnrollmentType::PENDING) {
                $activeEnrollment->update(['enrollment_type' => EnrollmentType::REGULAR]);
            }
        }

        $this->auditLog(
            'materias_pendientes',
            'deleted',
            "Eliminó registro de materia pendiente '{$subjectName}' de {$studentName}",
            null
        );

        return back()->with('success', 'Materia pendiente eliminada exitosamente.');
    }
}
