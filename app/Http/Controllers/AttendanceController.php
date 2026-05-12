<?php

namespace App\Http\Controllers;

use App\Actions\Attendance\RecordAttendanceAction;
use App\DTOs\AttendanceDTO;
use App\Http\Requests\StoreAttendanceRequest;
use App\Models\AcademicLoad;
use App\Models\AttendanceLock;
use App\Models\Enrollment;
use App\Models\Lapse;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $schoolYears = SchoolYear::orderBy('start_date', 'desc')->get();
        
        $selectedYearId = $request->input('school_year_id');
        
        if ($selectedYearId) {
            $selectedYear = SchoolYear::with('lapses')->findOrFail($selectedYearId);
        } else {
            $selectedYear = SchoolYear::active()->with('lapses')->first();
        }

        if (!$selectedYear) {
            return Inertia::render('Attendance/Index', [
                'sections' => [], 
                'activeYear' => null,
                'schoolYears' => $schoolYears
            ]);
        }

        if ($user->hasRole('Docente')) {
            $loads = AcademicLoad::where('teacher_id', $user->id)
                ->where('school_year_id', $selectedYear->id)
                ->with(['section.gradeLevel', 'subject'])
                ->get();
            
            // Transformar para que la vista lo entienda fácil
            $sections = $loads->map(fn($l) => [
                'id' => $l->section_id,
                'name' => $l->section->name,
                'grade_level' => $l->section->gradeLevel,
                'subject' => $l->subject
            ]);
        } else {
            // Admin ve todo, agrupado por seccion-materia
            $sections = AcademicLoad::where('school_year_id', $selectedYear->id)
                ->with(['section.gradeLevel', 'subject'])
                ->get()
                ->map(fn($l) => [
                    'id' => $l->section_id,
                    'name' => $l->section->name,
                    'grade_level' => $l->section->gradeLevel,
                    'subject' => $l->subject
                ]);
        }

        return Inertia::render('Attendance/Index', [
            'sections' => $sections,
            'activeYear' => $selectedYear,
            'schoolYears' => $schoolYears,
        ]);
    }

    public function datagrid(Request $request, Section $section)
    {
        $subjectId = $request->input('subject_id');
        $subject = \App\Models\Subject::findOrFail($subjectId);
        
        $date = $request->input('date', now()->toDateString());
        $activeYear = SchoolYear::active()->with('lapses')->first();
        $activeLapse = $activeYear ? $activeYear->lapses()->where('is_open', true)->first() : null;

        $manualLock = AttendanceLock::where('subject_id', $subjectId)
            ->where('section_id', $section->id)
            ->where('date', $date)
            ->exists();

        // Bloqueado si hay cierre manual O si no hay lapso abierto
        $isLocked = $manualLock || !$activeLapse;

        $enrollments = Enrollment::where('section_id', $section->id)
            ->where('school_year_id', $section->school_year_id)
            ->active()
            ->with([
                'student',
                'attendances' => fn($q) => $q->where('date', $date)->where('subject_id', $subjectId),
            ])
            ->get()
            ->sortBy('student.last_name');

        return Inertia::render('Attendance/DataGrid', [
            'section' => $section->load('gradeLevel'),
            'subject' => $subject,
            'date' => $date,
            'lapse' => $activeLapse,
            'enrollments' => $enrollments->values(),
            'isLocked' => $isLocked,
        ]);
    }

    public function history(Request $request, Section $section, Subject $subject, Lapse $lapse)
    {
        // Obtener todas las fechas únicas de asistencia para esta sesión
        $dates = \App\Models\Attendance::where('subject_id', $subject->id)
            ->whereHas('enrollment', fn($q) => $q->where('section_id', $section->id))
            ->where('lapse_id', $lapse->id)
            ->orderBy('date')
            ->pluck('date')
            ->unique()
            ->values();

        // Obtener alumnos y sus asistencias
        $enrollments = Enrollment::where('section_id', $section->id)
            ->active()
            ->with(['student', 'attendances' => fn($q) => 
                $q->where('subject_id', $subject->id)
                  ->where('lapse_id', $lapse->id)
            ])
            ->get()
            ->sortBy('student.last_name');

        // Obtener todos los lapsos del año para el selector
        $allLapses = SchoolYear::active()->first()->lapses;

        return Inertia::render('Attendance/History', [
            'section' => $section->load('gradeLevel'),
            'subject' => $subject,
            'lapse' => $lapse,
            'allLapses' => $allLapses,
            'dates' => $dates,
            'enrollments' => $enrollments->values(),
        ]);
    }

    public function lock(Request $request)
    {
        $data = $request->validate([
            'subject_id' => ['required', 'exists:subjects,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'date' => ['required', 'date'],
        ]);

        AttendanceLock::create([
            'subject_id' => $data['subject_id'],
            'section_id' => $data['section_id'],
            'date' => $data['date'],
            'user_id' => $request->user()->id,
        ]);

        return back()->with('success', 'Asistencia finalizada y bloqueada correctamente.');
    }

    public function update(StoreAttendanceRequest $request, RecordAttendanceAction $action)
    {
        $subjectId = $request->input('subject_id');
        $date = $request->input('date');
        $enrollment = Enrollment::find($request->input('enrollment_id'));

        $isLocked = AttendanceLock::where('subject_id', $subjectId)
            ->where('section_id', $enrollment->section_id)
            ->where('date', $date)
            ->exists();

        if ($isLocked) {
            return back()->withErrors(['message' => 'Esta asistencia está bloqueada y no se puede modificar.']);
        }

        $dto = AttendanceDTO::fromArray($request->validated());
        $action->execute($dto);

        return back()->with('success', 'Asistencia registrada.');
    }

    public function batchUpdate(Request $request, RecordAttendanceAction $action)
    {
        $request->validate([
            'changes' => ['required', 'array'],
            'changes.*.enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'changes.*.subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'changes.*.date' => ['required', 'date'],
            'changes.*.status' => ['required', 'string'],
            'changes.*.lapse_id' => ['required', 'integer', 'exists:lapses,id'],
            'changes.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        // Verificar bloqueo (tomamos el primero para validar la sesion)
        $first = $request->input('changes')[0];
        $enrollment = Enrollment::find($first['enrollment_id']);
        
        $isLocked = AttendanceLock::where('subject_id', $first['subject_id'])
            ->where('section_id', $enrollment->section_id)
            ->where('date', $first['date'])
            ->exists();

        if ($isLocked) {
            return back()->withErrors(['message' => 'Esta asistencia está bloqueada y no se puede modificar.']);
        }

        foreach ($request->input('changes') as $change) {
            $dto = AttendanceDTO::fromArray($change);
            $action->execute($dto);
        }

        return back()->with('success', 'Asistencias guardadas correctamente.');
    }
}
