<?php

namespace App\Http\Controllers;

use App\City;
use App\Exports\OrderExport;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Mail\SendMail;
use App\OrderTracking;
use App\Partner;
use App\Receiver;
use App\Repositories\OrderRepository;
use App\Http\Controllers\AppBaseController;
use App\Http\Requests\OrderFormRequestLevelPosman;
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
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use PHPMailer\PHPMailer\SMTP;
use Response;
use Illuminate\Support\Facades\Http;
use App\Models\Order;
use App\OrderImage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ExceptionExcel;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as ExceptionMail;

class OrderController extends AppBaseController
{
    /** @var  OrderRepository */

    private $orderRepository;

    private $limit = 20;

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
        if(isset($request->image_data)) {
            $fileName = $this->upload($request->image_data, $request->type_image);
        }
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
            if($orderForm['invoice_code']) {
                $orderForm['order_code'] = $orderForm['invoice_code'];
            } else {
                $orderForm['order_code'] = app(OrderService::class)->getOrderCode($prefix_code);
                $orderForm['invoice_code'] = $orderForm['order_code'];
            }
            $order = $this->orderRepository->create($orderForm);
            if($order){
                app(OrderTrackingService::class)->create($order, $request->all());
                if(isset($fileName)) {
                    $order_image = new OrderImage();
                    $order_image->fill([
                        'order_id' => $order->id,
                        'image' => $fileName,
                        'type_upload' => $request->type_image
                    ]);
                    $order_image->save();
                    // $order->delivery_status = Order::DELIVERY_STATUS_OK;
                    $order->save();
                }
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
            return redirect()->route('orders.edit', [$order->id]);
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
        $senderForm = $request->sender;
        $receiverForm = $request->receiver;
        $orderForm = $request->order;
        $order_service = isset($request->order_service) ? $request->order_service : [];
        $fileName = null;
        if(isset($request->image_data)) {
            $fileName = $this->upload($request->image_data, $request->type_image);
        }
        DB::beginTransaction();
        try {
            $order = $this->orderRepository->find($id);
            if($order) {
                if($request->image_remove) {
                    $path = public_path(). "/uploads/". $order->image->image;
                    if (File::exists($path)) {
                        unlink($path);
                    }
                    $order->image->delete();
                }
                if(isset($fileName)) {
                    $order_image = OrderImage::where('order_id', $id)->first();
                    if(!isset($order_image))  {
                        $order_image = new OrderImage();
                    } else {
                        $path = public_path(). "/uploads/". $order_image->image;
                        if (File::exists($path)) {
                            unlink($path);
                        }
                    }
                    $order_image->fill([
                        'order_id' => $id,
                        'image' => $fileName,
                        'type_upload' => $request->type_image
                    ]);
                    $order_image->save();
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
                if($orderForm['signator']) {
                    $orderForm['delivery_status'] = Order::DELIVERY_STATUS_OK;
                }
                if($orderForm['invoice_code']) {
                    $orderForm['order_code'] = $orderForm['invoice_code'];
                }
                Order::where('id', $id)->update($orderForm);
                if($order &&  array_key_exists('delivery_status', $orderForm) && $orderForm['delivery_status'] != $order->delivery_status){
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
            return back();
        }catch (Exception $e) {
            Flash::error('Xảy ra lỗi khi cập nhật vận đơn');
            DB::rollback();
        }
        return redirect()->route('orders.index');
    }

    public function export(Request $request) {
        $start_date = app(OrderService::class)->explodeDate($request->start_date);
        $end_date = app(OrderService::class)->explodeDate($request->end_date);
        try {
            $file = Excel::download(new OrderExport($start_date, $end_date), 'orders.xlsx');
            return $file;
        } catch (Exception $exception) {
            Flash::error('Không có đơn nào trong '. $request->start_date. ' - '. $request->end_date);
            return back();
        }
    }

    public function import(Request $request) {
        $file = $request->file('file');
        if($file) {
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
                            ];
                            if($sheet->getCell( 'H' . $row )->getValue()) {
                                $orderData['payment_method'] = app(OrderService::class)->getKeyPaymentMethod($sheet->getCell( 'H' . $row )->getValue());
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
                            $order = Order::create($orderData);
                            if($order ){
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
        $start = $request->start;
        $end = $request->end;
        $orderData = Order::with([
            'services',
            'sender.ward',
            'sender.district',
            'sender.city',
            'receiver.ward',
            'receiver.district',
            'receiver.city'
        ]);
        if($start && $end) {
            $orderData = $orderData->where('id', '>=', $start)->where('id', '<=', $end)->get();
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
            foreach ($orderIds as $id){
                $orderId = $id;
                $order = Order::where('id', $id)->first();
                if($order && isset($order->sender) && $order->sender->sender_email){
                    $template = '';
                    if($type_email == 2) {
                        $template = view('template.email_success', ['order' => $order])->render();
                    }else {
                        $template = view('template.email_confirm', ['order' => $order])->render();
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
            route('orders.edit', [$orderId]);
        }
        return route('orders.index');
    }

    public function upload($image_data, $type_image) {
        $folderPath = public_path()."/uploads/";
        $fileName = uniqid() . '.jpeg';
        if($type_image == OrderImage::TYPE_IMAGE_FILE) {
            $fileName = uniqid(). '.' .$image_data->getClientOriginalExtension();
            $image_data->move($folderPath, $fileName);
        }else {
            $image_parts = explode(";base64,", $image_data);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];

            $image_base64 = base64_decode($image_parts[1]);


            $file = $folderPath . $fileName;
            file_put_contents($file, $image_base64);
        }

        return $fileName;
    }
}
