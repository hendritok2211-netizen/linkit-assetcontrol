<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(DivisionSeeder::class);

        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        User::query()->updateOrCreate(
            ['email' => 'admin@linkit.local'],
            [
                'name' => 'Administrator LinkIT',
                'password' => Hash::make(env('LOCAL_ADMIN_PASSWORD', 'password')),
                'role' => 'admin',
                'is_approved' => true,
                'is_active' => true,
            ],
        );

        $this->call(LaptopSeeder::class);
    }
}
