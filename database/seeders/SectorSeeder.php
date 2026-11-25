<?php

namespace Database\Seeders;

use App\Modules\Sector\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        $sectors = [
            ['name' => 'Education', 'code' => 'education'],
            ['name' => 'Health', 'code' => 'health'],
            ['name' => 'Agriculture', 'code' => 'agriculture'],
            ['name' => 'WASH', 'code' => 'wash'],
            ['name' => 'Other', 'code' => 'other'],
        ];

        foreach ($sectors as $sector) {
            Sector::query()->updateOrCreate(
                ['code' => $sector['code']],
                [
                    'name' => $sector['name'],
                    'is_system' => true,
                    'status' => 'active',
                ],
            );
        }
    }
}
