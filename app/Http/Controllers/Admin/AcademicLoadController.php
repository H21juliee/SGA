<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicLoad;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AcademicLoadController extends Controller
{
    public function index(Request $request)
    {
        $schoolYearId = $request->input('school_year_id', SchoolYear::active()->first()?->id);
        $gradeLevelId = $request->input('grade_level_id');
        $sectionId = $request->input('section_id');
        
        $years = SchoolYear::orderByDesc('start_date')->get();
        $levels = \App\Models\GradeLevel::orderBy('order_num')->get();
        $teachers = User::role('Docente')->where('is_active', true)->orderBy('name')->get();
        
        $loads = [];
        $sections = [];
        $subjects = [];

        if ($schoolYearId) {
            $query = AcademicLoad::where('school_year_id', $schoolYearId)
                ->with(['teacher', 'subject.gradeLevel', 'section.gradeLevel']);
            
            if ($gradeLevelId) {
                $query->whereHas('section', function($q) use ($gradeLevelId) {
                    $q->where('grade_level_id', $gradeLevelId);
                });
            }

            if ($sectionId) {
                $query->where('section_id', $sectionId);
            }

            $loads = $query->get();
                
            $sections = Section::where('school_year_id', $schoolYearId)->with('gradeLevel')->get();
            $subjects = Subject::with('gradeLevel')->get();
        }

        return Inertia::render('Admin/AcademicLoads/Index', [
            'loads' => $loads,
            'years' => $years,
            'levels' => $levels,
            'teachers' => $teachers,
            'sections' => $sections,
            'subjects' => $subjects,
            'filters' => [
                'school_year_id' => $schoolYearId,
                'grade_level_id' => $gradeLevelId,
                'section_id' => $sectionId,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'teacher_id' => 'required|exists:users,id',
            'subject_id' => 'required|exists:subjects,id',
            'section_id' => 'required|exists:sections,id',
        ]);

        // Evitar duplicados (un mismo profe no puede dar la misma materia en la misma seccion dos veces)
        $exists = AcademicLoad::where('school_year_id', $validated['school_year_id'])
            ->where('subject_id', $validated['subject_id'])
            ->where('section_id', $validated['section_id'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'Esta materia ya fue asignada en esta sección para este año escolar.');
        }

        AcademicLoad::create($validated);

        return back()->with('success', 'Carga académica asignada exitosamente.');
    }

    public function destroy(AcademicLoad $academicLoad)
    {
        $academicLoad->delete();
        return back()->with('success', 'Asignación removida.');
    }
}
