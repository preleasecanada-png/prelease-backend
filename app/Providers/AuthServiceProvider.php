<?php

namespace App\Providers;

use Laravel\Passport\Passport;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // On Lambda, storage_path() points to /tmp/storage but keys are in /var/task/storage
        if (isset($_SERVER['LAMBDA_TASK_ROOT'])) {
            Passport::loadKeysFrom($_SERVER['LAMBDA_TASK_ROOT'] . '/storage');
        }

        // Set token expiration to 1 days
        // Passport::tokensExpireIn(now()->addDays(1));
    }
}
