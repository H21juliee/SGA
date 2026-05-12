<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SchoolYearController extends Controller
{
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
                'name' => "{$i}er Lapso",
                'number' => $i,
                'is_open' => false,
                'start_date' => $year->start_date,
                'end_date' => $year->end_date,
            ]);
        }

        return back()->with('success', 'Año escolar creado exitosamente con sus 3 lapsos.');
    }

    public function update(Request $request, SchoolYear $schoolYear)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:school_years,name,' . $schoolYear->id,
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $schoolYear->update($validated);

        return back()->with('success', 'Año escolar actualizado exitosamente.');
    }

    public function toggleActive(SchoolYear $school_year)
    {
        // Desactivar todos
        SchoolYear::where('is_active', true)->update(['is_active' => false]);
        
        // Activar el seleccionado
        $school_year->update(['is_active' => true]);

        return back()->with('success', "El año escolar {$school_year->name} ahora es el año activo.");
    }

    public function toggleLapse(\App\Models\Lapse $lapse)
    {
        $lapse->update(['is_open' => !$lapse->is_open]);
        $status = $lapse->is_open ? 'abierto' : 'cerrado';
        return back()->with('success', "El {$lapse->name} ahora está {$status}.");
    }

    public function destroy(SchoolYear $schoolYear)
    {
        if ($schoolYear->enrollments()->exists() || $schoolYear->is_active) {
            return back()->with('error', 'No se puede eliminar un año escolar activo o que ya tiene inscripciones.');
        }

        $schoolYear->delete();
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
            return back()->with('success', "Cierre exitoso. Promovidos: {$result['total_promoted']}, Reprobados: {$result['total_failed']}, Graduados: {$result['total_graduated']}");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
