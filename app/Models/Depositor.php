<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 

class Depositor extends Model
{
    protected $table= 'depositor'; 
    protected $fillable = ['*'];
}
