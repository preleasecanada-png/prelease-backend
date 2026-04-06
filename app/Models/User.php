<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// use Laravel\Passport\HasApiTokens;
use Laravel\Passport\HasApiTokens;

// class User extends Authenticatable implements MustVerifyEmail
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    // use HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'user_name',
        'phone_no',
        'date_of_birth',
        'email_verified_at',
        'google_id',
        'facebook_id',
        'apple_id',
        'verify_status',
        'role',
        'bio',
        'gender',
        'picture'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function receivedMessages()
    {
        return $this->hasMany(UserChat::class, 'received_id', 'id');
    }
    public function senderMessages()
    {
        return $this->hasMany(UserChat::class, 'sender_id', 'id');
    }

    public function renterPreference()
    {
        return $this->hasOne(RenterPreference::class);
    }

    public function rentalApplications()
    {
        return $this->hasMany(RentalApplication::class, 'renter_id');
    }

    public function receivedApplications()
    {
        return $this->hasMany(RentalApplication::class, 'landlord_id');
    }

    public function renterLeases()
    {
        return $this->hasMany(LeaseAgreement::class, 'renter_id');
    }

    public function landlordLeases()
    {
        return $this->hasMany(LeaseAgreement::class, 'landlord_id');
    }

    public function renterPayments()
    {
        return $this->hasMany(Payment::class, 'renter_id');
    }

    public function landlordPayments()
    {
        return $this->hasMany(Payment::class, 'landlord_id');
    }

    public function referralsMade()
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    public function referredBy()
    {
        return $this->hasOne(Referral::class, 'referred_id');
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function verifications()
    {
        return $this->hasMany(UserVerification::class);
    }

    public function rentalInsurances()
    {
        return $this->hasMany(RentalInsurance::class, 'renter_id');
    }

    public function properties()
    {
        return $this->hasMany(Property::class, 'user_id');
    }

    public function reviewsGiven()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function reviewsReceived()
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }
}
