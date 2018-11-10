<?php

use Illuminate\Http\Request;

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

Route::post('/qianxiangqunguan/checkexperienceuser', 'Api\GroupManagement@checkExperienceUser');
Route::post('/qianxiangqunguan/checkexformaluser', 'Api\GroupManagement@checkFormalUser');
Route::post('/qianxiangqunguan/add-experience', 'Api\GroupManagement@addExperience');

Route::post('/campus_help/handleWx', 'Api\Campus_help\Index@handleWx');
Route::post('/campus_help/showData', 'Api\Campus_help\Index@showData');
Route::post('/campus_help/checkSchool', 'Api\Campus_help\Index@checkSchool');