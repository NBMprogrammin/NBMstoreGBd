<?php

namespace App\Models;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ZebouneForUser extends Model
{
    //

    protected $table = 'zeboune_for_users';
    protected $fillable = ['user_id', 'image', 'HaletDeyn', 'username', 'TotelDeyn', 'TotelBayMent', 'usernameBss', 'ConfirmedRelactionUserZeboune', 'ConfirmedRelactionUserBss', 'numberPhone', 'TypeAccounte', 'ConfirmedRelation'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
