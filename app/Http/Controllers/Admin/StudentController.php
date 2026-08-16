<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StudentStatus;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $sort = $request->input('sort', 'last_name');
        $direction = $request->input('direction', 'asc');

        $allowedSorts = ['first_name', 'last_name', 'cedula', 'birth_date', 'status'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'last_name';
        }

        $students = Student::query()
            ->when($search, function ($query, $search) {
                $query->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('cedula', 'like', "%{$search}%");
            })
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Students/Index', [
            'students' => $students,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ],
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
            'status' => ['required', Rule::enum(StudentStatus::class)],
        ]);

        $student->update($validated);

        return back()->with('success', 'Estudiante actualizado exitosamente.');
    }

    public function destroy(Student $student)
    {
        $student->update(['status' => StudentStatus::WITHDRAWN]);

        return back()->with('success', 'Estudiante retirado.');
    }
}
