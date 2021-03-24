<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    //
    protected $table = "history";
    protected $fillable = ['alertid', 'value','period','type','userid'];

    public function alert()
	{
		return $this->belongsTo(AlertParams::class,'alertid');
	}
}
