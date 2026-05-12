<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $curriculum = [
            '1er Año' => [
                ['name' => 'Castellano', 'code' => 'CAS1'],
                ['name' => 'Inglés y Otras Lenguas Extranjeras', 'code' => 'ING1'],
                ['name' => 'Matemáticas', 'code' => 'MAT1'],
                ['name' => 'Educación Física', 'code' => 'EDF1'],
                ['name' => 'Arte y Patrimonio', 'code' => 'ART1'],
                ['name' => 'Ciencias Naturales', 'code' => 'CIN1'],
                ['name' => 'Geografía, Historia y Ciudadanía', 'code' => 'GHC1'],
                ['name' => 'Orientación y Convivencia', 'code' => 'ORC1'],
                ['name' => 'Grupos de Creación, Recreación y Producción', 'code' => 'GCRP1'],
            ],
            '2do Año' => [
                ['name' => 'Castellano', 'code' => 'CAS2'],
                ['name' => 'Inglés y Otras Lenguas Extranjeras', 'code' => 'ING2'],
                ['name' => 'Matemáticas', 'code' => 'MAT2'],
                ['name' => 'Educación Física', 'code' => 'EDF2'],
                ['name' => 'Arte y Patrimonio', 'code' => 'ART2'],
                ['name' => 'Ciencias Naturales', 'code' => 'CIN2'],
                ['name' => 'Geografía, Historia y Ciudadanía', 'code' => 'GHC2'],
                ['name' => 'Orientación y Convivencia', 'code' => 'ORC2'],
                ['name' => 'Grupos de Creación, Recreación y Producción', 'code' => 'GCRP2'],
            ],
            '3er Año' => [
                ['name' => 'Castellano', 'code' => 'CAS3'],
                ['name' => 'Inglés y Otras Lenguas Extranjeras', 'code' => 'ING3'],
                ['name' => 'Matemáticas', 'code' => 'MAT3'],
                ['name' => 'Educación Física', 'code' => 'EDF3'],
                ['name' => 'Física', 'code' => 'FIS3'],
                ['name' => 'Química', 'code' => 'QUI3'],
                ['name' => 'Biología', 'code' => 'BIO3'],
                ['name' => 'Geografía, Historia y Ciudadanía', 'code' => 'GHC3'],
                ['name' => 'Orientación y Convivencia', 'code' => 'ORC3'],
                ['name' => 'Grupos de Creación, Recreación y Producción', 'code' => 'GCRP3'],
            ],
            '4to Año' => [
                ['name' => 'Castellano', 'code' => 'CAS4'],
                ['name' => 'Inglés y Otras Lenguas Extranjeras', 'code' => 'ING4'],
                ['name' => 'Matemáticas', 'code' => 'MAT4'],
                ['name' => 'Educación Física', 'code' => 'EDF4'],
                ['name' => 'Física', 'code' => 'FIS4'],
                ['name' => 'Química', 'code' => 'QUI4'],
                ['name' => 'Biología', 'code' => 'BIO4'],
                ['name' => 'Geografía, Historia y Ciudadanía', 'code' => 'GHC4'],
                ['name' => 'Orientación y Convivencia', 'code' => 'ORC4'],
                ['name' => 'Grupos de Creación, Recreación y Producción', 'code' => 'GCRP4'],
                ['name' => 'Formación para la Soberanía Nacional', 'code' => 'FSN4'],
            ],
            '5to Año' => [
                ['name' => 'Castellano', 'code' => 'CAS5'],
                ['name' => 'Inglés y Otras Lenguas Extranjeras', 'code' => 'ING5'],
                ['name' => 'Matemáticas', 'code' => 'MAT5'],
                ['name' => 'Educación Física', 'code' => 'EDF5'],
                ['name' => 'Física', 'code' => 'FIS5'],
                ['name' => 'Química', 'code' => 'QUI5'],
                ['name' => 'Biología', 'code' => 'BIO5'],
                ['name' => 'Geografía, Historia y Ciudadanía', 'code' => 'GHC5'],
                ['name' => 'Orientación y Convivencia', 'code' => 'ORC5'],
                ['name' => 'Grupos de Creación, Recreación y Producción', 'code' => 'GCRP5'],
                ['name' => 'Formación para la Soberanía Nacional', 'code' => 'FSN5'],
                ['name' => 'Ciencias de la Tierra', 'code' => 'CDT5'],
            ],
        ];

        foreach ($curriculum as $levelName => $subjects) {
            $level = GradeLevel::where('name', $levelName)->first();
            if (!$level) continue;

            foreach ($subjects as $subjectData) {
                Subject::updateOrCreate(
                    ['code' => $subjectData['code']],
                    [
                        'name' => $subjectData['name'],
                        'grade_level_id' => $level->id,
                        'weight' => 10,
                    ]
                );
            }
        }
    }
}
