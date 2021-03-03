<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Teams extends Model
{
    //
    protected $table = "teams";
    protected $fillable = ['team1', 'team2', 'spread','total','moneyline','commence_time','status'];
}
