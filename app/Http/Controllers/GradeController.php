<?php

namespace App\Http\Controllers;

use App\Actions\Grades\StoreGradeAction;
use App\DTOs\GradeDTO;
use App\Http\Requests\StoreGradeRequest;
use App\Models\AcademicLoad;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Lapse;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\Subject;
use App\Traits\ValidatesTeacherLoad;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GradeController extends Controller
{
    use ValidatesTeacherLoad;
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
            return Inertia::render('Grades/Index', [
                'loads'       => [], 
                'activeYear'  => null,
                'schoolYears' => $schoolYears,
                'openLapseId' => null,
            ]);
        }

        // Si es docente, solo mostrar su carga académica en ese año
        if ($user->hasRole('Docente')) {
            $loads = AcademicLoad::where('teacher_id', $user->id)
                ->where('school_year_id', $selectedYear->id)
                ->with(['subject', 'section.gradeLevel'])
                ->get();
        } else {
            $loads = AcademicLoad::where('school_year_id', $selectedYear->id)
                ->with(['subject', 'section.gradeLevel', 'teacher'])
                ->get();
        }

        $enrollmentCounts = Enrollment::where('school_year_id', $selectedYear->id)
            ->active()
            ->selectRaw('section_id, count(*) as total')
            ->groupBy('section_id')
            ->pluck('total', 'section_id')
            ->toArray();

        $gradesCounts = Grade::join('enrollments', 'enrollments.id', '=', 'grades.enrollment_id')
            ->where('enrollments.school_year_id', $selectedYear->id)
            ->where('enrollments.status', 'active')
            ->whereNotNull('grades.score')
            ->selectRaw('grades.subject_id, enrollments.section_id, grades.lapse_id, count(*) as total')
            ->groupBy('grades.subject_id', 'enrollments.section_id', 'grades.lapse_id')
            ->get();

        $gradesMap = [];
        foreach ($gradesCounts as $g) {
            $gradesMap[$g->subject_id . '_' . $g->section_id . '_' . $g->lapse_id] = $g->total;
        }

        $openLapse = $selectedYear->lapses->firstWhere('is_open', true);

        $enrichedLoads = $loads->map(function ($load) use ($enrollmentCounts, $gradesMap, $selectedYear) {
            $studentsCount = $enrollmentCounts[$load->section_id] ?? 0;
            $lapsesCount = $selectedYear->lapses->count();
            
            $lapsesProgress = [];
            $totalAnnualLoaded = 0;
            foreach ($selectedYear->lapses as $lapse) {
                $loaded = $gradesMap[$load->subject_id . '_' . $load->section_id . '_' . $lapse->id] ?? 0;
                $pct = $studentsCount > 0 ? round(($loaded / $studentsCount) * 100, 1) : 0;
                $lapsesProgress[$lapse->id] = [
                    'id'         => $lapse->id,
                    'name'       => $lapse->name,
                    'is_open'    => (bool) $lapse->is_open,
                    'loaded'     => $loaded,
                    'expected'   => $studentsCount,
                    'percentage' => $pct,
                ];
                $totalAnnualLoaded += $loaded;
            }
            
            $totalAnnualExpected = $studentsCount * max(1, $lapsesCount);
            $totalAnnualPct = $totalAnnualExpected > 0 ? round(($totalAnnualLoaded / $totalAnnualExpected) * 100, 1) : 0;

            $arr = $load->toArray();
            $arr['students_count']          = $studentsCount;
            $arr['lapses_progress']         = $lapsesProgress;
            $arr['total_annual_loaded']     = $totalAnnualLoaded;
            $arr['total_annual_expected']   = $totalAnnualExpected;
            $arr['total_annual_percentage'] = $totalAnnualPct;
            return $arr;
        });

        return Inertia::render('Grades/Index', [
            'loads'       => $enrichedLoads,
            'activeYear'  => $selectedYear,
            'lapses'      => $selectedYear->lapses,
            'schoolYears' => $schoolYears,
            'openLapseId' => $openLapse?->id,
        ]);
    }

    public function datagrid(Request $request, Section $section, Subject $subject, Lapse $lapse)
    {
        $this->authorizeLoad($section->id, $subject->id);

        $enrollments = Enrollment::where('section_id', $section->id)
            ->where('school_year_id', $section->school_year_id)
            ->active()
            ->with([
                'student',
                'grades' => fn($q) => $q->where('subject_id', $subject->id)
                                         ->where('lapse_id', $lapse->id),
            ])
            ->get()
            ->sortBy('student.last_name');

        return Inertia::render('Grades/DataGrid', [
            'section' => $section->load('gradeLevel'),
            'subject' => $subject,
            'lapse' => $lapse,
            'enrollments' => $enrollments->values(),
        ]);
    }

    public function update(StoreGradeRequest $request, StoreGradeAction $action)
    {
        $enrollment = Enrollment::findOrFail($request->input('enrollment_id'));
        $this->authorizeLoad($enrollment->section_id, $request->input('subject_id'));

        $lapse = Lapse::findOrFail($request->input('lapse_id'));
        
        if (!$lapse->is_open) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => 'No se pueden cargar notas en un lapso cerrado.'], 422);
            }
            return back()->withErrors(['message' => 'No se pueden cargar notas en un lapso cerrado.']);
        }

        $dto = GradeDTO::fromRequest($request);
        $action->execute($dto);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Nota guardada correctamente.',
                'score' => $dto->score,
            ]);
        }

        return back()->with('success', 'Nota guardada correctamente.');
    }

    public function batchUpdate(Request $request, StoreGradeAction $action)
    {
        $request->validate([
            'changes' => ['required', 'array'],
            'changes.*.enrollment_id' => ['required', 'integer', 'exists:enrollments,id'],
            'changes.*.subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'changes.*.lapse_id' => ['required', 'integer', 'exists:lapses,id'],
            'changes.*.score' => ['required', 'numeric', 'min:1', 'max:20'],
        ]);

        // Validar que el docente tenga acceso a la carga (usamos el primer registro)
        $first = $request->input('changes')[0];
        $enrollment = Enrollment::findOrFail($first['enrollment_id']);
        $this->authorizeLoad($enrollment->section_id, $first['subject_id']);

        // Validar que el lapso del primer cambio esté abierto
        $lapse = Lapse::findOrFail($first['lapse_id']);

        if (!$lapse->is_open) {
            return back()->withErrors(['message' => 'No se pueden cargar notas en un lapso cerrado.']);
        }

        foreach ($request->input('changes') as $change) {
            $dto = GradeDTO::fromArray($change);
            $action->execute($dto);
        }

        return back()->with('success', 'Notas guardadas correctamente.');
    }
}
