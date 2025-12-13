<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;

class EdartPaymentRwatibeMeweves extends Model
{
    //
    protected $table = 'edart_payment_rwatibe_meweves';
    protected $fillable = ['user_id', 'idbss', 'Ratibe', 'MentheNow', 'curent'];
    
    public function user() {
        return $this->belongsToMany(User::class);
    }
}
