<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $payment;
    protected $recipientRole;

    public function __construct($payment, $recipientRole = 'renter')
    {
        $this->payment = $payment;
        $this->recipientRole = $recipientRole;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $payment = $this->payment;
        $property = $payment->property;
        $recipientName = $notifiable->first_name ?? 'User';

        return (new MailMessage)
            ->subject('Payment Confirmation - ' . $payment->payment_reference)
            ->view('emails.payment-confirmation', [
                'recipientName' => $recipientName,
                'recipientRole' => $this->recipientRole,
                'propertyTitle' => $property->title ?? 'N/A',
                'paymentReference' => $payment->payment_reference,
                'rentAmount' => $payment->rent_amount,
                'supportFee' => $payment->support_fee,
                'insuranceFee' => $payment->insurance_fee,
                'totalAmount' => $payment->total_amount,
                'paymentMethod' => $payment->payment_method,
                'paidAt' => $payment->paid_at ? $payment->paid_at->format('M d, Y') : now()->format('M d, Y'),
            ]);
    }

    public function toArray($notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'payment_reference' => $this->payment->payment_reference,
            'total_amount' => $this->payment->total_amount,
        ];
    }
}
