<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AmentiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amenities = [
            ['name' => 'Wi-Fi', 'image' => 'build/assets/amenties/Wi-Fi.webp'],
            ['name' => 'Air Conditioning', 'image' => 'build/assets/amenties/conditioner.webp'],
            ['name' => 'Heating', 'image' => 'build/assets/amenties/Heating.webp'],
            ['name' => 'TV', 'image' => 'build/assets/amenties/TV.png'],
            ['name' => 'Washer', 'image' => 'build/assets/Washer.webp'],
            ['name' => 'Dryer', 'image' => 'build/assets/amenties/Dryer.png'],
            ['name' => 'Kitchen', 'image' => 'build/assets/amenties/kitchen.png'],
            ['name' => 'Free Parking on Premises', 'image' => 'build/assets/amenties/parking-area.png'],
            ['name' => 'Gym', 'image' => 'build/assets/amenties/gym.png'],
            ['name' => 'Pool', 'image' => 'build/assets/amenties/pool.png'],
            ['name' => 'Hot Tub', 'image' => 'build/assets/amenties/hot-tub.png'],
            ['name' => 'Elevator', 'image' => 'build/assets/amenties/elevator.png'],
            ['name' => 'Fireplace', 'image' => 'build/assets/amenties/fireplace.png'],
            ['name' => 'Smoke Alarm', 'image' => 'build/assets/amenties/fire-sensor.png'],
            ['name' => 'Hair Dryer', 'image' => 'build/assets/amenties/hairdryer.png'],
            ['name' => 'Iron', 'image' => 'build/assets/amenties/iron.png'],
            ['name' => 'Laptop-Friendly Workspace', 'image' => 'build/assets/amenties/tester.png'],
            ['name' => 'Crib', 'image' => 'build/assets/amenties/crib.png'],
            ['name' => 'High Chair', 'image' => 'build/assets/amenties/stool.png'],
            ['name' => 'Dishwasher', 'image' => 'build/assets/amenties/dishwasher.png'],
            ['name' => 'Refrigerator', 'image' => 'build/assets/amenties/refrigerator.png'],
            ['name' => 'Microwave', 'image' => 'build/assets/amenties/microwave.png'],
            ['name' => 'Stove', 'image' => 'build/assets/amenties/gas-stove.png'],
            ['name' => 'Coffee Maker', 'image' => 'build/assets/amenties/coffee-machine.png'],
            ['name' => 'Toaster', 'image' => 'build/assets/amenties/toaster.png'],
            ['name' => 'BBQ Grill', 'image' => 'build/assets/amenties/bbq.png'],
            ['name' => 'Self Check-In', 'image' => 'build/assets/amenties/self-check-in.png'],
            ['name' => 'Keypad Entry', 'image' => 'build/assets/amenties/password-entry.png'],
            ['name' => 'Lockbox', 'image' => 'build/assets/amenties/padlock.png'],
            ['name' => 'Security Cameras', 'image' => 'build/assets/amenties/cctv-camera.png'],
            ['name' => 'Private Entrance', 'image' => 'build/assets/amenties/no-entry.png'],
            ['name' => 'Long-Term Stays Allowed', 'image' => 'build/assets/amenties/future.png'],
            ['name' => 'Essentials', 'image' => 'build/assets/amenties/natural.png'],
            ['name' => 'Hangers', 'image' => 'build/assets/amenties/hanger.png'],
            ['name' => 'Shampoo', 'image' => 'build/assets/amenties/bottle.png'],
            ['name' => 'Soap', 'image' => 'build/assets/amenties/soap.png'],
            ['name' => 'Extra Pillows and Blankets', 'image' => 'build/assets/amenties/pillow.png'],
            ['name' => 'Room-Darkening Shades', 'image' => 'build/assets/amenties/solar.png'],
            ['name' => 'Outdoor Furniture', 'image' => 'build/assets/amenties/sun-umbrella.png'],
            ['name' => 'Bikes', 'image' => 'build/assets/amenties/bicycle.png'],
            ['name' => 'Game Console', 'image' => 'build/assets/amenties/console.png'],
            ['name' => 'Books and Reading Material', 'image' => 'build/assets/amenties/learning.png'],
            ['name' => 'Board Games', 'image' => 'build/assets/amenties/board-game.png'],
            ['name' => 'Exercise Equipment', 'image' => 'build/assets/amenties/pilates.png'],
            ['name' => 'Children’s Toys', 'image' => 'build/assets/amenties/toys.png'],
            ['name' => 'Children’s Dinnerware', 'image' => 'build/assets/amenties/dinnerware.png'],
            ['name' => 'Bathtub', 'image' => 'build/assets/amenties/bathtub.png'],
            ['name' => 'Bidet', 'image' => 'build/assets/amenties/bidet.png'],
            ['name' => 'Cooking Basics', 'image' => 'build/assets/amenties/chef.png'],
            ['name' => 'Piano', 'image' => 'build/assets/amenties/piano.png'],
            ['name' => 'Sound System', 'image' => 'build/assets/amenties/speaker.png'],
            ['name' => 'DVD Player', 'image' => 'build/assets/amenties/dvd-player.png'],
            ['name' => 'Foosball Table', 'image' => 'build/assets/amenties/table-soccer.png'],
            ['name' => 'Pet-Friendly', 'image' => 'build/assets/amenties/pet.png'],
            ['name' => 'Smoking Allowed', 'image' => 'build/assets/amenties/smoking.png'],
            ['name' => 'Daily Housekeeping', 'image' => 'build/assets/amenties/cleaning.png'],
            ['name' => 'Doorman', 'image' => 'build/assets/amenties/doorman.png'],
            ['name' => 'Hypoallergenic Bedding', 'image' => 'build/assets/amenties/double-bed.png'],
            ['name' => 'Mountain View', 'image' => 'build/assets/amenties/mountain.png'],
            ['name' => 'Ocean View', 'image' => 'build/assets/amenties/sea.png'],
        ];


        foreach ($amenities as $amenity) {
            DB::table('amenities')->insert([
                'name' => $amenity['name'],
                'image' => $amenity['image'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
