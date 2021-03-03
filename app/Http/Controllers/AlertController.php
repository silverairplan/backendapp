<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Model\User;
use App\Model\Teams;
use App\Model\AlertParams;

class AlertController extends Controller
{
	public function __construct()
	{

	}

	public function getalerts(Request $request)
	{
		$token = $request->input('token');
		$user = User::where('token',$token)->first();

		if($user)
		{
			$alerts = AlertParams::where('userid',$user->id)->get();
			$list = array();
			foreach ($alerts as $alert) {
				array_push($list,array(
					'id'=>$alert->id,
					'team1'=>$alert->teams->team1,
					'team2'=>$alert->teams->team2,
					'value'=>$alert->value,
					'odd'=>$alert->odd,
					'team'=>$alert->team,
					'type'=>$alert->type,
					'commencetime'=>$alert->commencetime,
					'minutes'=>$alert->minutes
				));
			}

			return array('success'=>true,'data'=>$list);
		}
		else
		{
			return array('success'=>false);
		}
	}

	public function updatealert(Request $request)
	{
		$token = $request->input('token');
		$teams = $request->input('teams');
		$type = $request->input('type');
		$value = $request->input('value');
		$odd = $request->input('odd');
		$minute = $request->input('minute');
		$user = User::where('token',$token)->first();
		$teaminfo = $request->input('team');
		$commencetime = $request->input('commencetime');
		if($user)
		{
			$team = Teams::where('team1',$teams[0])->where('team2',$teams[1])->first();

			if(!$team)
			{
				$team = Teams::where('team2',$teams[0])->where('team1',$teams[1])->first();
			}

			if($team)
			{
				$alert = AlertParams::where('userid',$user->id)->where('gameid',$team->id)->where('team',$teaminfo)->where('type',$type)->first();

				if(!$alert)
				{
					if($user->balance < 1)
					{
						return array('success'=>false,'message'=>"You don't have enough balance to set alert paramaters");
					}

					$user->balance --;
					$user->save();

					$alert = new AlertParams(array(
						'userid'=>$user->id,
						'gameid'=>$team->id,
						'value'=>$value,
						'odd'=>$odd,
						'team'=>$teaminfo,
						'minutes'=>$minute,
						'commencetime'=>$commencetime,
						'type'=>$type
					));

					$alert->save();	
				}
				else
				{
					if($alert->commencetime != $commencetime)
					{
						if($user->balance < 1)
						{
							return array('success'=>false,'message'=>"You don't have enough balance to set alert paramaters");
						}

						$user->balance --;
						$user->save();
					}

					$alert->update(
						[
							'value'=>$value,
							'odd'=>$odd,
							'minutes'=>$minute
						]
					);
				}

				return array('success'=>true,'balance'=>$user->balance);
			}
			else
			{
				return array('success'=>false,'message'=>'This game is not exist');
			}
		}
		else
		{
			return array('success'=>false,'message'=>'User token has been expired');
		}
	}
}

?>