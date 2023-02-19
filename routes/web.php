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
    return redirect('/orders');
});
// Route::get('/test', function () {
//    $order = \App\Models\Order::limit(10)->get();
//    $level = 1;
//    return view('template.print', ['orders' => $order, 'level' => $level])->render();
// });
// Route::get('email', function () {
//     $order = \App\Models\Order::first();
//     return view('template.email_success', ['order' => $order]);
// });

Auth::routes();
Route::post('/register', 'Auth\RegisterController@create');

Route::middleware(['checkLevel'])->group(function() {
    Route::resource('users', 'UserController');
});
Route::middleware(['auth'])->group(function () {
//    Route::get('/profile', 'ProfileController@index');
//    Route::any('/profile/password', 'ProfileController@changePass');
//    Route::get('user/{id}', 'UserController@showFormPassword')->name('users.showFormPassword');
//    Route::post('user/{id}','UserController@updatePassword' )->name('users.updatePassword');
//    Route::get('user/{id}/info', 'UserController@show')->name('users.showInfo');
    Route::resource('orders', 'OrderController');
    Route::post('orders/import', 'OrderController@import')->name('orders.import');
    Route::get('fileDemo', 'OrderController@fileDownload')->name('fileDemo');
    Route::get('order/import', 'OrderController@showFormImport')->name('orders.showFormImport');
    Route::get('order/export', 'OrderController@export')->name('orders.export');
    Route::resource('partners', 'PartnerController');
    Route::post('/template/render', 'OrderController@renderTemplate');
    Route::post('/order/delete-many', 'OrderController@deleteMany');
    Route::post('/order/update-many', 'OrderController@updateMany');
    Route::post('/order/send-email', 'OrderController@sendEmail');
});
Route::get('/order/tracking', 'OrderTrackingController@tracking')->name('tracking');
