<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
    protected $table= 'transaction_cashdeposit';
    protected $fillable = ["*"]; 
}
