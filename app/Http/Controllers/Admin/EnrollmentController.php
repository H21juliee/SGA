<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnrollmentController extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware
{
    public static function middleware(): array
    {
        return [
        new \Illuminate\Routing\Controllers\Middleware('permission:enrollments.view', only: ['index', 'show']),
        new \Illuminate\Routing\Controllers\Middleware('permission:enrollments.create', only: ['store']),
        new \Illuminate\Routing\Controllers\Middleware('permission:enrollments.edit', only: ['update']),
        new \Illuminate\Routing\Controllers\Middleware('permission:enrollments.delete', only: ['destroy']),
        new \Illuminate\Routing\Controllers\Middleware('permission:enrollments.update_status', only: ['updateStatus']),
        new \Illuminate\Routing\Controllers\Middleware('permission:enrollments.transfer', only: ['transfer']),
        ];
    }
    public function index(Request $request)
    {
        $activeYear = SchoolYear::active()->first();
        $gradeLevelId = $request->input('grade_level_id');
        $sectionId = $request->input('section_id');

        $gradeLevels = \App\Models\GradeLevel::orderBy('order_num')->get();
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

            // Estudiantes no inscritos en ese año escolar
            $enrolledStudentIds = Enrollment::where('school_year_id', $activeYear->id)->pluck('student_id');
            
            $availableStudents = Student::active()
                ->whereNotIn('id', $enrolledStudentIds)
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

        // Verificar si la seccion ya llego a su capacidad
        $section = Section::findOrFail($validated['section_id']);
        $currentEnrolled = Enrollment::where('section_id', $section->id)->count();

        if ($currentEnrolled >= $section->capacity) {
            return back()->with('error', 'La sección ya alcanzó su capacidad máxima (' . $section->capacity . ' estudiantes).');
        }

        // Crear la inscripcion
        Enrollment::create([
            'school_year_id' => $activeYear->id,
            'section_id' => $validated['section_id'],
            'student_id' => $validated['student_id'],
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        return back()->with('success', 'Estudiante matriculado exitosamente en la sección.');
    }

    public function destroy(Enrollment $enrollment)
    {
        if ($enrollment->grades()->exists() || $enrollment->attendances()->exists()) {
            return back()->with('error', 'No se puede eliminar la inscripción porque el estudiante ya tiene notas o asistencia registrada. Modifica su estado a Retirado.');
        }

        $enrollment->delete();
        return back()->with('success', 'Inscripción eliminada.');
    }

    public function updateStatus(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate([
            'status' => ['required', \Illuminate\Validation\Rule::enum(EnrollmentStatus::class)],
        ]);

        $enrollment->update(['status' => $validated['status']]);

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

        $enrollment->update(['section_id' => $newSection->id]);

        return back()->with('success', 'Estudiante transferido exitosamente a la nueva sección.');
    }
}
