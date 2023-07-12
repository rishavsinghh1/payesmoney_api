<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 

class CommissionTemplate extends Model
{
    protected $table= 'commission_template'; 
    protected $fillable = ['*'];
}
