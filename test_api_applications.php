<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\RentalApplication;
use Illuminate\Support\Facades\Auth;

$user = User::where('email', 'daniblucoul@gmail.com')->first();
if (!$user) {
    die("User not found\n");
}

echo "Simulating request for User ID: " . $user->id . " (" . $user->email . ")\n";

// Mock authentication
Auth::guard('api')->setUser($user);

try {
    $controller = new \App\Http\Controllers\FrontEndApi\RentalApplicationController();
    $request = new \Illuminate\Http\Request();
    $response = $controller->index($request);
    
    echo "Status: " . $response->getStatusCode() . "\n";
    $data = json_decode($response->getContent(), true);
    
    if (isset($data['data']['data'])) {
        echo "Found " . count($data['data']['data']) . " applications in paginated data.\n";
        foreach ($data['data']['data'] as $app) {
            echo " - ID: {$app['id']}, Renter: {$app['renter_id']}, Landlord: {$app['landlord_id']}, Status: {$app['status']}\n";
        }
    } else {
        echo "Response data structure:\n";
        print_r($data);
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
