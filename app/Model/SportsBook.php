<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SportsBook extends Model
{
    //
    protected $table = "sportsbook";
    protected $fillable = ['site_key','site_nice'];
}
