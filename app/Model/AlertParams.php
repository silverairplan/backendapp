<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AlertParams extends Model
{
	protected $table = "alertparams";
	protected $fillable = ["userid","gameid","value","odd","team","type","minutes","commencetime"];

	public function teams()
	{
		return $this->belongsTo(Teams::class,'gameid');
	}

	public function user()
	{
		return $this->belongsTo(User::class,'userid');
	}
}