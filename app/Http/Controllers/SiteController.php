<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Model\User;

class SiteController extends Controller
{
    //
    public function index(Request $request)
    {
    	return view('welcome');
    }

    public function login(Request $request)
    {
    	return view('login');
    }

    public function dologout(Request $request)
    {
    	Auth::logout();
    	return Redirect::to('login');
    }

    public function dologin(Request $request)
    {
    	$rules = array(
	      'email' => 'required|email',
	      'password' => 'required|alphaNum|min:8');

    	$validator = Validator::make($request->input(),$rules);

    	if($validator->fails())
    	{
    		return Redirect::to('login')->withErrors($validator);
    	}
    	else
    	{
    		$userdata = array(
    			'email'=>$request->input('email'),
    			'password'=>$request->input('password')
    		);

    		$user = User::where('email',$userdata['email'])->first();
    		if($user)
    		{
    			if(!Hash::check($userdata['password'],$user->password))
    			{
    				return Redirect::to('login')->withErrors(['password'=>'The Password is incorrect']);	
    			}
    			else if($user->role != 'admin')
    			{
    				return Redirect::to('login')->withErrors(['role'=>"You can't access to this site"]);
    			}	
    			
    		}
    		else
    		{
    			return Redirect::to('login')->withErrors(['email'=>'Email does not exist']);
    		}

    		if(Auth::attempt($userdata))
    		{
    			return Redirect::to('/home');
    		}
    		else
    		{
    			return Redirect::to('login');
    		}
    	}
    }

    public function register(Request $request)
    {
    	return view('register');
    }
}
