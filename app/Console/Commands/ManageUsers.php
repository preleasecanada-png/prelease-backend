<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ManageUsers extends Command
{
    protected $signature = 'users:manage {action=list} {--email=}';
    protected $description = 'List or delete users';

    public function handle()
    {
        $action = $this->argument('action');
        $email = $this->option('email');

        if ($action === 'list') {
            $users = User::select('id', 'first_name', 'last_name', 'email', 'role', 'verify_status', 'created_at')->get();
            foreach ($users as $u) {
                $this->info("ID:{$u->id} | {$u->first_name} {$u->last_name} | {$u->email} | role:{$u->role} | verified:{$u->verify_status} | {$u->created_at}");
            }
            $this->info("Total: " . $users->count() . " users");
        } elseif ($action === 'delete-all') {
            $count = User::count();
            User::truncate();
            $this->info("Deleted all {$count} users");
        } elseif ($action === 'delete' && $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->delete();
                $this->info("Deleted user: {$email}");
            } else {
                $this->error("User not found: {$email}");
            }
        } elseif ($action === 'test-email' && $email) {
            try {
                \Illuminate\Support\Facades\Mail::raw('Test email from PreLease Canada', function ($message) use ($email) {
                    $message->to($email)->subject('PreLease - Test Email');
                });
                $this->info("Test email sent to {$email}");
            } catch (\Exception $e) {
                $this->error("Email error: " . $e->getMessage());
            }
        } else {
            $this->info("Usage: users:manage {list|delete|delete-all|test-email} [--email=user@example.com]");
        }
    }
}
