<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 

class UniqueRef extends Model
{
    protected $table= 'unique'; 
    protected $fillable = ["*"]; 
}
