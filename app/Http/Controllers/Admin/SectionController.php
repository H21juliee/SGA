<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GradeLevel;
use App\Models\GradeLevelCoordinator;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SectionController extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware
{
    use LogsActivity;
    public static function middleware(): array
    {
        return [
            new \Illuminate\Routing\Controllers\Middleware('permission:sections.view', only: ['index', 'show']),
            new \Illuminate\Routing\Controllers\Middleware('permission:sections.manage', only: ['store', 'update', 'destroy', 'assignCoordinator']),
        ];
    }
    public function index(Request $request)
    {
        $activeYear = SchoolYear::active()->first();
        $schoolYearId = $request->input('school_year_id', $activeYear?->id);
        $gradeLevelId = $request->input('grade_level_id');
        
        $years = SchoolYear::orderByDesc('start_date')->get();
        $user = $request->user();
        $isCoordinator = $user && $user->isYearCoordinatorOnly();
        $coordinatedLevelIds = [];

        if ($isCoordinator) {
            $coordinatedLevelIds = $user->coordinatedGradeLevelIds($schoolYearId ? (int) $schoolYearId : null);
        }

        $levelsQuery = GradeLevel::orderBy('order_num')->with([
            'coordinators' => function ($q) use ($schoolYearId) {
                if ($schoolYearId) {
                    $q->where('school_year_id', $schoolYearId)->with('user');
                }
            }
        ]);

        if ($isCoordinator) {
            $levelsQuery->whereIn('id', $coordinatedLevelIds);
        }

        $levels = $levelsQuery->get()->map(function ($lvl) {
            $coordRecord = $lvl->coordinators->first();
            $lvl->current_coordinator = $coordRecord?->user;
            return $lvl;
        });

        $teachers = User::where('is_active', true)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['Coordinador de Año', 'Docente', 'Administrador', 'SuperAdmin']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        
        $sections = [];
        if ($schoolYearId) {
            $query = Section::where('school_year_id', $schoolYearId)->with('gradeLevel');
            
            if ($isCoordinator) {
                $query->whereIn('grade_level_id', $coordinatedLevelIds);
            }

            if ($gradeLevelId) {
                $query->where('grade_level_id', $gradeLevelId);
            }
            
            $sections = $query->withCount(['enrollments', 'academicLoads'])->orderBy('grade_level_id')->orderBy('name')->get();
        }

        return Inertia::render('Admin/Sections/Index', [
            'sections' => $sections,
            'years' => $years,
            'levels' => $levels,
            'teachers' => $teachers,
            'activeYear' => $activeYear,
            'filters' => [
                'school_year_id' => $schoolYearId,
                'grade_level_id' => $gradeLevelId,
            ],
            'isCoordinator' => $isCoordinator,
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

        $section = Section::create($validated);

        $this->auditLog('secciones', 'created', "Creó la sección {$section->name}", $section);

        return back()->with('success', 'Sección creada exitosamente.');
    }

    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:5',
            'capacity' => 'required|integer|min:1|max:100',
        ]);

        $before = $section->only(['name', 'capacity']);
        $section->update($validated);
        $diff = $this->diffProperties($before, $validated);

        $this->auditLog('secciones', 'updated', "Editó la sección {$section->name}", $section, $diff);

        return back()->with('success', 'Sección actualizada.');
    }

    public function destroy(Section $section)
    {
        if ($section->enrollments()->exists() || $section->academicLoads()->exists()) {
            return back()->with('error', 'No se puede eliminar la sección porque tiene estudiantes inscritos o carga asignada.');
        }

        $name = $section->name;
        $section->delete();

        $this->auditLog('secciones', 'deleted', "Eliminó la sección {$name}");

        return back()->with('success', 'Sección eliminada.');
    }

    public function assignCoordinator(Request $request, GradeLevel $gradeLevel)
    {
        $validated = $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $schoolYearId = (int) $validated['school_year_id'];
        $userId = !empty($validated['user_id']) ? (int) $validated['user_id'] : null;

        if ($userId === null) {
            GradeLevelCoordinator::where('grade_level_id', $gradeLevel->id)
                ->where('school_year_id', $schoolYearId)
                ->delete();

            $this->auditLog('anos_escolares', 'updated', "Removió el coordinador asignado a {$gradeLevel->name}");

            return back()->with('success', "Se removió el coordinador de {$gradeLevel->name}.");
        }

        GradeLevelCoordinator::updateOrCreate(
            [
                'grade_level_id' => $gradeLevel->id,
                'school_year_id' => $schoolYearId,
            ],
            [
                'user_id' => $userId,
            ]
        );

        $user = User::find($userId);
        $this->auditLog('anos_escolares', 'updated', "Asignó a {$user?->name} como coordinador de {$gradeLevel->name}");

        return back()->with('success', "Coordinador asignado a {$gradeLevel->name} exitosamente.");
    }
}
