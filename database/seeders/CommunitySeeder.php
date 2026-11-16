<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Community;
use Illuminate\Database\Seeder;

class CommunitySeeder extends Seeder
{
    public function run(): void
    {
        // Comunidades y sectores de Lagunillas, Municipio Sucre, Estado Mérida, Venezuela
        $communities = [
            'Casco Central',
            'Pueblo Viejo',
            'Pueblo Nuevo',
            'Agua de Urao',
            'Conjunto Residencial Urao',
            'La Alameda',
            'El Molino',
            'San Miguel',
            'La Hoyada',
            'La Calera',
            'Sector Belén',
            'La Punta',
            'La Trinidad',
            'Las Trincheras',
            'La Alegría',
            'La Huerta',
            'La Joya',
            'Los Araques',
            'Mucumbó',
            'Mucumí',
            'Quinanoque',
            'San Benito',
            'San Juan',
            'San Martín',
            'Llano Seco',
            'Loma de Corozo',
            'Loma de Piedra',
            'Los Llanitos',
            'Los Curos',
            'Mocoyón',
            'Otra Comunidad / Sector',
        ];

        foreach ($communities as $name) {
            Community::updateOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }
    }
}
