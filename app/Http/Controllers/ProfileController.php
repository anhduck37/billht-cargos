<?php

namespace App\Http\Controllers;

use App\Http\Controllers\AppBaseController;
use App\Models\Order;
use App\Models\PaymentHistory;
use App\Models\PercentCommission;
use App\Models\Setting;

use App\Services\PaymentService;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Flash;


class ProfileController extends AppBaseController
{
    public $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index()
    {
        $links = '';
        $metaLink = Setting::where('meta_key', 'link_aff')->first();
        if($metaLink) {
            $dataDecode = json_decode($metaLink->meta_value, true);
            $links = $dataDecode[auth()->user()->lang];
        }

        $userId = auth()->user()->id;
        $lang = auth()->user()->lang == 'bgh' ? 'vi' : auth()->user()->lang;
        $carbon = Carbon::now();
        $startOfMonth = $carbon->month(date('m'))->year(date('Y'))->firstOfMonth()->toDateString();
        $endOfMonth = $carbon->month(date('m'))->year(date('Y'))->endOfMonth()->toDateString();

        $totalOrder = Order::where('lang', $lang)
            ->where('user_id', $userId)
            ->count();


        $totalPaidProfit = PaymentHistory::where('receiver_id', $userId)
            ->where('lang', $lang)
            ->sum('amount_transferred');

        $infoOrderUnpaid = Order::where('is_paid_profit', Order::ORDER_UNPAID_PROFIT)
            ->where('delivery_status', Order::DELIVERY_STATUS_SUCCESSFULL)
            ->where('user_id', $userId)
            ->where('lang', $lang)
            ->select(DB::raw('SUM(orders.total) as total_money'), DB::raw('COUNT(orders.id) as order_number'))->first();


        $infoOrderMonth = Order::where('delivery_status', Order::DELIVERY_STATUS_SUCCESSFULL)
            ->where('user_id', $userId)
            ->where('delivery_date', '>=', $startOfMonth)
            ->where('delivery_date', '<=', $endOfMonth)
            ->where('lang', $lang)
            ->select(DB::raw('SUM(orders.total) as total_money'), DB::raw('COUNT(orders.id) as order_number'))->first();

        $percentProfitMonth = $this->paymentService->calPercentCommission(auth()->user()->lang === User::LANG_BEEGREEN ? $infoOrderMonth->total_money : $infoOrderMonth->order_number, auth()->user()->lang);
        $totalProfitMonth = $infoOrderMonth->total_money * $percentProfitMonth / 100;

        $percentUnpaidProfit =  $this->paymentService->calPercentCommission(auth()->user()->lang === User::LANG_BEEGREEN ? $infoOrderUnpaid->total_money : $infoOrderUnpaid->order_number, auth()->user()->lang);
        $totalUnpaidProfit = $infoOrderUnpaid->total_money * $percentUnpaidProfit / 100;
        $totalMoney = $totalPaidProfit + $totalUnpaidProfit;

        $infoProfit = [
            'totalOrder' => $totalOrder,
            'totalMoney' => number_format($totalMoney, 0, '', ',') . ' ' . PercentCommission::CURRENCY_MAP[auth()->user()->lang],
            'totalPaidProfit' => number_format($totalPaidProfit, 0, '', ',') . ' ' . PercentCommission::CURRENCY_MAP[auth()->user()->lang],
            'totalUnpaidProfit' => number_format($totalUnpaidProfit, 0, '', ',') . ' ' . PercentCommission::CURRENCY_MAP[auth()->user()->lang],
            'totalProfitMonth' => number_format($totalProfitMonth, 0, '', ',') . ' ' . PercentCommission::CURRENCY_MAP[auth()->user()->lang]
        ];

        return view('profile', ['user' => auth()->user(), 'links' => $links, 'infoProfit' => $infoProfit]);
    }


    public function changePass()
    {
        if (request()->method() == 'POST') {
            $password = request('password', '');
            if ($password != '') {
                $user  = User::where('id', auth()->user()->id)->update([
                    'password' => \Hash::make($password)
                ]);
            }
            Flash::success('Cập nhập Mật khẩu thành công');
            return redirect('/profile');
        }
        return view('changepass');
    }

}
