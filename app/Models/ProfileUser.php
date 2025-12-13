<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;

class ProfileUser extends Model
{
    
    protected $table = 'profile_users';
    protected $fillable = ['user_id', 'name', 'country_code', 'email', 'Gender', 'bastgaming', 'bastclabe', 'cantry', 'city', 'address', 'image','TypProfileUser', 'data_of_birth', 'NumberPhone'];

    public function User() {
        return $this->belongsTo(User::class);
    }
}
