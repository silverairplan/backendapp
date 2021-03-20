<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Model\Sport;
use App\Model\SportsBook;
use MySportsFeeds\MySportsFeeds;
use App\Model\SportList;
use App\Model\User;
use App\Model\Teams;
use App\Model\TeamHistory;

class SportBetting extends Controller
{
	protected $api_key;
	protected $api_url;
	protected $api_feed_key;
	protected $api_feed_password;

	public function __construct()
	{
		$this->api_key = env('API_KEY',false);
		$this->api_url = env('API_URL',false);
		$this->api_feed_key = env('FEED_API_KEY',false);
		$this->api_feed_host = env('FEED_API_HOST',false);
	}

    public function getsports()
    {
    	$client = new Client();
    	$response = $client->request('GET',$this->api_url . '/v3/sports',[
    		'query'=>[
    			'apiKey'=>$this->api_key,
    			'all'=>true
    		]
    	]);

    	$content = $response->getBody();

    	$contentjson = json_decode($content,true);

    	foreach ($contentjson['data'] as $key => $value) {
    		$sport = new Sport($value);
    		$sport->save();
    	}
    	return 'success';
    }

    public function getsportsbook()
    {
    	$client = new Client();

    	foreach (Sport::all() as $sport) {
    		$response = $client->request('GET',$this->api_url . '/v3/odds',[
	    		'query'=>[
	    			'apiKey'=>$this->api_key,
	    			'region'=>'us',
	    			'sport'=>$sport->key
	    		]
	    	]);

	    	$content = $response->getBody();

	    	$contentjson = json_decode($content,true);

	    	foreach ($contentjson['data'] as $key => $value) {
	    		foreach ($value['sites'] as $key => $item) {
	    			if(!SportsBook::where('site_key',$item['site_key'])->first())
	    			{
	    				$sportsbook = new SportsBook(['site_key'=>$item['site_key'],'site_nice'=>$item['site_nice']]);
	    				$sportsbook->save();
	    			}
	    		}
	    	}
    	}
    	
    	return 'success';
    }

    public function getevents(Request $request)
    {
    	$date = $request->input('date');
    	$site = $request->input('site');
        $token = $request->input('token');

        if($token)
        {
            if(!User::where('token',$token)->first())
            {
                return array('success'=>false,'message'=>'You have to signin to get this data');           
            }
        }
        else
        {
            return array('success'=>false,'message'=>'You have to signin to get this data');
        }

    	$resultarray = array();
    	foreach(Sport::all() as $sport)
    	{
    		$resultarray[$sport->title] = array();

    		$sportinfo = SportList::where('sport',$sport->key)->first();

            $result = json_decode($sportinfo->data,true);

            $resultarray[$sport->title] = array();

            foreach ($result as $key => $sportdata) {
                $sportdatainfo = array(
                    'commence_time'=>$sportdata['commence_time'],
                    'home_team'=>$sportdata['home_team'],
                    'teams'=>$sportdata['teams'],
                    'scoreboard'=>$sportdata['scoreboard'],
                    'status'=>$sportdata['status']
                );

                $enable = false;

                foreach ($sportdata['moneyline'] as $datainfo) {
                    if($datainfo['site_key'] == $site)
                    {
                        $enable = true;
                        $sportdatainfo['moneyline'] = $datainfo['odds']['h2h'];
                        break;
                    }
                }

                if($enable)
                {
                    $enable = false;
                    foreach ($sportdata['spreads'] as $datainfo) {
                        if($datainfo['site_key'] == $site)
                        {
                            $enable = true;
                            $sportdatainfo['spreads'] = $datainfo['odds']['spreads'];
                            break;
                        }
                    }

                }
                
                if($enable)
                {
                    $enable = false;
                    foreach ($sportdata['totals'] as $datainfo) {
                        if($datainfo['site_key'] == $site)
                        {
                            $enable = true;
                            $sportdatainfo['totals'] = $datainfo['odds']['totals'];
                            break;
                        }
                    }
                }
                
                if($enable)
                {
                    $team = null;
                    if($sportdatainfo['status'] == 'in progress')
                    {
                        $team = Teams::where('team1',$sportdatainfo['teams'][0])->where('team2',$sportdatainfo['teams'][1])->first();
                    }

                    if($team)
                    {
                        $moneyline = json_decode($team->moneyline,true);

                        $enable = false;
                        foreach ($moneyline as $info) {
                            if($info['site_key'] == $site)
                            {
                                $sportdatainfo['closed_moneyline'] = $info['odds']['h2h'];
                                $enable = true;
                                break;
                            }
                        }

                        if(!$enable)
                        {
                            $sportdatainfo['closed_moneyline'] = $sportdatainfo['moneyline'];
                        }

                        $enable = false;
                        $totals = json_decode($team->total,true);
                        foreach ($totals as $info) {
                            if($info['site_key'] == $site)
                            {
                                $sportdatainfo['closed_totals'] = $info['odds']['totals'];
                                $enable = true;
                                break;
                            }
                        }             

                        if(!$enable)
                        {
                            $sportdatainfo['closed_totals'] = $sportdatainfo['totals'];
                        }               

                        $enable = false;
                        $spreads = json_decode($team->spread,true);
                        foreach ($spreads as $info) {
                            if($info['site_key'] == $site)
                            {
                                $sportdatainfo['closed_spreads'] = $info['odds']['spreads'];
                                $enable = true;
                                break;
                            }
                        }

                        if(!$enable)
                        {
                            $sportdatainfo['closed_spreads'] = $sportdatainfo['spreads'];
                        }

                        $sportdatainfo['team1'] = $team->team1;
                        $sportdatainfo['team2'] = $team->team2;
                    }
                    else
                    {
                        $sportdatainfo['closed_spreads'] = $sportdatainfo['spreads'];
                        $sportdatainfo['closed_totals'] = $sportdatainfo['totals'];
                        $sportdatainfo['closed_moneyline'] = $sportdatainfo['moneyline'];
                    }

                    $sportdatainfo['history'] = array();
                    $teamlists = TeamHistory::where('gameid',$team->id)->get();

                    foreach ($teamlists as $teamhistory) {
                        $spreads = json_decode($teamhistory->spread,true);
                        $totals = json_decode($teamhistory->total,true);
                        $moneyline = json_decode($teamhistory->moneyline,true);
                        $spreadinfo = array();
                        foreach ($spreads as $info) {
                            if($info['site_key'] == $site)
                            {
                                $spreadinfo['spread'] = $info['odds']['spreads'];
                            }
                        }

                        foreach ($totals as $info) {
                            if($info['site_key'] == $site)
                            {
                                $spreadinfo['total'] = $info['odds']['totals'];
                            }
                        }

                        foreach ($moneyline as $info) {
                            if($info['site_key'] == $site)
                            {
                                $spreadinfo['moneyline'] = $info['odds']['h2h'];
                            }
                        }

                        array_push($sportdatainfo['history'], $spreadinfo);
                    }

                    array_push($resultarray[$sport->title],$sportdatainfo);

                }
            }
    	}

        return array('success'=>true,'result'=>$resultarray);
    }


    function getscore($game,$date)
    {
    	$client = new Client();
    	$score_array = array();
    	$response_conf = $client->request('GET','https://' . $this->api_feed_host . '/conferences',[
    		'query'=>[
    			'league'=>$game
    		],
    		'headers'=>[
    			'x-rapidapi-key'=>$this->api_feed_key,
    			'x-rapidapi-host'=>$this->api_feed_host,
    			'useQueryString'=>true
    		]
    	]);



    	$conference = json_decode($response_conf->getBody(),true);

    	if($conference['status'] == 200)
    	{
    		$conf_array = array();

    		foreach ($conference['results'] as $key => $value) {
    			if(!in_array($value['conference'],$conf_array))
    			{
    				array_push($conf_array,$value['conference']);
    				$response_score = $client->request('GET','https://' . $this->api_feed_host . '/games',[
	    				'query'=>[
	    					'league'=>$game,
	    					'conference'=>$value['conference']
	    				],
	    				'headers'=>[
			    			'x-rapidapi-key'=>$this->api_feed_key,
			    			'x-rapidapi-host'=>$this->api_feed_host,
			    			'useQueryString'=>true
			    		]
	    			]);

	    			$score_response = json_decode($response_score->getBody(),true);
	    			if($score_response['status'] == 200)
	    			{
	    				foreach ($score_response['results'] as $item) {
	    					array_push($score_array,array(
	    						'scheduled'=>$item['schedule']['date'],
	    						'status'=>$item['status'],
	    						'teams'=>[$item['teams']['away']['team'] . ($item['teams']['away']['mascot']?' ' . $item['teams']['away']['mascot']:''),$item['teams']['home']['team'] . ($item['teams']['home']['mascot']?' ' . $item['teams']['home']['mascot']:'')],
	    						'scoreboard'=>isset($item['scoreboard'])?$item['scoreboard']:array()
	    					));
	    				}
	    			}
    			}
    			
    		}
    	}

    	return $score_array;
	}


}
