<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject; 

class BankList extends Model
{
    protected $table= 'bank_lists';
    protected $connection = 'pgsql';
    protected $fillable = ['name','ifsc','remarks','status'];
}
