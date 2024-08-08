<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\Order;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $notifications = [
            [
                'type'          => Notification::PUSH,
                'payload'       => null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ];

        foreach ($notifications as $notification) {
            Notification::updateOrCreate([
                'type' => data_get($notification, 'type')
            ], $notification);
        }

    }
}
