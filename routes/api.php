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
Route::post('/campus_help/showRequiredRecord', 'Api\Campus_help\Owner@showRequiredRecord');
Route::post('/campus_help/getUserInformation', 'Api\Campus_help\Owner@getUserInformation');
Route::post('/campus_help/modifyInformation', 'Api\Campus_help\Owner@modifyInformation');
Route::post('/campus_help/publish', 'Api\Campus_help\Publish@post');
Route::post('/campus_help/userAction', 'Api\Campus_help\UserAction@action');
Route::post('/campus_help/handleBrowseCount', 'Api\Campus_help\UserAction@handleBrowseCount');
Route::post('/campus_help/detail', 'Api\Campus_help\UserAction@detail');
Route::post('/campus_help/deleteRequiredRecord', 'Api\Campus_help\Owner@deleteRequiredRecord');
Route::post('/campus_help/confirmFinish', 'Api\Campus_help\Owner@confirmFinish');
Route::post('/campus_help/ownerLove', 'Api\Campus_help\Owner@ownerLove');
Route::post('/campus_help/deleteMyLove', 'Api\Campus_help\Owner@deleteMyLove');
Route::post('/campus_help/getQuestionList', 'Api\Campus_help\Owner@getQuestionList');
Route::post('/campus_help/getKeyWords', 'Api\Campus_help\Search@getKeyWords');
Route::post('/campus_help/submitAccusation', 'Api\Campus_help\Index@submitAccusation');
Route::post('/campus_help/getHelpData', 'Api\Campus_help\Help@getHelpData');
Route::post('/campus_help/handleHelp', 'Api\Campus_help\Help@handleHelp');
Route::post('/campus_help/postImagesUpload', 'Api\Campus_help\Publish@postImagesUpload');
