<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Models\PropertyImages;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedProperties extends Command
{
    protected $signature = 'seed:properties {email}';
    protected $description = 'Seed 3 example properties with images for a given user email';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found: {$email}");
            return 1;
        }

        $this->info("Found user: {$user->first_name} {$user->last_name} (ID: {$user->id})");

        $properties = [
            [
                'title' => 'Modern Downtown Condo with City View',
                'description' => 'Beautiful modern condo in the heart of downtown Toronto. Floor-to-ceiling windows offer stunning city views. The unit features hardwood floors, a gourmet kitchen with stainless steel appliances, and an open-concept living area. Building amenities include a rooftop terrace, fitness center, and 24/7 concierge. Steps away from restaurants, transit, and entertainment.',
                'describe_your_place' => 'An entire place',
                'country' => 'Canada',
                'city' => 'Toronto',
                'state' => 'Ontario',
                'street_address' => '88 Blue Jays Way',
                'postal_code' => 'M5V 2G3',
                'how_many_guests' => '4',
                'how_many_bedrooms' => '2',
                'how_many_bathroom' => '1',
                'set_your_price' => '2400',
                'guest_service_fee' => '120',
                'confirm_reservation' => 'instant',
                'images' => ['prop1_1.jpg', 'prop1_2.jpg', 'prop1_3.jpg'],
            ],
            [
                'title' => 'Cozy Plateau Mont-Royal Apartment',
                'description' => 'Charming apartment located in the trendy Plateau Mont-Royal neighborhood of Montreal. This bright and spacious unit features exposed brick walls, original woodwork, and a private balcony overlooking a tree-lined street. Fully furnished with modern amenities. Walking distance to Mile End cafés, boutiques, and Parc La Fontaine.',
                'describe_your_place' => 'An entire place',
                'country' => 'Canada',
                'city' => 'Montreal',
                'state' => 'Quebec',
                'street_address' => '4520 Rue Saint-Denis',
                'postal_code' => 'H2J 2L3',
                'how_many_guests' => '3',
                'how_many_bedrooms' => '1',
                'how_many_bathroom' => '1',
                'set_your_price' => '1800',
                'guest_service_fee' => '90',
                'confirm_reservation' => 'instant',
                'images' => ['prop2_1.jpg', 'prop2_2.jpg', 'prop2_3.jpg'],
            ],
            [
                'title' => 'Spacious Family Home in Kitsilano',
                'description' => 'Stunning detached home in Vancouver\'s beloved Kitsilano neighborhood. This 3-bedroom house offers a large backyard, updated kitchen, and a cozy fireplace in the living room. Enjoy ocean views from the master bedroom. Minutes from Kits Beach, local shops, and excellent schools. Perfect for families or professionals looking for space and comfort.',
                'describe_your_place' => 'An entire place',
                'country' => 'Canada',
                'city' => 'Vancouver',
                'state' => 'British Columbia',
                'street_address' => '2785 West 4th Avenue',
                'postal_code' => 'V6K 1R2',
                'how_many_guests' => '6',
                'how_many_bedrooms' => '3',
                'how_many_bathroom' => '2',
                'set_your_price' => '3200',
                'guest_service_fee' => '160',
                'confirm_reservation' => 'instant',
                'images' => ['prop3_1.jpg', 'prop3_2.jpg', 'prop3_3.jpg'],
            ],
        ];

        foreach ($properties as $data) {
            $images = $data['images'];
            unset($data['images']);

            $data['user_id'] = $user->id;
            $data['slug'] = Str::slug($data['title']);
            $data['step'] = 'complete';

            $property = Property::create($data);
            $this->info("Created property: {$property->title} (ID: {$property->id})");

            foreach ($images as $img) {
                PropertyImages::create([
                    'property_id' => $property->id,
                    'original' => 'images/place_gallery_images/' . $img,
                    'extension' => 'jpg',
                ]);
            }
            $this->info("  -> Added " . count($images) . " images");
        }

        $this->info("Done! 3 properties created for {$email}");
        return 0;
    }
}
