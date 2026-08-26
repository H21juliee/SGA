<?php

namespace App\Imports;

use App\Enums\StudentStatus;
use App\Models\Student;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

/**
 * StudentsImport — Importación masiva de estudiantes desde Excel / CSV.
 *
 * Usa ToCollection para iterar manualmente y manejar:
 * - Filas vacías (SkipsEmptyRows)
 * - Filas de instrucciones del template (se omiten si no pasan validación)
 * - Cédulas duplicadas (omitir sin abortar)
 * - Fechas en formato DD/MM/YYYY o YYYY-MM-DD o serial Excel
 */
class StudentsImport implements
    ToCollection,
    WithHeadingRow,
    WithValidation,
    SkipsEmptyRows,
    SkipsOnFailure
{
    use Importable;

    public int $created = 0;
    public int $skipped = 0;

    /** Filas con errores de validación */
    private array $failures = [];

    /**
     * Recibe la colección de filas ya validadas y las procesa.
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $cedula = $this->normalizeCedula($row['cedula_escolar'] ?? null);

            // Si tiene cédula y ya existe → omitir
            if ($cedula && Student::where('cedula', $cedula)->exists()) {
                $this->skipped++;
                continue;
            }

            Student::create([
                'first_name' => trim($row['nombres']),
                'last_name'  => trim($row['apellidos']),
                'cedula'     => $cedula,
                'birth_date' => $this->parseBirthDate($row['fecha_nacimiento']),
                'gender'     => strtoupper(trim($row['genero'])),
                'status'     => StudentStatus::REGULAR,
            ]);

            $this->created++;
        }
    }

    /**
     * Filas que fallaron la validación → se omiten y se acumulan.
     */
    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->failures[] = $failure;
            $this->skipped++;
        }
    }

    /**
     * Devuelve el array de Failure acumulados (para mostrar en la respuesta).
     */
    public function getFailures(): array
    {
        return $this->failures;
    }

    /**
     * Reglas de validación por columna.
     */
    public function rules(): array
    {
        return [
            'nombres'          => ['required', 'string', 'max:255'],
            'apellidos'        => ['required', 'string', 'max:255'],
            'cedula_escolar'   => ['nullable', 'string', 'max:20', 'regex:/^[VEPvep]-\d{6,10}$/'],
            'fecha_nacimiento' => ['required'],
            'genero'           => ['required', 'string', 'in:M,F,m,f'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nombres.required'          => 'El campo nombres es obligatorio.',
            'apellidos.required'        => 'El campo apellidos es obligatorio.',
            'cedula_escolar.regex'      => 'La cédula debe tener el formato V-12345678 o E-12345678.',
            'fecha_nacimiento.required' => 'El campo fecha de nacimiento es obligatorio.',
            'genero.required'           => 'El campo género es obligatorio.',
            'genero.in'                 => 'El género debe ser M (Masculino) o F (Femenino).',
        ];
    }

    public function customValidationAttributes(): array
    {
        return [
            'nombres'          => 'Nombres',
            'apellidos'        => 'Apellidos',
            'cedula_escolar'   => 'Cédula Escolar',
            'fecha_nacimiento' => 'Fecha de Nacimiento',
            'genero'           => 'Género',
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function normalizeCedula(mixed $raw): ?string
    {
        if (empty($raw)) return null;
        return strtoupper(trim((string) $raw));
    }

    /**
     * Parsea fecha aceptando:
     *   - Número serial de Excel  (ej. 40401)
     *   - DD/MM/YYYY              (formato venezolano)
     *   - YYYY-MM-DD              (ISO)
     */
    private function parseBirthDate(mixed $raw): ?string
    {
        if (empty($raw)) return null;

        // Serial numérico de Excel
        if (is_numeric($raw)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $raw)
                ->format('Y-m-d');
        }

        $raw = trim((string) $raw);

        // DD/MM/YYYY
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $raw)) {
            return Carbon::createFromFormat('d/m/Y', $raw)->format('Y-m-d');
        }

        // YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            return $raw;
        }

        return null;
    }
}
