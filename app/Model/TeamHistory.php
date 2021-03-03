<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TeamHistory extends Model
{
	protected $table = "history_score";
	protected $fillable = ["gameid","team1","team2","commencetime"];

	public function teams()
	{
		return $this->belongsTo(Teams::class,'gameid');
	}
}