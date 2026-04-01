<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            'New York',
            'Los Angeles',
            'Chicago',
            'Houston',
            'Paris',
            'London',
            'Tokyo',
            'Sydney',
        ];


        foreach ($cities as $city) {
            DB::table('cities')->insert([
                'name' => $city,
                'slug' => Str::slug($city, '-'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
