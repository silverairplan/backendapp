<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Model\SportList;
use App\Model\Sport;
use App\Model\Teams;
use App\Model\TeamHistory;
use App\Model\History;
use App\Model\AlertParams;

class sportlistcommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:sportlist';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'updating sportlist table';

    protected $api_key;

    protected $api_url;

    protected $api_feed_key;

    protected $api_feed_host;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->api_key = env('API_KEY',false);

        $this->api_url = env('API_URL',false);

        $this->api_feed_key = env('FEED_API_KEY',false);

        $this->api_feed_host = env('FEED_API_HOST',false);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $client = new Client();

        foreach (Sport::all() as $sport) {
            $response = $client->request('GET',$this->api_url . '/v3/odds',[
                'query'=>[
                    'apiKey'=>$this->api_key,
                    'region'=>'us',
                    'sport'=>$sport->key,
                    'oddsFormat'=>'american'
                ]
            ]);

            $sportlistinfo = SportList::where('sport',$sport->key)->first();

            $datetime = strtotime($sportlistinfo->updated_at);
            $now = new \DateTime();
            $now = $now->format('U');

            $databefore = json_decode($sportlistinfo->data,true);

            echo 'now:' . $now;

            echo 'datetime:' . $datetime;

            if($now - $datetime < 60)
            {
                $inline = false;
                foreach ($databefore as $key => $value) {
                    if($value['status'] == 'in progress')
                    {
                        $inline = true;
                        break;
                    }        
                } 

                if(!$inline)
                {
                    continue;
                }   
            }
            

            $sportslist = json_decode($response->getBody(),true);

            $sportsarray = [];
            $scoreenable = false;

            $nowtime = new \DateTime();
            if($nowtime->format('H') < 3 || $nowtime->format('H') > 6)
            {
                $scorearray = $this->getscore($sport->title);    
                $scoreenable = true;
            }
            

            $valid_games = array();
            if($sportslist['success'])
            {
                $sportslist = $sportslist['data'];

                foreach ($sportslist as $keyinfo => $sportinfo) {
                    $enable = false;

                    $hometeam = $sportinfo['home_team'];
                    $away_team = $sportinfo['teams'][0];

                    if($away_team == $hometeam)
                    {
                        $away_team = $sportinfo['teams'][1];
                    }

                    foreach ($scorearray as $item) {

                        if($item['teams'][1] == $hometeam && $item['teams'][0] == $away_team)
                        {
                            array_push($sportsarray,array(
                                'commence_time'=>$sportinfo['commence_time'],
                                'home_team'=>$sportinfo['home_team'],
                                'teams'=>$sportinfo['teams'],
                                'scheduled'=>$item['scheduled'],
                                'status'=>$item['status'],
                                'moneyline'=>$sportinfo['sites'],
                                'scoreboard'=>isset($item['scoreboard'])?$item['scoreboard']:array()
                            ));


                            array_push($valid_games, $keyinfo);
                            break;
                        }
                    }

                    if(!$scoreenable)
                    {
                        array_push($sportsarray,array(
                            'commence_time'=>$sportinfo['commence_time'],
                            'home_team'=>$sportinfo['home_team'],
                            'teams'=>$sportinfo['teams'],
                            'scheduled'=>gmdate('Y-M-d H:i:s',$sportinfo['commence_time']),
                            'status'=>'scheduled',
                            'moneyline'=>$sportinfo['sites'],
                            'scoreboard'=>array()
                        ));

                        array_push($valid_games, $keyinfo);
                    }
                }
            }    

            $response = $client->request('GET',$this->api_url . '/v3/odds',[
                'query'=>[
                    'apiKey'=>$this->api_key,
                    'region'=>'us',
                    'sport'=>$sport->key,
                    'mkt'=>'spreads',
                    'oddsFormat'=>'american'
                ]
            ]);

            $sportslist = json_decode($response->getBody(),true);

            if($sportslist['success'])
            {
                $sportslist = $sportslist['data'];

                foreach ($valid_games as $key => $gameitem) {
                    $sportsarray[$key]['spreads'] = $sportslist[$gameitem]['sites'];
                }
            }

            $response = $client->request('GET',$this->api_url . '/v3/odds',[
                'query'=>[
                    'apiKey'=>$this->api_key,
                    'region'=>'us',
                    'sport'=>$sport->key,
                    'mkt'=>'totals',
                    'oddsFormat'=>'american'
                ]
            ]);

            $sportslist = json_decode($response->getBody(),true);

            if($sportslist['success'])
            {
                $sportslist = $sportslist['data'];

                foreach ($valid_games as $key => $gameitem) {
                    $sportsarray[$key]['totals'] = $sportslist[$gameitem]['sites'];
                }
            }

            foreach ($sportsarray as $sportinfo) {
                $team = Teams::where('team1',$sportinfo['teams'][0])->where('team2',$sportinfo['teams'][1])->first();

                if(!$team)
                {
                    $team = Teams::where('team1',$sportinfo['teams'][1])->where('team2',$sportinfo['teams'][0])->first();
                }

                if($team)
                {
                    if($team->commence_time != $sportinfo['commence_time'] || $team->status != $sportinfo['status'])
                    {
                        $team->update([
                            'commence_time'=>$sportinfo['commence_time'],
                            'moneyline'=>json_encode($sportinfo['moneyline']),
                            'total'=>json_encode($sportinfo['totals']),
                            'spread'=>json_encode($sportinfo['spreads']),
                            'status'=>$sportinfo['status']
                        ]);
                    }
                }
                else
                {
                    $team = Teams::create([
                        'team1'=>$sportinfo['teams'][0],
                        'team2'=>$sportinfo['teams'][1],
                        'commence_time'=>$sportinfo['commence_time'],
                        'moneyline'=>json_encode($sportinfo['moneyline']),
                        'total'=>json_encode($sportinfo['totals']),
                        'spread'=>json_encode($sportinfo['spreads']),
                        'status'=>$sportinfo['status']
                    ]);
                }

                if($team->status == 'in progress')
                {
                    $index = array_search($team->team1,$sportinfo['teams']);

                    TeamHistory::create([
                        'gameid'=>$team->id,
                        'total'=>json_encode($sportinfo['totals']),
                        'moneyline'=>json_encode($sportinfo['moneyline']),
                        'spread'=>json_encode($sportinfo['spreads']),
                        'commencetime'=>$sportinfo['commence_time']
                    ]);

                    $alertinfos = AlertParams::where('gameid',$team->id)->get();

                    if($alertinfos && count($alertinfos) > 0)
                    {
                        foreach ($alertinfos as $alertinfo) {
                            if($sportinfo['commence_time'] == $alertinfo->commencetime)
                            {
                                switch ($alertinfo->type) {
                                    case 'SPREAD':
                                        $index = array_search($alertinfo->team, $sportinfo['teams']);
                                        if($index > -1)
                                        {
                                            $spreads = $this->getvalue($sportinfo['spreads'],'spreads',$alertinfo->user->sportsbook);
                                            if($spreads['points'][$index] > $alertinfo->value)
                                            {
                                                History::create([
                                                    'alertid'=>$alertinfo->id,
                                                    'value'=>$spreads['points'][$index],
                                                    'period'=>$sportinfo['scoreboard']['periodTimeRemaining'] . ' ' . $sportinfo['scoreboard']['currentPeriod'] . 'Q',
                                                    'type'=>'point',
                                                    'userid'=>$alertinfo->user->id
                                                ]);
                                            }

                                            if($spreads['odds'][$index] > $alertinfo->odd)
                                            {
                                                History::create([
                                                    'alertid'=>$alertinfo->id,
                                                    'value'=>$spreads['odds'][$index],
                                                    'period'=>$sportinfo['scoreboard']['periodTimeRemaining'] . ' ' . $sportinfo['scoreboard']['currentPeriod'] . 'Q',
                                                    'type'=>'odd',
                                                    'userid'=>$alertinfo->user->id
                                                ]);
                                            }
                                        }
                                        break;
                                    case 'TOTAL':
                                        $index = array_search($alertinfo->team, $sportinfo['teams']);
                                        if($index > -1)
                                        {
                                            $totals = $this->getvalue($sportinfo['totals'],'totals',$alertinfo->user->sportsbook);
                                            if($totals['points'][$index] > $alertinfo->value)
                                            {
                                                History::create([
                                                    'alertid'=>$alertinfo->id,
                                                    'value'=>$totals['points'][$index],
                                                    'period'=>$sportinfo['scoreboard']['periodTimeRemaining'] . ' ' . $sportinfo['scoreboard']['currentPeriod'] . 'Q',
                                                    'type'=>'point',
                                                    'userid'=>$alertinfo->user->id
                                                ]);
                                            }

                                            if($totals['odds'][$index] > $alertinfo->odd)
                                            {
                                                History::create([
                                                    'alertid'=>$alertinfo->id,
                                                    'value'=>$totals['odds'][$index],
                                                    'period'=>$sportinfo['scoreboard']['periodTimeRemaining'] . ' ' . $sportinfo['scoreboard']['currentPeriod'] . 'Q',
                                                    'type'=>'odd',
                                                    'userid'=>$alertinfo->user->id
                                                ]);
                                            }
                                        }
                                        break;
                                    case 'MONEYLINE':
                                        $index = array_search($alertinfo->team, $sportinfo['teams']);
                                        if($index > -1)
                                        {
                                            $moneyline = $this->getvalue($sportinfo['moneyline'],'moneyline',$alertinfo->user->sportsbook);
                                            if($moneyline[$index] > $alertinfo->value)
                                            {
                                                History::create([
                                                    'alertid'=>$alertinfo->id,
                                                    'value'=>$moneyline[$index],
                                                    'period'=>$sportinfo['scoreboard']['periodTimeRemaining'] . ' ' . $sportinfo['scoreboard']['currentPeriod'] . 'Q',
                                                    'type'=>'point',
                                                    'userid'=>$alertinfo->user->id
                                                ]);
                                            }
                                        }
                                        break;
                                    
                                    default:
                                        # code...
                                        break;
                                }    
                            }
                            
                        }
                    }
                }   
                else
                {
                    TeamHistory::where('gameid',$team->id)->delete();
                }
            }

            $sportlistinfo->update(['data'=>json_encode($sportsarray)]);
        }
        

        //return $response->getBody();

        //
    }

    public function getvalue($data,$type,$site)
    {
        foreach ($data as $item) {
            if($item['site_key'] == $site)
            {
                if($type == 'moneyline')
                {
                    return $data['moneyline'];
                }
                else
                {
                    return $data['odds'][$type];
                }
            }
        }

        return false;
    }

    public function getscore($game)
    {
        
        $client = new Client();
        $score_array = array();
        $response_score = $client->request('GET','https://' . $this->api_feed_host . '/games',[
            'query'=>[
                'league'=>$game
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
                if($item['status'] == 'scheduled' || $item['status'] == 'in progress')
                {
                    array_push($score_array,array(
                        'scheduled'=>$item['schedule']['date'],
                        'status'=>$item['status'],
                        'teams'=>[$this->getteamname($item['teams']['away']),$this->getteamname($item['teams']['home'])],
                        'scoreboard'=>isset($item['scoreboard'])?$item['scoreboard']:array()
                    ));
                }
            }
        }

        return $score_array;
    }

    public function getteamname($team)
    {
        $teamname = $team['team'];

        if(isset($team['mascot']))
        {
            $arrayname = explode(' ', $teamname);
            if($arrayname[count($arrayname) - 1] != $team['mascot']) 
            {
                $teamname .= ' ' . $team['mascot'];
            }   
        }

        return $teamname;
    }
}
