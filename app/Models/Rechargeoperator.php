<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 

class Rechargeoperator extends Model
{
    protected $table= 'recharge_operator'; 
    protected $fillable = ["*"]; 
}
