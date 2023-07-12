<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 

class CommissionModel extends Model
{
    protected $table= 'commission'; 
    protected $fillable = ['*'];
}
