<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Shop;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'shop_id' => Shop::inRandomOrder()->first(),
            'category_id' => Category::inRandomOrder()->first(),
            'brand_id' => Brand::inRandomOrder()->first(),
            'unit_id' => Unit::inRandomOrder()->first(),
            'tax' => rand(5,10),
            'active' => true,
            'img' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
