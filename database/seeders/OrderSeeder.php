<?php

namespace Database\Seeders;

use App\Models\OrderStatus;
use App\Traits\Loggable;
use Illuminate\Database\Seeder;
use Throwable;

class OrderSeeder extends Seeder
{
    use Loggable;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $orders = [
            [
                'name'      => 'new',
                'active'    => 1,
                'sort'      => 1,
            ],
            [
                'name'      => 'accepted',
                'active'    => 1,
                'sort'      => 2,
            ],
            [
                'name'      => 'ready',
                'active'    => 1,
                'sort'      => 4,
            ],
            [
                'name'      => 'on_a_way',
                'active'    => 1,
                'sort'      => 5,
            ],
            [
                'name'      => 'pause',
                'active'    => 1,
                'sort'      => 6,
            ],
            [
                'name'      => 'delivered',
                'active'    => 1,
                'sort'      => 7,
            ],
            [
                'name'      => 'canceled',
                'active'    => 1,
                'sort'      => 8,
            ],
        ];

        foreach ($orders as $order) {
            try {
                OrderStatus::updateOrCreate(['name' => data_get($order, 'name')], $order);
            } catch (Throwable $e) {
                $this->error($e);
            }
        }

    }

}
