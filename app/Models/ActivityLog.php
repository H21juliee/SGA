<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ActivityLog extends Model
{
    // Este modelo es append-only. Solo existe created_at.
    public $timestamps  = false;
    public $updatedAt   = false;

    protected $fillable = [
        'user_id',
        'module',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    // ── Relaciones ───────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    /** Filtra por módulo */
    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    /** Filtra por tipo de acción */
    public function scopeForAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /** Solo registros con datos before/after (para la pestaña Historial) */
    public function scopeWithChanges(Builder $query): Builder
    {
        return $query->whereNotNull('properties');
    }

    /** Filtra por rango de fechas */
    public function scopeInDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        return $query;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Etiquetas legibles para los módulos.
     */
    public static function moduleLabel(string $module): string
    {
        return match($module) {
            'estudiantes'    => 'Estudiantes',
            'inscripciones'  => 'Inscripciones',
            'usuarios'       => 'Usuarios',
            'roles'          => 'Roles y Permisos',
            'años_escolares' => 'Años Escolares',
            'secciones'      => 'Secciones',
            'materias'       => 'Materias',
            'carga_academica'=> 'Carga Académica',
            'consejo'        => 'Ajuste de Consejo',
            'revisiones'     => 'Revisiones',
            'configuracion'  => 'Configuración',
            'importacion'    => 'Importación',
            default          => ucfirst($module),
        };
    }

    /**
     * Etiquetas legibles para las acciones.
     */
    public static function actionLabel(string $action): string
    {
        return match($action) {
            'created'          => 'Creado',
            'updated'          => 'Editado',
            'deleted'          => 'Eliminado',
            'imported'         => 'Importado',
            'promoted'         => 'Promoción',
            'council_updated'  => 'Ajuste Consejo',
            'revision_updated' => 'Nota Revisión',
            default            => ucfirst($action),
        };
    }
}
