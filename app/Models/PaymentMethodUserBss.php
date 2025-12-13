<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\User;

class PaymentMethodUserBss extends Model
{
    protected $table = 'payment_method_user_bsses';
    protected $fillable = ['user_id','TypePayment',  'usernameBss', 'namepayment', 'TypeNumberPay', 'currentPayment'];

    public function user() {
        return $this->belongsToMany(User::class);
    }

}
