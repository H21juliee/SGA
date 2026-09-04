<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;
use Spatie\Permission\Models\Role;

/**
 * TeachersImport — Importación masiva de docentes desde Excel / CSV.
 *
 * - Si el email ya existe → omitir con error en resumen.
 * - Cédula obligatoria: se usa como contraseña inicial.
 * - Se asigna automáticamente el rol 'Docente'.
 * - must_change_password = true según configuración del sistema.
 */
class TeachersImport implements
    ToCollection,
    WithHeadingRow,
    WithValidation,
    SkipsEmptyRows,
    SkipsOnFailure
{
    use Importable;

    public int $created  = 0;
    public int $skipped  = 0;

    /** Filas omitidas con detalle del motivo */
    public array $skippedRows = [];

    private array $failures = [];

    /** ID del rol Docente (cargado una sola vez) */
    private ?Role $docenteRole = null;

    public function collection(Collection $rows): void
    {
        $this->docenteRole = Role::findByName('Docente', 'web');
        $securityEnabled   = config('app.security_questions_enabled', false);

        foreach ($rows as $index => $row) {
            $email  = strtolower(trim($row['email'] ?? ''));
            $cedula = $this->normalizeCedula($row['cedula'] ?? null);
            $nombre = trim($row['nombre'] ?? '');

            // Si el email ya existe → omitir con error
            if (User::where('email', $email)->exists()) {
                $this->skipped++;
                $this->skippedRows[] = [
                    'fila'   => $index + 2,
                    'valor'  => $nombre ?: $email,
                    'motivo' => "El correo {$email} ya está registrado en el sistema.",
                ];
                continue;
            }

            // Si la cédula ya existe → omitir con error
            if ($cedula && User::where('cedula', $cedula)->exists()) {
                $this->skipped++;
                $this->skippedRows[] = [
                    'fila'   => $index + 2,
                    'valor'  => $nombre,
                    'motivo' => "La cédula {$cedula} ya está registrada en el sistema.",
                ];
                continue;
            }

            // Contraseña inicial = cédula del docente
            $password = Hash::make($cedula);

            $user = User::create([
                'name'                => $nombre,
                'email'               => $email,
                'cedula'              => $cedula,
                'phone'               => trim($row['telefono'] ?? '') ?: null,
                'password'            => $password,
                'is_active'           => true,
                'must_change_password'=> $securityEnabled,
            ]);

            if ($this->docenteRole) {
                $user->assignRole($this->docenteRole);
            }

            $this->created++;
        }
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->failures[] = $failure;
            $this->skipped++;
            $this->skippedRows[] = [
                'fila'   => $failure->row(),
                'valor'  => implode(', ', (array) $failure->values()),
                'motivo' => implode('. ', $failure->errors()),
            ];
        }
    }

    public function getFailures(): array
    {
        return $this->failures;
    }

    public function rules(): array
    {
        return [
            'nombre'   => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255'],
            'cedula'   => ['required', 'string', 'max:20', 'regex:/^[VEPvep]-\d{6,10}$/'],
            'telefono' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nombre.required'  => 'El campo nombre es obligatorio.',
            'email.required'   => 'El campo email es obligatorio.',
            'email.email'      => 'El email no tiene un formato válido.',
            'cedula.required'  => 'La cédula es obligatoria para los docentes.',
            'cedula.regex'     => 'La cédula debe tener el formato V-12345678 o E-12345678.',
        ];
    }

    public function customValidationAttributes(): array
    {
        return [
            'nombre'   => 'Nombre',
            'email'    => 'Correo electrónico',
            'cedula'   => 'Cédula',
            'telefono' => 'Teléfono',
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function normalizeCedula(mixed $raw): ?string
    {
        if (empty($raw)) return null;
        return strtoupper(trim((string) $raw));
    }
}
