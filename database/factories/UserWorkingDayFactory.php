<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class UserWorkingDayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'user_id'       => User::inRandomOrder()->first(),
            'day'           => $this->faker->word(),
            'from'          => '9-00',
            'to'            => '21-00',
            'disabled'      => rand(0,1),
            'created_at'    => Carbon::now(),
            'updated_at'    => Carbon::now(),
        ];
    }
}
