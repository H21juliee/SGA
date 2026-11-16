<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Desactivar sectores anteriores de Caracas sin romper claves foráneas existentes
        $legacyCaracas = [
            'La Floresta',
            'Las Acacias',
            'San José',
            'El Paraíso',
            'La Candelaria',
            'Santa Rosa',
            'Los Rosales',
            'Bella Vista',
            'El Valle',
        ];

        DB::table('communities')
            ->whereIn('name', $legacyCaracas)
            ->update([
                'is_active'  => false,
                'updated_at' => now(),
            ]);

        // Registrar / activar comunidades y sectores de Lagunillas, Municipio Sucre, Estado Mérida
        $lagunillasCommunities = [
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

        foreach ($lagunillasCommunities as $name) {
            $exists = DB::table('communities')->where('name', $name)->exists();

            if ($exists) {
                DB::table('communities')
                    ->where('name', $name)
                    ->update([
                        'is_active'  => true,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('communities')->insert([
                    'name'       => $name,
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $legacyCaracas = [
            'La Floresta',
            'Las Acacias',
            'San José',
            'El Paraíso',
            'La Candelaria',
            'Santa Rosa',
            'Los Rosales',
            'Bella Vista',
            'El Valle',
        ];

        DB::table('communities')
            ->whereIn('name', $legacyCaracas)
            ->update([
                'is_active'  => true,
                'updated_at' => now(),
            ]);
    }
};
