<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Notification;
use App\Models\Shop;
use App\Models\ShopTag;
use App\Models\ShopTranslation;
use App\Models\User;
use App\Services\UserServices\UserWalletService;
use App\Traits\Loggable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Throwable;

class UserSeeder extends Seeder
{
    use Loggable;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $users = [
            [
                'id' => 102,
                'uuid' => Str::uuid(),
                'firstname' => 'User',
                'lastname' => 'User',
                'email' => 'user@githubit.com',
                'phone' => '998911902595',
                'birthday' => '1993-12-30',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('user123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 103,
                'uuid' => Str::uuid(),
                'firstname' => 'Owner',
                'lastname' => 'Owner',
                'email' => 'owner@githubit.com',
                'phone' => '998911902696',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('githubit'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 104,
                'uuid' => Str::uuid(),
                'firstname' => 'Manager',
                'lastname' => 'Manager',
                'email' => 'manager@githubit.com',
                'phone' => '998911902616',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('manager'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 105,
                'uuid' => Str::uuid(),
                'firstname' => 'Moderator',
                'lastname' => 'Moderator',
                'email' => 'moderator@githubit.com',
                'phone' => '998911902116',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('moderator'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 106,
                'uuid' => Str::uuid(),
                'firstname' => 'Delivery',
                'lastname' => 'Delivery',
                'email' => 'delivery@githubit.com',
                'phone' => '998911912116',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('delivery'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 107,
                'uuid' => Str::uuid(),
                'firstname' => 'sellers',
                'lastname' => 'sellers',
                'email' => 'sellers@githubit.com',
                'phone' => '998911902691',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('seller'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 112,
                'uuid' => Str::uuid(),
                'firstname' => 'master',
                'lastname' => 'master',
                'email' => 'master@githubit.com',
                'phone' => '998911902694',
                'birthday' => '1990-12-31',
                'gender' => 'male',
                'email_verified_at' => now(),
                'password' => bcrypt('master'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($users as $user) {

            try {
                $user = User::updateOrCreate(['id' => data_get($user, 'id')], $user);

                (new UserWalletService)->create($user);

                $id = Notification::where('type', Notification::PUSH)
                    ->select(['id', 'type'])
                    ->first()
                    ?->id;

                $user->notifications()->sync([$id]);
            } catch (Throwable $e) {
                $this->error($e);
            }

        }

        User::find(102)?->syncRoles('user');
        User::find(103)?->syncRoles(['admin']);
        User::find(107)?->syncRoles('seller');
        User::find(104)?->syncRoles('manager');
        User::find(105)?->syncRoles('moderator');
        User::find(106)?->syncRoles('deliveryman');

        $shop = Shop::updateOrCreate([
            'user_id'           => 107,
        ], [
            'uuid'              => Str::uuid(),
            'latitude'          => -69.3453324,
            'longitude'         => 69.3453324,
            'phone'             => '+1234566',
            'open'              => 1,
            'background_img'    => 'url.webp',
            'logo_img'          => 'url.webp',
            'status'            => 'approved',
            'status_note'       => 'approved',
            'delivery_time'     => [
                'from'              => '10',
                'to'                => '90',
                'type'              => 'minute',
            ],
            'type'              => 1,
        ]);

        ShopTranslation::updateOrCreate([
            'shop_id'       => $shop->id,
        ], [
            'description'   => 'branch desc',
            'title'         => 'branch title',
            'locale'        => data_get(Language::first(), 'locale', 'en'),
            'address'       => 'address',
        ]);

        $shop->tags()->sync(ShopTag::pluck('id')->toArray());

    }

}
