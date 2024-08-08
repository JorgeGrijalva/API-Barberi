<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'type'          => $this->faker->word(),
            'price'         => $this->faker->randomNumber(),
            'month'         => rand(1, 12),
            'active'        => rand(0, 1),
            'title'         => $this->faker->word(),
            'product_limit' => rand(100, 10000),
            'order_limit'   => rand(100, 10000),
            'booking_limit' => rand(100, 10000),
            'with_report'   => rand(0, 1),
            'created_at'    => Carbon::now(),
            'updated_at'    => Carbon::now(),
        ];
    }
}
