<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        // Información Adicional
        'lives_with',
        'family_group_count',
        'religion',
        'extracurricular_activities',
        'extracurricular_activities_detail',
        'has_canaima',
        'uses_canteen',
        // Información Médica
        'has_medical_condition',
        'medical_condition_detail',
        'has_allergies',
        'allergies_detail',
        'requires_pe_medication',
        'has_medical_certificate',
        'blood_type',
        'height',
        'weight',
        'doctor_visit_frequency',
        // Información de Habitación
        'housing_type',
        'housing_tenure',
        'housing_condition',
        'has_water',
        'has_electricity',
        'has_internet',
        'additional_services',
        // Información de Comunidad
        'community_name',
        'belongs_to_communal_council',
        'functioning_services',
        'services_quality',
        'community_institutions',
        // Recaudos y Consignación
        'documents_consignment',
    ];

    protected $casts = [
        'lives_with'                  => 'array',
        'family_group_count'          => 'integer',
        'extracurricular_activities'  => 'boolean',
        'has_canaima'                 => 'boolean',
        'uses_canteen'                => 'boolean',
        'has_medical_condition'       => 'boolean',
        'has_allergies'               => 'boolean',
        'requires_pe_medication'      => 'boolean',
        'has_medical_certificate'     => 'boolean',
        'has_water'                   => 'boolean',
        'has_electricity'             => 'boolean',
        'has_internet'                => 'boolean',
        'belongs_to_communal_council' => 'boolean',
        'functioning_services'        => 'array',
        'community_institutions'      => 'array',
        'documents_consignment'       => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
