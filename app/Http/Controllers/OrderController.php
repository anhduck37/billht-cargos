<?php

namespace App\Http\Controllers;

use App\City;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\OrderTracking;
use App\Partner;
use App\Receiver;
use App\Repositories\OrderRepository;
use App\Http\Controllers\AppBaseController;
use App\Sender;
use App\Service;
use App\Services\OrderService;
use App\Services\OrderTrackingService;
use Exception;
use Illuminate\Http\Request;
use Auth;
use App\User;
use Flash;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Response;
use Illuminate\Support\Facades\Http;
use App\Models\Order;


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ExceptionExcel;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\IOFactory;

class OrderController extends AppBaseController
{
    /** @var  OrderRepository */

    private $orderRepository;

    private $limit = 50;

    public function __construct(OrderRepository $orderRepo)
    {
        $this->orderRepository = $orderRepo;
    }

    /**
     * Display a listing of the Order.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $formFilter = $request->all();
        $pageSize = config('order_manager.page_size');
        $orders = Order::join('senders', 'senders.id', '=', 'orders.sender_id')
                        ->join('receivers', 'receivers.id', '=', 'orders.receiver_id');
        if(array_key_exists('email', $formFilter) && $formFilter['email']){
            $orders->where('senders.sender_email', $formFilter['email'])
                    ->orWhere('receivers.receiver_email', $formFilter['email']);
        }
        if (array_key_exists('phone', $formFilter) && $formFilter['phone']) {
            $orders->where('senders.sender_phone', $formFilter['phone'])
                ->orWhere('receivers.receiver_phone', $formFilter['phone']);
        }
        if(array_key_exists('partner', $formFilter) && $formFilter['partner']) {
            $orders->where('orders.partner', $formFilter['partner']);
        }
        if(array_key_exists('order_code', $formFilter) && $formFilter['order_code']) {
            $orders->where('orders.order_code', $formFilter['order_code']);
        }
        if(array_key_exists('order_date', $formFilter) && $formFilter['order_date']) {
            $dates = explode(' - ', $formFilter['order_date']);
            $startDate = app(OrderService::class)->explodeDate($dates[0]);
            $endDate = app(OrderService::class)->explodeDate($dates[1]);
            if($startDate && $endDate){
                $orders->where('orders.order_date', '>=', $startDate)
                        ->where('orders.order_date', '<=', $endDate);
            }
        }
        if(array_key_exists('delivery_status', $formFilter) && $formFilter['delivery_status']) {
            $orders->where('delivery_status', $formFilter['delivery_status']);
        }
        if(auth()->user()->level != User::LEVEL_ADMIN) {
            if(auth()->user()->level == User::LEVEL_POSTMAN){
                $orders->where('orders.person_charge', auth()->user()->id);
            }else {
                $orders->where('orders.user_id', auth()->user()->id);
            }
        }
        $orders = $orders->select('orders.*')->orderBy('orders.id', 'DESC')->paginate($pageSize);
        $partners = Partner::get();
        return view('orders.index', ['orders' => $orders, 'partners' => $partners]);
    }

    public function show($id)
    {
        $order = $this->orderRepository->with(['orderItem'])->find($id);
        if (empty($order)) {
            Flash::error('Vận đơn không tồn tại.');

            return redirect(route('orders.index'));
        }

        return view('orders.show')->with('order', $order);
    }

    public function create()
    {
        $citys = City::get();
        $partners = Partner::get();
        $users = User::where('level', User::LEVEL_POSTMAN)->get();
        return view('orders.create', ['citys' => $citys, 'partners' => $partners, 'order' => new Order(), 'users' => $users]);
    }

    public function store(CreateOrderRequest $request)
    {
        $senderForm = $request->sender;
        $receiverForm = $request->receiver;
        $orderForm = $request->order;
        $orderForm['order_status'] = 0;
        $order_service = isset($request->order_service) ? $request->order_service : [];
        DB::beginTransaction();
        try {
            $sender = Sender::create($senderForm);
            $receiver = Receiver::create($receiverForm);
            $orderForm['sender_id'] = $sender->id;
            $orderForm['receiver_id'] = $receiver->id;
            if(array_key_exists('order_date', $orderForm) && !empty($orderForm['order_date'])){
                $orderForm['order_date'] = app(OrderService::class)->explodeDate($orderForm['order_date']);
                if(empty($orderForm['order_date'])) {
                    unset($orderForm['order_date']);
                }
            }else {
                $orderForm['order_date'] = date('Y-m-d');
            }
            $orderForm['user_id'] = auth()->user()->id;
            $prefix_code = '';
            if(array_key_exists('partner', $orderForm)){
                $partner = Partner::where('id', $orderForm['partner'])->first();
                $prefix_code = $partner ? $partner->prefix_code : config('order_manager.prefix_code');
            } else {
                $prefix_code = config('order_manager.prefix_code');
            }
            $orderForm['order_code'] = app(OrderService::class)->getOrderCode($prefix_code);
            $order = $this->orderRepository->create($orderForm);
            if($order){
                app(OrderTrackingService::class)->create($order, $request->all());
            }
            if (!empty($order_service) && $order) {
                $data = [];
                foreach ($order_service as $key => $value) {
                    foreach ($value as $item) {
                        $dataOrdeService = [
                            'order_id' => $order->id,
                            'service' => $item,
                            'type' => $key
                        ];
                        array_push($data, $dataOrdeService);
                    }
                }
                if(!empty($data)){
                    Service::insert($data);
                }
            }
            DB::commit();
            Flash::success('Tạo vận đơn thành công.');
        }catch (Exception $e) {
            Flash::error('Tạo vận đơn thất bại.');
            DB::rollback();
        }
        return redirect()->route('orders.index');

    }

    public function edit($id) {
        $order = $this->orderRepository->find($id);
        $citys = City::get();
        $partners = Partner::get();
        $users = User::where('level', User::LEVEL_POSTMAN)->get();
        if(empty($order)) {
            Flash::error('Vận đơn không tồn tại.');
            return redirect(route('orders.index'));
        }
        return view('orders.edit', ['citys' => $citys, 'partners' => $partners,'order' => $order, 'update' => true, 'users' => $users]);
    }

    public function update(UpdateOrderRequest $request, $id) {
        $senderForm = $request->sender;
        $receiverForm = $request->receiver;
        $orderForm = $request->order;
        $order_service = isset($request->order_service) ? $request->order_service : [];
//        DB::beginTransaction();
//        try {
            $order = $this->orderRepository->find($id);
            if($order) {
                if(auth()->user()->level !== User::LEVEL_POSTMAN) {
                    $sender = Sender::where('id', $order->sender_id)->update($senderForm);
                    $receiver = Receiver::where('id', $order->receiver_id)->update($receiverForm);
                    if(array_key_exists('order_date', $orderForm) && !empty($orderForm['order_date'])){
                        $orderForm['order_date'] = app(OrderService::class)->explodeDate($orderForm['order_date']);
                        if(empty($orderForm['order_date'])) {
                            unset($orderForm['order_date']);
                        }
                    }
                }
                Order::where('id', $id)->update($orderForm);
                if($order &&  $orderForm['delivery_status'] != $order->delivery_status){
                    $order->delivery_status = $orderForm['delivery_status'];
                    $order->city_id = $orderForm['location_id'];
                    if(array_key_exists('person_charge', $orderForm)){
                        $order->person_charge = $orderForm['person_charge'];
                    }
                    $order->signator = $orderForm['signator'];
                    app(OrderTrackingService::class)->create($order, $request->all());
                }
                $serviceData = [];
                foreach ($order_service as $key => $value) {
                    foreach ($value as $item) {
                        $dataOrdeService = [
                            'order_id' => $id,
                            'service' => $item,
                            'type' => $key
                        ];
                        $findService = Service::where('order_id', $id)->where('service', $item)->first();
                        if(empty($findService)){
                            Service::create($dataOrdeService);
                        }
                        array_push($serviceData, $item);
                    }
                }
                if(!empty($serviceData)) {
                    Service::where('order_id', $id)->whereNotIn('service', $serviceData)->delete();
                }
            }
            DB::commit();
            Flash::success('Cập nhật vận đơn thành công.');
//        }catch (Exception $e) {
//            Flash::error('Xảy ra lỗi khi cập nhật vận đơn');
//            DB::rollback();
//        }
        return redirect()->route('orders.index');
    }

    public function import(Request $request) {
        $file = $request->file('file');
        if($file) {
            $mimes = array('application/vnd.ms-excel','text/xls','text/xlsx');
            if(in_array($_FILES["file"]["type"], $mimes)) {
                $spreadsheet = IOFactory::load($file->getRealPath());
                $sheet        = $spreadsheet->getActiveSheet();
                $row_limit    = $sheet->getHighestDataRow();
                $column_limit = $sheet->getHighestDataColumn();
                $row_range    = range( 2, $row_limit );
                $column_range = range( 'F', $column_limit );
                $startcount = 2;
                foreach ( $row_range as $row ) {
                    DB::beginTransaction();
                    try {
                        $senderData = [
                            'sender_name' => $sheet->getCell( 'B' . $row )->getValue() ? $sheet->getCell( 'B' . $row )->getValue() : '',
                            'sender_phone' => $sheet->getCell( 'E' . $row )->getValue() ? $sheet->getCell( 'B' . $row )->getValue() : '' ,
                        ];
                        // dd($senderData);
                        $receiverData = [
                            'receiver_name' => $sheet->getCell( 'D' . $row )->getValue() ? $sheet->getCell( 'D' . $row )->getValue() : '',
                            'address' => $sheet->getCell( 'F' . $row )->getValue() ? $sheet->getCell( 'F' . $row )->getValue() : '',
                            'receiver_phone' => $sheet->getCell( 'G' . $row )->getValue() ? $sheet->getCell( 'G' . $row )->getValue() : '',
                            'receiver_email' => $sheet->getCell( 'H' . $row )->getValue() ? $sheet->getCell( 'H' . $row )->getValue() : '',
                        ];
                        if($receiverData['receiver_name'] != '' && $receiverData['receiver_phone'] != '' && $receiverData['address'] != ''){
                            $sender = Sender::create($senderData);
                            $receiver = Receiver::create($receiverData);
                            $orderData = [
                                'sender_id' => isset($sender) ? $sender->id : 0,
                                'receiver_id' => isset($receiver) ? $receiver->id: 0,
                                'order_date' => $sheet->getCell( 'A' . $row )->getValue(),
                                'department' => $sheet->getCell( 'C' . $row )->getValue(),
                                'weight' => $sheet->getCell( 'I' . $row )->getValue(),
                                'note' => $sheet->getCell( 'M' . $row )->getValue(),
                                'invoice_code' => $sheet->getCell( 'N' . $row )->getValue(),
                                'user_id' => auth()->user()->id,
                                'order_status' => Order::ORDER_BLANK,
                            ];
                            if($orderData['order_date']){
                                $times = explode('-',$orderData['order_date']);
                                if(count($times) >= 3){
                                    $convertDate = $times[2].'-'.$times[1].'-'.$times[0];
                                    $orderData['order_date'] = $convertDate;
                                }
                            } else {
                                $order['order_date'] = date('Y-m-d');
                            }
                            $orderData['order_code'] = app(OrderService::class)->getOrderCode(config('order_manager.prefix_code'));
                            $order = Order::create($orderData);
                            if($order ){
                                app(OrderTrackingService::class)->create($order, $request->all());
                            }
                            $dataService = [];
                            if($sheet->getCell( 'K' . $row )->getValue()){
                                $infoService = app(OrderService::class)->getKeyService($sheet->getCell( 'K' . $row )->getValue());
                                if($infoService && array_key_exists('type', $infoService) && array_key_exists('service_key', $infoService)) {
                                    $dataService[$infoService['type']][] = $infoService['service_key'];
                                }
                            }

                            if($sheet->getCell( 'K' . $row )->getValue()) {
                                $service_extra_string = $sheet->getCell( 'K' . $row )->getValue();
                                $service_extra_array = isset($service_extra_string) ? explode(',', $row[10]): [];
                                foreach ($service_extra_array as $service_name) {
                                    $item = app(OrderService::class)->getKeyService(trim($service_name));
                                    if($item && array_key_exists('type', $item) && array_key_exists('service_key', $item)){
                                        $dataService[$item['type']][] = $item['service_key'];
                                    }
                                }
                                if($order){
                                    app(OrderService::class)->insertService($dataService, $order->id);
                                }
                            }
                        }
                        
                        DB::commit();
                    }catch (Exception $e) {
                        DB::rollback();
                    }
                    $startcount++;
                }

                return redirect()->route('orders.index');
            } else {
                Flash::error('File đã chọn phải là excel');
                return back();
            }
        }else {
            Flash::error('Không có file');
            return back();
        }

    }

    public function showFormImport() {
        return view('orders.import');
    }

    public function renderTemplate(Request $request) {
        $orders = $request->order;
        if(!empty($orders)) {
            $orders = Order::whereIn('id', $orders)->get();
        }else {
            $orders = [];
        }
        $level = auth()->user()->level;
        return view('template.print', ['orders' => $orders, 'level' => $level])->render();
    }
}
