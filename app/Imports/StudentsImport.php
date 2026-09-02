<?php

namespace App\Imports;

use App\Enums\EnrollmentStatus;
use App\Enums\StudentStatus;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\SchoolYear;
use App\Models\Section;
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

    /** Filas omitidas con detalle del motivo */
    public array $skippedRows = [];

    /** Filas con errores de validación */
    private array $failures = [];

    /**
     * Recibe la colección de filas ya validadas y las procesa.
     */
    public function collection(Collection $rows): void
    {
        $activeYear = SchoolYear::active()->first();

        foreach ($rows as $index => $row) {
            $cedula = $this->normalizeCedula($row['cedula_escolar'] ?? null);

            // Si tiene cédula y ya existe → omitir con detalle
            if ($cedula && Student::where('cedula', $cedula)->exists()) {
                $this->skipped++;
                $this->skippedRows[] = [
                    'fila'   => $index + 2,
                    'valor'  => trim($row['nombres'] ?? '') . ' ' . trim($row['apellidos'] ?? ''),
                    'motivo' => "Cédula {$cedula} ya existe en el sistema.",
                ];
                continue;
            }

            $birthDate = $this->parseBirthDate($row['fecha_nacimiento'] ?? null);

            $student = Student::create([
                'first_name' => trim($row['nombres']),
                'last_name'  => trim($row['apellidos']),
                'cedula'     => $cedula,
                'birth_date' => $birthDate,
                'gender'     => strtoupper(trim($row['genero'])),
                'status'     => StudentStatus::REGULAR,
            ]);

            // Asignación e inscripción opcional a Grado y Sección
            $gradoNombre = trim((string) ($row['grado'] ?? $row['ano'] ?? ''));
            $seccionNombre = strtoupper(trim((string) ($row['seccion'] ?? '')));

            if (!empty($gradoNombre) && !empty($seccionNombre) && $activeYear) {
                $gradeLevel = GradeLevel::where('name', $gradoNombre)
                    ->orWhere('name', 'like', "%{$gradoNombre}%")
                    ->first();

                if ($gradeLevel) {
                    $section = Section::firstOrCreate([
                        'school_year_id' => $activeYear->id,
                        'grade_level_id' => $gradeLevel->id,
                        'name'           => $seccionNombre,
                    ], [
                        'capacity'       => 40,
                    ]);

                    Enrollment::firstOrCreate([
                        'student_id'     => $student->id,
                        'school_year_id' => $activeYear->id,
                    ], [
                        'section_id'     => $section->id,
                        'status'         => EnrollmentStatus::ACTIVE,
                        'enrolled_at'    => $activeYear->start_date ?? now(),
                    ]);
                }
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
            'nombres'          => ['required', 'string', 'max:255'],
            'apellidos'        => ['required', 'string', 'max:255'],
            'cedula_escolar'   => ['nullable', 'string', 'max:20', 'regex:/^([VEPvep]-\d{6,15}|\d{6,15}|CE-\d{6,15})$/i'],
            'fecha_nacimiento' => ['nullable'],
            'genero'           => ['required', 'string', 'in:M,F,m,f'],
            'grado'            => ['nullable', 'string', 'max:50'],
            'ano'              => ['nullable', 'string', 'max:50'],
            'seccion'          => ['nullable', 'string', 'max:10'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nombres.required'          => 'El campo nombres es obligatorio.',
            'apellidos.required'        => 'El campo apellidos es obligatorio.',
            'cedula_escolar.regex'      => 'La cédula debe tener el formato V-12345678, E-12345678 o solo números.',
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
            'grado'            => 'Grado / Año',
            'ano'              => 'Grado / Año',
            'seccion'          => 'Sección',
        ];
    }

    private function normalizeCedula(mixed $raw): ?string
    {
        if (empty($raw)) return null;
        $raw = strtoupper(trim((string) $raw));
        if (preg_match('/^\d{6,15}$/', $raw)) {
            return 'V-' . $raw;
        }
        return $raw;
    }

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
