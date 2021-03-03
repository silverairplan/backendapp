<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SportList extends Model
{
	protected $table = "sportlist";
    protected $fillable = ['sport', 'data','title'];
}