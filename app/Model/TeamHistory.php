<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TeamHistory extends Model
{
	protected $table = "history_score";
	protected $fillable = ["gameid","total","moneyline","spread","commencetime"];

	public function teams()
	{
		return $this->belongsTo(Teams::class,'gameid');
	}
}