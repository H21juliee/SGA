<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EnrollmentController extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware
{
    use LogsActivity;
    public static function middleware(): array
    {
        return [
        new \Illuminate\Routing\Controllers\Middleware('permission:enrollments.view', only: ['index', 'show']),
        new \Illuminate\Routing\Controllers\Middleware('permission:enrollments.create', only: ['store']),
        new \Illuminate\Routing\Controllers\Middleware('permission:enrollments.edit', only: ['update']),
        new \Illuminate\Routing\Controllers\Middleware('permission:enrollments.delete', only: ['destroy']),
        new \Illuminate\Routing\Controllers\Middleware('permission:enrollments.update_status', only: ['updateStatus']),
        new \Illuminate\Routing\Controllers\Middleware('permission:enrollments.transfer', only: ['transfer']),
        new \Illuminate\Routing\Controllers\Middleware('permission:students.create|students.edit', only: ['assignGuardian']),
        ];
    }
    public function index(Request $request)
    {
        $activeYear = SchoolYear::active()->first();
        $user = $request->user();
        $isCoordinator = $user && $user->isYearCoordinatorOnly();

        $coordinatedGradeLevelIds = [];
        if ($isCoordinator && $activeYear) {
            $coordinatedGradeLevelIds = $user->coordinatedGradeLevelIds($activeYear->id);
        }

        $gradeLevelsQuery = \App\Models\GradeLevel::orderBy('order_num');
        if ($isCoordinator) {
            $gradeLevelsQuery->whereIn('id', $coordinatedGradeLevelIds);
        }
        $gradeLevels = $gradeLevelsQuery->get();

        $gradeLevelId = $request->input('grade_level_id');
        if ($isCoordinator && count($coordinatedGradeLevelIds) > 0) {
            if (!$gradeLevelId || !in_array((int)$gradeLevelId, $coordinatedGradeLevelIds)) {
                $gradeLevelId = (string) $coordinatedGradeLevelIds[0];
            }
        }

        $sectionId = $request->input('section_id');
        $sections = [];
        $enrollments = [];
        $availableStudents = [];

        if ($activeYear && $gradeLevelId) {
            $sections = Section::where('school_year_id', $activeYear->id)
                ->where('grade_level_id', $gradeLevelId)
                ->get();
        }

        if ($activeYear && $sectionId) {
            $enrollments = Enrollment::where('school_year_id', $activeYear->id)
                ->where('section_id', $sectionId)
                ->with('student')
                ->get();
        }

        if ($activeYear) {
            // Estudiantes disponibles: no inscritos en el año activo o cuya inscripción previa esté retirada
            $activeEnrolledStudentIds = Enrollment::where('school_year_id', $activeYear->id)
                ->where('status', '!=', EnrollmentStatus::WITHDRAWN->value)
                ->pluck('student_id');
            
            $availableStudents = Student::active()
                ->with(['guardian', 'community'])
                ->whereNotIn('id', $activeEnrolledStudentIds)
                ->orderBy('last_name')
                ->get();
        }

        return Inertia::render('Admin/Enrollments/Index', [
            'activeYear' => $activeYear,
            'gradeLevels' => $gradeLevels,
            'sections' => $sections,
            'enrollments' => $enrollments,
            'availableStudents' => $availableStudents,
            'filters' => [
                'grade_level_id' => $gradeLevelId,
                'section_id' => $sectionId,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'student_id' => 'required|exists:students,id',
        ]);

        $activeYear = SchoolYear::active()->first();
        if (!$activeYear) {
            return back()->with('error', 'No hay un año escolar activo.');
        }

        $user = $request->user();
        if ($user && $user->isYearCoordinatorOnly()) {
            $section = Section::find($validated['section_id']);
            $isAssigned = in_array((int) $section->grade_level_id, $user->coordinatedGradeLevelIds($activeYear->id));

            if (!$isAssigned) {
                return back()->with('error', 'No tiene permisos de coordinación para inscribir en este nivel académico.');
            }
        }

        /*
         * Control de concurrencia del cupo:
         * Se bloquea la fila de la sección (SELECT ... FOR UPDATE, soportado por MySQL/InnoDB y
         * PostgreSQL) durante la transacción. Una inscripción simultánea a la misma sección espera
         * a que esta termine y recién entonces cuenta, evitando superar la capacidad.
         */
        $result = DB::transaction(function () use ($validated, $activeYear) {
            $section = Section::lockForUpdate()->findOrFail($validated['section_id']);

            // La sección debe pertenecer al año escolar activo (evita inscripciones incoherentes)
            if ((int) $section->school_year_id !== (int) $activeYear->id) {
                return ['error' => 'La sección seleccionada no pertenece al año escolar activo.'];
            }

            // Se bloquea también al alumno: evita que dos inscripciones simultáneas del mismo
            // alumno lleguen a la restricción única (student_id, school_year_id) y den error 500.
            $student = Student::lockForUpdate()->findOrFail($validated['student_id']);

            if ($student->status !== StudentStatus::REGULAR) {
                return ['error' => 'El estudiante no está activo y no puede ser inscrito.'];
            }

            // Verificar si ya tiene inscripción en el año activo
            $existingEnrollment = Enrollment::where('student_id', $student->id)
                ->where('school_year_id', $activeYear->id)
                ->first();

            if ($existingEnrollment && $existingEnrollment->status !== EnrollmentStatus::WITHDRAWN) {
                return ['error' => 'El estudiante ya cuenta con una inscripción activa en este año escolar.'];
            }

            // Los estudiantes retirados liberan cupo
            $currentEnrolled = Enrollment::where('section_id', $section->id)
                ->where('status', '!=', EnrollmentStatus::WITHDRAWN->value)
                ->count();

            if ($currentEnrolled >= $section->capacity) {
                return ['error' => 'La sección ya alcanzó su capacidad máxima (' . $section->capacity . ' estudiantes).'];
            }

            // Si ya existía una inscripción retirada, se reactiva en la sección seleccionada (respeta restricción única uq_enrollment_student_year)
            if ($existingEnrollment) {
                $existingEnrollment->update([
                    'section_id'  => $section->id,
                    'status'      => EnrollmentStatus::ACTIVE,
                    'enrolled_at' => now(),
                ]);
                $enrollment = $existingEnrollment;
                $isReactivation = true;
            } else {
                $enrollment = Enrollment::create([
                    'school_year_id' => $activeYear->id,
                    'section_id'     => $section->id,
                    'student_id'     => $student->id,
                    'status'         => EnrollmentStatus::ACTIVE,
                    'enrolled_at'    => now(),
                ]);
                $isReactivation = false;
            }

            return [
                'section' => $section,
                'enrollment' => $enrollment,
                'student' => $student,
                'is_reactivation' => $isReactivation,
            ];
        });

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        $section = $result['section'];
        $enrollment = $result['enrollment'];
        $student = $result['student'];
        $isReactivation = $result['is_reactivation'];

        $actionDescription = $isReactivation
            ? "Reinscribió al estudiante {$student->full_name} en la sección {$section->name} (previamente retirado)"
            : "Matriculó al estudiante {$student->full_name} en la sección {$section->name}";

        $this->auditLog('inscripciones', $isReactivation ? 'updated' : 'created',
            $actionDescription,
            $enrollment
        );

        $successMessage = $isReactivation
            ? 'Estudiante reinscrito exitosamente en la sección.'
            : 'Estudiante matriculado exitosamente en la sección.';

        return back()->with('success', $successMessage);
    }



    /**
     * Asigna (o cambia) el representante de un alumno existente desde el asistente de inscripción.
     */
    public function assignGuardian(Request $request, Student $student)
    {
        $validated = $request->validate([
            'guardian_id' => 'required|exists:guardians,id',
        ], [
            'guardian_id.required' => 'Debe seleccionar un representante.',
            'guardian_id.exists'   => 'El representante seleccionado no existe.',
        ]);

        $student->update(['guardian_id' => $validated['guardian_id']]);

        $this->auditLog('estudiantes', 'updated', "Asignó representante al estudiante {$student->full_name} (asistente de inscripción)", $student);

        return response()->json(['student' => $student->fresh()->load('guardian')]);
    }

    public function destroy(Enrollment $enrollment)
    {
        if ($enrollment->grades()->exists() || $enrollment->attendances()->exists()) {
            return back()->with('error', 'No se puede eliminar la inscripción porque el estudiante ya tiene notas o asistencia registrada. Modifica su estado a Retirado.');
        }

        $enrollment->load('student');
        $enrollment->delete();

        $this->auditLog('inscripciones', 'deleted',
            "Eliminó la inscripción de {$enrollment->student->full_name}",
            $enrollment
        );

        return back()->with('success', 'Inscripción eliminada.');
    }

    public function updateStatus(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'status' => ['required', \Illuminate\Validation\Rule::enum(EnrollmentStatus::class)],
        ]);

        $oldStatus = $enrollment->status->value;
        $enrollment->load('student');
        $enrollment->update(['status' => $validated['status']]);

        $this->auditLog('inscripciones', 'updated',
            "Cambió estado de la inscripción de {$enrollment->student->full_name}: {$oldStatus} → {$validated['status']}",
            $enrollment,
            ['old' => ['status' => $oldStatus], 'new' => ['status' => $validated['status']]]
        );

        return back()->with('success', 'Estado de la inscripción actualizado.');
    }

    public function transfer(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'section_id' => 'required|exists:sections,id',
        ]);

        $newSection = Section::findOrFail($validated['section_id']);

        if ($newSection->school_year_id !== $enrollment->school_year_id) {
            return back()->with('error', 'La nueva sección debe pertenecer al mismo año escolar.');
        }

        // $currentEnrolled = Enrollment::where('section_id', $newSection->id)->count();
        // if ($currentEnrolled >= $newSection->capacity) {
        //     return back()->with('error', 'La sección destino ya alcanzó su capacidad máxima.');
        // }

        $oldSection = $enrollment->section->name ?? '—';
        $enrollment->update(['section_id' => $newSection->id]);
        $enrollment->load('student');

        $this->auditLog('inscripciones', 'updated',
            "Transfirió a {$enrollment->student->full_name} de sección {$oldSection} → {$newSection->name}",
            $enrollment,
            ['old' => ['section_id' => $oldSection], 'new' => ['section_id' => $newSection->name]]
        );

        return back()->with('success', 'Estudiante transferido exitosamente a la nueva sección.');
    }
}
