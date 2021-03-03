<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

use App\Model\SportsBook;
use App\Model\Sport;

class AppController extends Controller
{

	public function __construct()
	{
		
	}

	public function getsportsbook(Request $request)
	{
		$sportsbook = array();
		foreach (SportsBook::all() as $sport) {
			array_push($sportsbook,array(
				'sitename'=>$sport->site_nice,
				'sitekey'=>$sport->site_key
			));
		}

		return $sportsbook;
	}

	public function getsportlist(Request $request)
	{
		$sports = array();

		foreach (Sport::all() as $sport) {
			array_push($sports,array('title'=>$sport->title,'logo'=>$sport->logo));
		}

		return $sports;
	}
}