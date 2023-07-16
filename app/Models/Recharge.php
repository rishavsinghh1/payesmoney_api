<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 

class Recharge extends Model
{
    protected $table= 'recharge'; 
    protected $fillable = ["*"]; 
}
