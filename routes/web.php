<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::post('/login_user','SiteController@dologin')->name('login_user');
Route::group(['middleware'=>['auth']],function(){
	Route::get('/',function(){
		return redirect(route('home'));
	});
	Route::get('/home', 'HomeController@index')->name('home');	
	Route::get('/logout','Auth/LoginController@logout')->name('logout');
	Route::get('/articles','SiteController@article')->name('articles');
	Route::get('/articles/edit','SiteController@article_edit')->name('article.edit');
});

