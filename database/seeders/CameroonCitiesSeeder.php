<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use Illuminate\Database\Seeder;

class CameroonCitiesSeeder extends Seeder
{
    public function run(): void
    {
        Country::firstOrCreate(['code' => 'CM'], [
            'name' => 'Cameroon',
            'currency_code' => 'XAF',
            'phone_code' => '+237',
            'is_enabled' => true,
        ]);

        $cities = [
            ['name' => 'Douala', 'slug' => 'douala', 'code' => 'DLA'],
            ['name' => 'Yaoundé', 'slug' => 'yaounde', 'code' => 'YDE'],
            ['name' => 'Bamenda', 'slug' => 'bamenda', 'code' => 'BAM'],
            ['name' => 'Bafoussam', 'slug' => 'bafoussam', 'code' => 'BAF'],
            ['name' => 'Garoua', 'slug' => 'garoua', 'code' => 'GAR'],
            ['name' => 'Maroua', 'slug' => 'maroua', 'code' => 'MRA'],
            ['name' => 'Ngaoundéré', 'slug' => 'ngaoundere', 'code' => 'NGD'],
            ['name' => 'Bertoua', 'slug' => 'bertoua', 'code' => 'BTA'],
            ['name' => 'Ebolowa', 'slug' => 'ebolowa', 'code' => 'EBW'],
            ['name' => 'Buea', 'slug' => 'buea', 'code' => 'BUE'],
            ['name' => 'Kribi', 'slug' => 'kribi', 'code' => 'KRI'],
            ['name' => 'Limbe', 'slug' => 'limbe', 'code' => 'LMB'],
            ['name' => 'Dschang', 'slug' => 'dschang', 'code' => 'DSH'],
            ['name' => 'Foumban', 'slug' => 'foumban', 'code' => 'FMB'],
            ['name' => 'Kousseri', 'slug' => 'kousseri', 'code' => 'KSR'],
            ['name' => "Edéa", 'slug' => 'edea', 'code' => 'EDA'],
            ['name' => 'Kumbo', 'slug' => 'kumbo', 'code' => 'KMB'],
            ['name' => 'Nkongsamba', 'slug' => 'nkongsamba', 'code' => 'NKS'],
            ['name' => 'Tiko', 'slug' => 'tiko', 'code' => 'TKO'],
            ['name' => 'Mbalmayo', 'slug' => 'mbalmayo', 'code' => 'MBM'],
        ];

        foreach ($cities as $city) {
            City::firstOrCreate(['code' => $city['code']], [
                'country_code' => 'CM',
                'name' => $city['name'],
                'slug' => $city['slug'],
                'is_enabled' => true,
            ]);
        }
    }
};