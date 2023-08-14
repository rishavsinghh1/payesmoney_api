<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundTransaction extends Model
{
    protected $table= 'refund_transaction';
    protected $fillable = ["*"]; 
}
