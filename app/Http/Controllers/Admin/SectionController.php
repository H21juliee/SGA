<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GradeLevel;
use App\Models\SchoolYear;
use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SectionController extends Controller
{
    public function index(Request $request)
    {
        $activeYear = SchoolYear::active()->first();
        $schoolYearId = $request->input('school_year_id', $activeYear?->id);
        $gradeLevelId = $request->input('grade_level_id');
        
        $years = SchoolYear::orderByDesc('start_date')->get();
        $levels = GradeLevel::orderBy('order_num')->get();
        
        $sections = [];
        if ($schoolYearId) {
            $query = Section::where('school_year_id', $schoolYearId)->with('gradeLevel');
            
            if ($gradeLevelId) {
                $query->where('grade_level_id', $gradeLevelId);
            }
            
            $sections = $query->orderBy('grade_level_id')->orderBy('name')->get();
        }

        return Inertia::render('Admin/Sections/Index', [
            'sections' => $sections,
            'years' => $years,
            'levels' => $levels,
            'activeYear' => $activeYear,
            'filters' => [
                'school_year_id' => $schoolYearId,
                'grade_level_id' => $gradeLevelId,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'grade_level_id' => 'required|exists:grade_levels,id',
            'name' => 'required|string|max:5',
            'capacity' => 'required|integer|min:1|max:100',
        ]);

        Section::create($validated);

        return back()->with('success', 'Sección creada exitosamente.');
    }

    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:5',
            'capacity' => 'required|integer|min:1|max:100',
        ]);

        $section->update($validated);

        return back()->with('success', 'Sección actualizada.');
    }

    public function destroy(Section $section)
    {
        if ($section->enrollments()->exists() || $section->academicLoads()->exists()) {
            return back()->with('error', 'No se puede eliminar la sección porque tiene estudiantes inscritos o carga asignada.');
        }

        $section->delete();
        return back()->with('success', 'Sección eliminada.');
    }
}
