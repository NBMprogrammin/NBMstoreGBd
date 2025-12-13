<?php

namespace App\Models;
use App\Models\User;
use App\Models\UserCategory;
use App\Models\PaymentProdectUserBss;

use Illuminate\Database\Eloquent\Model;

class ProdectUser extends Model
{
    protected $table = 'prodect_users';
    protected $fillable = ['user_id', 'TypePayprd', 'idBss', 'categoryID', 'name', 'price', 'currentPay', 'totaleinstorage'];


    public function user() {
        return $this->belongsToMany(User::class);
    }

    public function categorysUser() {
        return $this->belongsTo(UserCategory::class);
    }

    public function PaymentProdectUserBss() {
        return $this->belongsToMany(PaymentProdectUserBss::class)->withPivot('totaleinstorage');
    }
}
