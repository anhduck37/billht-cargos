<?php

use App\Models\Order;
use App\Services\ZaloService;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GoogleOAuthController;

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
Route::get('/google/oauth2/start', [GoogleOAuthController::class, 'start']);
Route::get('/google/oauth2/callback', [GoogleOAuthController::class, 'callback']);
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

Route::middleware(['checkLevel'])->group(function () {
    Route::resource('users', 'UserController');
    Route::resource('partners', 'PartnerController');
    Route::get('orders/create-new', 'OrderController@createNew')->name('orders.createNew');
    Route::resource('orders', 'OrderController');
    Route::get('/order-historys', 'OrderHistoryController@index')->name('order_historys.index');
});
Route::middleware(['auth'])->group(function () {
    //    Route::get('/profile', 'ProfileController@index');
    //    Route::any('/profile/password', 'ProfileController@changePass');
    //    Route::get('user/{id}', 'UserController@showFormPassword')->name('users.showFormPassword');
    //    Route::post('user/{id}','UserController@updatePassword' )->name('users.updatePassword');
    //    Route::get('user/{id}/info', 'UserController@show')->name('users.showInfo');
    Route::get('orders/create-new', 'OrderController@createNew')->name('orders.createNew');
    Route::resource('orders', 'OrderController');
    Route::post('orders/{id}/upload-image', 'OrderController@uploadImage')->name('orders.upload-image');
    Route::post('orders/import', 'OrderController@import')->name('orders.import');
    Route::post('orders/import-old', 'OrderController@importOld')->name('orders.importOld');
    Route::post('orders/import-new', 'OrderController@importNew')->name('orders.importNew');
    Route::get('fileDemo', 'OrderController@fileDownload')->name('fileDemo');
    Route::get('order/address-import-tool', 'OrderController@showAddressImportTool')->name('orders.addressImportTool');
    Route::post('order/address-import-tool/preview', 'OrderController@previewAddressImportTool')->name('orders.addressImportTool.preview');
    Route::get('order/address-import-tool/download/{token}/{type}', 'OrderController@downloadAddressImportTool')->name('orders.addressImportTool.download');
    Route::get('order/import', 'OrderController@showFormImport')->name('orders.showFormImport');
    Route::get('order/export', 'OrderController@export')->name('orders.export');
    Route::post('/template/render', 'OrderController@renderTemplate');
    Route::post('/order/delete-many', 'OrderController@deleteMany');
    Route::post('/order/update-many', 'OrderController@updateMany');
    Route::post('/order/send-email', 'OrderController@sendEmail');
    Route::post('/order/send-sms', 'OrderController@sendSMS');
    Route::post('/order/create-viettel-post', 'OrderController@createViettelPost');
    Route::post('/order/send-zalo-zns', 'OrderController@sendZaloZNS');
    Route::get('/order/create-order-viettel/{id}', 'OrderController@createOrderViettelPost')->name('orders.createViettelPost');

    Route::post('/order/create-ems', 'OrderController@createEms');
    Route::get('/order/create-order-ems/{id}', 'OrderController@createOrderEms')->name('orders.createEms');
    Route::get('/order-partner-logs', 'OrderPartnerLogController@index')->name('order_partner_logs.index');
    Route::post('/order-partner-logs/api-status/{provider}/check', 'OrderPartnerLogController@checkApiStatus')->name('order_partner_logs.api_status.check');
    Route::post('/order-partner-logs/mickey/detect', 'OrderPartnerLogController@runMickeyDetect')->name('order_partner_logs.mickey.detect');
    Route::post('/order-partner-logs/mickey/sync', 'OrderPartnerLogController@runMickeySync')->name('order_partner_logs.mickey.sync');
    Route::post('/order-partner-logs/bulk-cancel', 'OrderPartnerLogController@bulkCancel')->name('order_partner_logs.bulk_cancel');
    Route::post('/order-partner-logs/{orderPartnerLog}/cancel', 'OrderPartnerLogController@cancel')->name('order_partner_logs.cancel');
    Route::get('/order/status-change', 'OrderStatusChangeController@index')->name('order_status_changes.index');
    Route::post('/order/status-change/import', 'OrderStatusChangeController@import')->name('order_status_changes.import');
    Route::get('/order/status-change/template', 'OrderStatusChangeController@template')->name('order_status_changes.template');
});
Route::get('/order/tracking', 'OrderTrackingController@tracking')->name('tracking');
