<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\ProfileUserBss;

class EdaretMewevin extends Model
{
    //
    protected $table = 'edaret_mewevins';
    protected $fillable = ['user_id', 'idbss', 'edartOreders', 'idmessagreRel', "Ratibe", "curent", "totalMenths", 'totaledartPayProds', 'totalorders', 'totaledartmaney', 'totaledartPayEct', "PaymentEcteronect", 'nameMewve', 'numberMewve', 'typerelation', 'img', 'edartmebigate', 'edartPaymentProdects', 'edartemaney', 'confirmUser','confirmBss'];

    public function userBss() {
        return $this->belongsToMany(ProfileUserBss::class);
    }
}
