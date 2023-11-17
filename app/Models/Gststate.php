<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 

class Gststate extends Model
{
    protected $table= 'gst_state'; 
    protected $fillable = ['*'];
}
