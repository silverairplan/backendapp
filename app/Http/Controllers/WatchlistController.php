<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Model\Watchlist;
use App\Model\Teams;
use App\Model\User;

class WatchlistController extends Controller
{
	public function __construct()
	{

	}

	public function createwatchlist(Request $request)
	{
		$token = $request->input('token');
		$teams = $request->input('teams');

		$user = User::where('token',$token)->first();

		if($user)
		{
			$team = Teams::where('team1',$teams[0])->where('team2',$teams[1])->first();

			if(!$team)
			{
				$team = Teams::where('team1',$teams[1])->where('team2',$teams[0])->first();
			}



			if($team)
			{
				$watchlist = array(
					'userid'=>$user->id,
					'gameid'=>$team->id
				);

				$watchlistinfo = new Watchlist($watchlist);
				$watchlistinfo->save();
				return array('success'=>true);
			}
			else
			{
				return array('success'=>false);
			}
		}
		else
		{
			return array('success'=>false);
		}
	}

	public function deletewatchlist(Request $request)
	{
		$token = $request->input('token');
		$id = $request->input('id');

		$user = User::where('token',$token)->first();

		if($user)
		{
			Watchlist::where('id',$id)->delete();

			return array('success'=>true);
		}
		else
		{
			return array('success'=>false);
		}
	}

	public function getwatchlist(Request $request)
	{
		$token = $request->input('token');
		$userid = $request->input('userid');

		$user = User::where('token',$token)->first();

		if($user)
		{
			$watchlists = Watchlist::where('userid',$user->id)->get();
			$array = array();
			foreach($watchlists as $watchlist)
			{
				$team = $watchlist->getteams;
				array_push($array,array(
					'id'=>$watchlist->id,
					'team1'=>$team->team1,
					'team2'=>$team->team2
				));
			}

			return array('success'=>true,'data'=>$array);
			
		}
		else
		{
			return array('success'=>false);
		}
	}
}