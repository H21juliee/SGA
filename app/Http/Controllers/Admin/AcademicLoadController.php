<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicLoad;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Models\GradeLevel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AcademicLoadController extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware
{
    public static function middleware(): array
    {
        return [
        new \Illuminate\Routing\Controllers\Middleware('permission:academic_load.view', only: ['index']),
        new \Illuminate\Routing\Controllers\Middleware('permission:academic_load.manage', only: ['store', 'update', 'destroy']),
        new \Illuminate\Routing\Controllers\Middleware('permission:academic_load.assign', only: ['assign']),
        ];
    }
    public function index(Request $request)
    {
        $schoolYearId = $request->input('school_year_id', SchoolYear::active()->first()?->id);
        
        $years = SchoolYear::orderByDesc('start_date')->get();
        $teachers = User::role('Docente')->where('is_active', true)->orderBy('name')->get()->map(function($t) {
            return ['id' => $t->id, 'name' => $t->name];
        });
        
        $tree = [];

        if ($schoolYearId) {
            $gradeLevels = GradeLevel::orderBy('order_num')->get();
            $sections = Section::where('school_year_id', $schoolYearId)->get()->groupBy('grade_level_id');
            $subjects = Subject::all()->groupBy('grade_level_id');
            $loads = AcademicLoad::where('school_year_id', $schoolYearId)->get();

            foreach ($gradeLevels as $grade) {
                $gradeSections = $sections->get($grade->id, collect());
                
                if ($gradeSections->isEmpty()) {
                    continue; // Skip grade levels without sections in this year
                }
                
                $gradeSubjects = $subjects->get($grade->id, collect());
                
                $gradeData = [
                    'id' => $grade->id,
                    'name' => $grade->name,
                    'sections' => []
                ];
                
                foreach ($gradeSections as $section) {
                    $sectionData = [
                        'id' => $section->id,
                        'name' => $section->name, // Solo "A", "B", etc.
                        'subjects' => []
                    ];
                    
                    foreach ($gradeSubjects as $subject) {
                        $load = $loads->where('section_id', $section->id)
                                      ->where('subject_id', $subject->id)
                                      ->first();
                                      
                        $sectionData['subjects'][] = [
                            'id' => $subject->id,
                            'name' => $subject->name,
                            'teacher_id' => $load ? $load->teacher_id : null,
                            'load_id' => $load ? $load->id : null
                        ];
                    }
                    
                    $gradeData['sections'][] = $sectionData;
                }
                
                // Sort sections alphabetically (A, B, C...)
                usort($gradeData['sections'], function($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });
                
                $tree[] = $gradeData;
            }
        }

        return Inertia::render('Admin/AcademicLoads/Index', [
            'tree' => $tree,
            'years' => $years,
            'teachers' => $teachers,
            'filters' => [
                'school_year_id' => $schoolYearId,
            ],
        ]);
    }

    public function assign(Request $request)
    {
        $validated = $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'nullable|exists:users,id',
        ]);

        if (empty($validated['teacher_id'])) {
            AcademicLoad::where('school_year_id', $validated['school_year_id'])
                ->where('section_id', $validated['section_id'])
                ->where('subject_id', $validated['subject_id'])
                ->delete();
            return back()->with('success', 'Docente removido de la materia.');
        }

        AcademicLoad::updateOrCreate(
            [
                'school_year_id' => $validated['school_year_id'],
                'section_id' => $validated['section_id'],
                'subject_id' => $validated['subject_id'],
            ],
            [
                'teacher_id' => $validated['teacher_id'],
            ]
        );

        return back()->with('success', 'Docente asignado a la materia.');
    }

    public function store(Request $request) { return back(); }
    public function destroy(AcademicLoad $academicLoad) { return back(); }
}