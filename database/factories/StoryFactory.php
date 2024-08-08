<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class StoryFactory extends Factory
{
	public function definition(): array
	{
		return [
			'file_urls' => $this->faker->words(),
			'active' => $this->faker->boolean(),
			'created_at' => Carbon::now(),
			'updated_at' => Carbon::now(),

			'product_id' => Product::factory(),
			'shop_id' => Shop::factory(),
		];
	}
}
