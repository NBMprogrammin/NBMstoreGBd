<?php

namespace App\Models;
use App\Models\User;
use App\Models\EdaretMewevin;

use Illuminate\Database\Eloquent\Model;

class ProfileUserBss extends Model
{
    //
    protected $table = 'profile_user_bsses';
    protected $fillable = ['user_id', 'discription', 'address', 'Country', 'Numberphone', 'email', 'bigImg', 'usernameBss', 'megaleBss', 'gbsbss', 'cantryBss', 'image'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function EdaretMewevin() {
        return $this->hasMany(EdaretMewevin::class);
    }

}
