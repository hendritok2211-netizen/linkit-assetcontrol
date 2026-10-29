<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Laptop;
use Illuminate\Database\Seeder;

class LaptopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $division = Division::query()->where('name', 'IT Support')->firstOrFail();

        foreach ([
            ['LT-0001', 'LKIT-24-001', 'Lenovo', 'ThinkPad E14 Gen 5'],
            ['LT-0002', 'LKIT-24-002', 'Dell', 'Latitude 5440'],
            ['LT-0003', 'LKIT-24-003', 'HP', 'ProBook 440 G10'],
            ['LT-0004', 'LKIT-24-004', 'Asus', 'ExpertBook B1'],
            ['LT-0005', 'LKIT-24-005', 'Lenovo', 'ThinkPad L14 Gen 4'],
        ] as [$assetCode, $serialNumber, $brand, $model]) {
            Laptop::query()->firstOrCreate(
                ['asset_code' => $assetCode],
                [
                    'serial_number' => $serialNumber,
                    'brand' => $brand,
                    'model' => $model,
                    'purchase_date' => now()->subMonths(6)->toDateString(),
                    'division_id' => $division->id,
                    'status' => 'available',
                    'condition' => 'good',
                ],
            );
        }
    }
}
