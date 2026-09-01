<?php

namespace App\Imports;

use App\Models\GradeLevel;
use App\Models\Subject;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Validators\Failure;

/**
 * SubjectsImport — Importación masiva de materias desde Excel / CSV.
 *
 * - Busca el nivel (GradeLevel) por nombre exacto (case-insensitive).
 * - Si el código de materia ya existe → omitir con error en resumen.
 * - Si el nivel no existe → omitir con error en resumen.
 * - grading_type: 'numeric' o 'qualitative', por defecto 'numeric'.
 */
class SubjectsImport implements
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

    /** Cache de niveles para evitar N+1 */
    private ?Collection $gradeLevels = null;

    public function collection(Collection $rows): void
    {
        // Cargar todos los niveles una sola vez
        $this->gradeLevels = GradeLevel::all();

        foreach ($rows as $index => $row) {
            $nivelNombre = trim($row['nivel'] ?? '');
            $codigo      = strtoupper(trim($row['codigo'] ?? ''));
            $nombre      = trim($row['nombre'] ?? '');

            // Buscar el nivel por nombre (case-insensitive)
            $gradeLevel = $this->gradeLevels->first(
                fn($gl) => strtolower($gl->name) === strtolower($nivelNombre)
            );

            if (!$gradeLevel) {
                $this->skipped++;
                $this->skippedRows[] = [
                    'fila'   => $index + 2,
                    'valor'  => $nombre ?: $codigo,
                    'motivo' => "El nivel '{$nivelNombre}' no existe en el sistema.",
                ];
                continue;
            }

            // Si el código ya existe → omitir con error
            if (Subject::where('code', $codigo)->exists()) {
                $this->skipped++;
                $this->skippedRows[] = [
                    'fila'   => $index + 2,
                    'valor'  => "{$nombre} ({$codigo})",
                    'motivo' => "El código {$codigo} ya existe en el sistema.",
                ];
                continue;
            }

            // Normalizar tipo de evaluación
            $rawType = strtolower(trim($row['tipo_evaluacion'] ?? 'numeric'));
            $gradingType = in_array($rawType, ['numeric', 'qualitative']) ? $rawType : 'numeric';

            Subject::create([
                'grade_level_id' => $gradeLevel->id,
                'name'           => $nombre,
                'code'           => $codigo,
                'weight'         => 10,
                'grading_type'   => $gradingType,
            ]);

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
            'nivel'           => ['required', 'string', 'max:100'],
            'nombre'          => ['required', 'string', 'max:100'],
            'codigo'          => ['required', 'string', 'max:20'],
            'tipo_evaluacion' => ['nullable', 'string', 'in:numeric,qualitative'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nivel.required'  => 'El campo nivel es obligatorio.',
            'nombre.required' => 'El campo nombre es obligatorio.',
            'codigo.required' => 'El campo código es obligatorio.',
            'tipo_evaluacion.in' => 'El tipo de evaluación debe ser "numeric" o "qualitative".',
        ];
    }

    public function customValidationAttributes(): array
    {
        return [
            'nivel'           => 'Nivel',
            'nombre'          => 'Nombre',
            'codigo'          => 'Código',
            'tipo_evaluacion' => 'Tipo de Evaluación',
        ];
    }
}
