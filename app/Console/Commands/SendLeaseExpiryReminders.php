<?php

namespace App\Console\Commands;

use App\Models\LeaseAgreement;
use App\Models\User;
use App\Notifications\LeaseReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendLeaseExpiryReminders extends Command
{
    protected $signature = 'leases:send-expiry-reminders {--days=14 : Days before expiry to send reminder}';
    protected $description = 'Send email reminders for leases expiring within the specified number of days';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoffDate = Carbon::now()->addDays($days);

        $expiringLeases = LeaseAgreement::with(['property', 'renter', 'landlord'])
            ->where('status', 'active')
            ->whereBetween('end_date', [Carbon::now(), $cutoffDate])
            ->get();

        if ($expiringLeases->isEmpty()) {
            $this->info('No leases expiring within the next ' . $days . ' days.');
            return Command::SUCCESS;
        }

        $sent = 0;

        foreach ($expiringLeases as $lease) {
            try {
                $renter = User::find($lease->renter_id);
                $landlord = User::find($lease->landlord_id);

                if ($renter) {
                    $renter->notify(new LeaseReminderNotification($lease, 'renter', 'expiring'));
                    $sent++;
                }

                if ($landlord) {
                    $landlord->notify(new LeaseReminderNotification($lease, 'landlord', 'expiring'));
                    $sent++;
                }
            } catch (\Throwable $e) {
                Log::error("Failed to send lease expiry reminder for lease #{$lease->id}: " . $e->getMessage());
                $this->error("Failed for lease #{$lease->id}: " . $e->getMessage());
            }
        }

        $this->info("Sent {$sent} expiry reminders for {$expiringLeases->count()} leases.");

        return Command::SUCCESS;
    }
}
