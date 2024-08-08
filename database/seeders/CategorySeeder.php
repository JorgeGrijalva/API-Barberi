<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Language;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $locale = Language::first();

        foreach (Category::TYPES as $key => $value) {
            $category = Category::create([
                'keywords' => $key,
                'type' => $value,
            ]);

            CategoryTranslation::create([
                'category_id' => $category->id,
                'locale'      => data_get($locale, 'locale', 'en'),
                'title'       => $key
            ]);
        }

        Category::whereIn('type', [Category::SUB_MAIN, Category::CHILD])->update([
            'parent_id' => Category::where('type', Category::MAIN)->first()?->id,
        ]);
    }
}
