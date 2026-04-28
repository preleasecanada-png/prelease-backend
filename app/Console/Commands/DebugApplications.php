<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RentalApplication;
use App\Models\Property;
use App\Models\User;

class DebugApplications extends Command
{
    protected $signature = 'debug:apps {user_id?}';
    protected $description = 'Debug rental applications visibility for a user';

    public function handle()
    {
        $userId = $this->argument('user_id');

        if (!$userId) {
            $this->info("Total Users: " . User::count());
            $this->info("Total Properties: " . Property::count());
            $this->info("Total Applications: " . RentalApplication::count());
            
            $this->info("\nRecent Applications:");
            $apps = RentalApplication::latest()->limit(5)->get();
            foreach ($apps as $app) {
                $this->line("App ID: {$app->id}, Property ID: {$app->property_id}, Renter ID: {$app->renter_id}, Landlord ID: {$app->landlord_id}, Status: {$app->status}");
            }
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->error("User not found!");
            return;
        }

        $this->info("Debugging for User: {$user->email} (ID: {$user->id}, Role: {$user->role})");

        $isLandlord = in_array($user->role, ['landlord', 'host', 'admin']);
        $this->info("Is Landlord: " . ($isLandlord ? 'Yes' : 'No'));

        if ($isLandlord) {
            $propertyIds = Property::where('user_id', $user->id)->pluck('id');
            $this->info("Property IDs owned by user: " . $propertyIds->implode(', '));

            $appsByLandlordId = RentalApplication::where('landlord_id', $user->id)->count();
            $this->info("Applications with landlord_id = {$user->id}: $appsByLandlordId");

            $appsByPropertyId = RentalApplication::whereIn('property_id', $propertyIds)->count();
            $this->info("Applications for owned properties: $appsByPropertyId");

            if ($appsByPropertyId > 0) {
                $apps = RentalApplication::whereIn('property_id', $propertyIds)->get();
                foreach ($apps as $app) {
                    $this->line("- App ID: {$app->id}, Property: {$app->property_id}, Landlord ID in DB: {$app->landlord_id}");
                }
            }
        } else {
            $appsAsRenter = RentalApplication::where('renter_id', $user->id)->count();
            $this->info("Applications as Renter: $appsAsRenter");
        }
    }
}
