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

Route::get('/', 'SiteController@index');
Route::get('/login','SiteController@login');
Route::post('/login','SiteController@login');
Route::get('/register','SiteController@register');

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
