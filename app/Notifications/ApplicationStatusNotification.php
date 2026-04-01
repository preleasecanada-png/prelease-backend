<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $application;
    protected $recipientRole;

    public function __construct($application, $recipientRole = 'renter')
    {
        $this->application = $application;
        $this->recipientRole = $recipientRole;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $application = $this->application;
        $property = $application->property;
        $recipientName = $notifiable->first_name ?? 'User';

        return (new MailMessage)
            ->subject('Application Update - ' . ($property->title ?? 'Property'))
            ->view('emails.application-status', [
                'recipientName' => $recipientName,
                'recipientRole' => $this->recipientRole,
                'propertyTitle' => $property->title ?? 'N/A',
                'applicationId' => $application->id,
                'status' => $application->status,
                'rejectionReason' => $application->rejection_reason,
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'application_id' => $this->application->id,
            'status' => $this->application->status,
            'property_id' => $this->application->property_id,
        ];
    }
}
