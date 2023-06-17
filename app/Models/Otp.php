<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Otp extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $table="otp";
    protected $fillable = ['otp','name','otptype','status'];
}
