<?php

namespace App\Http\Controllers;

use App\Models\AcademicLoad;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectDebt;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $activeYear = SchoolYear::active()->first();
        $roles = $user ? $user->getRoleNames()->toArray() : [];

        $isSuperAdmin = in_array('SuperAdmin', $roles);
        $isAdmin = in_array('Administrador', $roles);
        $isDocente = in_array('Docente', $roles);
        $isSecretaria = in_array('Secretaria', $roles);

        $stats = [
            'school_year' => $activeYear?->name ?? '—',
            'open_lapses' => $activeYear ? $activeYear->lapses()->where('is_open', true)->count() : 0,
        ];

        $gradeProgress = [];
        $todayAttendance = [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0,
            'total' => 0,
        ];
        $recentActivity = [];
        $teacherLoads = [];
        $teacherStats = [];
        $secretaryStats = [];

        // -------------------------------------------------------------------
        // SUPERADMIN / ADMINISTRADOR
        // -------------------------------------------------------------------
        if ($isSuperAdmin || $isAdmin) {
            $stats['total_students'] = Student::active()->count();
            $stats['total_enrollments'] = $activeYear ? Enrollment::forSchoolYear($activeYear->id)->active()->count() : 0;

            if ($activeYear) {
                // Cálculo de notas esperadas por lapso: suma de (alumnos inscritos activos en cada sección * materias de ese grado)
                $totalExpectedGradesPerLapse = 0;
                $sections = Section::where('school_year_id', $activeYear->id)->get();
                foreach ($sections as $section) {
                    $enrolledCount = Enrollment::where('section_id', $section->id)->active()->count();
                    $subjectsCount = Subject::where('grade_level_id', $section->grade_level_id)->count();
                    $totalExpectedGradesPerLapse += ($enrolledCount * $subjectsCount);
                }

                $lapses = $activeYear->lapses()->orderBy('id')->get();
                foreach ($lapses as $lapse) {
                    $loaded = Grade::where('lapse_id', $lapse->id)->whereNotNull('score')->count();
                    $gradeProgress[] = [
                        'id'         => $lapse->id,
                        'name'       => $lapse->name,
                        'is_open'    => (bool) $lapse->is_open,
                        'loaded'     => $loaded,
                        'expected'   => $totalExpectedGradesPerLapse,
                        'percentage' => $totalExpectedGradesPerLapse > 0 ? round(($loaded / $totalExpectedGradesPerLapse) * 100, 1) : 0,
                    ];
                }
            }

            // Asistencia de hoy
            $today = now()->toDateString();
            $attCounts = Attendance::where('date', $today)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $todayAttendance = [
                'present' => (int) ($attCounts['present'] ?? 0),
                'absent'  => (int) ($attCounts['absent'] ?? 0),
                'late'    => (int) ($attCounts['late'] ?? 0),
                'excused' => (int) ($attCounts['excused'] ?? 0),
                'total'   => array_sum($attCounts),
            ];

            // Última actividad de auditoría
            $recentActivity = ActivityLog::with('user:id,name')
                ->latest('created_at')
                ->limit(6)
                ->get(['id', 'user_id', 'module', 'action', 'description', 'created_at'])
                ->map(function ($log) {
                    return [
                        'id'          => $log->id,
                        'user_name'   => $log->user?->name ?? 'Sistema',
                        'module'      => $log->module,
                        'action'      => $log->action,
                        'description' => $log->description,
                        'time_ago'    => $log->created_at ? $log->created_at->diffForHumans() : '',
                    ];
                });
        }
        // -------------------------------------------------------------------
        // DOCENTE
        // -------------------------------------------------------------------
        elseif ($isDocente) {
            $openLapse = $activeYear ? $activeYear->lapses()->where('is_open', true)->first() : null;

            if ($activeYear) {
                $loads = AcademicLoad::where('teacher_id', $user->id)
                    ->where('school_year_id', $activeYear->id)
                    ->with(['subject:id,name,grading_type', 'section.gradeLevel:id,name'])
                    ->get();

                foreach ($loads as $load) {
                    $enrollmentIds = Enrollment::where('section_id', $load->section_id)
                        ->active()
                        ->pluck('id');
                    $studentsCount = $enrollmentIds->count();

                    $gradesLoaded = 0;
                    if ($openLapse && $studentsCount > 0) {
                        $gradesLoaded = Grade::where('subject_id', $load->subject_id)
                            ->where('lapse_id', $openLapse->id)
                            ->whereIn('enrollment_id', $enrollmentIds)
                            ->whereNotNull('score')
                            ->count();
                    }

                    $teacherLoads[] = [
                        'id'             => $load->id,
                        'subject_id'     => $load->subject_id,
                        'subject_name'   => $load->subject?->name ?? 'Materia',
                        'grading_type'   => $load->subject?->grading_type ?? 'numeric',
                        'section_id'     => $load->section_id,
                        'section_name'   => $load->section?->name ?? '',
                        'grade_level'    => $load->section?->gradeLevel?->name ?? '',
                        'students_count' => $studentsCount,
                        'grades_loaded'  => $gradesLoaded,
                        'percentage'     => $studentsCount > 0 ? round(($gradesLoaded / $studentsCount) * 100, 1) : 0,
                        'open_lapse_id'  => $openLapse?->id,
                    ];
                }

                $teacherStats = [
                    'total_sections'       => count(array_unique(array_column($teacherLoads, 'section_id'))),
                    'total_subjects'       => count(array_unique(array_column($teacherLoads, 'subject_id'))),
                    'total_students_reach' => array_sum(array_column($teacherLoads, 'students_count')),
                    'open_lapse_name'      => $openLapse?->name ?? 'Ninguno activo',
                ];
            }
        }
        // -------------------------------------------------------------------
        // SECRETARIA / OTROS
        // -------------------------------------------------------------------
        else {
            $stats['total_students'] = Student::active()->count();
            $stats['total_enrollments'] = $activeYear ? Enrollment::forSchoolYear($activeYear->id)->active()->count() : 0;
            $secretaryStats = [
                'pending_debts' => SubjectDebt::pending()->distinct('student_id')->count('student_id'),
            ];
        }

        return Inertia::render('Dashboard', [
            'stats'           => $stats,
            'activeYear'      => $activeYear,
            'gradeProgress'   => $gradeProgress,
            'todayAttendance' => $todayAttendance,
            'recentActivity'  => $recentActivity,
            'teacherLoads'    => $teacherLoads,
            'teacherStats'    => $teacherStats,
            'secretaryStats'  => $secretaryStats,
            'userRoles'       => $roles,
        ]);
    }
}
