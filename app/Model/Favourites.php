<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Favourites extends Model
{
    //
    protected $table = "favourites";
    protected $fillable = ['title', 'content'];
}
