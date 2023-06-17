<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $dates = ['deleted_at'];
    protected $fillable = ['name', 'status'];

}
