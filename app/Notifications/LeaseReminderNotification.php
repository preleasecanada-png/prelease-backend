<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaseReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $lease;
    protected $recipientRole;
    protected $reminderType;

    public function __construct($lease, $recipientRole = 'renter', $reminderType = 'signing')
    {
        $this->lease = $lease;
        $this->recipientRole = $recipientRole;
        $this->reminderType = $reminderType;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $lease = $this->lease;
        $property = $lease->property;
        $recipientName = $notifiable->first_name ?? 'User';

        $subject = match($this->reminderType) {
            'signing' => 'Lease Awaiting Signature',
            'expiring' => 'Lease Expiring Soon',
            'active' => 'Lease Now Active',
            default => 'Lease Update',
        };

        return (new MailMessage)
            ->subject($subject . ' - ' . ($property->title ?? 'Property'))
            ->view('emails.lease-reminder', [
                'recipientName' => $recipientName,
                'recipientRole' => $this->recipientRole,
                'reminderType' => $this->reminderType,
                'propertyTitle' => $property->title ?? 'N/A',
                'leaseType' => $lease->lease_type,
                'startDate' => $lease->start_date ? date('M d, Y', strtotime($lease->start_date)) : 'N/A',
                'endDate' => $lease->end_date ? date('M d, Y', strtotime($lease->end_date)) : 'N/A',
                'monthlyRent' => $lease->monthly_rent,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'lease_id' => $this->lease->id,
            'reminder_type' => $this->reminderType,
            'property_id' => $this->lease->property_id,
        ];
    }
}
