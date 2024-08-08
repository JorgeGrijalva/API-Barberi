<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\City;
use App\Models\Country;
use App\Models\Language;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Str;

class RegionSeeder extends Seeder
{
    //Asia, Africa, North America, South America, Europe, Oceania, Americas, Polar
    private array $regionAllow = [
        '*',
//        'Asia',
//        'Africa',
//        'North America',
//        'South America',
//        'Europe',
//        'Oceania',
//        'America',
//        'Polar',
    ];

    private array $countryAllow = [
        '*',
//        'Antarctica'
    ];

    private array $cityAllow = [
        '*'
    ];

    private array $areaAllow = [
//        '*'
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $default = data_get(Language::where('default', 1)->first(), 'locale', 'en');

        $data = collect(json_decode(file_get_contents('database/seeders/json/countries.json')))->lazy();

        foreach ($data as $item) {

            $title = data_get($item, 'region');

            if ($title == 'Americas') {
                $title = 'America';
            }

            if (!in_array($title, $this->regionAllow) && !in_array('*', $this->regionAllow)) {
                continue;
            }

            $region  = $this->region($item, $default, $title);

            if (!in_array(data_get($item, 'name'), $this->countryAllow) && !in_array('*', $this->countryAllow)) {
                continue;
            }

            $country = $this->country($region, $item, $default);

            if (count($this->cityAllow) === 0) {
                continue;
            }

            foreach (data_get($item, 'states', []) as $state) {

                if (!in_array(data_get($state, 'name'), $this->cityAllow) && !in_array('*', $this->cityAllow)) {
                    continue;
                }

                $city = $this->city($region, $country, $state, $default);

//                if (count($this->areaAllow) === 0) {
//                    continue;
//                }
//
//                foreach (data_get($state, 'cities', []) as $area) {
//
//                    if (!in_array(data_get($area, 'name'), $this->areaAllow) && !in_array('*', $this->areaAllow)) {
//                        continue;
//                    }
//
//                    $this->area($region, $country, $city, $area, $default);
//
//                }

            }

        }

    }

    /**
     * @param object $item
     * @param string $default
     * @param string $title
     * @return Region
     */
    private function region(object $item, string $default, string $title): Region
    {

        $region = Region::whereHas('translation', function ($q) use ($item, $default, $title) {
            $q->where('locale', $default)->where('title', $title);
        })->first();

        if (empty($region)) {

            $region = Region::create([
                'active' => true
            ]);

            $region->translations()->create([
                'title'  => $title,
                'locale' => $default,
            ]);

            $this->command->info("region: $title");
        }

        return $region;
    }

    /**
     * @param Region $region
     * @param object $item
     * @param string $default
     * @return Country
     */
    private function country(Region $region, object $item, string $default): Country
    {

        $country = Country::whereHas('translation', function ($q) use ($item, $default) {
            $q->where('locale', $default)->where('title', data_get($item, 'name'));
        })->first();

        if (empty($country)) {

            $iso2 = Str::lower(data_get($item, 'iso2'));

            $country = Country::create([
                'region_id' => $region->id,
                'code'      => $iso2,
                'active'    => true,
                'img'       => "https://flagcdn.com/h120/$iso2.png"
            ]);

            $country->translations()->create([
                'title'  => data_get($item, 'name'),
                'locale' => $default,
            ]);

            $this->command->info('country: ' . data_get($item, 'name'));

        }

        return $country;
    }

    /**
     * @param Region $region
     * @param Country $country
     * @param object $item
     * @param string $default
     * @return City
     */
    private function city(Region $region, Country $country, object $item, string $default): City
    {

        $city = City::whereHas('translation', function ($q) use ($item, $default) {
            $q->where('locale', $default)->where('title', data_get($item, 'name'));
        })->first();

        if (empty($city)) {

            $city = City::create([
                'active'        => true,
                'region_id'     => $region->id,
                'country_id'    => $country->id,
            ]);

            $city->translations()->create([
                'title'  => data_get($item, 'name'),
                'locale' => $default,
            ]);

            $this->command->info('city: ' . data_get($item, 'name'));
            $this->command->info('start insert cities');
        }

        return $city;
    }

    /**
     * @param Region $region
     * @param Country $country
     * @param City $city
     * @param object $item
     * @param string $default
     * @return void
     */
    private function area(Region $region, Country $country, City $city, object $item, string $default): void
    {

        $area = Area::whereHas('translation', function ($q) use ($item, $default) {
            $q->where('locale', $default)->where('title', data_get($item, 'name'));
        })->first();

        if (empty($area)) {

            $area = Area::create([
                'active'        => true,
                'region_id'     => $region->id,
                'country_id'    => $country->id,
                'city_id'       => $city->id,
            ]);

            $area->translations()->create([
                'title'  => data_get($item, 'name'),
                'locale' => $default,
            ]);

            $this->command->info('area: ' . data_get($item, 'name'));
        }

    }

}
