<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Model\History;
use App\Model\User;

class HistoryController extends Controller
{
	public function __construct()
	{

	}

	public function gethistory(Request $request)
	{
		$token = $request->input('token');
		$user = User::where('token',$token)->first();

		if($user)
		{
			$history = History::where('userid',$user->id)->get();

			$list = array();
			foreach ($history as $key => $value) {
				if($value->alert)
				{
					$history[$key]->team = $value->alert->team;
					$history[$key]->alerttype = $value->alert->type;
					array_push($list,$history);
				}
			}

			return array('success'=>true,'history'=>$list);
		}
		else
		{
			return array('success'=>false);
		}
	}

	public function createhistory(Request $request)
	{
		
	}
}