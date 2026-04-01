<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $properties = [
            [
                'name' => 'Luxury Apartment',
                'bedroom' => '3',
                'bath_room' => '2',
                'bath_room_no' => '2',
                'user_id' => 1,
                'guest' => '6',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Beach House',
                'bedroom' => '4',
                'bath_room' => '3',
                'bath_room_no' => '3',
                'user_id' => 1,
                'guest' => '8',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('properties')->insert($properties);
    }
}
