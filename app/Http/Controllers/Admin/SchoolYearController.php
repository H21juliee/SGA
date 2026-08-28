<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SchoolYearController extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware
{
    use LogsActivity;
    public static function middleware(): array
    {
        return [
        new \Illuminate\Routing\Controllers\Middleware('permission:school_years.view', only: ['index']),
        new \Illuminate\Routing\Controllers\Middleware('permission:school_years.manage', only: ['store', 'update', 'destroy']),
        new \Illuminate\Routing\Controllers\Middleware('permission:school_years.toggle', only: ['toggleActive']),
        new \Illuminate\Routing\Controllers\Middleware('permission:school_years.promote', only: ['closeAndPromote']),
        new \Illuminate\Routing\Controllers\Middleware('permission:school_years.toggle_lapse', only: ['toggleLapse']),
        ];
    }
    public function index()
    {
        $years = SchoolYear::with('lapses')->orderByDesc('start_date')->get();

        return Inertia::render('Admin/SchoolYears/Index', [
            'years' => $years,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:school_years,name',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $year = SchoolYear::create($validated);

        // Generar automáticamente los 3 lapsos por defecto
        for ($i = 1; $i <= 3; $i++) {
            $year->lapses()->create([
                'name'       => "{$i}er Lapso",
                'number'     => $i,
                'is_open'    => false,
                'start_date' => $year->start_date,
                'end_date'   => $year->end_date,
            ]);
        }

        $this->auditLog('años_escolares', 'created', "Creó el año escolar {$year->name}", $year);

        return back()->with('success', 'Año escolar creado exitosamente con sus 3 lapsos.');
    }

    public function update(Request $request, SchoolYear $schoolYear)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:school_years,name,' . $schoolYear->id,
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $before = $schoolYear->only(['name', 'start_date', 'end_date']);
        $schoolYear->update($validated);
        $diff = $this->diffProperties($before, $validated);

        $this->auditLog('años_escolares', 'updated', "Editó el año escolar {$schoolYear->name}", $schoolYear, $diff);

        return back()->with('success', 'Año escolar actualizado exitosamente.');
    }

    public function toggleActive(SchoolYear $school_year)
    {
        SchoolYear::where('is_active', true)->update(['is_active' => false]);
        $school_year->update(['is_active' => true]);

        $this->auditLog('años_escolares', 'updated',
            "Cambió el año activo a {$school_year->name}",
            $school_year
        );

        return back()->with('success', "El año escolar {$school_year->name} ahora es el año activo.");
    }

    public function toggleLapse(\App\Models\Lapse $lapse)
    {
        $willBeOpen = !$lapse->is_open;
        
        if ($willBeOpen) {
            // Asegurarnos de cerrar cualquier otro lapso abierto en el mismo año escolar
            \App\Models\Lapse::where('school_year_id', $lapse->school_year_id)
                             ->where('id', '!=', $lapse->id)
                             ->update(['is_open' => false]);
        }
        
        $lapse->update(['is_open' => $willBeOpen]);
        $status = $lapse->is_open ? 'abierto' : 'cerrado';

        $this->auditLog('años_escolares', 'updated',
            "{$lapse->name} marcado como {$status}",
            $lapse
        );

        return back()->with('success', "El {$lapse->name} ahora está {$status}.");
    }

    public function destroy(SchoolYear $schoolYear)
    {
        if ($schoolYear->enrollments()->exists() || $schoolYear->is_active) {
            return back()->with('error', 'No se puede eliminar un año escolar activo o que ya tiene inscripciones.');
        }

        $schoolYear->delete();

        $this->auditLog('años_escolares', 'deleted', "Eliminó el año escolar {$schoolYear->name}");

        return back()->with('success', 'Año escolar eliminado.');
    }

    public function closeAndPromote(Request $request, SchoolYear $school_year, \App\Services\PromotionService $promotionService)
    {
        $validated = $request->validate([
            'next_school_year_id' => 'required|exists:school_years,id|different:id',
        ]);

        $nextYear = SchoolYear::findOrFail($validated['next_school_year_id']);

        try {
            $result = $promotionService->promoteAll($school_year, $nextYear);

            $this->auditLog('años_escolares', 'promoted',
                "Ejecutó cierre y promoción de {$school_year->name}: "
                . "Promovidos: {$result['total_promoted']}, Con pendientes: {$result['total_promoted_pending']}, "
                . "Repitientes: {$result['total_failed']}, Graduados: {$result['total_graduated']}",
                $school_year
            );

            return back()->with('success', "Cierre exitoso. Promovidos: {$result['total_promoted']}, Con pendientes: {$result['total_promoted_pending']}, Repitientes: {$result['total_failed']}, Graduados: {$result['total_graduated']}");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
