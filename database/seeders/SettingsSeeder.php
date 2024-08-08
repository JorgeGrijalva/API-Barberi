<?php

namespace Database\Seeders;

use App\Models\Settings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $items = [
            [
                'key'   => 'is_demo',
                'value' => true
            ]
        ];

        foreach ($items as $item) {
            Settings::updateOrCreate([
                'key'   => data_get($item, 'key'),
            ], [
                'value' => data_get($item, 'value')
            ]);
        }
    }
}
