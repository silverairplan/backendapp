<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Model\User;
use App\Model\Article;

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

    public function article(Request $request)
    {
    	$articles = Article::all();
    	return view('article',['articles'=>$articles]);
    }

    public function article_edit(Request $request)
    {
    	$id = $request->input('id');

    	$article = false;

    	if($id)
    	{
    		$article = Article::where('id',$id)->first();	
    	}
    	

    	return view('article_edit',['article'=>$article]);
    }
    
    public function article_update(Request $request)
    {
    	$data = $request->input();

    	if(isset($data['id']))
    	{
    		$article = Article::where('id',$id)->first();
    		$article->update($data);
    		Redirect::to('article.edit')->with('id',$article->id);
    	}
    	else
    	{
    		$article = Article::create($data);
    		Redirect::to('article.edit')->with('id',$article->id);
    	}
    }

    public function article_delete(Request $request)
    {
    	$id = $request->input('id');

    	Article::where('id',$id)->delete();
    	return Redirect::to('articles');
    }
}
