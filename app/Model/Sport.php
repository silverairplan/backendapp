<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    //
    protected $table = "sports";
    protected $fillable = ['key', 'active', 'details','title','has_outrights','group'];
}
