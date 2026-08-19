<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GradeLevel;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $sort = $request->input('sort', 'grade_level_id');
        $direction = $request->input('direction', 'asc');
        
        $allowedSorts = ['code', 'name', 'grade_level_id', 'is_active'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'grade_level_id';
        }

        $subjects = Subject::with('gradeLevel')
            ->withCount(['academicLoads', 'grades'])
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhereHas('gradeLevel', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
            })
            ->orderBy($sort, $direction)
            ->get();

        return Inertia::render('Admin/Subjects/Index', [
            'subjects' => $subjects,
            'levels' => GradeLevel::orderBy('order_num')->get(),
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'direction' => $direction,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'grade_level_id' => 'required|exists:grade_levels,id',
            'name'           => 'required|string|max:100',
            'code'           => 'required|string|max:20|unique:subjects,code',
            'weight'         => 'integer|min:1|max:10',
            'grading_type'   => 'in:numeric,qualitative',
        ]);

        // Defaults
        $validated['weight']       = $validated['weight'] ?? 10;
        $validated['grading_type'] = $validated['grading_type'] ?? 'numeric';

        Subject::create($validated);

        return back()->with('success', 'Materia creada exitosamente.');
    }

    public function update(Request $request, Subject $subject)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:100',
            'code'         => 'required|string|max:20|unique:subjects,code,' . $subject->id,
            'weight'       => 'integer|min:1|max:10',
            'grading_type' => 'in:numeric,qualitative',
        ]);

        $validated['weight']       = $validated['weight'] ?? $subject->weight ?? 10;
        $validated['grading_type'] = $validated['grading_type'] ?? $subject->grading_type ?? 'numeric';

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
