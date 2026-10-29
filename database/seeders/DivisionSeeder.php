<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['name' => 'IT Support', 'description' => 'Dukungan teknis dan pengelolaan perangkat.'],
            ['name' => 'Back End', 'description' => 'Pengembangan dan pemeliharaan layanan back end.'],
            ['name' => 'Data Analyst', 'description' => 'Pengolahan dan analisis data.'],
        ] as $division) {
            Division::query()->updateOrCreate(
                ['name' => $division['name']],
                ['description' => $division['description']],
            );
        }
    }
}
