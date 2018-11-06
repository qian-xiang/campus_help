<?php

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/beikaobaodian/search/search_source', 'Beikaobaodian\Search@search_source');
Route::post('beikaobaodian/search/accept_source', 'Beikaobaodian\Search@accept_source');
Route::get('beikaobaodian/search/send_email', 'Beikaobaodian\Search@send_email');
Route::any('test', 'Test@index');