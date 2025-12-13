<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrentPaymentForUseBss extends Model
{
    
    protected $table = 'current_payment_for_use_bsses';
    protected $fillable = ['user_id', 'usernameBss', 'currentCantry'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
