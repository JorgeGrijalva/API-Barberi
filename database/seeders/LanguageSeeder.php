<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $languages = [
            [
                'locale' => 'en',
                'title' => 'English',
                'default' => 1,
            ]
        ];

        foreach ($languages as $language) {

            Language::updateOrCreate(['locale' => $language['locale']], $language);
        }
    }
}
