<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Route::get("/sports",'SportBetting@getsports');
// Route::get("/sportsbook",'SportBetting@getsportsbook');
Route::get("/events",'SportBetting@getevents');
Route::get("/scores",'SportBetting@getscore');


Route::get("/app/sportsbook",'AppController@getsportsbook');
Route::get('/app/sports','AppController@getsportlist');


Route::post("/user/create",'UserController@create_user');
Route::post("/user/login",'UserController@login_user');
Route::post("/user/social_login",'UserController@social_login');
Route::post("/user/upload",'UserController@upload_profile');
Route::post("/user/setprofile","UserController@set_profile");
Route::get('/user/get','UserController@getuser');

Route::get('/watchlist/get','WatchlistController@getwatchlist');
Route::post('/watchlist/create','WatchlistController@createwatchlist');
Route::post('/watchlist/delete','WatchlistController@deletewatchlist');
Route::post('/watchlist/favourite','WatchlistController@setfavourite');

Route::post('/alert/update','AlertController@updatealert');
Route::get('/alert/get','AlertController@getalerts');

Route::get('/article/get','ArticleController@get_article');
Route::get('/article/init','ArticleController@init_article');