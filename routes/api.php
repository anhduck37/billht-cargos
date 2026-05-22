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
Route::middleware([])->group(function () {
    Route::get('/district/{city_id}', 'DistrictController@getDistricts');
    Route::get('/ward/{district_id}', 'WardController@getWards');
    Route::get('/new-ward/{province_id}', 'WardController@getNewWards');
});

Route::group(['prefix' => 'webhook'], function () {
    Route::any('/viettel-post', 'WebhookController@viettelPost');
    Route::any('/ems', 'WebhookController@ems');
});

Route::group([], function () {
    Route::post('/auth/login', 'AuthController@login');
    Route::middleware(['auth:api_jwt'])->group(function () {
        Route::group(['prefix' => 'order'], function () {
            Route::get('/detail/{order_code}', 'OrderController@detail');
            Route::get('/tracking/{order_code}', 'OrderTrackingController@list');
        });
        Route::post('/auth/refresh', 'AuthController@refresh');
    });
});
