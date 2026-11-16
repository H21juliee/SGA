<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Students\SaveStudentAction;
use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\Community;
use App\Models\GradeLevel;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Subject;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware
{
    use LogsActivity;

    public static function middleware(): array
    {
        return [
            new \Illuminate\Routing\Controllers\Middleware('permission:students.view', only: ['index', 'show']),
            new \Illuminate\Routing\Controllers\Middleware('permission:students.create', only: ['create', 'store']),
            new \Illuminate\Routing\Controllers\Middleware('permission:students.create|students.edit', only: ['checkCedula']),
            new \Illuminate\Routing\Controllers\Middleware('permission:students.edit', only: ['update']),
            new \Illuminate\Routing\Controllers\Middleware('permission:students.delete', only: ['destroy']),
        ];
    }

    public function checkCedula(Request $request)
    {
        $rawCedula = strtoupper(trim((string) $request->input('cedula', '')));
        $excludeId = $request->input('student_id');

        if (empty($rawCedula)) {
            return response()->json(['exists' => false]);
        }

        $query = Student::query();
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $existing = $query->where(function ($q) use ($rawCedula) {
            $q->where('cedula', $rawCedula);
            if (preg_match('/^\d{6,10}$/', $rawCedula)) {
                $q->orWhere('cedula', "V-{$rawCedula}")
                  ->orWhere('cedula', "E-{$rawCedula}");
            }
        })->first();

        if ($existing) {
            return response()->json([
                'exists' => true,
                'student' => [
                    'id' => $existing->id,
                    'full_name' => "{$existing->last_name}, {$existing->first_name}",
                    'cedula' => $existing->cedula,
                    'status' => $existing->status,
                ],
                'message' => "La cédula o documento {$existing->cedula} ya se encuentra registrada con el estudiante {$existing->last_name}, {$existing->first_name}."
            ]);
        }

        return response()->json([
            'exists' => false,
        ]);
    }

    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $sort = $request->input('sort', 'last_name');
        $direction = $request->input('direction', 'asc');
        $status_filter = $request->input('status_filter', 'regular');
        $enrollment_filter = $request->input('enrollment_filter', 'all');
        $grade_level_id = $request->input('grade_level_id', 'all');

        $activeYear = SchoolYear::active()->first();
        $user = $request->user();
        $isCoordinator = $user && $user->isYearCoordinatorOnly();
        $coordinatedLevelIds = [];

        if ($isCoordinator && $activeYear) {
            $coordinatedLevelIds = $user->coordinatedGradeLevelIds($activeYear->id);
            if ($grade_level_id === 'all' && count($coordinatedLevelIds) === 1) {
                $grade_level_id = (string) $coordinatedLevelIds[0];
            }
        }

        $allowedSorts = ['first_name', 'last_name', 'cedula', 'birth_date', 'status'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'last_name';
        }

        $query = Student::query()
            ->with([
                'guardian',
                'community',
                'enrollments' => function ($q) use ($activeYear) {
                    if ($activeYear) {
                        $q->where('school_year_id', $activeYear->id)
                          ->where('status', '!=', EnrollmentStatus::WITHDRAWN->value)
                          ->with('section.gradeLevel');
                    }
                }
            ]);

        // Restricción para Coordinador de Año: Solo estudiantes matriculados en sus años asignados
        if ($isCoordinator) {
            if (empty($coordinatedLevelIds) || !$activeYear) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('enrollments', function ($q) use ($activeYear, $coordinatedLevelIds) {
                    $q->where('school_year_id', $activeYear->id)
                      ->where('status', '!=', EnrollmentStatus::WITHDRAWN->value)
                      ->whereHas('section', function ($sq) use ($coordinatedLevelIds) {
                          $sq->whereIn('grade_level_id', $coordinatedLevelIds);
                      });
                });
            }
        }

        $students = $query
            ->when($status_filter && $status_filter !== 'all', function ($query) use ($status_filter) {
                $query->where('status', $status_filter);
            })
            // Filtro por Año de Estudio (Nivel Académico)
            ->when(!$isCoordinator && $activeYear && $grade_level_id === 'unassigned', function ($query) use ($activeYear) {
                $query->whereDoesntHave('enrollments', function ($q) use ($activeYear) {
                    $q->where('school_year_id', $activeYear->id)
                      ->where('status', '!=', EnrollmentStatus::WITHDRAWN->value);
                });
            })
            ->when($activeYear && is_numeric($grade_level_id) && (!$isCoordinator || in_array((int)$grade_level_id, $coordinatedLevelIds)), function ($query) use ($activeYear, $grade_level_id) {
                $query->whereHas('enrollments', function ($q) use ($activeYear, $grade_level_id) {
                    $q->where('school_year_id', $activeYear->id)
                      ->where('status', '!=', EnrollmentStatus::WITHDRAWN->value)
                      ->whereHas('section', function ($sq) use ($grade_level_id) {
                          $sq->where('grade_level_id', $grade_level_id);
                      });
                });
            })
            // Filtro por Estado de Matrícula (solo si no se especificó un año específico y no es coordinador)
            ->when(!$isCoordinator && $activeYear && $grade_level_id === 'all' && $enrollment_filter === 'enrolled', function ($query) use ($activeYear) {
                $query->whereHas('enrollments', function ($q) use ($activeYear) {
                    $q->where('school_year_id', $activeYear->id)
                      ->where('status', '!=', EnrollmentStatus::WITHDRAWN->value);
                });
            })
            ->when(!$isCoordinator && $activeYear && $grade_level_id === 'all' && $enrollment_filter === 'not_enrolled', function ($query) use ($activeYear) {
                $query->whereDoesntHave('enrollments', function ($q) use ($activeYear) {
                    $q->where('school_year_id', $activeYear->id)
                      ->where('status', '!=', EnrollmentStatus::WITHDRAWN->value);
                });
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('cedula', 'like', "%{$search}%")
                      ->orWhere('birth_date', 'like', "%{$search}%");
                });
            })
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        $gradeLevelsQuery = GradeLevel::orderBy('order_num');
        if ($isCoordinator) {
            $gradeLevelsQuery->whereIn('id', $coordinatedLevelIds);
        }
        $gradeLevels = $gradeLevelsQuery->get();

        $communities = Community::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('Admin/Students/Index', [
            'students'       => $students,
            'communities'    => $communities,
            'activeYear'     => $activeYear,
            'gradeLevels'    => $gradeLevels,
            'isCoordinator'  => $isCoordinator,
            'filters'        => [
                'search'            => $search,
                'sort'              => $sort,
                'direction'         => $direction,
                'status_filter'     => $status_filter,
                'enrollment_filter' => $enrollment_filter,
                'grade_level_id'    => $grade_level_id,
            ],
        ]);
    }

    public function create(): Response
    {
        $communities = Community::where('is_active', true)->orderBy('name')->get();
        $gradeLevels = GradeLevel::orderBy('order_num')->get();

        return Inertia::render('Admin/Students/Create', [
            'communities' => $communities,
            'gradeLevels' => $gradeLevels,
        ]);
    }

    public function show(Student $student): Response
    {
        $user = request()->user();
        if ($user && $user->isYearCoordinatorOnly()) {
            $activeYear = SchoolYear::active()->first();
            $coordinatedLevelIds = $user->coordinatedGradeLevelIds($activeYear?->id);

            $isEnrolledInCoordinatedLevel = $student->enrollments()
                ->where('school_year_id', $activeYear?->id)
                ->where('status', '!=', EnrollmentStatus::WITHDRAWN->value)
                ->whereHas('section', function ($sq) use ($coordinatedLevelIds) {
                    $sq->whereIn('grade_level_id', $coordinatedLevelIds);
                })
                ->exists();

            if (!$isEnrolledInCoordinatedLevel) {
                abort(403, 'No tiene autorización para visualizar el expediente de este estudiante.');
            }
        }

        $student->load([
            'guardian',
            'community',
            'profile',
            'targetGradeLevel',
            'subjectDebts.subject.gradeLevel',
            'subjectDebts.originSchoolYear',
        ]);

        $enrollments = $student->enrollments()
            ->with([
                'schoolYear',
                'section.gradeLevel',
                'grades.subject',
                'grades.lapse',
                'attendances',
                'revisionGrades.subject',
            ])
            ->orderByDesc('school_year_id')
            ->get();

        $allSubjects = Subject::with('gradeLevel')
            ->orderBy('grade_level_id')
            ->orderBy('name')
            ->get();

        $schoolYears = SchoolYear::orderByDesc('start_date')->get();
        $communities = Community::where('is_active', true)->orderBy('name')->get();
        $gradeLevels = GradeLevel::orderBy('order_num')->get();

        return Inertia::render('Admin/Students/Show', [
            'student'      => $student,
            'communities'  => $communities,
            'gradeLevels'  => $gradeLevels,
            'enrollments'  => $enrollments,
            'subjectDebts' => $student->subjectDebts,
            'allSubjects'  => $allSubjects,
            'schoolYears'  => $schoolYears,
        ]);
    }

    public function store(StoreStudentRequest $request, SaveStudentAction $saveStudentAction)
    {
        $student = $saveStudentAction->execute($request->validated());

        $this->auditLog('estudiantes', 'created', "Creó al estudiante {$student->full_name}", $student);

        return redirect()->route('admin.students.show', $student->id)
            ->with('success', 'Estudiante registrado exitosamente con su ficha socioeducativa.');
    }

    public function update(UpdateStudentRequest $request, Student $student, SaveStudentAction $saveStudentAction)
    {
        $user = $request->user();
        if ($user && $user->isYearCoordinatorOnly()) {
            $activeYear = SchoolYear::active()->first();
            $coordinatedLevelIds = $user->coordinatedGradeLevelIds($activeYear?->id);

            $isEnrolledInCoordinatedLevel = $student->enrollments()
                ->where('school_year_id', $activeYear?->id)
                ->where('status', '!=', EnrollmentStatus::WITHDRAWN->value)
                ->whereHas('section', function ($sq) use ($coordinatedLevelIds) {
                    $sq->whereIn('grade_level_id', $coordinatedLevelIds);
                })
                ->exists();

            if (!$isEnrolledInCoordinatedLevel) {
                abort(403, 'No tiene autorización para editar el expediente de este estudiante.');
            }
        }

        $before = $student->load('profile')->toArray();
        $updatedStudent = $saveStudentAction->execute($request->validated(), $student);
        $diff = $this->diffProperties($before, $updatedStudent->toArray());

        $this->auditLog('estudiantes', 'updated', "Editó al estudiante {$student->full_name}", $student, $diff);

        return back()->with('success', 'Estudiante actualizado exitosamente.');
    }

    public function destroy(Student $student)
    {
        $student->update(['status' => StudentStatus::WITHDRAWN]);

        $this->auditLog('estudiantes', 'deleted', "Retiró al estudiante {$student->full_name}", $student);

        return back()->with('success', 'Estudiante retirado.');
    }
}