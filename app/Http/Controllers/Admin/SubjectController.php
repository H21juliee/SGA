<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GradeLevel;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::with('gradeLevel')
            ->orderBy('grade_level_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Subjects/Index', [
            'subjects' => $subjects,
            'levels' => GradeLevel::orderBy('order_num')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'grade_level_id' => 'required|exists:grade_levels,id',
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:subjects,code',
            'weight' => 'required|integer|min:1|max:10',
        ]);

        Subject::create($validated);

        return back()->with('success', 'Materia creada exitosamente.');
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:subjects,code,' . $subject->id,
            'weight' => 'required|integer|min:1|max:10',
        ]);

        $subject->update($validated);

        return back()->with('success', 'Materia actualizada.');
    }

    public function destroy(Subject $subject)
    {
        if ($subject->academicLoads()->exists() || $subject->grades()->exists()) {
            return back()->with('error', 'No se puede eliminar la materia porque ya está en uso en cargas o notas.');
        }

        $subject->delete();
        return back()->with('success', 'Materia eliminada.');
    }
}
