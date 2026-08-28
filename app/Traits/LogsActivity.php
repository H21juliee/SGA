<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * LogsActivity — Trait reutilizable para registrar acciones críticas en el log de auditoría.
 *
 * Uso en controladores:
 *   use LogsActivity;
 *   ...
 *   $this->auditLog('estudiantes', 'updated', "Editó al estudiante {$student->full_name}", $student, [
 *       'old' => ['first_name' => 'Juan'],
 *       'new' => ['first_name' => 'Juan Carlos'],
 *   ]);
 */
trait LogsActivity
{
    /**
     * Registra una acción en el log de auditoría.
     *
     * @param string     $module      Módulo: 'estudiantes', 'usuarios', etc.
     * @param string     $action      Acción: 'created', 'updated', 'deleted', 'council_updated', etc.
     * @param string     $description Texto legible para el humano.
     * @param Model|null $subject     Modelo afectado (opcional).
     * @param array      $properties  ['old' => [...], 'new' => [...]] — dejar vacío si no aplica.
     */
    protected function auditLog(
        string $module,
        string $action,
        string $description,
        ?Model $subject = null,
        array  $properties = []
    ): void {
        ActivityLog::create([
            'user_id'      => auth()->id(),
            'module'       => $module,
            'action'       => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'description'  => $description,
            'properties'   => empty($properties) ? null : $properties,
            'ip_address'   => request()->ip(),
            'created_at'   => now(),
        ]);
    }

    /**
     * Retorna solo los campos que cambiaron entre dos arrays.
     * Útil para construir el 'old' y 'new' de properties.
     *
     * @param array $before  Valores originales (ej. $model->getOriginal())
     * @param array $after   Valores nuevos (ej. $validated)
     * @param array $exclude Campos a excluir (ej. ['password', 'updated_at'])
     * @return array ['old' => [...], 'new' => [...]]  — vacío si no hubo cambios
     */
    protected function diffProperties(array $before, array $after, array $exclude = ['password', 'updated_at', 'created_at']): array
    {
        $old = [];
        $new = [];

        foreach ($after as $key => $value) {
            if (in_array($key, $exclude)) {
                continue;
            }
            $beforeVal = $before[$key] ?? null;
            $afterVal = $value;

            // Extraer el valor real si se trata de un Enum (ej. StudentStatus)
            if ($beforeVal instanceof \BackedEnum) {
                $beforeVal = $beforeVal->value;
            } elseif ($beforeVal instanceof \UnitEnum) {
                $beforeVal = $beforeVal->name;
            }

            if ($afterVal instanceof \BackedEnum) {
                $afterVal = $afterVal->value;
            } elseif ($afterVal instanceof \UnitEnum) {
                $afterVal = $afterVal->name;
            }

            // Comparamos como string para manejar null y tipos mixtos
            if ((string)$beforeVal !== (string)$afterVal) {
                $old[$key] = $before[$key] ?? null; // guardamos el objeto original si es necesario, o el valor
                $new[$key] = $value;
            }
        }

        if (empty($old)) {
            return [];
        }

        return ['old' => $old, 'new' => $new];
    }
}
