<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Watchlist extends Model
{
	protected $table = "watchlist";
	protected $fillable = ["userid","gameid","is_favourite"];

	function getteams()
	{
		return $this->belongsTo(Teams::class,'gameid');
	}
}