<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    //
    protected $table = "users";
    protected $fillable = ['first_name', 'last_name', 'email','password','state_residence','phone_number','sports_book','balance','username','token','referal_username','profile','provider','active','notification_token','role','free'];
}
