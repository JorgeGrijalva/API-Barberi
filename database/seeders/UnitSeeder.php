<?php

namespace Database\Seeders;

use App\Models\Unit;
use App\Models\UnitTranslation;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Units
        $units = [
            [
                'id' => 1,
                'active' => 1,
                'position' => 'after',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($units as $unit) {
            Unit::updateOrCreate(['id' => $unit['id']], $unit);
        }

        // Unit Languages
        $unitLang = [
            [
                'id' => 1,
                'unit_id' => 1,
                'locale' => 'en',
                'title' => 'PCS',
            ],
        ];

        foreach ($unitLang as $lang) {
            UnitTranslation::updateOrCreate(['id' => $lang['id']], $lang);
        }
    }
}
