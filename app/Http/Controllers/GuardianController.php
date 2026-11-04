<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Guardian;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;

class GuardianController extends Controller implements \Illuminate\Routing\Controllers\HasMiddleware
{
    use LogsActivity;

    public static function middleware(): array
    {
        return [
            new \Illuminate\Routing\Controllers\Middleware('permission:students.create|permission:students.edit', only: ['search', 'store']),
        ];
    }

    public function search(Request $request)
    {
        $request->validate([
            'cedula' => 'required|string',
        ], [
            'cedula.required' => 'Debe ingresar una cédula o documento para realizar la búsqueda.',
        ]);

        $rawCedula = strtoupper(trim((string) $request->input('cedula')));

        // Tolera búsqueda con o sin prefijo (ej: V-12345678, 12345678, v-12345678)
        $guardian = Guardian::where('cedula', $rawCedula)
            ->when(preg_match('/^\d{6,10}$/', $rawCedula), function ($query) use ($rawCedula) {
                $query->orWhere('cedula', "V-{$rawCedula}")
                      ->orWhere('cedula', "E-{$rawCedula}");
            })
            ->first();

        return response()->json([
            'guardian' => $guardian,
        ]);
    }

    public function store(Request $request)
    {
        $merge = [];

        // 1. Normalizar documento/cédula
        if ($request->has('cedula')) {
            $rawCedula = strtoupper(trim((string) $request->input('cedula')));
            if (preg_match('/^([VEP])(\d+|[A-Z0-9]+)$/', $rawCedula, $m)) {
                $rawCedula = "{$m[1]}-{$m[2]}";
            } elseif (preg_match('/^\d{6,10}$/', $rawCedula)) {
                $rawCedula = "V-{$rawCedula}";
            }
            $merge['cedula'] = $rawCedula;
        }

        // 2. Normalizar campos a MAYÚSCULAS
        if ($request->has('first_name')) {
            $merge['first_name'] = mb_strtoupper(preg_replace('/\s+/', ' ', trim((string) $request->input('first_name'))), 'UTF-8');
        }
        if ($request->has('last_name')) {
            $merge['last_name'] = mb_strtoupper(preg_replace('/\s+/', ' ', trim((string) $request->input('last_name'))), 'UTF-8');
        }
        if (!empty($merge['first_name']) && !empty($merge['last_name'])) {
            $merge['name'] = "{$merge['first_name']} {$merge['last_name']}";
        }
        if ($request->has('nationality')) {
            $merge['nationality'] = strtoupper(trim((string) $request->input('nationality')));
        }
        if ($request->has('birth_place')) {
            $merge['birth_place'] = mb_strtoupper(trim((string) $request->input('birth_place')), 'UTF-8');
        }
        if ($request->has('education_level')) {
            $merge['education_level'] = mb_strtoupper(trim((string) $request->input('education_level')), 'UTF-8');
        }
        if ($request->has('address')) {
            $merge['address'] = mb_strtoupper(trim((string) $request->input('address')), 'UTF-8');
        }
        if ($request->has('work_sector')) {
            $merge['work_sector'] = mb_strtoupper(trim((string) $request->input('work_sector')), 'UTF-8');
        }
        if ($request->has('work_type')) {
            $merge['work_type'] = mb_strtoupper(trim((string) $request->input('work_type')), 'UTF-8');
        }
        if ($request->has('occupation')) {
            $merge['occupation'] = mb_strtoupper(trim((string) $request->input('occupation')), 'UTF-8');
        }
        if ($request->has('relationship')) {
            $merge['relationship'] = mb_strtoupper(trim((string) $request->input('relationship')), 'UTF-8');
        }

        // 3. Normalizar teléfonos y correo
        if ($request->has('phone')) {
            $rawPhone = trim((string) $request->input('phone'));
            if (preg_match('/^(0[24]\d{2})(\d{7})$/', $rawPhone, $pm)) {
                $rawPhone = "{$pm[1]}-{$pm[2]}";
            }
            $merge['phone'] = empty($rawPhone) ? null : $rawPhone;
        }
        if ($request->has('phone_home')) {
            $rawHome = trim((string) $request->input('phone_home'));
            if (preg_match('/^(0[24]\d{2})(\d{7})$/', $rawHome, $pm)) {
                $rawHome = "{$pm[1]}-{$pm[2]}";
            }
            $merge['phone_home'] = empty($rawHome) ? null : $rawHome;
        }
        if ($request->has('email')) {
            $rawEmail = trim((string) $request->input('email'));
            $merge['email'] = empty($rawEmail) ? null : mb_strtolower($rawEmail, 'UTF-8');
        }

        if (!empty($merge)) {
            $request->merge($merge);
        }

        $minDate = now()->subYears(90)->format('Y-m-d');
        $maxDate = now()->subYears(18)->format('Y-m-d');

        $validated = $request->validate([
            'cedula' => [
                'required',
                'string',
                'max:20',
                'regex:/^(V-\d{6,9}|E-\d{6,9}|P-[A-Z0-9]{6,15})$/',
                'unique:guardians,cedula',
            ],
            'first_name'      => ['required', 'string', 'max:120'],
            'last_name'       => ['required', 'string', 'max:120'],
            'nationality'     => ['required', 'string', 'in:V,E'],
            'birth_place'     => ['required', 'string', 'max:255'],
            'birth_date'      => [
                'required',
                'date',
                "after_or_equal:{$minDate}",
                "before_or_equal:{$maxDate}",
            ],
            'education_level' => ['required', 'string', 'max:100'],
            'address'         => ['required', 'string', 'max:500'],
            'is_working'      => ['required', 'boolean'],
            'work_sector'     => ['nullable', 'string', 'max:50', 'required_if:is_working,true,1'],
            'work_type'       => ['nullable', 'string', 'max:50', 'required_if:is_working,true,1'],
            'occupation'      => ['nullable', 'string', 'max:150'],
            'phone'           => [
                'nullable',
                'string',
                'regex:/^(0412|0414|0424|0416|0426|02\d{2})-\d{7}$/',
            ],
            'phone_home'      => [
                'nullable',
                'string',
                'regex:/^(02\d{2}-\d{7}|0[24]\d{2}-\d{7})$/',
            ],
            'email'           => ['nullable', 'email:rfc', 'max:255'],
            'relationship'    => ['required', 'string', 'max:50'],
        ], [
            'cedula.required'          => 'La cédula o documento del representante es obligatorio.',
            'cedula.regex'             => 'Formato de documento inválido. Use V- (ej: V-12345678), E- (ej: E-84550210) o Pasaporte P- (ej: P-ABC123456).',
            'cedula.unique'            => 'Este número de cédula o documento ya está registrado para otro representante.',
            'first_name.required'      => 'El nombre del representante es obligatorio.',
            'last_name.required'       => 'El apellido del representante es obligatorio.',
            'nationality.required'     => 'Debe seleccionar la nacionalidad del representante.',
            'birth_place.required'     => 'El lugar de nacimiento del representante es obligatorio.',
            'birth_date.required'      => 'La fecha de nacimiento del representante es obligatoria.',
            'birth_date.before_or_equal' => 'El representante debe tener al menos 18 años de edad.',
            'birth_date.after_or_equal'  => 'El representante no puede tener más de 90 años de edad.',
            'education_level.required' => 'Debe seleccionar el nivel de instrucción del representante.',
            'address.required'         => 'La dirección de habitación del representante es obligatoria.',
            'is_working.required'      => 'Debe indicar si el representante trabaja.',
            'work_sector.required_if'  => 'Indique si trabaja en el sector público o privado.',
            'work_type.required_if'    => 'Indique el tipo de trabajo del representante.',
            'phone.regex'              => 'El teléfono móvil debe tener un formato válido en Venezuela (ej: 0414-1234567).',
            'phone_home.regex'         => 'El teléfono fijo debe tener un formato válido en Venezuela (ej: 0212-1234567).',
            'email.email'              => 'El correo electrónico no tiene un formato válido.',
            'relationship.required'    => 'Debe seleccionar el parentesco del representante con el estudiante.',
        ]);

        $guardian = Guardian::create([
            'cedula'          => $validated['cedula'],
            'first_name'      => $validated['first_name'],
            'last_name'       => $validated['last_name'],
            'nationality'     => $validated['nationality'],
            'birth_place'     => $validated['birth_place'],
            'birth_date'      => $validated['birth_date'],
            'education_level' => $validated['education_level'],
            'address'         => $validated['address'],
            'is_working'      => (bool) $validated['is_working'],
            'work_sector'     => $validated['work_sector'] ?? null,
            'work_type'       => $validated['work_type'] ?? null,
            'occupation'      => $validated['occupation'] ?? null,
            'phone'           => $validated['phone'] ?? null,
            'phone_home'      => $validated['phone_home'] ?? null,
            'email'           => $validated['email'] ?? null,
            'relationship'    => $validated['relationship'],
        ]);

        $this->auditLog('representantes', 'created', "Creó al representante {$guardian->name} ({$guardian->cedula})", $guardian);

        return response()->json([
            'message'  => 'Representante creado exitosamente',
            'guardian' => $guardian,
        ]);
    }
}