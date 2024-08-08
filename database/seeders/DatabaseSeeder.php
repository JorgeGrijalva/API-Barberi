<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\CityTranslation;
use App\Models\Country;
use App\Models\CountryTranslation;
use App\Models\Region;
use App\Models\RegionTranslation;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run(): void
    {
        $this->call(LanguageSeeder::class);
        $this->call(CurrencySeeder::class);
        $this->call(NotificationSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(CategorySeeder::class);
        $this->call(ShopTagSeeder::class);
        $this->call(PaymentSeeder::class);
        $this->call(SubscriptionSeeder::class);
        $this->call(TranslationSeeder::class);
        $this->call(EmailSettingSeeder::class);
        $this->call(SmsGatewaySeeder::class);
        $this->call(UnitSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(OrderSeeder::class);
//        $this->call(RegionSeeder::class);

//        if (app()->environment() == 'local') {
//            Category::factory()->hasTranslations(1)->count(10)->create();
//            Brand::factory()->count(10)->create();
//            ExtraGroup::factory()->hasTranslation(1)->hasExtraValues(3)->count(5)->create();
//            User::factory()->has(
//                Shop::factory()->hasTranslation(1)->has(
//                    Product::factory()->hasTranslation(1)->hasExtras(2)->hasProperties(10)->count(rand(10,30))
//                )->count(1)
//            )->count(100)->create();
//            Order::factory()->has(OrderDetail::factory()->hasProducts(2)->count(3))->count(10)->create();
//        }
    }
}
