<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CommunityController extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware
{
    use LogsActivity;

    public static function middleware(): array
    {
        return [
            new \Illuminate\Routing\Controllers\Middleware('permission:students.create|students.edit|settings.manage', only: ['quickStore']),
            new \Illuminate\Routing\Controllers\Middleware('permission:settings.manage', only: ['index', 'store', 'update', 'toggle']),
        ];
    }

    /**
     * Listado paginado de comunidades y sectores (Configuración -> Comunidades y Sectores).
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $status = (string) $request->input('status', 'all');
        $sort = (string) $request->input('sort', 'name');
        $direction = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['name', 'students_count', 'is_active', 'created_at'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        $query = Community::withCount('students');

        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        } else {
            $status = 'all';
        }

        $query->orderBy($sort, $direction);
        if ($sort !== 'name') {
            $query->orderBy('name', 'asc');
        }

        return Inertia::render('Admin/Communities/Index', [
            'communities' => $query->paginate(15)->withQueryString(),
            'filters'     => [
                'search'    => $search,
                'status'    => $status,
                'sort'      => $sort,
                'direction' => $direction,
            ],
            'stats'       => [
                'total'    => Community::count(),
                'active'   => Community::where('is_active', true)->count(),
                'inactive' => Community::where('is_active', false)->count(),
            ],
        ]);
    }

    private function normalizeForComparison(string $value): string
    {
        $clean = preg_replace('/\s+/', ' ', trim($value)) ?? '';
        return Str::ascii(mb_strtolower($clean, 'UTF-8'));
    }

    private function formatCommunityName(string $value): string
    {
        $clean = preg_replace('/\s+/', ' ', trim($value)) ?? '';
        return mb_convert_case($clean, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Registro rápido desde el formulario de inscripción/edición de estudiantes.
     */
    public function quickStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
        ], [
            'name.required' => 'Ingresa el nombre de la comunidad o sector.',
            'name.min'      => 'El nombre debe tener al menos 3 caracteres.',
            'name.max'      => 'El nombre no debe exceder los 120 caracteres.',
        ]);

        $normalized = $this->normalizeForComparison($validated['name']);
        $existing = Community::all()->first(
            fn (Community $c) => $this->normalizeForComparison($c->name) === $normalized
        );

        if ($existing) {
            if (!$existing->is_active) {
                $existing->update(['is_active' => true]);
            }

            return response()->json([
                'community'      => $existing->fresh(),
                'already_exists' => true,
                'message'        => "La comunidad «{$existing->name}» ya existía en el catálogo y fue seleccionada.",
            ]);
        }

        $community = Community::create([
            'name'      => $this->formatCommunityName($validated['name']),
            'is_active' => true,
        ]);

        $this->auditLog('configuracion', 'created', "Registró rápidamente la comunidad {$community->name}", $community);

        return response()->json([
            'community'      => $community,
            'already_exists' => false,
            'message'        => "Comunidad «{$community->name}» agregada y seleccionada.",
        ], 201);
    }

    /**
     * Creación desde Administración -> Configuración.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
        ], [
            'name.required' => 'El nombre de la comunidad es obligatorio.',
            'name.min'      => 'El nombre debe tener al menos 3 caracteres.',
        ]);

        $normalized = $this->normalizeForComparison($validated['name']);
        $existing = Community::all()->first(
            fn (Community $c) => $this->normalizeForComparison($c->name) === $normalized
        );

        if ($existing) {
            return back()->with('error', "Ya existe una comunidad registrada con el nombre «{$existing->name}».");
        }

        $community = Community::create([
            'name'      => $this->formatCommunityName($validated['name']),
            'is_active' => true,
        ]);

        $this->auditLog('configuracion', 'created', "Creó la comunidad {$community->name}", $community);

        return back()->with('success', "Comunidad «{$community->name}» creada correctamente.");
    }

    /**
     * Actualización de nombre desde Administración -> Configuración.
     */
    public function update(Request $request, Community $community): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:120'],
        ], [
            'name.required' => 'El nombre de la comunidad es obligatorio.',
            'name.min'      => 'El nombre debe tener al menos 3 caracteres.',
        ]);

        $normalized = $this->normalizeForComparison($validated['name']);
        $duplicate = Community::where('id', '!=', $community->id)->get()->first(
            fn (Community $c) => $this->normalizeForComparison($c->name) === $normalized
        );

        if ($duplicate) {
            return back()->with('error', "Ya existe otra comunidad con el nombre «{$duplicate->name}».");
        }

        $oldName = $community->name;
        $newName = $this->formatCommunityName($validated['name']);

        $community->update(['name' => $newName]);

        $this->auditLog(
            'configuracion',
            'updated',
            "Renombró la comunidad {$oldName} a {$newName}",
            $community,
            ['old' => ['name' => $oldName], 'new' => ['name' => $newName]]
        );

        return back()->with('success', "Comunidad actualizada a «{$newName}».");
    }

    /**
     * Activar o desactivar comunidad desde Administración -> Configuración.
     */
    public function toggle(Community $community): RedirectResponse
    {
        $newStatus = !$community->is_active;
        $community->update(['is_active' => $newStatus]);

        $action = $newStatus ? 'Activó' : 'Desactivó';
        $this->auditLog('configuracion', 'updated', "{$action} la comunidad {$community->name}", $community);

        return back()->with('success', $newStatus
            ? "Comunidad «{$community->name}» activada."
            : "Comunidad «{$community->name}» desactivada.");
    }
}
