<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::controller('user','UserController');

Route::get('action/{do}', 'UserController@action');
Route::get('score/{do}', 'UserController@score');
Route::get('download/{do}', 'UserController@download');