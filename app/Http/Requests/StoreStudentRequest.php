<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('students.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('first_name')) {
            $cleaned = preg_replace('/\s+/', ' ', trim((string) $this->input('first_name')));
            $merge['first_name'] = mb_strtoupper($cleaned, 'UTF-8');
        }
        if ($this->has('last_name')) {
            $cleaned = preg_replace('/\s+/', ' ', trim((string) $this->input('last_name')));
            $merge['last_name'] = mb_strtoupper($cleaned, 'UTF-8');
        }
        if ($this->has('birth_place')) {
            $merge['birth_place'] = mb_strtoupper(trim((string) $this->input('birth_place')), 'UTF-8');
        }
        if ($this->has('nationality')) {
            $merge['nationality'] = strtoupper(trim((string) $this->input('nationality')));
        }

        if ($this->has('cedula')) {
            $rawCedula = trim((string) $this->input('cedula'));
            $merge['cedula'] = empty($rawCedula) ? null : strtoupper($rawCedula);
        }

        if ($this->has('address')) {
            $merge['address'] = mb_strtoupper(trim((string) $this->input('address')), 'UTF-8');
        }

        if ($this->has('email')) {
            $rawEmail = trim((string) $this->input('email'));
            $merge['email'] = empty($rawEmail) ? null : mb_strtolower($rawEmail, 'UTF-8');
        }

        if ($this->has('phone_home')) {
            $merge['phone_home'] = $this->formatPhone($this->input('phone_home'));
        }
        if ($this->has('phone_mobile')) {
            $merge['phone_mobile'] = $this->formatPhone($this->input('phone_mobile'));
        }

        if ($this->has('profile') && is_array($this->input('profile'))) {
            $profile = $this->input('profile');
            $textKeys = [
                'extracurricular_activities_detail',
                'medical_condition_detail',
                'allergies_detail',
                'additional_services',
                'community_name',
            ];
            foreach ($textKeys as $k) {
                if (isset($profile[$k]) && is_string($profile[$k])) {
                    $profile[$k] = mb_strtoupper(trim($profile[$k]), 'UTF-8');
                }
            }

            // Normalización de talla (estatura en cm)
            if (isset($profile['height']) && $profile['height'] !== '' && $profile['height'] !== null) {
                $rawHeight = str_replace(',', '.', trim((string) $profile['height']));
                $rawHeight = preg_replace('/[^\d.]/', '', $rawHeight);
                if (is_numeric($rawHeight)) {
                    $numH = (float) $rawHeight;
                    if ($numH > 0 && $numH <= 2.5) {
                        $numH = round($numH * 100);
                    }
                    $profile['height'] = (string) $numH;
                } else {
                    $profile['height'] = trim((string) $profile['height']);
                }
            } else {
                $profile['height'] = null;
            }

            // Normalización de peso corporal (en kg)
            if (isset($profile['weight']) && $profile['weight'] !== '' && $profile['weight'] !== null) {
                $rawWeight = str_replace(',', '.', trim((string) $profile['weight']));
                $rawWeight = preg_replace('/[^\d.]/', '', $rawWeight);
                $profile['weight'] = $rawWeight !== '' ? $rawWeight : null;
            } else {
                $profile['weight'] = null;
            }

            $merge['profile'] = $profile;
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    private function formatPhone(?string $phone): ?string
    {
        if (empty($phone)) return null;
        $clean = trim($phone);
        if (preg_match('/^(0[24]\d{2})(\d{7})$/', $clean, $m)) {
            return "{$m[1]}-{$m[2]}";
        }
        return $clean;
    }

    public function rules(): array
    {
        $minDate = now()->subYears(20)->format('Y-m-d');
        $maxDate = now()->subYears(10)->format('Y-m-d');

        return [
            // Inf. Principal
            'first_name'   => ['required', 'string', 'max:100'],
            'last_name'    => ['required', 'string', 'max:100'],
            'nationality'  => ['required', 'string', 'in:V,E'],
            'cedula'       => [
                'nullable',
                'string',
                'max:20',
                'regex:/^(V-\d{6,9}|E-\d{6,9}|[VE][1-9]\d{10}|CE-\d{10,15}|P-[A-Z0-9]{6,15})$/',
                'unique:students,cedula',
            ],
            'birth_date'   => [
                'required',
                'date',
                "after_or_equal:{$minDate}",
                "before_or_equal:{$maxDate}",
            ],
            'birth_place'  => ['required', 'string', 'max:255'],
            'gender'       => ['required', 'in:M,F'],
            'address'      => ['required', 'string', 'max:500'],
            'community_id' => ['required', 'exists:communities,id'],
            'email'        => ['nullable', 'email:rfc', 'max:255'],
            'phone_home'   => ['nullable', 'string', 'regex:/^(02\d{2}-\d{7}|0[24]\d{2}-\d{7})$/'],
            'phone_mobile' => ['nullable', 'string', 'regex:/^(0412|0414|0424|0416|0426|02\d{2})-\d{7}$/'],
            'guardian_id'  => ['required', 'exists:guardians,id'],

            // Inf. Adicional
            'profile.lives_with'                        => ['nullable', 'array'],
            'profile.family_group_count'                => ['nullable', 'integer', 'min:1', 'max:50'],
            'profile.religion'                          => ['nullable', 'string', 'max:100'],
            'profile.extracurricular_activities'        => ['nullable', 'boolean'],
            'profile.extracurricular_activities_detail' => ['nullable', 'string', 'max:255', 'required_if:profile.extracurricular_activities,true,1'],
            'profile.has_canaima'                       => ['nullable', 'boolean'],
            'profile.uses_canteen'                      => ['nullable', 'boolean'],

            // Inf. Médica
            'profile.has_medical_condition'             => ['nullable', 'boolean'],
            'profile.medical_condition_detail'          => ['nullable', 'string', 'max:255', 'required_if:profile.has_medical_condition,true,1'],
            'profile.has_allergies'                     => ['nullable', 'boolean'],
            'profile.allergies_detail'                  => ['nullable', 'string', 'max:255', 'required_if:profile.has_allergies,true,1'],
            'profile.requires_pe_medication'            => ['nullable', 'boolean'],
            'profile.has_medical_certificate'           => ['nullable', 'boolean'],
            'profile.blood_type'                        => ['nullable', 'string', 'max:10'],
            'profile.height'                            => ['nullable', 'numeric', 'min:90', 'max:230', 'decimal:0,2'],
            'profile.weight'                            => ['nullable', 'numeric', 'min:20', 'max:200', 'decimal:0,2'],
            'profile.doctor_visit_frequency'            => ['nullable', 'string', 'max:50'],

            // Inf. Habitación
            'profile.housing_type'                      => ['nullable', 'string', 'max:50'],
            'profile.housing_tenure'                    => ['nullable', 'string', 'max:50'],
            'profile.housing_condition'                 => ['nullable', 'string', 'max:50'],
            'profile.has_water'                         => ['nullable', 'boolean'],
            'profile.has_electricity'                   => ['nullable', 'boolean'],
            'profile.has_internet'                      => ['nullable', 'boolean'],
            'profile.additional_services'               => ['nullable', 'string', 'max:500'],

            // Inf. Comunidad
            'profile.community_name'                    => ['nullable', 'string', 'max:150'],
            'profile.belongs_to_communal_council'       => ['nullable', 'boolean'],
            'profile.functioning_services'              => ['nullable', 'array'],
            'profile.services_quality'                  => ['nullable', 'string', 'max:50'],
            'profile.community_institutions'            => ['nullable', 'array'],
            'profile.documents_consignment'             => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required'   => 'Los nombres del estudiante son obligatorios.',
            'last_name.required'    => 'Los apellidos del estudiante son obligatorios.',
            'nationality.required'  => 'Debe indicar la nacionalidad del estudiante.',
            'nationality.in'        => 'La nacionalidad debe ser Venezolana (V) o Extranjera (E).',
            'birth_date.required'   => 'La fecha de nacimiento es obligatoria.',
            'birth_date.before_or_equal' => 'El estudiante debe tener al menos 10 años de edad.',
            'birth_date.after_or_equal'  => 'El estudiante no puede tener más de 20 años de edad.',
            'birth_place.required'  => 'El lugar de nacimiento es obligatorio.',
            'gender.required'       => 'Debe seleccionar el sexo del estudiante.',
            'address.required'      => 'La dirección de habitación es obligatoria.',
            'community_id.required' => 'Debe seleccionar la comunidad a la que pertenece.',
            'community_id.exists'   => 'La comunidad seleccionada no es válida.',
            'cedula.regex'          => 'Formato de documento inválido. Use Cédula SAIME V- (ej: V-30123456), E- (ej: E-84550210), Cédula Escolar oficial MPPE de 12 caracteres (ej: V11214200350) o Pasaporte P-.',
            'cedula.unique'         => 'Este número de cédula o documento ya está registrado con otro estudiante.',
            'email.email'           => 'El correo electrónico no tiene un formato válido.',
            'phone_home.regex'      => 'El teléfono fijo debe tener un código de área y formato válido en Venezuela (ej: 0212-1234567).',
            'phone_mobile.regex'    => 'El teléfono móvil debe tener un formato válido en Venezuela (ej: 0412-1234567, 0414-1234567, 0424-1234567).',
            'guardian_id.required'  => 'Debe asociar un representante legal al estudiante.',
            'guardian_id.exists'    => 'El representante seleccionado no existe.',
            'profile.extracurricular_activities_detail.required_if' => 'Indique qué actividad extraescolar practica.',
            'profile.medical_condition_detail.required_if'          => 'Indique qué enfermedad o tratamiento posee.',
            'profile.allergies_detail.required_if'                  => 'Indique a qué es alérgico el estudiante.',
            'profile.height.numeric'                                => 'La talla (estatura) debe ser un valor numérico.',
            'profile.height.min'                                    => 'La talla debe ser de al menos 90 cm.',
            'profile.height.max'                                    => 'La talla no debe exceder los 230 cm.',
            'profile.height.decimal'                                => 'La talla (estatura) debe tener como máximo 2 cifras decimales (ej: 165.50 cm).',
            'profile.weight.numeric'                                => 'El peso corporal debe ser un valor numérico.',
            'profile.weight.min'                                    => 'El peso corporal debe ser de al menos 20 kg.',
            'profile.weight.max'                                    => 'El peso corporal no debe exceder los 200 kg.',
            'profile.weight.decimal'                                => 'El peso corporal debe tener como máximo 2 cifras decimales (ej: 63.75 kg).',
        ];
    }

    public function passedValidation(): void
    {
        // Validación de duplicado por nombre, apellido y fecha si no tiene cédula
        if (empty($this->input('cedula')) && !empty($this->input('birth_date'))) {
            $duplicateExists = Student::where('first_name', $this->input('first_name'))
                ->where('last_name', $this->input('last_name'))
                ->whereDate('birth_date', $this->input('birth_date'))
                ->exists();

            if ($duplicateExists) {
                throw ValidationException::withMessages([
                    'first_name' => 'Ya existe un estudiante registrado con estos mismos nombres, apellidos y fecha de nacimiento.',
                ]);
            }
        }
    }
}
