<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\User;

class MyOrdersPayBss extends Model
{
    //
    protected $table = 'my_orders_pay_bsses';
    protected $fillable = ['user_id', 'usernameBss', "TypeOrder", 'namebss', 'idMeshole', 'typMeshole', 'typeorderforzeboune', 'currentPay', 'namezeboune', 'idusergetorder', 'nameusergetorder', 'numberusergetorder', 'numberzeboune', 'typeaccountzeboune', 'idaccountzeboune', 'allquantitelprodect', 'totalprodectspay', 'totalpriceprodectspay', 'nameprodectspay', 'idprodectspay', 'priceprodectspay', 'quantiteyprodectspay', 'imgprofilezeboune', 'imgconfirmedpay', 'typepayment', 'paymentmethod', 'numberpaymentmethod'];

    public function user() {
        return $this->belongsToMany(User::class);
    }
}
