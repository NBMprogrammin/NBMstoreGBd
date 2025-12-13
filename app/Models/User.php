<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\UserCategory;
use App\Models\ProdectUser;
use App\Models\ProfileUserBss;
use App\Models\MessageEghar;
use App\Models\ProfileUser;
use App\Models\PaymentMethodUserBss;
use App\Models\CurrentPaymentForUseBss;
use App\Models\PaymentProdectUserBss;
use App\Models\UserPasswordStting;
use App\Models\MyOrdersPayBss;
use App\Models\EdaretMewevin;
use App\Models\EdartManey;
use App\Models\EdartPaymentRwatibeMeweves;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'NumberPhone',
        'country_code',
        'password',
        'Confirempassword',
        'current_my_travel',
        'curret_profile_id_Bss',
        'curret_profile_id',
        'user_token'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // 'email_verified_at' => 'datetime',
            // 'password' => 'hashed',
        ];
    }

    public function userCategory() {
        return $this->hasMany(UserCategory::class);
    }

    public function UserPasswordStting() {
        return $this->hasOne(UserPasswordStting::class);
    }

    public function prodectUser() {
        return $this->hasMany(ProdectUser::class);
    }

    public function ZebouneForUser() {
        return $this->hasMany(ZebouneForUser::class);
    }

    public function ProfileUserBss() {
        return $this->hasMany(ProfileUserBss::class);
    }

    public function ProfileUser() {
        return $this->hasMany(ProfileUser::class);
    }

    public function MessageEghar() {
        return $this->hasMany(MessageEghar::class);
    }

    public function curretprofile() {
        return $this->belongsTo(ProfileUser::class, 'curret_profile_id');
    }

    public function curretprofileBss() {
        return $this->belongsTo(ProfileUserBss::class, 'curret_profile_id_Bss');
    }

    public function paymentmethoduserbsses() {
        return $this->hasMany(PaymentMethodUserBss::class,);
    }

    public function CurrentPaymentForUseBss() {
        return $this->hasOne(CurrentPaymentForUseBss::class);
    }

    public function PaymentProdectUserBss() {
        return $this->hasMany(PaymentProdectUserBss::class);
    }

    public function MyOrdersPayBss() {
        return $this->hasMany(MyOrdersPayBss::class);
    }

    public function EdaretMewevin() {
        return $this->hasMany(EdaretMewevin::class);
    }

    public function EdartManey() {
        return $this->hasMany(EdartManey::class);
    }

    public function EdartPaymentRewatebMweves() {
        return $this->hasMany(EdartPaymentRwatibeMeweves::class);
    }
    
}
