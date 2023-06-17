<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantUpi extends Model{
    protected $connection = 'pgsql';
    protected $fillable = ["*"];

    public function qr(){
        return $this->belongsTo(MerchantQr::class, 'merchantID','merchantID');
    }

    public function vpa(){
        return $this->hasOne(MerchantVpa::class, 'merchantID','merchant_code');
       // return $this->hasMany(MerchantQr::class,'refId', 'qr_refid',)
    }

    // public function qroption(): HasMany
    // {
    //     return $this->hasMany(MerchantQr::class,'refId', 'qr_refid',);
    // }
}