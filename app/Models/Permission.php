<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 

class Permission extends Model
{
    protected $table= 'permission'; 
    protected $fillable = ["*"]; 
}
