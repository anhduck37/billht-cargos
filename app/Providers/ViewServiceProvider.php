<?php

namespace App\Providers;

use App\Models\Order;
use DateTime;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Auth\Guard;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(Guard $auth)
    {
        View::composer('*', function ($view) use ($auth) {
            $totalOrder = 0;
            if(isset($auth->user()->id)) {
                $start_time = date('Y-m-d');
                $end_time = (new DateTime($start_time))->modify('+1 day')->format('Y-m-d');
                $totalOrder = Order::join('order_images', 'order_images.order_id', '=', 'orders.id')
                            ->where('orders.user_id', $auth->user()->id)
                            ->where(function($q) use ($start_time, $end_time) {
                                $q->where(function($t) use ($start_time, $end_time) {
                                    $t->where('orders.created_at', '>=', $start_time)
                                    ->where('orders.created_at', '<', $end_time);
                                })->orWhere(function($h) use ($start_time, $end_time) {
                                    $h->where('order_images.updated_at', '>=', $start_time)
                                    ->where('order_images.updated_at', '<', $end_time);
                                });
                            })->count();
            }
            $view->with('totalOrder', $totalOrder);
        });
    }
}
