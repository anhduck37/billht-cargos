<?php

namespace App\Http\Controllers;

use App\City;
use App\Exports\OrderExport;
use App\Partner;
use App\PartnerConfig;
use App\Receiver;
use App\Repositories\OrderRepository;
use App\Http\Controllers\AppBaseController;
use App\Http\Requests\OrderFormRequestLevelPosman;
use App\Jobs\SendOrderViettelPostJob;
use App\Jobs\SendSMSJob;
use App\Jobs\UploadGoogleDriveJob;
use App\Sender;
use App\Service;
use App\Services\OrderService;
use App\Services\OrderTrackingService;
use Exception;
use Illuminate\Http\Request;
use App\User;
use Flash;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Response;
use App\Models\Order;
use App\OrderHistory;
use App\OrderImage;
use App\Services\GoogleDriveService;
use App\Services\OrderHistoryService;
use App\Services\OrderImageService;
use App\Services\SendSMSService;
use App\Services\ZaloService;
use App\ZaloConfig;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as ExceptionMail;

class OrderController extends AppBaseController
{
    /** @var  OrderRepository */

    private $orderRepository;
    private $googleDriveService;
    private $orderImageService;

    private $zaloService;

    private $limit = 20;

    public function __construct(
        OrderRepository $orderRepo, 
        GoogleDriveService $googleDriveService, 
        OrderImageService $orderImageService,
        ZaloService $zaloSevice
    )
    {
        $this->orderRepository = $orderRepo;
        $this->googleDriveService = $googleDriveService;
        $this->orderImageService = $orderImageService;
        $this->zaloService = $zaloSevice;
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
        if(isset($formFilter['search'])) {
            $orders->where(function($q) use ($formFilter) {
                $q->orWhere('senders.sender_name', 'LIKE','%' . $formFilter['search'] . '%')
                  ->orWhere('senders.sender_phone', 'LIKE','%' . $formFilter['search'] . '%')
                  ->orWhere('senders.address', 'LIKE','%' . $formFilter['search'] . '%')
                  ->orWhere('receivers.receiver_name', 'LIKE','%' . $formFilter['search'] . '%')
                  ->orWhere('receivers.receiver_phone', 'LIKE','%' . $formFilter['search'] . '%')
                  ->orWhere('receivers.address', 'LIKE','%' . $formFilter['search'] . '%')
                  ->orWhere('orders.order_code', 'LIKE','%' . $formFilter['search'] . '%');
            });
        }
        // if(array_key_exists('name', $formFilter) && $formFilter['name']){
        //     $orders->where('senders.sender_name', 'LIKE','%' . $formFilter['name'] . '%');
        // }
        // if (array_key_exists('phone', $formFilter) && $formFilter['phone']) {
        //     $orders->where('senders.sender_phone', $formFilter['phone']);
        // }
        // if(array_key_exists('partner', $formFilter) && $formFilter['partner']) {
        //     $orders->where('orders.partner', $formFilter['partner']);
        // }
        // if(array_key_exists('order_code', $formFilter) && $formFilter['order_code']) {
        //     $orders->where('orders.order_code', $formFilter['order_code']);
        // }
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
        if(!in_array(auth()->user()->level, [User::LEVEL_ADMIN, User::LEVEL_STAFF])) {
            if(auth()->user()->level == User::LEVEL_POSTMAN){
                // $orders->where('orders.person_charge', auth()->user()->id);
            }else {
                $orders->where('orders.user_id', auth()->user()->id);
            }
        }
        if(isset($formFilter['order_code_from']) && isset($formFilter['order_code_to'])) {
            $prefix_code = config('order_manager.prefix_code');
            $order_id_from = (int)str_replace($prefix_code,'', $formFilter['order_code_from']);
            $order_id_to = (int)str_replace($prefix_code,'',$formFilter['order_code_to']);
            $orders->where('orders.id', '>=', $order_id_from)->where('orders.id', '<=',  $order_id_to)->where('order_code', 'LIKE', $prefix_code.'%');
        }
        $orders = $orders->select('orders.*')->orderBy('orders.id', 'DESC')->groupBy('orders.id')->paginate($pageSize);
        $partners = Partner::get();

        if(count($orders) == 0 && !empty($formFilter)) {
            Flash::warning('Không có kết quả trùng khớp');
        }
        return view('orders.index', ['orders' => $orders, 'partners' => $partners]);
    }

    public function show($id)
    {
        $order = $this->orderRepository->with(['orderItem'])->find($id);
        if (empty($order)) {
            Flash::error('Vận đơn không tồn tại.');

            return redirect(route('orders.index'));
        }
        $user = auth()->user();
        if($user->level == User::LEVEL_USER && $order->user_id != $user->id) {
            return abort(403);
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

    public function store(OrderFormRequestLevelPosman $request)
    {
        $senderForm = $request->sender ? $request->sender : [];
        $receiverForm = $request->receiver ? $request->receiver : [];
        $orderForm = $request->order;
        $orderForm['order_status'] = 0;
        $order_service = isset($request->order_service) ? $request->order_service : [];
        $fileName = null;
        $order = null;
        $is_update = false;
        DB::beginTransaction();
        try {
            if(isset($orderForm['invoice_code'])) {
                $order = Order::where('order_code', $orderForm['invoice_code'])->first();
            }
            if($order) {
                $order->fill(array_filter($orderForm));
                $order->save();
                $is_update = true;
            }else {
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
                if(isset($orderForm['invoice_code'])) {
                    $orderForm['order_code'] = $orderForm['invoice_code'];
                } else {
                    $orderForm['order_code'] = app(OrderService::class)->getOrderCode($prefix_code);
                    $orderForm['invoice_code'] = $orderForm['order_code'];
                }
                $order = $this->orderRepository->create($orderForm);
            }
            if(isset($request->image_data)) {
                $fileName = $this->upload($request->image_data, $request->type_image, $order->order_code, OrderImage::SAVE_SERVER, $order);
            }
            app(OrderTrackingService::class)->create($order, $request->all());
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
            app(OrderHistoryService::class)->createOrderHistory(null, $order, $request->all(), OrderHistory::IS_TOTAL_ORDER, OrderHistory::TYPE_ORDER_CREATE);
            DB::commit();
            Flash::success( ($is_update ? 'Cập nhật' : 'Tạo') . ' vận đơn thành công.');
            return redirect()->route('orders.edit', [$order->id]);
        }catch (Exception $e) {
            Flash::error(($is_update ? 'Cập nhật' : 'Tạo') . ' vận đơn thất bại.'. ' '. $e->getMessage());
            DB::rollback();
        }
        if($is_update) {
            $citys = City::get();
            $partners = Partner::get();
            $users = User::where('level', User::LEVEL_POSTMAN)->get();
            return view('orders.edit', ['citys' => $citys, 'partners' => $partners,'order' => $order, 'update' => true, 'users' => $users]);
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
        $user = auth()->user();
        if($user->level == User::LEVEL_USER && $order->user_id != $user->id) {
            return abort(403);
        }
        return view('orders.edit', ['citys' => $citys, 'partners' => $partners,'order' => $order, 'update' => true, 'users' => $users]);
    }

    public function destroy($id) {
        $order = Order::where('id', $id)->first();
        if($order) {
            Order::where('id', $id)->delete();
            Flash::success('Xóa vận đơn thành công');
            return back();
        }
        Flash::error('Vận đơn không tồn tại');
        return back();
    }

    public function update(OrderFormRequestLevelPosman $request, $id) {
        $is_total_order = 0;
        $senderForm = $request->sender;
        $receiverForm = $request->receiver;
        $orderForm = $request->order;
        $order_service = isset($request->order_service) ? $request->order_service : [];
        $fileName = null;
        DB::beginTransaction();
        try {
            $order = $this->orderRepository->find($id);
            $user = auth()->user();
            if($user->level == User::LEVEL_USER && $order->user_id != $user->id) {
                return abort(403);
            }
            $order_old = $order;
            if($order) {
                if(isset($request->image_data)) {
                    $fileName = $this->upload(
                        $request->image_data,
                        $request->type_image,
                        isset($orderForm['invoice_code']) ? $orderForm['invoice_code'] : $order->order_code,
                        OrderImage::SAVE_SERVER, $order,
                        $request->image_remove
                    );
                } else if($request->image_remove) {
                    if($order->image->type_save == OrderImage::SAVE_GOOGLE_DRIVE) {
                        // $this->googleDriveService->deleteFile($order->image->file_id);
                    }
                    $order->image->delete();
                }
                if(isset($fileName)) {
                    $is_total_order = OrderHistory::IS_TOTAL_ORDER;
                    // $orderForm['delivery_status'] = Order::DELIVERY_STATUS_OK;
                }
                if(auth()->user()->level !== User::LEVEL_POSTMAN) {
                    if($senderForm) {
                        $sender = Sender::where('id', $order->sender_id)->update($senderForm);
                    }
                    if($receiverForm) {
                        $receiver = Receiver::where('id', $order->receiver_id)->update($receiverForm);
                    }

                    if(array_key_exists('order_date', $orderForm) && !empty($orderForm['order_date'])){
                        $orderForm['order_date'] = app(OrderService::class)->explodeDate($orderForm['order_date']);
                        if(empty($orderForm['order_date'])) {
                            unset($orderForm['order_date']);
                        }
                    }
                }
                // if($orderForm['signator']) {
                //     $orderForm['delivery_status'] = Order::DELIVERY_STATUS_OK;
                // }
                if(isset($orderForm['invoice_code'])) {
                    $orderForm['order_code'] = $orderForm['invoice_code'];
                    if($orderForm['invoice_code'] != $order_old->order_code) {
                        $is_total_order = OrderHistory::IS_TOTAL_ORDER;
                        if(isset($order->image)) {
                            app(OrderService::class)->renameImage($order->image);
                        }
                    }
                }
                Order::where('id', $id)->update($orderForm);
                if($order &&  array_key_exists('delivery_status', $orderForm) && $orderForm['delivery_status'] != $order->delivery_status){
                    $is_total_order = OrderHistory::IS_TOTAL_ORDER;
                    $order->delivery_status = $orderForm['delivery_status'];
                    if(isset($orderForm['location_id'])) {
                        $order->city_id = $orderForm['location_id'];
                    }
                    if(array_key_exists('person_charge', $orderForm)){
                        $order->person_charge = $orderForm['person_charge'];
                    }
                    $order->signator = $orderForm['signator'];
                    app(OrderTrackingService::class)->create($order, $request->all());
                }
                if(isset($orderForm['signator']) && $orderForm['signator'] != $order_old->signator) {
                    $is_total_order = OrderHistory::IS_TOTAL_ORDER;
                    $order->signator = $orderForm['signator'];
                    app(OrderTrackingService::class)->update($order, $orderForm['delivery_status']);
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
            app(OrderHistoryService::class)->createOrderHistory($order_old, $order, $request->all(), $is_total_order, OrderHistory::TYPE_ORDER_UPDATE);
            DB::commit();
            Flash::success('Cập nhật vận đơn thành công.');
            return back();
        }catch (Exception $e) {
            Flash::error('Xảy ra lỗi khi cập nhật vận đơn');
            DB::rollback();
        }
        return redirect()->route('orders.index');
    }

    public function export(Request $request) {

        $formFilter = $request->all();

        if(!array_filter($formFilter)) {
            Flash::error('Bạn vui lòng nhập mục tìm kiếm');
            return back();
        }
        try {
            $file = Excel::download(new OrderExport($formFilter), 'orders.xlsx');
            return $file;
        } catch (Exception $exception) {
            Flash::error('Không có đơn nào trong '. $request->start_date. ' - '. $request->end_date);
            return back();
        }
    }

    public function import(Request $request) {
        $file = $request->file('file');
        $message = '';
        if($file) {
            $partners = Partner::get();
            $orders = [];
            $mimes = array('application/vnd.ms-excel','text/xls','text/xlsx','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            if(in_array($_FILES["file"]["type"], $mimes)) {
                $spreadsheet = IOFactory::load($file->getRealPath());
                $sheet        = $spreadsheet->getActiveSheet();
                $row_limit    = $sheet->getHighestDataRow();
                $column_limit = $sheet->getHighestDataColumn();
                $row_range    = range( 2, $row_limit );
                $column_range = range( 'O', $column_limit );
                $startcount = 2;
                foreach ( $row_range as $row ) {
                    DB::beginTransaction();
                    try {
                        $senderData = [
                            'sender_name' => $sheet->getCell( 'B' . $row )->getValue() ? $sheet->getCell( 'B' . $row )->getValue() : '',
                            'sender_phone' => $sheet->getCell( 'C' . $row )->getValue() ? $sheet->getCell( 'C' . $row )->getValue() : '' ,
                            'address' => $sheet->getCell( 'S' . $row )->getValue() ?? '',
                        ];
                        // dd($senderData);
                        $receiverData = [
                            'receiver_name' => $sheet->getCell( 'E' . $row )->getValue() ? $sheet->getCell( 'E' . $row )->getValue() : '',
                            'address' => $sheet->getCell( 'F' . $row )->getValue() ? $sheet->getCell( 'F' . $row )->getValue() : '',
                            'receiver_phone' => $sheet->getCell( 'G' . $row )->getValue() ? $sheet->getCell( 'G' . $row )->getValue() : '',
//                            'receiver_email' => $sheet->getCell( 'H' . $row )->getValue() ? $sheet->getCell( 'H' . $row )->getValue() : '',
                        ];
                        if($receiverData['address'] != ''){

                            $sender = Sender::create($senderData);
                            $receiver = Receiver::create($receiverData);
                            $orderData = [
                                'sender_id' => isset($sender) ? $sender->id : 0,
                                'receiver_id' => isset($receiver) ? $receiver->id: 0,
                                'order_date' => $sheet->getCell( 'A' . $row )->getValue(),
                                'department' => $sheet->getCell( 'D' . $row )->getValue(),
                                'weight' => $sheet->getCell( 'I' . $row )->getValue(),
                                'note' => $sheet->getCell( 'L' . $row )->getValue(),
                                'user_id' => auth()->user()->id,
                                'order_status' => Order::ORDER_BLANK,
                                'delivery_status' => Order::DELIVERY_STATUS_PROCESSING,
                                'quantity' => $sheet->getCell( 'O' . $row )->getValue() ?? 1
                            ];
                            if($sheet->getCell( 'H' . $row )->getValue()) {
                                $orderData['payment_method'] = app(OrderService::class)->getKeyPaymentMethod($sheet->getCell( 'H' . $row )->getValue());
                            }
                            if($sheet->getCell( 'P' . $row )->getValue()) {
                                $orderData['type'] = app(OrderService::class)->getType($sheet->getCell( 'P' . $row )->getValue());
                            }
                            if(in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF])){
                                if($sheet->getCell( 'N' . $row )->getValue()) {
                                    $person_charge = User::where('name', 'LIKE', '%'.$sheet->getCell( 'N' . $row )->getValue().'%')->first();
                                    $orderData['person_charge'] = isset($person_charge) ? $person_charge->id: 0;
                                }
                                if($sheet->getCell( 'M' . $row )->getValue()) {
                                    $orderData['invoice_code'] = $sheet->getCell( 'M' . $row )->getValue();
                                }

                            }

                            if($orderData['order_date']){
                                if(gettype($orderData['order_date']) == 'integer'){
                                    $date = intval($orderData['order_date']);
                                    $orderData['order_date'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('d/m/Y');
                                }
                                $times = explode('/',$orderData['order_date']);
                                if(count($times) >= 3){
                                    $convertDate = $times[2].'-'.$times[1].'-'.$times[0];
                                    $orderData['order_date'] = $convertDate;
                                }

                            } else {
                                $orderData['order_date'] = date('Y-m-d');
                            }

                            $orderData['order_code'] = app(OrderService::class)->getOrderCode(config('order_manager.prefix_code'));
                            if(isset($orderData['invoice_code'])) {
                                $checkOrder = Order::where('order_code', $orderData['invoice_code'])->first();
                                if(!$checkOrder) {
                                    $orderData['order_code'] = $orderData['invoice_code'];
                                } else {
                                    $message .= 'Mã bill '.$orderData['invoice_code'].' đã tồn tại trên hệ thống nên được thay thế bằng mã bill mới là: '.$orderData['order_code'] . '<br>';
                                    $orderData['invoice_code'] = $orderData['order_code'];
                                }
                            }
                            $total = $sheet->getCell( 'Q' . $row )->getValue();
                            $collection = $sheet->getCell( 'R' . $row )->getValue();
                            $orderData['total'] = (int)$total;
                            $orderData['collection'] = (int)$collection;
                            $order = Order::create($orderData);
                            if($order){
                                $orders[] = $order;

                                if(isset($order->receiver) && !empty($order->receiver->receiver_phone)) {
                                    dispatch(new SendSMSJob($order));
                                }
                                $partnerCode = $sheet->getCell( 'T' . $row )->getValue();
                                if($partnerCode) {
                                    if($partnerCode == Order::CODE_VIETTEL_POST) {
                                        // dispatch(new SendOrderViettelPostJob($order));
                                        $sendOrderViettelPost = new SendOrderViettelPostJob($order);
                                        $sendOrderViettelPost->handle();
                                    }
                                }
                                app(OrderTrackingService::class)->create($order, $request->all());
                                
                            }
                            $dataService = [];
                            if($sheet->getCell( 'J' . $row )->getValue()){
                                $infoService = app(OrderService::class)->getKeyService($sheet->getCell( 'J' . $row )->getValue());
                                if($infoService && array_key_exists('type', $infoService) && array_key_exists('service_key', $infoService)) {
                                    $dataService[$infoService['type']][] = $infoService['service_key'];
                                }
                            }

                            if($sheet->getCell( 'K' . $row )->getValue()) {
                                $service_extra_string = $sheet->getCell( 'K' . $row )->getValue();
                                $service_extra_array = isset($service_extra_string) ? explode(',', $service_extra_string): [];
                                foreach ($service_extra_array as $service_name) {
                                    $item = app(OrderService::class)->getKeyService(trim($service_name));
                                    if($item && array_key_exists('type', $item) && array_key_exists('service_key', $item)){
                                        $dataService[$item['type']][] = $item['service_key'];
                                    }
                                }

                            }
                            if($order && !empty($dataService)){
                                app(OrderService::class)->insertService($dataService, $order->id);
                            }
                            
                        }
                        DB::commit();
                    }catch (Exception $e) {
                        Flash::error($e->getMessage());
                        DB::rollback();
                    }
                    $startcount++;
                }
                if($message != '') {
                    Flash::success($message);
                }
                return view('orders.import', ['orders' => $orders, 'partners' => $partners]);
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
        $partners = Partner::get();
        return view('orders.import', ['orders' => [], 'partners' => $partners]);
    }

    public function renderTemplate(Request $request) {
        $orders = $request->order;
        $start = $request->start;
        $end = $request->end;
        $orderData = Order::with([
            'services',
            'sender.ward',
            'sender.district',
            'sender.city',
            'receiver.ward',
            'receiver.district',
            'receiver.city',
            'getPersonCharge',
            'user'
        ]);
        if($start && $end) {
            $prefix_code = config('order_manager.prefix_code');
            $orderData = $orderData->where('id', '>=', $start)->where('id', '<=', $end)->where('order_code', 'LIKE', $prefix_code.'%')->get();
        }else if(!empty($orders)) {
            $orderData = $orderData->whereIn('id', $orders)->get();
        }else {
            $orderData = [];
        }
        $level = $request->number;
        return response()->json([
            'orders' => $orderData,
            'level' => $level,
            'level_admin' => User::LEVEL_ADMIN,
            'service_domestic' => Service::SERVICE_MAP[Service::SERVICE_DOMESTIC],
            'service_international' => Service::SERVICE_MAP[Service::SERVICE_INTERNATIONAL],
            'service_extra' => Service::SERVICE_MAP[Service::SERVICE_EXTRA],
            'favicon' => asset('argon/img/brand/favicon.png'),
            'nucleo' => asset('argon/vendor/nucleo/css/nucleo.css'),
            'css_min' => asset('argon/vendor/@fortawesome/fontawesome-free/css/all.min.css'),
            'css_argon' => asset('argon/css/argon.css?v=1.0.0'),
            'renderCode' => asset('/js/renderCode.js'),
            'logo_print' => asset('image/logo_print.png'),
            'csrf_token' => csrf_token(),
            'locale' => str_replace('_', '-', app()->getLocale()),
            'payment_method' => Order::PAYMENT_METHOD_MAP
        ]);
        // return view('template.print', ['orders' => $orders, 'level' => $level])->render();
    }

    public function fileDownload()
    {
        $file= public_path(). "/file/mau_tao_nhanh_bill.xls";

        $headers = array(
            'Content-Type: application/vnd.ms-excel',
        );
        return Response::download($file, 'mau_tao_nhanh_bill.xls', $headers);
    }

    function deleteMany(Request $request) {
        $orderIds = $request->order_ids;
        if(empty($orderIds)){
            Flash::error('Bạn hãy chọn các vận đơn muốn xóa');
        }else {
            Order::whereIn('id', $orderIds)->delete();
            Flash::success('Xóa vận đơn thành công');
        }
        return route('orders.index');
    }
    function updateMany(Request $request) {
        $orderIds = $request->order_ids;
        $delivery_status = $request->delivery_status;
        if (empty($delivery_status)) {
            Flash::error('Bạn hãy chọn các trạng thái muốn cập nhật');
        }else if(empty($orderIds)) {
            Flash::error('Bạn hãy chọn các vận đơn muốn cập nhật');
        }else {
            Order::whereIn('id', $orderIds)->update(['delivery_status' => $delivery_status]);
            app(OrderTrackingService::class)->createMany($orderIds, $delivery_status, $request->all());
            Flash::success('Cập nhật vận đơn thành công');
        }
        return route('orders.index');
    }

    public function sendEmail(Request $request) {
        $type_email = $request->type_email;
        $orderIds = $request->order_ids;
        if(empty($type_email) || empty($orderIds)){
            Flash::error('Bạn vui lòng chọn vận đơn và temlate email');
            return route('orders.index');
        }
        $errors = [];
        $success = [];
        $mail = new PHPMailer(true); // notice the \  you have to use root namespace here
        $orderId = null;
        try {
            $orders = Order::whereIn('id', $orderIds)->get();
            foreach ($orders as $order){
                $orderId = $order->id;
                if($order && isset($order->sender) && $order->sender->sender_email){
                    $template = '';
                    if($type_email == 2) {
                        $template = view('template.email_success', ['order' => $order, 'type_email' => $type_email])->render();
                    }else {
                        $template = view('template.email_confirm', ['order' => $order, 'type_email' => $type_email])->render();
                    }

                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        ));
                    $mail->isSMTP();
                    $mail->CharSet = "utf-8";
                    $mail->SMTPAuth = true;
                    $mail->SMTPSecure = env('MAIL_ENCRYPTION', 'tls');
                    $mail->Host = env("MAIL_HOST", 'mailer-0104.inet.vn');
                    $mail->Port = env('MAIL_PORT', 587);
                    $mail->Username = env('MAIL_USERNAME', 'bill@ht-cargos.com');
                    $mail->Password = env('MAIL_PASSWORD', 'dlorhxxlmqzzjvmv');
                    $mail->setFrom(env('MAIL_FROM_ADDRESS', 'bill@ht-cargos.com'), env('MAIL_FROM_NAME', 'HT EXPRESS'));
                    $mail->Subject = env('MAIL_FROM_NAME', 'HT EXPRESS');
                    $mail->MsgHTML($template);
                    $mail->addAddress($order->sender->sender_email, $order->sender->sender_name);
                    $mail->send();

                    array_push($success, $order->order_code);
                }else {
                    array_push($errors, $order->order_code);
                }
            }
            if(!empty($errors)) {
                Flash::error('Xảy ra lỗi gửi email với các vận đơn: '. implode(', ', $errors));
            }
            if(!empty($success)){
                Flash::success('Gửi email thành công với các vận đơn: ' . implode(', ', $success));
            }

        } catch (ExceptionMail $e) {
            Flash::error($e->getMessage());
        }
        if($request->isUpdate && $orderId) {
            return route('orders.edit', [$orderId]);
        }
        return route('orders.index');
    }

    public function upload($image_data, $type_image, $order_code, $type_save, $order, $is_remove=false) {
        $dataOrderImage = [];
        if($type_save == OrderImage::SAVE_GOOGLE_DRIVE) {
            $fileImage = $this->orderImageService->setUp($image_data, $type_image, $order_code);
            $fileName = $fileImage->getFileName();
            $data = $this->googleDriveService->createFile($fileName, $fileImage->getContentFile(), $fileImage->getMimeType());
            $dataOrderImage['google_drive_id'] = $data->getFolder()->id;
            $dataOrderImage['file_id'] = $data->getFile()->id;
            $dataOrderImage['url'] = config('google_drive.url') . $data->getFile()->id;
        } else {
            $folderPath = public_path()."/uploads/";
            $fileName = $order_code . '.jpeg';
            if (File::exists($folderPath . $fileName)) {
                unlink($folderPath . $fileName);
            }
            if($type_image == OrderImage::TYPE_IMAGE_FILE) {
                $fileName = $order_code. '.' .$image_data->getClientOriginalExtension();
                $image_data->move($folderPath, $fileName);
            }else {
                $image_parts = explode(";base64,", $image_data);
                $image_type_aux = explode("image/", $image_parts[0]);
                $image_type = $image_type_aux[1];
                $image_base64 = base64_decode($image_parts[1]);
                $file = $folderPath . $fileName;
                file_put_contents($file, $image_base64);
            }
            dispatch(new UploadGoogleDriveJob($order));
        }
        $dataOrderImage['image'] = $fileName;
        $dataOrderImage['order_id'] = $order->id;
        $dataOrderImage['type_upload'] = $type_image;
        $dataOrderImage['type_save'] = $type_save;
        $this->orderImageService->createOrUpdate($order->id, $dataOrderImage, $is_remove);
        return $fileName;
    }

    public function sendSMS(Request $request) {
        $orderIds = $request->order_ids;
        if(empty($orderIds)){
            Flash::error('Bạn vui lòng chọn vận đơn');
            return route('orders.index');
        }
        $errors = [];
        $success = [];
        $orderId = null;
        try {
            $orders = Order::whereIn('id', $orderIds)->get();
            foreach ($orders as $order){
                $orderId = $order->id;
                if($order && isset($order->receiver) && !empty($order->receiver->receiver_phone)){
                    $response = app(SendSMSService::class)->sendSMS($order->receiver->receiver_phone, null, $order, true);
                    if($response->CodeResult == 100) {
                        array_push($success, $order->order_code);
                    } else {
                        array_push($errors, $order->order_code . ': '.  $response->ErrorMessage);
                    }
                }else {
                    array_push($errors, $order->order_code);
                }
            }
            if(!empty($errors)) {
                Flash::error('Xảy ra lỗi gửi SMS với các vận đơn: '. implode(', ', $errors));
            }
            if(!empty($success)){
                Flash::success('Gửi SMS thành công với các vận đơn: ' . implode(', ', $success));
            }

        } catch (Exception $e) {
            Flash::error($e->getMessage());
        }

        if($request->isUpdate && $orderId) {
            return route('orders.edit', [$orderId]);
        }
        return route('orders.index');
    }

    public function sendZaloZNS(Request $request) {
        $orderIds = $request->order_ids;
        if(empty($orderIds)){
            Flash::error('Bạn vui lòng chọn vận đơn');
            return route('orders.index');
        }

        $errors = [];
        $success = [];
        $orderId = null;

        try {
            $orders = Order::whereIn('id', $orderIds)->get();
            foreach ($orders as $order){
                $orderId = $order->id;
                if($order && isset($order->receiver) && !empty($order->receiver->receiver_phone) && !empty($order->receiver->receiver_name)){
                    $response = $this->zaloService->sendZNS($order);
                    if($response['error'] == ZaloConfig::SUCCESS_CODE) {
                        array_push($success, $order->order_code);
                    } else {
                        array_push($errors, $order->order_code . ': '.  $response['message']);
                    }
                }else {
                    array_push($errors, $order->order_code);
                }
            }
            if(!empty($errors)) {
                Flash::error('Xảy ra lỗi gửi zalo với các vận đơn: '. implode(', ', $errors));
            }
            if(!empty($success)){
                Flash::success('Gửi zalo thành công với các vận đơn: ' . implode(', ', $success));
            }

        } catch (Exception $e) {
            Flash::error($e->getMessage());
        }

        if($request->isUpdate && $orderId) {
            return route('orders.edit', [$orderId]);
        }
        return route('orders.index');
    }

    public function createOrderViettelPost($id) {
        $order = Order::findOrFail($id);
        if($order->order_partner_code) {
            Flash::error('Vận đơn đã có trên Viettel Post.');
            return back();
        }
        $sendOrderViettelPost = new SendOrderViettelPostJob($order);
        $result = $sendOrderViettelPost->handle();
        if($result['error']) {
            Flash::error('Xảy ra lỗi tạo vận đơn sang Viettel Post: '. ($result['message'] ?? ''));
        } else {
            Flash::success('Tạo vận đơn sang Viettel Post thành công.');
        }
        return back();
    }

    public function createViettelPost(Request $request) {
        $orderIds = $request->order_ids;
        if(empty($orderIds)){
            Flash::error('Bạn vui lòng chọn vận đơn');
            return route('orders.index');
        }
        try {
            $orders = Order::whereIn('id', $orderIds)->get();
            foreach ($orders as $order){
                // dispatch(new SendOrderViettelPostJob($order));
                $sendOrderViettelPost = new SendOrderViettelPostJob($order);
                $result = $sendOrderViettelPost->handle();
            }

        } catch (Exception $e) {
            Flash::error($e->getMessage());
        }
        return route('orders.index');
    }
}
