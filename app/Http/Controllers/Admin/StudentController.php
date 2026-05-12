<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $students = Student::query()
            ->when($search, function ($query, $search) {
                $query->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('cedula', 'like', "%{$search}%");
            })
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Students/Index', [
            'students' => $students,
            'filters' => ['search' => $search],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'cedula' => 'nullable|string|max:20|unique:students,cedula',
            'birth_date' => 'required|date',
            'gender' => 'required|in:M,F',
        ]);

        Student::create($validated);

        return back()->with('success', 'Estudiante creado exitosamente.');
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'cedula' => 'nullable|string|max:20|unique:students,cedula,' . $student->id,
            'birth_date' => 'required|date',
            'gender' => 'required|in:M,F',
            'is_active' => 'boolean',
        ]);

        $student->update($validated);

        return back()->with('success', 'Estudiante actualizado exitosamente.');
    }

    public function destroy(Student $student)
    {
        // Solo soft delete o desactivación si tiene notas
        if ($student->enrollments()->exists()) {
            $student->update(['is_active' => false]);
            return back()->with('success', 'El estudiante ha sido desactivado (no se puede borrar porque tiene historial).');
        }

        $student->delete();
        return back()->with('success', 'Estudiante eliminado exitosamente.');
    }
}
