<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Sms_Model extends Model {
    
    protected $table= "sms";

    protected $fillable = ["*"];
}
?>
