<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPasswordStting extends Model
{
    //

    protected $table = 'user_password_sttings';
    protected $fillable = ['user_id', 'password', 'idbss'];

    public function UserPasswordStting() {
        return $this->belongsTo(UserPasswordStting::class);
    }
}
