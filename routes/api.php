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
//Route for getting all the companies list
Route::post('/parsedata', 'CompanyListController@webparse'); //http://www.mycorporateinfo.com/industry/section/A

//Route for getting Company Details
Route::post('/Company/Details', 'CompanyListController@getCompanyLinks');

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
