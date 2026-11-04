<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Models\Student;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;

final class SaveStudentAction
{
    /**
     * Guarda o actualiza un estudiante junto con su ficha de perfil socioeducativo/médico.
     */
    public function execute(array $data, ?Student $student = null): Student
    {
        return DB::transaction(function () use ($data, $student) {
            // 1. Extraer campos propios de students
            $studentData = [
                'first_name'   => $data['first_name'],
                'last_name'    => $data['last_name'],
                'cedula'       => $data['cedula'] ?? null,
                'nationality'  => $data['nationality'] ?? 'V',
                'birth_date'   => $data['birth_date'] ?? null,
                'birth_place'  => $data['birth_place'] ?? null,
                'gender'       => $data['gender'],
                'address'      => $data['address'] ?? null,
                'community_id' => $data['community_id'] ?? null,
                'email'        => $data['email'] ?? null,
                'phone_home'   => $data['phone_home'] ?? null,
                'phone_mobile' => $data['phone_mobile'] ?? null,
                'guardian_id'  => $data['guardian_id'] ?? null,
            ];

            if (isset($data['status'])) {
                $studentData['status'] = $data['status'];
            }

            if ($student) {
                $student->update($studentData);
            } else {
                $student = Student::create($studentData);
            }

            // 2. Extraer campos de student_profiles (pueden venir anidados en 'profile' o planos)
            $rawProfile = $data['profile'] ?? $data;

            $profileData = [
                // Adicional
                'lives_with'                         => $rawProfile['lives_with'] ?? [],
                'family_group_count'                 => isset($rawProfile['family_group_count']) && $rawProfile['family_group_count'] !== '' ? (int) $rawProfile['family_group_count'] : null,
                'religion'                           => $rawProfile['religion'] ?? null,
                'extracurricular_activities'         => (bool) ($rawProfile['extracurricular_activities'] ?? false),
                'extracurricular_activities_detail'  => ($rawProfile['extracurricular_activities'] ?? false) ? ($rawProfile['extracurricular_activities_detail'] ?? null) : null,
                'has_canaima'                        => (bool) ($rawProfile['has_canaima'] ?? false),
                'uses_canteen'                       => (bool) ($rawProfile['uses_canteen'] ?? false),

                // Médica
                'has_medical_condition'              => (bool) ($rawProfile['has_medical_condition'] ?? false),
                'medical_condition_detail'           => ($rawProfile['has_medical_condition'] ?? false) ? ($rawProfile['medical_condition_detail'] ?? null) : null,
                'has_allergies'                      => (bool) ($rawProfile['has_allergies'] ?? false),
                'allergies_detail'                   => ($rawProfile['has_allergies'] ?? false) ? ($rawProfile['allergies_detail'] ?? null) : null,
                'requires_pe_medication'             => (bool) ($rawProfile['requires_pe_medication'] ?? false),
                'has_medical_certificate'            => (bool) ($rawProfile['has_medical_certificate'] ?? false),
                'blood_type'                         => $rawProfile['blood_type'] ?? null,
                'height'                             => $rawProfile['height'] ?? null,
                'weight'                             => $rawProfile['weight'] ?? null,
                'doctor_visit_frequency'             => $rawProfile['doctor_visit_frequency'] ?? null,

                // Habitación
                'housing_type'                       => $rawProfile['housing_type'] ?? null,
                'housing_tenure'                     => $rawProfile['housing_tenure'] ?? null,
                'housing_condition'                  => $rawProfile['housing_condition'] ?? null,
                'has_water'                          => (bool) ($rawProfile['has_water'] ?? false),
                'has_electricity'                    => (bool) ($rawProfile['has_electricity'] ?? false),
                'has_internet'                       => (bool) ($rawProfile['has_internet'] ?? false),
                'additional_services'                => $rawProfile['additional_services'] ?? null,

                // Comunidad
                'community_name'                     => $rawProfile['community_name'] ?? null,
                'belongs_to_communal_council'        => (bool) ($rawProfile['belongs_to_communal_council'] ?? false),
                'functioning_services'               => $rawProfile['functioning_services'] ?? [],
                'services_quality'                   => $rawProfile['services_quality'] ?? null,
                'community_institutions'             => $rawProfile['community_institutions'] ?? [],
                // Recaudos
                'documents_consignment'              => $rawProfile['documents_consignment'] ?? [],
            ];

            $student->profile()->updateOrCreate(
                ['student_id' => $student->id],
                $profileData
            );

            return $student->load(['profile', 'guardian', 'community']);
        });
    }
}
