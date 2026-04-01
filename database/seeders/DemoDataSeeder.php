<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding demo data...');

        // ── 1. USERS ──
        $this->command->info('Creating users...');

        $landlord1Id = DB::table('users')->insertGetId([
            'user_name' => 'sophie_martin',
            'first_name' => 'Sophie',
            'last_name' => 'Martin',
            'email' => 'sophie@prelease.ca',
            'password' => Hash::make('password123'),
            'role' => 'host',
            'phone_no' => '514-555-1001',
            'description' => 'Experienced property manager with 5+ years in Montreal real estate. I take pride in maintaining quality homes for my tenants.',
            'verify_status' => 1,
            'email_verified_at' => now(),
            'created_at' => now()->subMonths(8),
            'updated_at' => now(),
        ]);

        $landlord2Id = DB::table('users')->insertGetId([
            'user_name' => 'james_chen',
            'first_name' => 'James',
            'last_name' => 'Chen',
            'email' => 'james@prelease.ca',
            'password' => Hash::make('password123'),
            'role' => 'host',
            'phone_no' => '416-555-2002',
            'description' => 'Toronto-based landlord offering modern condos and apartments in the downtown core.',
            'verify_status' => 1,
            'email_verified_at' => now(),
            'created_at' => now()->subMonths(6),
            'updated_at' => now(),
        ]);

        $landlord3Id = DB::table('users')->insertGetId([
            'user_name' => 'marie_dubois',
            'first_name' => 'Marie',
            'last_name' => 'Dubois',
            'email' => 'marie@prelease.ca',
            'password' => Hash::make('password123'),
            'role' => 'host',
            'phone_no' => '613-555-3003',
            'description' => 'Offering beautiful rental homes in Ottawa. Clean, modern, and well-maintained.',
            'verify_status' => 1,
            'email_verified_at' => now(),
            'created_at' => now()->subMonths(4),
            'updated_at' => now(),
        ]);

        $renter1Id = DB::table('users')->insertGetId([
            'user_name' => 'alex_roy',
            'first_name' => 'Alex',
            'last_name' => 'Roy',
            'email' => 'alex@prelease.ca',
            'password' => Hash::make('password123'),
            'role' => 'guest',
            'phone_no' => '514-555-4004',
            'verify_status' => 1,
            'email_verified_at' => now(),
            'created_at' => now()->subMonths(3),
            'updated_at' => now(),
        ]);

        $renter2Id = DB::table('users')->insertGetId([
            'user_name' => 'emma_wilson',
            'first_name' => 'Emma',
            'last_name' => 'Wilson',
            'email' => 'emma@prelease.ca',
            'password' => Hash::make('password123'),
            'role' => 'guest',
            'phone_no' => '604-555-5005',
            'verify_status' => 1,
            'email_verified_at' => now(),
            'created_at' => now()->subMonths(2),
            'updated_at' => now(),
        ]);

        $renter3Id = DB::table('users')->insertGetId([
            'user_name' => 'liam_tremblay',
            'first_name' => 'Liam',
            'last_name' => 'Tremblay',
            'email' => 'liam@prelease.ca',
            'password' => Hash::make('password123'),
            'role' => 'guest',
            'phone_no' => '780-555-6006',
            'verify_status' => 1,
            'email_verified_at' => now(),
            'created_at' => now()->subMonths(1),
            'updated_at' => now(),
        ]);

        // ── 2. DOWNLOAD SAMPLE IMAGES ──
        $this->command->info('Downloading sample property images...');

        $imgDir = public_path('images/place_gallery_images');
        if (!File::isDirectory($imgDir)) {
            File::makeDirectory($imgDir, 0755, true);
        }

        $imageSeeds = [
            // Modern apartments / condos
            ['seed' => 'apt-living-1', 'w' => 800, 'h' => 600],
            ['seed' => 'apt-kitchen-1', 'w' => 800, 'h' => 600],
            ['seed' => 'apt-bedroom-1', 'w' => 800, 'h' => 600],
            ['seed' => 'apt-bath-1', 'w' => 800, 'h' => 600],
            ['seed' => 'condo-living-2', 'w' => 800, 'h' => 600],
            ['seed' => 'condo-kitchen-2', 'w' => 800, 'h' => 600],
            ['seed' => 'condo-bedroom-2', 'w' => 800, 'h' => 600],
            ['seed' => 'condo-view-2', 'w' => 800, 'h' => 600],
            ['seed' => 'loft-living-3', 'w' => 800, 'h' => 600],
            ['seed' => 'loft-kitchen-3', 'w' => 800, 'h' => 600],
            ['seed' => 'loft-bedroom-3', 'w' => 800, 'h' => 600],
            ['seed' => 'loft-bath-3', 'w' => 800, 'h' => 600],
            ['seed' => 'house-front-4', 'w' => 800, 'h' => 600],
            ['seed' => 'house-living-4', 'w' => 800, 'h' => 600],
            ['seed' => 'house-kitchen-4', 'w' => 800, 'h' => 600],
            ['seed' => 'house-yard-4', 'w' => 800, 'h' => 600],
            ['seed' => 'studio-main-5', 'w' => 800, 'h' => 600],
            ['seed' => 'studio-kitchen-5', 'w' => 800, 'h' => 600],
            ['seed' => 'studio-bath-5', 'w' => 800, 'h' => 600],
            ['seed' => 'studio-window-5', 'w' => 800, 'h' => 600],
            ['seed' => 'penthouse-living-6', 'w' => 800, 'h' => 600],
            ['seed' => 'penthouse-kitchen-6', 'w' => 800, 'h' => 600],
            ['seed' => 'penthouse-terrace-6', 'w' => 800, 'h' => 600],
            ['seed' => 'penthouse-bed-6', 'w' => 800, 'h' => 600],
            ['seed' => 'townhouse-front-7', 'w' => 800, 'h' => 600],
            ['seed' => 'townhouse-living-7', 'w' => 800, 'h' => 600],
            ['seed' => 'townhouse-kitchen-7', 'w' => 800, 'h' => 600],
            ['seed' => 'townhouse-bed-7', 'w' => 800, 'h' => 600],
            ['seed' => 'duplex-main-8', 'w' => 800, 'h' => 600],
            ['seed' => 'duplex-living-8', 'w' => 800, 'h' => 600],
            ['seed' => 'duplex-kitchen-8', 'w' => 800, 'h' => 600],
            ['seed' => 'duplex-yard-8', 'w' => 800, 'h' => 600],
        ];

        $downloadedImages = [];

        foreach ($imageSeeds as $i => $img) {
            $fileName = "demo_{$img['seed']}.jpg";
            $filePath = $imgDir . DIRECTORY_SEPARATOR . $fileName;
            $relativePath = "images/place_gallery_images/{$fileName}";

            if (!File::exists($filePath)) {
                $url = "https://picsum.photos/seed/{$img['seed']}/{$img['w']}/{$img['h']}";
                try {
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $data = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($httpCode === 200 && $data) {
                        File::put($filePath, $data);
                        $this->command->info("  Downloaded: {$fileName}");
                    } else {
                        $this->command->warn("  Failed to download: {$fileName} (HTTP {$httpCode})");
                    }
                } catch (\Throwable $e) {
                    $this->command->warn("  Error downloading {$fileName}: " . $e->getMessage());
                }
            } else {
                $this->command->info("  Already exists: {$fileName}");
            }

            $downloadedImages[] = $relativePath;
        }

        // ── 3. PROPERTIES ──
        $this->command->info('Creating properties...');

        $properties = [
            [
                'title' => 'Modern Downtown Loft – Plateau Mont-Royal',
                'slug' => 'modern-downtown-loft-plateau-mont-royal',
                'description' => '<p>Stunning modern loft in the heart of Plateau Mont-Royal. This beautifully renovated space features exposed brick walls, 12-foot ceilings, and floor-to-ceiling windows flooding the space with natural light.</p><p>Walking distance to Mont-Royal metro, trendy cafés, boutiques, and Parc La Fontaine. Perfect for professionals or couples looking for urban living at its finest.</p><p>Features include in-unit laundry, central air, hardwood floors, and a fully equipped kitchen with stainless steel appliances.</p>',
                'user_id' => $landlord1Id,
                'city' => 'Montreal',
                'state' => 'Quebec',
                'country' => 'Canada',
                'street_address' => '4521 Rue Saint-Denis',
                'postal_code' => 'H2J 2L4',
                'how_many_guests' => '4',
                'how_many_bedrooms' => '2',
                'how_many_bathroom' => '1',
                'set_your_price' => '1850',
                'guest_service_fee' => '100',
                'describe_your_place' => 'Apartment',
                'images' => [0, 1, 2, 3],
            ],
            [
                'title' => 'Luxury Condo with CN Tower View – King West',
                'slug' => 'luxury-condo-cn-tower-view-king-west',
                'description' => '<p>Experience Toronto living at its best in this stunning luxury condo with breathtaking CN Tower views. Located in the vibrant King West neighbourhood, you\'re steps away from the best restaurants, bars, and entertainment the city has to offer.</p><p>This high-rise unit features floor-to-ceiling windows, a modern open kitchen with quartz countertops, and a spa-like bathroom. Building amenities include a rooftop pool, fitness centre, 24-hour concierge, and co-working lounge.</p>',
                'user_id' => $landlord2Id,
                'city' => 'Toronto',
                'state' => 'Ontario',
                'country' => 'Canada',
                'street_address' => '88 Blue Jays Way, Unit 3201',
                'postal_code' => 'M5V 2G3',
                'how_many_guests' => '3',
                'how_many_bedrooms' => '1',
                'how_many_bathroom' => '1',
                'set_your_price' => '2400',
                'guest_service_fee' => '100',
                'describe_your_place' => 'Condo',
                'images' => [4, 5, 6, 7],
            ],
            [
                'title' => 'Cozy Industrial Loft – Mile End',
                'slug' => 'cozy-industrial-loft-mile-end',
                'description' => '<p>Charming industrial-style loft in Mile End, one of Montreal\'s most creative and vibrant neighbourhoods. This unique space combines original industrial elements with modern comfort.</p><p>Features include a chef\'s kitchen with gas range, polished concrete floors, exposed ductwork, and a private rooftop terrace with city views. Walking distance to Fairmount Bagels, Parc du Mont-Royal, and Mile End\'s famous café culture.</p>',
                'user_id' => $landlord1Id,
                'city' => 'Montreal',
                'state' => 'Quebec',
                'country' => 'Canada',
                'street_address' => '156 Avenue Fairmount Ouest',
                'postal_code' => 'H2T 2M1',
                'how_many_guests' => '2',
                'how_many_bedrooms' => '1',
                'how_many_bathroom' => '1',
                'set_your_price' => '1600',
                'guest_service_fee' => '100',
                'describe_your_place' => 'Loft',
                'images' => [8, 9, 10, 11],
            ],
            [
                'title' => 'Charming Family Home – Glebe, Ottawa',
                'slug' => 'charming-family-home-glebe-ottawa',
                'description' => '<p>Beautiful 3-bedroom family home in the sought-after Glebe neighbourhood. This classic brick home has been lovingly maintained and updated with modern amenities while preserving its original character.</p><p>Enjoy a spacious backyard with mature trees, a renovated kitchen with granite countertops, hardwood floors throughout, and a finished basement. Steps from Bank Street shops, Lansdowne Park, and the Rideau Canal.</p>',
                'user_id' => $landlord3Id,
                'city' => 'Ottawa',
                'state' => 'Ontario',
                'country' => 'Canada',
                'street_address' => '312 Fourth Avenue',
                'postal_code' => 'K1S 2L3',
                'how_many_guests' => '6',
                'how_many_bedrooms' => '3',
                'how_many_bathroom' => '2',
                'set_your_price' => '2200',
                'guest_service_fee' => '100',
                'describe_your_place' => 'House',
                'images' => [12, 13, 14, 15],
            ],
            [
                'title' => 'Bright Studio – Griffintown',
                'slug' => 'bright-studio-griffintown',
                'description' => '<p>Newly built studio apartment in the trendy Griffintown district. Perfect for a student or young professional. This bright, efficiently designed space features a Murphy bed, modern kitchenette, and large windows overlooking the Lachine Canal.</p><p>Building includes a gym, rooftop terrace with BBQ, bike storage, and package lockers. Minutes from ETS, Concordia, and McGill universities.</p>',
                'user_id' => $landlord1Id,
                'city' => 'Montreal',
                'state' => 'Quebec',
                'country' => 'Canada',
                'street_address' => '1240 Rue des Bassins',
                'postal_code' => 'H3C 0G4',
                'how_many_guests' => '2',
                'how_many_bedrooms' => '0',
                'how_many_bathroom' => '1',
                'set_your_price' => '1200',
                'guest_service_fee' => '100',
                'describe_your_place' => 'Studio',
                'images' => [16, 17, 18, 19],
            ],
            [
                'title' => 'Penthouse Suite – Yorkville, Toronto',
                'slug' => 'penthouse-suite-yorkville-toronto',
                'description' => '<p>Extraordinary penthouse suite in the heart of Yorkville, Toronto\'s most prestigious neighbourhood. This expansive two-bedroom unit features a private terrace with panoramic city views, a gourmet kitchen with premium appliances, and spa-inspired bathrooms with heated floors.</p><p>Building amenities include valet parking, concierge, indoor pool, sauna, and a private screening room. Steps from the Royal Ontario Museum, University of Toronto, and Bloor-Yorkville shopping.</p>',
                'user_id' => $landlord2Id,
                'city' => 'Toronto',
                'state' => 'Ontario',
                'country' => 'Canada',
                'street_address' => '155 Yorkville Ave, PH01',
                'postal_code' => 'M5R 1C4',
                'how_many_guests' => '4',
                'how_many_bedrooms' => '2',
                'how_many_bathroom' => '2',
                'set_your_price' => '4500',
                'guest_service_fee' => '100',
                'describe_your_place' => 'Penthouse',
                'images' => [20, 21, 22, 23],
            ],
            [
                'title' => 'Modern Townhouse – Kitsilano, Vancouver',
                'slug' => 'modern-townhouse-kitsilano-vancouver',
                'description' => '<p>Stunning 3-level townhouse in the coveted Kitsilano neighbourhood. This contemporary home features an open-concept main floor, chef\'s kitchen with island, and a sunny south-facing patio perfect for entertaining.</p><p>Walk to Kits Beach, enjoy ocean views from the rooftop deck, and explore the vibrant West 4th Avenue shops and restaurants. Two-car garage and in-unit laundry included.</p>',
                'user_id' => $landlord3Id,
                'city' => 'Vancouver',
                'state' => 'British Columbia',
                'country' => 'Canada',
                'street_address' => '2345 West 4th Avenue',
                'postal_code' => 'V6K 1P3',
                'how_many_guests' => '5',
                'how_many_bedrooms' => '3',
                'how_many_bathroom' => '2',
                'set_your_price' => '3200',
                'guest_service_fee' => '100',
                'describe_your_place' => 'Townhouse',
                'images' => [24, 25, 26, 27],
            ],
            [
                'title' => 'Renovated Duplex – Little Italy, Edmonton',
                'slug' => 'renovated-duplex-little-italy-edmonton',
                'description' => '<p>Beautifully renovated upper duplex in Edmonton\'s charming Little Italy neighbourhood. This spacious unit features an updated kitchen with butcher block counters, original hardwood floors, and a private balcony overlooking a tree-lined street.</p><p>Close to the Italian Centre Shop, excellent restaurants, and quick access to downtown via LRT. Perfect for a family or roommates looking for affordable city living.</p>',
                'user_id' => $landlord2Id,
                'city' => 'Edmonton',
                'state' => 'Alberta',
                'country' => 'Canada',
                'street_address' => '9612 95th Avenue NW',
                'postal_code' => 'T6C 1Z3',
                'how_many_guests' => '4',
                'how_many_bedrooms' => '2',
                'how_many_bathroom' => '1',
                'set_your_price' => '1450',
                'guest_service_fee' => '100',
                'describe_your_place' => 'Duplex',
                'images' => [28, 29, 30, 31],
            ],
        ];

        $propertyIds = [];

        foreach ($properties as $prop) {
            $imgIndexes = $prop['images'];
            unset($prop['images']);

            $propId = DB::table('properties')->insertGetId(array_merge($prop, [
                'created_at' => now()->subDays(rand(5, 60)),
                'updated_at' => now(),
            ]));

            $propertyIds[] = $propId;

            foreach ($imgIndexes as $imgIdx) {
                if (isset($downloadedImages[$imgIdx])) {
                    DB::table('property_images')->insert([
                        'property_id' => $propId,
                        'original' => $downloadedImages[$imgIdx],
                        'extension' => 'jpg',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            // Link some amenities (random subset of IDs 1-20)
            $amenityIds = collect(range(1, 20))->shuffle()->take(rand(6, 12))->toArray();
            foreach ($amenityIds as $amenityId) {
                DB::table('property_guest_amenities')->insertOrIgnore([
                    'property_id' => $propId,
                    'amenity_id' => $amenityId,
                ]);
            }
        }

        // ── 4. LEASE AGREEMENTS ──
        $this->command->info('Creating lease agreements...');

        $lease1Id = DB::table('lease_agreements')->insertGetId([
            'property_id' => $propertyIds[0],
            'renter_id' => $renter1Id,
            'landlord_id' => $landlord1Id,
            'lease_type' => '6_month',
            'start_date' => now()->subMonths(4),
            'end_date' => now()->addMonths(2),
            'monthly_rent' => 1850,
            'total_rent' => 11100,
            'support_fee' => 100,
            'commission_fee' => 185,
            'insurance_fee' => 50,
            'total_payable' => 11435,
            'status' => 'active',
            'renter_signed_at' => now()->subMonths(4)->addDays(1),
            'landlord_signed_at' => now()->subMonths(4)->addDays(2),
            'terms' => 'Standard 6-month residential lease agreement per Quebec civil code.',
            'created_at' => now()->subMonths(4),
            'updated_at' => now(),
        ]);

        $lease2Id = DB::table('lease_agreements')->insertGetId([
            'property_id' => $propertyIds[1],
            'renter_id' => $renter2Id,
            'landlord_id' => $landlord2Id,
            'lease_type' => '3_month',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->addMonths(1),
            'monthly_rent' => 2400,
            'total_rent' => 7200,
            'support_fee' => 100,
            'commission_fee' => 240,
            'insurance_fee' => 60,
            'total_payable' => 7600,
            'status' => 'active',
            'renter_signed_at' => now()->subMonths(2)->addDays(1),
            'landlord_signed_at' => now()->subMonths(2)->addDays(1),
            'terms' => 'Standard 3-month residential lease agreement per Ontario Residential Tenancies Act.',
            'created_at' => now()->subMonths(2),
            'updated_at' => now(),
        ]);

        $lease3Id = DB::table('lease_agreements')->insertGetId([
            'property_id' => $propertyIds[3],
            'renter_id' => $renter3Id,
            'landlord_id' => $landlord3Id,
            'lease_type' => '6_month',
            'start_date' => now()->subMonths(5),
            'end_date' => now()->addMonth(),
            'monthly_rent' => 2200,
            'total_rent' => 13200,
            'support_fee' => 100,
            'commission_fee' => 220,
            'insurance_fee' => 55,
            'total_payable' => 13575,
            'status' => 'active',
            'renter_signed_at' => now()->subMonths(5)->addDays(2),
            'landlord_signed_at' => now()->subMonths(5)->addDays(3),
            'terms' => 'Standard 6-month residential lease agreement per Ontario Residential Tenancies Act.',
            'created_at' => now()->subMonths(5),
            'updated_at' => now(),
        ]);

        // Expired lease for property 2 (so renter1 can review it)
        $lease4Id = DB::table('lease_agreements')->insertGetId([
            'property_id' => $propertyIds[2],
            'renter_id' => $renter1Id,
            'landlord_id' => $landlord1Id,
            'lease_type' => '3_month',
            'start_date' => now()->subMonths(6),
            'end_date' => now()->subMonths(3),
            'monthly_rent' => 1600,
            'total_rent' => 4800,
            'support_fee' => 100,
            'commission_fee' => 160,
            'insurance_fee' => 40,
            'total_payable' => 5100,
            'status' => 'expired',
            'renter_signed_at' => now()->subMonths(6),
            'landlord_signed_at' => now()->subMonths(6),
            'terms' => 'Standard 3-month residential lease agreement.',
            'created_at' => now()->subMonths(6),
            'updated_at' => now()->subMonths(3),
        ]);

        // ── 5. RENTAL INSURANCE ──
        $this->command->info('Creating rental insurance...');

        DB::table('rental_insurance')->insert([
            [
                'lease_agreement_id' => $lease1Id,
                'renter_id' => $renter1Id,
                'policy_number' => 'PLC-2026-' . str_pad(rand(1000, 9999), 4, '0'),
                'provider' => 'Prelease Insurance Partners',
                'premium_amount' => 45.00,
                'coverage_start' => now()->subMonths(4),
                'coverage_end' => now()->addMonths(2),
                'status' => 'active',
                'coverage_details' => 'Comprehensive renter coverage: personal liability ($1M), contents ($30K), additional living expenses ($10K). Deductible: $500.',
                'created_at' => now()->subMonths(4),
                'updated_at' => now(),
            ],
            [
                'lease_agreement_id' => $lease2Id,
                'renter_id' => $renter2Id,
                'policy_number' => 'PLC-2026-' . str_pad(rand(1000, 9999), 4, '0'),
                'provider' => 'Prelease Insurance Partners',
                'premium_amount' => 55.00,
                'coverage_start' => now()->subMonths(2),
                'coverage_end' => now()->addMonths(1),
                'status' => 'active',
                'coverage_details' => 'Comprehensive renter coverage: personal liability ($2M), contents ($50K), additional living expenses ($15K). Deductible: $500.',
                'created_at' => now()->subMonths(2),
                'updated_at' => now(),
            ],
            [
                'lease_agreement_id' => $lease3Id,
                'renter_id' => $renter3Id,
                'policy_number' => 'PLC-2026-' . str_pad(rand(1000, 9999), 4, '0'),
                'provider' => 'Prelease Insurance Partners',
                'premium_amount' => 50.00,
                'coverage_start' => now()->subMonths(5),
                'coverage_end' => now()->addMonth(),
                'status' => 'active',
                'coverage_details' => 'Comprehensive renter coverage: personal liability ($1M), contents ($40K), additional living expenses ($12K). Deductible: $500.',
                'created_at' => now()->subMonths(5),
                'updated_at' => now(),
            ],
            [
                'lease_agreement_id' => $lease4Id,
                'renter_id' => $renter1Id,
                'policy_number' => 'PLC-2025-' . str_pad(rand(1000, 9999), 4, '0'),
                'provider' => 'Prelease Insurance Partners',
                'premium_amount' => 0,
                'coverage_start' => now()->subMonths(6),
                'coverage_end' => now()->subMonths(3),
                'status' => 'expired',
                'coverage_details' => 'Coverage period ended with lease expiration.',
                'created_at' => now()->subMonths(6),
                'updated_at' => now()->subMonths(3),
            ],
        ]);

        // ── 6. PAYMENTS ──
        $this->command->info('Creating payments...');

        $paymentData = [
            ['lease' => $lease1Id, 'renter' => $renter1Id, 'landlord' => $landlord1Id, 'property' => $propertyIds[0], 'rent' => 1850, 'months' => 4],
            ['lease' => $lease2Id, 'renter' => $renter2Id, 'landlord' => $landlord2Id, 'property' => $propertyIds[1], 'rent' => 2400, 'months' => 2],
            ['lease' => $lease3Id, 'renter' => $renter3Id, 'landlord' => $landlord3Id, 'property' => $propertyIds[3], 'rent' => 2200, 'months' => 5],
        ];

        foreach ($paymentData as $pd) {
            for ($m = 0; $m < $pd['months']; $m++) {
                $supportFee = 100;
                $commissionFee = round($pd['rent'] * 0.10, 2);
                DB::table('payments')->insert([
                    'lease_agreement_id' => $pd['lease'],
                    'property_id' => $pd['property'],
                    'renter_id' => $pd['renter'],
                    'landlord_id' => $pd['landlord'],
                    'rent_amount' => $pd['rent'],
                    'support_fee' => $supportFee,
                    'commission_fee' => $commissionFee,
                    'total_amount' => $pd['rent'] + $supportFee + $commissionFee,
                    'payment_type' => 'monthly',
                    'payment_method' => collect(['credit_card', 'e_transfer', 'bank_transfer'])->random(),
                    'payment_reference' => 'PAY-' . strtoupper(Str::random(8)),
                    'status' => 'completed',
                    'paid_at' => now()->subMonths($pd['months'] - $m)->addDays(1),
                    'created_at' => now()->subMonths($pd['months'] - $m),
                    'updated_at' => now()->subMonths($pd['months'] - $m)->addDays(1),
                ]);
            }
        }

        // ── 7. REVIEWS ──
        $this->command->info('Creating reviews...');

        $reviews = [
            // Renter 1 reviews property 0 (Plateau loft)
            [
                'property_id' => $propertyIds[0],
                'reviewer_id' => $renter1Id,
                'reviewee_id' => $landlord1Id,
                'lease_agreement_id' => $lease1Id,
                'review_type' => 'renter_to_property',
                'rating' => 5,
                'comment' => 'Absolutely love this loft! The exposed brick and high ceilings make it feel so spacious. Great location — I can walk to everything I need in the Plateau. Sophie is a fantastic landlord, always responsive and helpful.',
                'cleanliness_rating' => 5,
                'communication_rating' => 5,
                'value_rating' => 4,
                'location_rating' => 5,
                'status' => 'published',
                'created_at' => now()->subWeeks(3),
                'updated_at' => now()->subWeeks(3),
            ],
            // Renter 1 reviews landlord 1
            [
                'property_id' => $propertyIds[0],
                'reviewer_id' => $renter1Id,
                'reviewee_id' => $landlord1Id,
                'lease_agreement_id' => $lease1Id,
                'review_type' => 'renter_to_landlord',
                'rating' => 5,
                'comment' => 'Sophie is the best landlord I\'ve ever had. She responds to messages within minutes and handled a minor plumbing issue the same day I reported it. Highly recommend!',
                'communication_rating' => 5,
                'status' => 'published',
                'created_at' => now()->subWeeks(3),
                'updated_at' => now()->subWeeks(3),
            ],
            // Renter 2 reviews property 1 (King West condo)
            [
                'property_id' => $propertyIds[1],
                'reviewer_id' => $renter2Id,
                'reviewee_id' => $landlord2Id,
                'lease_agreement_id' => $lease2Id,
                'review_type' => 'renter_to_property',
                'rating' => 4,
                'comment' => 'Beautiful condo with an incredible view of the CN Tower. The building amenities are top-notch — the rooftop pool is amazing. Only downside is that parking is expensive. Overall a great experience!',
                'cleanliness_rating' => 5,
                'communication_rating' => 4,
                'value_rating' => 3,
                'location_rating' => 5,
                'status' => 'published',
                'created_at' => now()->subWeeks(2),
                'updated_at' => now()->subWeeks(2),
            ],
            // Renter 3 reviews property 3 (Ottawa house)
            [
                'property_id' => $propertyIds[3],
                'reviewer_id' => $renter3Id,
                'reviewee_id' => $landlord3Id,
                'lease_agreement_id' => $lease3Id,
                'review_type' => 'renter_to_property',
                'rating' => 5,
                'comment' => 'This is a wonderful family home in the Glebe. The backyard is perfect for our kids, and the kitchen renovation is gorgeous. Walking to Lansdowne for events is a huge bonus. Marie keeps everything in great shape.',
                'cleanliness_rating' => 5,
                'communication_rating' => 5,
                'value_rating' => 5,
                'location_rating' => 5,
                'status' => 'published',
                'created_at' => now()->subWeeks(1),
                'updated_at' => now()->subWeeks(1),
            ],
            // Renter 1 reviews property 2 (Mile End loft — expired lease)
            [
                'property_id' => $propertyIds[2],
                'reviewer_id' => $renter1Id,
                'reviewee_id' => $landlord1Id,
                'lease_agreement_id' => $lease4Id,
                'review_type' => 'renter_to_property',
                'rating' => 4,
                'comment' => 'Loved the industrial vibe of this place. The rooftop terrace is incredible — perfect for summer BBQs. Only wished the heating was a bit stronger during the winter months. Great location near Fairmount Bagels!',
                'cleanliness_rating' => 4,
                'communication_rating' => 5,
                'value_rating' => 4,
                'location_rating' => 5,
                'status' => 'published',
                'created_at' => now()->subMonths(2),
                'updated_at' => now()->subMonths(2),
            ],
            // Landlord 1 reviews renter 1
            [
                'property_id' => $propertyIds[2],
                'reviewer_id' => $landlord1Id,
                'reviewee_id' => $renter1Id,
                'lease_agreement_id' => $lease4Id,
                'review_type' => 'landlord_to_renter',
                'rating' => 5,
                'comment' => 'Alex was an excellent tenant. Always paid rent on time, kept the apartment spotless, and was respectful of neighbours. Would happily rent to him again!',
                'cleanliness_rating' => 5,
                'communication_rating' => 5,
                'status' => 'published',
                'created_at' => now()->subMonths(2)->addDays(2),
                'updated_at' => now()->subMonths(2)->addDays(2),
            ],
            // Landlord 2 reviews renter 2
            [
                'property_id' => $propertyIds[1],
                'reviewer_id' => $landlord2Id,
                'reviewee_id' => $renter2Id,
                'lease_agreement_id' => $lease2Id,
                'review_type' => 'landlord_to_renter',
                'rating' => 4,
                'comment' => 'Emma has been a pleasant tenant. Good communication, takes care of the unit, and pays on time. A few minor issues with noise but nothing major.',
                'cleanliness_rating' => 4,
                'communication_rating' => 4,
                'status' => 'published',
                'created_at' => now()->subWeeks(1),
                'updated_at' => now()->subWeeks(1),
            ],
        ];

        foreach ($reviews as $review) {
            DB::table('reviews')->insert($review);
        }

        // ── 8. SUPPORT TICKETS ──
        $this->command->info('Creating support tickets...');

        DB::table('support_tickets')->insert([
            'user_id' => $renter1Id,
            'subject' => 'Hot water issue in unit',
            'message' => 'The hot water takes about 5 minutes to heat up in the morning. Could someone look into the water heater?',
            'category' => 'property',
            'priority' => 'medium',
            'status' => 'resolved',
            'admin_response' => 'We contacted the landlord and a plumber has been dispatched. The issue has been resolved.',
            'resolved_at' => now()->subWeeks(3),
            'created_at' => now()->subWeeks(4),
            'updated_at' => now()->subWeeks(3),
        ]);
        DB::table('support_tickets')->insert([
            'user_id' => $renter2Id,
            'subject' => 'Question about lease renewal',
            'message' => 'My 3-month lease is ending next month. I\'d like to know the process for renewal and if there are any rate changes.',
            'category' => 'lease',
            'priority' => 'medium',
            'status' => 'open',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('');
        $this->command->info('=== Demo Login Credentials ===');
        $this->command->info('Landlord 1: sophie@prelease.ca / password123');
        $this->command->info('Landlord 2: james@prelease.ca / password123');
        $this->command->info('Landlord 3: marie@prelease.ca / password123');
        $this->command->info('Renter 1:   alex@prelease.ca / password123');
        $this->command->info('Renter 2:   emma@prelease.ca / password123');
        $this->command->info('Renter 3:   liam@prelease.ca / password123');
        $this->command->info('==============================');
    }
}
