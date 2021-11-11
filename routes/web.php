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
Route::get('/', function () {
    return redirect('/users', 301);
});
Route::get('/test', function () {
    $order = \App\Models\Order::get();
    return view('template.print', ['orders' => $order])->render();
});
Auth::routes();
Route::post('/register', 'Auth\RegisterController@create');
Route::middleware(['checkLevel'])->group(function() {
    Route::resource('users', 'UserController');
});
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', 'ProfileController@index');
    Route::any('/profile/password', 'ProfileController@changePass');
    Route::get('user/{id}', 'UserController@showFormPassword')->name('users.showFormPassword');
    Route::post('user/{id}','UserController@updatePassword' )->name('users.updatePassword');
    Route::get('user/{id}/info', 'UserController@show')->name('users.showInfo');
    Route::resource('orders', 'OrderController');
    Route::post('orders/import', 'OrderController@import')->name('orders.import');
    Route::get('order/import', 'OrderController@showFormImport')->name('orders.showFormImport');
    Route::get('users/{id}', 'UserController@show')->name('users.show');
    Route::resource('partners', 'PartnerController');
});
Route::get('/order/tracking', 'OrderTrackingController@tracking')->name('tracking');
