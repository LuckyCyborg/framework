<?php

/*
|--------------------------------------------------------------------------
| Module Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for the module.
| It's a breeze. Simply tell Nova the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

Route::get( 'broadcast',        array('middleware' => 'auth', 'uses' => 'Sample@index'));
Route::get( 'broadcast/create', array('middleware' => 'auth', 'uses' => 'Sample@create'));
