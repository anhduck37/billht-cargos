<?php

namespace App\Providers;

use App\Models\Order;
use App\OrderHistory;
use DateTime;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\DB;

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
                $totalOrder = OrderHistory::where('user_id', $auth->user()->id)
                            ->where('is_total_order', OrderHistory::IS_TOTAL_ORDER)
                            ->where('created_at', '>=', $start_time)
                            ->where('created_at', '<', $end_time)
                            ->select('order_id')
                            ->groupBy('order_id')->get()->count();
            }
            $view->with('totalOrder', $totalOrder);
        });
    }
}
