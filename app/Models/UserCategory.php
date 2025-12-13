<?php

namespace App\Models;
use App\Models\User;
use App\Models\ProdectUser;

use Illuminate\Database\Eloquent\Model;

class UserCategory extends Model
{
    //
    protected $table = 'user_categories';
    protected $fillable = ['user_id', 'category', 'IdBss', 'passwordSetting'];


    public function user() {
        return $this->belongsToMany(User::class);
    }

    public function prodectUse() {
        return $this->hasMany(ProdectUser::class,);
    }
}
