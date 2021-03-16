<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Model\SportList;
use App\Model\Sport;
use App\Model\Teams;
use App\Model\TeamHistory;

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

            $scorearray = $this->getscore($sport->title);

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
                        'spread'=>json_encode($sportinfo['totals']),
                        'commencetime'=>$sportinfo['commence_time']
                    ]);
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
