<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdartManey extends Model
{
    //

    protected $table = 'edart_maneys';
    protected $fillable = ['user_id', 'currentPay', 'idbss', 'idTweve', 'NumberMeshol', 'totalEdichal', "descripctionforday", 'totalEstehlakat', 'TotalErbahe', 'counteAdd'];

    public function user() {
        return $this->belongsTo(User::class);
    }
    
}
