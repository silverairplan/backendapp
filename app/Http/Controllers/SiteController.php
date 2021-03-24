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
    		return Redirect::to('login')->withErrors($validator)->withInput(Input::except('password'));
    	}
    	else
    	{
    		$userdata = array(
    			'email'=>Input::get('email'),
    			'password'=>Input::get('password')
    		);

    		$user = User::where('email')->first();
    		if($user)
    		{
    			if(!Hash::check($userdata['password'],$user->password))
    			{
    				return Redirect::to('login')->withErrors(['password'=>'The Password is incorrect']);	
    			}
    			
    		}
    		else
    		{
    			return Redirect::to('login')->withErrors(['email'=>'Email does not exist']);
    		}

    		if(Auth::attempt($userdata))
    		{
    			return Redirect::to('/');
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
