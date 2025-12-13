<?php

namespace App\Models;
use App\Models\User;
use App\Models\ProdectUser;

use Illuminate\Database\Eloquent\Model;

class PaymentProdectUserBss extends Model
{
    protected $table = 'payment_prodect_user_bsses';
    protected $fillable = ['user_id', 'idbss', 'currentPay', 'typMeshole', 'idMeshole', 'allquantitelprodect', 'namezeboune', 'numberzeboune', 'typeaccountzeboune', 'idaccountzeboune', 'totalprodectspay', 'totalpriceprodectspay', 'nameprodectspay', 'idprodectspay', 'priceprodectspay', 'quantiteyprodectspay', 'imgconfirmedpay', 'typepayment', 'paymentmethod', 'numberpaymentmethod'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function ProdectUser() {
        return $this->belongsToMany(ProdectUser::class)->withPivot('totaleinstorage');
    }
}
