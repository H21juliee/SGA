<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Community;
use Illuminate\Database\Seeder;

class CommunitySeeder extends Seeder
{
    public function run(): void
    {
        $communities = [
            'Casco Central',
            'La Floresta',
            'Las Acacias',
            'San José',
            'El Paraíso',
            'La Candelaria',
            'Santa Rosa',
            'Los Rosales',
            'Bella Vista',
            'El Valle',
            'Otra Comunidad / Sector',
        ];

        foreach ($communities as $name) {
            Community::firstOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }
    }
}
