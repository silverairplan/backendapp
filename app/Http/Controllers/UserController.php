<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use App\Model\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\verification;


class UserController extends Controller
{
	public function __construct()
	{

	}

	public function create_user(Request $request)
	{
		$user = $request->input();

		if(User::where('email',$user['email'])->first())
		{
			echo json_encode(array('success'=>false,'message'=>'Email is already exist'));
		}
		else if(User::where('username',$user['username'])->first())
		{
			echo json_encode(array('success'=>false,'message'=>'Username is already exist'));
		}
		else if(User::where('phone_number',$user['phone_number'])->first())
		{
			echo json_encode(array('success'=>false,'message'=>'Phone Number is already exist'));
		}
		else
		{

			$user['password'] = bcrypt($user['password']);
			$user['balance'] = 5;
			$user['active'] = 0;

			if(isset($user['referal_username']))
			{
				$userinfo = User::where('username',$user['referal_username'])->first();
				
				if($userinfo)
				{
					$userinfo->update(['balance'=>$userinfo->balance + 5]);
				}	
			}
			
			$userinfo = new User($user);
			$userinfo->save();

			return array('success'=>true);
		}
	}

	public function login_user(Request $request)
	{
		$user = $request->input();

		$userinfo = User::where('username',$user['username'])->first();

		if(!$userinfo)
		{
			$userinfo = User::where('email',$user['username'])->first();
		}

		if(!$userinfo)
		{
			echo json_encode(array('success'=>false,'message'=>'Username or Email does not exist'));
		}
		else
		{

			if(Hash::check($user['password'],$userinfo->password))
			{
				if(!$userinfo->active)
				{
					echo json_encode(array('success'=>false,'message'=>'You have to verify your account'));
				}
				else
				{
					$credential = Str::random(60);
					$userinfo->update(['token'=>$credential]);
					echo json_encode(array('success'=>true,'token'=>$credential));	
				}
				
			}
			else
			{
				echo json_encode(array('success'=>false,'message'=>'The password is incorrect'));
			}
		}

	}

	public function social_login(Request $request)
	{
		$user = $request->input();

		$userinfo = User::where("email",$user['email'])->first();

		if($userinfo)
		{
			$credential = Str::random(60);
			$userinfo->update(['token'=>$credential]);
			return array('success'=>true,'token'=>$credential);
		}
		else
		{
			$user['token'] = Str::random(60);
			$user['balance'] = 5;
			$user['active'] = 1;
			
			$userinfo = new User($user);
			$userinfo->save();

			return array('success'=>true,"token"=>$user['token']);
		}
	}


	public function getuser(Request $request)
	{
		$token = $request->input('token');

		$userinfo = User::where('token',$token)->first();

		if($userinfo)
		{
			return array('success'=>true,'userinfo'=>$userinfo);
		}
		else
		{
			return array('success'=>false);
		}
	}

	public function updatebalance(Request $request)
	{
		$balance = $request->input('balance');
		$token = $request->input('token');
		$free = $request->input('free');
		$user = User::where('token',$token)->first();

		if($user)
		{
			if($free)
			{
				$datetime = strtotime($free);
				$user->update('free',date('Y-m-d',$datetime));
				return array('success'=>true,'user'=>$user);
			}
			else if($balance)
			{
				$user->update(['balance',$balance]);
				return array('success'=>true,'user'=>$user);
			}
		}
		else
		{
			return array('success'=>false);
		}
	}

	public function upload_profile(Request $request)
	{
		$token = $request->input('token');

		$user = User::where('token',$token)->first();

		if($user)
		{
			$file = $request->file('profile');
			$upload_destination = "public/profile";
			$file->move($upload_destination,$file->getClientOriginalName());
			$user->update(['profile'=>"profile/" . $file->getClientOriginalName()]);
			return array('success'=>true,'uri'=>"profile/" . $file->getClientOriginalName());
		}
		else
		{
			return array('success'=>false);
		}
	}

	public function set_notificationtoken(Request $request)
	{
		$token = $request->input('token');
		$notification = $request->input('notification');

		$user = User::where('token',$token)->first();

		if($user)
		{
			$user->update(['notification_token'=>$notification]);
			return array('success'=>true);
		}
		else
		{
			return array('success'=>false);
		}
	}

	public function set_profile(Request $request)
	{
		$token = $request->input('token');
		$updateinfo = $request->input('updateinfo');
		$user = User::where('token',$token)->first();

		$userinfo = false;

		if($user)
		{
			if(isset($updateinfo['username']))
			{
				$userinfo = User::where('username',$updateinfo['username'])->where('id','!=',$user->id)->first();
				if($userinfo)
				{
					return array('success'=>false,'message'=>'The username already exists');
				}
			}

			if(isset($updateinfo['email']))
			{
				$userinfo = User::where('email',$updateinfo['email'])->where('id','!=',$user->id)->first();	
				return array('success'=>false,'message'=>'The email already exists');
			}


			$user->update($updateinfo);
			return array('success'=>true);
		}
		else
		{
			return array('success'=>false,'message'=>'Token has been expired');
		}
	}
}