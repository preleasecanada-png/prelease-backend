<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReferralBonusNotification extends Notification
{
    use Queueable;

    protected $referral;
    protected $bonusAmount;
    protected $referredUserName;

    public function __construct($referral, $bonusAmount, $referredUserName)
    {
        $this->referral = $referral;
        $this->bonusAmount = $bonusAmount;
        $this->referredUserName = $referredUserName;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Bonus de parrainage reçu !',
            'message' => "Félicitations ! Vous avez reçu un bonus de 5% (\${$this->bonusAmount}) grâce au parrainage de {$this->referredUserName}.",
            'referral_id' => $this->referral->id,
            'bonus_amount' => $this->bonusAmount,
            'referred_user_name' => $this->referredUserName,
            'type' => 'referral_bonus',
        ];
    }
}
