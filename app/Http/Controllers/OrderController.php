<?php

namespace App\Http\Controllers;

use App\City;
use App\District;
use App\Ward;
use App\Exports\OrderExport;
use App\Partner;
use App\PartnerConfig;
use App\Receiver;
use App\Repositories\OrderRepository;
use App\Http\Controllers\AppBaseController;
use App\Http\Requests\OrderFormRequestLevelPosman;
use App\Jobs\SendOrderEmsJob;
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
use App\Services\EmsService;
use App\Services\GoogleDriveService;
use App\Services\OrderHistoryService;
use App\Services\OrderImageService;
use App\Services\SendSMSService;
use App\Services\ZaloService;
use App\ZaloConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
        $monthsAgo = Carbon::now()->subMonths(config('order_manager.months_ago_to_get_bill'));
        $firstMonthAgo = $monthsAgo->startOfMonth();
        $pageSize = config('order_manager.page_size');
        $orders = Order::with([
            'sender.city',
            'sender.ward',
            'sender.district',
            'receiver.city',
            'receiver.ward',
            'receiver.district',
            'order_print',
            'services'
        ])->join('senders', 'senders.id', '=', 'orders.sender_id')
            ->join('receivers', 'receivers.id', '=', 'orders.receiver_id')
            ->where(function ($q) use ($firstMonthAgo) {
            $q->where('orders.created_at', '>=', $firstMonthAgo)
                ->orWhere('orders.updated_at', '>=', $firstMonthAgo);
        });
        if (isset($formFilter['search'])) {
            $orders->where(function ($q) use ($formFilter) {
                $q->orWhere('senders.sender_name', 'LIKE', '%' . $formFilter['search'] . '%')
                    ->orWhere('senders.sender_phone', 'LIKE', '%' . $formFilter['search'] . '%')
                    ->orWhere('senders.address', 'LIKE', '%' . $formFilter['search'] . '%')
                    ->orWhere('receivers.receiver_name', 'LIKE', '%' . $formFilter['search'] . '%')
                    ->orWhere('receivers.receiver_phone', 'LIKE', '%' . $formFilter['search'] . '%')
                    ->orWhere('receivers.address', 'LIKE', '%' . $formFilter['search'] . '%')
                    ->orWhere('orders.order_code', 'LIKE', '%' . $formFilter['search'] . '%');
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
        if (array_key_exists('order_date', $formFilter) && $formFilter['order_date']) {
            $dates = explode(' - ', $formFilter['order_date']);
            $startDate = app(OrderService::class)->explodeDate($dates[0]);
            $endDate = app(OrderService::class)->explodeDate($dates[1]);
            if ($startDate && $endDate) {
                $orders->where('orders.order_date', '>=', $startDate)
                    ->where('orders.order_date', '<=', $endDate);
            }
        }
        if (array_key_exists('delivery_status', $formFilter) && $formFilter['delivery_status']) {
            $orders->where('delivery_status', $formFilter['delivery_status']);
        }
        if (array_key_exists('partner_code', $formFilter) && $formFilter['partner_code']) {
            if ($formFilter['partner_code'] === Order::TRACKING_PROVIDER_MICKEY) {
                $orders->where('orders.tracking_provider', Order::TRACKING_PROVIDER_MICKEY);
            } else {
                $orders->where('orders.partner_code', $formFilter['partner_code']);
            }
        }
        if (!in_array(auth()->user()->level, [User::LEVEL_ADMIN, User::LEVEL_STAFF])) {
            if (auth()->user()->level == User::LEVEL_POSTMAN) {
                $postmanId = auth()->user()->id;
                $orders->where(function ($q) use ($postmanId) {
                    $q->where('orders.user_id', $postmanId)
                        ->orWhereIn('orders.id', function ($historyQuery) use ($postmanId) {
                            $historyQuery->select('order_id')
                                ->from('order_historys')
                                ->where('user_id', $postmanId)
                                ->whereIn('type_order', [
                                    OrderHistory::TYPE_ORDER_CREATE,
                                    OrderHistory::TYPE_ORDER_UPDATE,
                                ]);
                        });
                });
            }
            else {
                $orders->where('orders.user_id', auth()->user()->id);
            }
        }
        if (isset($formFilter['order_code_from']) && isset($formFilter['order_code_to'])) {
            $prefix_code = config('order_manager.prefix_code');
            $order_id_from = (int)str_replace($prefix_code, '', $formFilter['order_code_from']);
            $order_id_to = (int)str_replace($prefix_code, '', $formFilter['order_code_to']);
            $orders->where('orders.id', '>=', $order_id_from)->where('orders.id', '<=', $order_id_to)->where('order_code', 'LIKE', $prefix_code . '%');
        }
        $orders = $orders->select('orders.*')->orderBy('orders.id', 'DESC')->groupBy('orders.id')->paginate($pageSize);
        $partners = Partner::get();

        if (count($orders) == 0 && !empty($formFilter)) {
            Flash::warning('Không có kết quả trùng khớp');
        }
        return view('orders.index', ['orders' => $orders, 'partners' => $partners]);
    }

    public function show($id)
    {
        $order = $this->orderRepository->with(['sender', 'receiver', 'services'])->find($id);
        if (empty($order)) {
            Flash::error('Vận đơn không tồn tại.');

            return redirect(route('orders.index'));
        }
        $user = auth()->user();
        if ($user->level == User::LEVEL_USER && $order->user_id != $user->id) {
            return abort(403);
        }
        return view('orders.show')->with('order', $order);
    }

    public function createNew()
    {
        $citys = City::get();
        $newProvinces = \App\NewProvince::where('is_active', 1)->get();
        $partners = Partner::get();
        $users = User::where('level', User::LEVEL_POSTMAN)->get();
        return view('orders.create_new', [
            'citys' => $citys,
            'newProvinces' => $newProvinces, 
            'partners' => $partners, 
            'order' => new Order(), 
            'users' => $users
        ]);
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
            if (isset($orderForm['invoice_code'])) {
                $order = Order::where('order_code', $orderForm['invoice_code'])->first();
            }
            if ($order) {
                $order->fill(array_filter($orderForm));
                $order->save();
                $is_update = true;
            }
            else {
                $sender = Sender::create($senderForm);
                $receiver = Receiver::create($receiverForm);
                $orderForm['sender_id'] = $sender->id;
                $orderForm['receiver_id'] = $receiver->id;
                if (array_key_exists('order_date', $orderForm) && !empty($orderForm['order_date'])) {
                    $orderForm['order_date'] = app(OrderService::class)->explodeDate($orderForm['order_date']);
                    if (empty($orderForm['order_date'])) {
                        unset($orderForm['order_date']);
                    }
                }
                else {
                    $orderForm['order_date'] = date('Y-m-d');
                }
                $orderForm['user_id'] = auth()->user()->id;
                $prefix_code = '';
                if (array_key_exists('partner', $orderForm)) {
                    $partner = Partner::where('id', $orderForm['partner'])->first();
                    $prefix_code = $partner ? $partner->prefix_code : config('order_manager.prefix_code');
                }
                else {
                    $prefix_code = config('order_manager.prefix_code');
                }
                if (isset($orderForm['invoice_code'])) {
                    $orderForm['order_code'] = $orderForm['invoice_code'];
                }
                else {
                    $orderForm['order_code'] = app(OrderService::class)->getOrderCode($prefix_code);
                    $orderForm['invoice_code'] = $orderForm['order_code'];
                }
                $order = $this->orderRepository->create($orderForm);
            }
            if (isset($request->image_data)) {
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
                if (!empty($data)) {
                    Service::insert($data);
                }
            }
            app(OrderHistoryService::class)->createOrderHistory(null, $order, $request->all(), OrderHistory::IS_TOTAL_ORDER, OrderHistory::TYPE_ORDER_CREATE);
            DB::commit();
            Flash::success(($is_update ? 'Cập nhật' : 'Tạo') . ' vận đơn thành công.');
            return redirect()->route('orders.edit', [$order->id]);
        }
        catch (Exception $e) {
            Flash::error(($is_update ? 'Cập nhật' : 'Tạo') . ' vận đơn thất bại.' . ' ' . $e->getMessage());
            DB::rollback();
        }
        if ($is_update) {
            $citys = City::get();
            $partners = Partner::get();
            $users = User::where('level', User::LEVEL_POSTMAN)->get();
            return view('orders.edit', ['citys' => $citys, 'partners' => $partners, 'order' => $order, 'update' => true, 'users' => $users]);
        }
        return redirect()->route('orders.index');
    }

    public function edit($id)
    {
        $order = $this->orderRepository->find($id);
        $citys = City::get();
        $newProvinces = \App\NewProvince::where('is_active', 1)->get();
        $partners = Partner::get();
        $users = User::where('level', User::LEVEL_POSTMAN)->get();
        if (empty($order)) {
            Flash::error('Vận đơn không tồn tại.');
            return redirect(route('orders.index'));
        }
        $user = auth()->user();
        if ($user->level == User::LEVEL_USER && $order->user_id != $user->id) {
            return abort(403);
        }
        if ($user->level == User::LEVEL_POSTMAN && !$this->postmanCanAccessOrder($order, $user->id)) {
            return abort(403, 'Bạn không có quyền xem vận đơn này. Bưu tá chỉ được xem các vận đơn do mình tạo hoặc đã cập nhật.');
        }
        $this->hydrateLegacyAddressIdsForDisplay($order);
        return view('orders.edit', [
            'citys' => $citys, 
            'newProvinces' => $newProvinces, 
            'partners' => $partners, 
            'order' => $order, 
            'update' => true, 
            'users' => $users
        ]);
    }

    private function postmanCanAccessOrder($order, $postmanId)
    {
        if (!$order) {
            return false;
        }

        if ((int)$order->user_id === (int)$postmanId) {
            return true;
        }

        return OrderHistory::where('order_id', $order->id)
            ->where('user_id', $postmanId)
            ->whereIn('type_order', [
                OrderHistory::TYPE_ORDER_CREATE,
                OrderHistory::TYPE_ORDER_UPDATE,
            ])
            ->exists();
    }

    public function destroy($id)
    {
        $order = Order::where('id', $id)->first();
        if ($order) {
            if (
            in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF])
            || (auth()->user()->level == \App\User::LEVEL_USER && $order->user_id == auth()->user()->id && in_array($order->delivery_status, [Order::DELIVERY_STATUS_PROCESSING, Order::DELIVERY_STATUS_BLANK]))
            ) {
                app(OrderHistoryService::class)->createOrderHistory(null, $order, null, OrderHistory::NOT_TOTAL_ORDER, OrderHistory::TYPE_ORDER_UPDATE, 'DELETE', ['action_desc' => 'Xóa vận đơn']);
                Order::where('id', $id)->delete();
                Flash::success('Xóa vận đơn thành công');
                return back();
            }
            else {
                Flash::error('Bạn không có quyền xóa vận đơn');
                return back();
            }
        }
        Flash::error('Vận đơn không tồn tại');
        return back();
    }

    public function update(OrderFormRequestLevelPosman $request, $id)
    {
        $is_total_order = OrderHistory::NOT_TOTAL_ORDER;
        $senderForm = $request->sender;
        $receiverForm = $request->receiver;
        $orderForm = $request->order;
        $order_service = isset($request->order_service) ? $request->order_service : [];
        $fileName = null;

        // XỬ LÝ UPLOAD ẢNH TRƯỚC KHI BẮT ĐẦU TRANSACTION (nếu có upload mới)
        // Nếu ảnh đã được upload qua AJAX, fileName sẽ được truyền qua request
        if (isset($request->uploaded_image_file)) {
            $fileName = $request->uploaded_image_file;
        }
        elseif (isset($request->image_data)) {
            // Fallback: Nếu chưa upload qua AJAX, upload ngay tại đây (nhưng vẫn tách khỏi transaction)
            try {
                $order = $this->orderRepository->find($id);
                $user = auth()->user();
                if ($user->level == User::LEVEL_USER && $order->user_id != $user->id) {
                    return abort(403);
                }
                if ($user->level == User::LEVEL_POSTMAN && !$this->postmanCanAccessOrder($order, $user->id)) {
                    return abort(403, 'Bạn không có quyền cập nhật vận đơn này. Bưu tá chỉ được cập nhật các vận đơn do mình tạo hoặc đã từng thao tác.');
                }

                $fileName = $this->upload(
                    $request->image_data,
                    $request->type_image,
                    isset($orderForm['invoice_code']) ? $orderForm['invoice_code'] : $order->order_code,
                    OrderImage::SAVE_SERVER,
                    $order,
                    $request->image_remove
                );
            }
            catch (\Exception $e) {
                Log::error('Lỗi upload ảnh trong update: ' . $e->getMessage());
                Flash::error($e->getMessage());
                return back();
            }
        }

        // BẮT ĐẦU TRANSACTION SAU KHI UPLOAD ẢNH XONG (nếu có)
        DB::beginTransaction();
        try {
            $order = $this->orderRepository->find($id);
            $user = auth()->user();
            if ($user->level == User::LEVEL_USER && $order->user_id != $user->id) {
                return abort(403);
            }
            if ($user->level == User::LEVEL_POSTMAN && !$this->postmanCanAccessOrder($order, $user->id)) {
                return abort(403, 'Bạn không có quyền cập nhật vận đơn này. Bưu tá chỉ được cập nhật các vận đơn do mình tạo hoặc đã từng thao tác.');
            }
            $order_old = clone $order;
            $sender_old = $order->sender ? clone $order->sender : null;
            $receiver_old = $order->receiver ? clone $order->receiver : null;

            if ($order) {
                // Xử lý xóa ảnh nếu cần (không có upload mới và không có fileName từ AJAX)
                if (!isset($request->image_data) && !isset($request->uploaded_image_file) && $request->image_remove) {
                    if ($order->image && $order->image->type_save == OrderImage::SAVE_GOOGLE_DRIVE) {
                    // $this->googleDriveService->deleteFile($order->image->file_id);
                    }
                    if ($order->image) {
                        $order->image->delete();
                    }
                }

                // Nếu có fileName từ upload (qua AJAX hoặc fallback), đánh dấu là total order
                if (isset($fileName)) {
                    $is_total_order = OrderHistory::IS_TOTAL_ORDER;
                // $orderForm['delivery_status'] = Order::DELIVERY_STATUS_OK;
                }
                if (auth()->user()->level !== User::LEVEL_POSTMAN) {
                    if (isset($senderForm) && $senderForm) {
                        $senderForm = $this->fillLegacyAddressIdsWhenMissing($senderForm, $order->sender);
                        Sender::where('id', $order->sender_id)->update($senderForm);
                    }
                    if (isset($receiverForm) && $receiverForm) {
                        $receiverForm = $this->fillLegacyAddressIdsWhenMissing($receiverForm, $order->receiver);
                        Receiver::where('id', $order->receiver_id)->update($receiverForm);
                    }

                    if (array_key_exists('order_date', $orderForm) && !empty($orderForm['order_date'])) {
                        $orderForm['order_date'] = app(OrderService::class)->explodeDate($orderForm['order_date']);
                        if (empty($orderForm['order_date'])) {
                            unset($orderForm['order_date']);
                        }
                    }
                }
                // if($orderForm['signator']) {
                //     $orderForm['delivery_status'] = Order::DELIVERY_STATUS_OK;
                // }
                if (isset($orderForm['invoice_code'])) {
                    $orderForm['order_code'] = $orderForm['invoice_code'];
                    if ($orderForm['invoice_code'] != $order_old->order_code) {
                        $is_total_order = OrderHistory::IS_TOTAL_ORDER;
                        if (isset($order->image)) {
                            app(OrderService::class)->renameImage($order->image);
                        }
                    }
                }
                Order::where('id', $id)->update($orderForm);
                
                // Refresh models instance to get updated attributes
                $order = $this->orderRepository->find($id);
                $sender_new = $order->sender;
                $receiver_new = $order->receiver;

                if ($order && array_key_exists('delivery_status', $orderForm) && $orderForm['delivery_status'] != $order_old->delivery_status) {
                    $is_total_order = OrderHistory::IS_TOTAL_ORDER;
                    if (isset($orderForm['location_id'])) {
                        $order->city_id = $orderForm['location_id'];
                    }
                    if (array_key_exists('person_charge', $orderForm)) {
                        $order->person_charge = $orderForm['person_charge'];
                    }
                    $order->signator = $orderForm['signator'];
                    app(OrderTrackingService::class)->create($order, $request->all());
                }
                if (isset($orderForm['signator']) && $orderForm['signator'] != $order_old->signator) {
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
                        if (empty($findService)) {
                            Service::create($dataOrdeService);
                        }
                        array_push($serviceData, $item);
                    }
                }
                if (!empty($serviceData)) {
                    Service::where('order_id', $id)->whereNotIn('service', $serviceData)->delete();
                }
            }
            app(OrderHistoryService::class)->createOrderHistory(
                $order_old, $order, $request->all(), $is_total_order, OrderHistory::TYPE_ORDER_UPDATE, 
                null, null, null, null,
                $sender_old, $sender_new, $receiver_old, $receiver_new
            );
            DB::commit();
            Flash::success('Cập nhật vận đơn thành công.');
            return back();
        }
        catch (\Exception $e) {
            DB::rollback();
            Log::error('Lỗi cập nhật vận đơn: ' . $e->getMessage(), [
                'order_id' => $id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            // Hiển thị thông báo lỗi chi tiết hơn
            $errorMessage = 'Xảy ra lỗi khi cập nhật vận đơn';
            if (strpos($e->getMessage(), 'Kích thước') !== false ||
            strpos($e->getMessage(), 'ảnh') !== false ||
            strpos($e->getMessage(), 'image') !== false) {
                $errorMessage = $e->getMessage();
            }
            elseif (strpos($e->getMessage(), 'timeout') !== false ||
            strpos($e->getMessage(), 'Connection') !== false) {
                $errorMessage = 'Kết nối bị gián đoạn. Vui lòng kiểm tra kết nối mạng và thử lại.';
            }

            Flash::error($errorMessage);
        }
        return redirect()->route('orders.index');
    }

    public function import(Request $request)
    {
        if ($request->input('address_scheme') === 'new') {
            return $this->importNew($request);
        }
        return $this->importOld($request);
    }

    public function importNew(Request $request)
    {
        $file = $request->file('file');
        $message = '';
        if ($file) {
            $mimes = array('application/vnd.ms-excel', 'text/xls', 'text/xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            if (in_array($_FILES["file"]["type"], $mimes)) {
                $reader = IOFactory::createReaderForFile($file->getRealPath());
                $reader->setReadDataOnly(true);
                $chunkFilter = new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
                    public function readCell($columnAddress, $row, $worksheetName = '') {
                        $allowedColumns = range('A', 'T');
                        return $row <= 2000 && in_array($columnAddress, $allowedColumns);
                    }
                };
                $reader->setReadFilter($chunkFilter);
                $spreadsheet = $reader->load($file->getRealPath());
                $sheet = $spreadsheet->getActiveSheet();
                $row_limit = $sheet->getHighestDataRow();
                $row_range = range(2, $row_limit);
                
                $consecutive_empty_rows = 0;
                $addressService = app(\App\Services\Address2025Service::class);
                $orders = [];
                
                foreach ($row_range as $row) {
                    $hasData = false;
                    foreach (['B', 'C', 'E', 'F', 'G'] as $col) {
                        if (!empty($sheet->getCell($col . $row)->getValue())) {
                            $hasData = true;
                            break;
                        }
                    }
                    
                    if (!$hasData) {
                        $consecutive_empty_rows++;
                        if ($consecutive_empty_rows >= 15) {
                            break;
                        }
                        continue;
                    }
                    
                    $consecutive_empty_rows = 0;
                    DB::beginTransaction();
                    try {
                        $senderData = [
                            'sender_name' => $sheet->getCell('B' . $row)->getCalculatedValue() ?? '',
                            'sender_phone' => $sheet->getCell('C' . $row)->getCalculatedValue() ?? '',
                            'address' => $sheet->getCell('S' . $row)->getCalculatedValue() ?? '',
                            'address_scheme' => 'new'
                        ];
                        if (!empty($senderData['address'])) {
                            $parsed = $addressService->parseFullAddress($senderData['address']);
                            if ($parsed['success']) {
                                $senderData['address'] = $parsed['address'];
                                $senderData['new_province_id'] = $parsed['new_province_id'];
                                $senderData['new_ward_id'] = $parsed['new_ward_id'];
                            }
                        }

                        $receiverData = [
                            'receiver_name' => $sheet->getCell('E' . $row)->getCalculatedValue() ?? '',
                            'address' => $sheet->getCell('F' . $row)->getCalculatedValue() ?? '',
                            'receiver_phone' => $sheet->getCell('G' . $row)->getCalculatedValue() ?? '',
                            'address_scheme' => 'new'
                        ];
                        if (!empty($receiverData['address'])) {
                            $parsed = $addressService->parseFullAddress($receiverData['address']);
                            if ($parsed['success']) {
                                $receiverData['address'] = $parsed['address'];
                                $receiverData['new_province_id'] = $parsed['new_province_id'];
                                $receiverData['new_ward_id'] = $parsed['new_ward_id'];
                            }
                        }

                        if ($receiverData['address'] != '' || $senderData['address'] != '') {
                            $sender = Sender::create($senderData);
                            $receiver = Receiver::create($receiverData);
                            $orderData = [
                                'sender_id' => isset($sender) ? $sender->id : 0,
                                'receiver_id' => isset($receiver) ? $receiver->id : 0,
                                'order_date' => $sheet->getCell('A' . $row)->getValue(),
                                'department' => $sheet->getCell('D' . $row)->getValue(),
                                'weight' => $sheet->getCell('I' . $row)->getValue(),
                                'note' => $sheet->getCell('L' . $row)->getValue(),
                                'user_id' => auth()->user()->id,
                                'order_status' => Order::ORDER_BLANK,
                                'delivery_status' => Order::DELIVERY_STATUS_BLANK,
                                'quantity' => $sheet->getCell('O' . $row)->getValue() ?? 1,
                                'address_scheme' => 'new'
                            ];

                            if ($orderData['order_date']) {
                                if (gettype($orderData['order_date']) == 'integer') {
                                    $date = intval($orderData['order_date']);
                                    $orderData['order_date'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('d/m/Y');
                                }
                                $times = explode('/', $orderData['order_date']);
                                if (count($times) >= 3) {
                                    $orderData['order_date'] = $times[2] . '-' . $times[1] . '-' . $times[0];
                                }
                            } else {
                                $orderData['order_date'] = date('Y-m-d');
                            }

                            if ($sheet->getCell('H' . $row)->getValue()) {
                                $orderData['payment_method'] = app(OrderService::class)->getKeyPaymentMethod($sheet->getCell('H' . $row)->getValue());
                            }
                            if ($sheet->getCell('P' . $row)->getValue()) {
                                $orderData['type'] = app(OrderService::class)->getType($sheet->getCell('P' . $row)->getValue());
                            }
                            if (in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF])) {
                                if ($sheet->getCell('N' . $row)->getValue()) {
                                    $person_charge = User::where('name', 'LIKE', '%' . $sheet->getCell('N' . $row)->getValue() . '%')->first();
                                    $orderData['person_charge'] = isset($person_charge) ? $person_charge->id : 0;
                                }
                                if ($sheet->getCell('M' . $row)->getCalculatedValue()) {
                                    $orderData['invoice_code'] = $sheet->getCell('M' . $row)->getCalculatedValue();
                                }
                            }

                            $orderData['total'] = (int)$sheet->getCell('Q' . $row)->getValue();
                            $orderData['collection'] = (int)$sheet->getCell('R' . $row)->getValue();
                            $orderData['order_code'] = app(OrderService::class)->getOrderCode(config('order_manager.prefix_code'));
                            
                            if (isset($orderData['invoice_code'])) {
                                $checkOrder = Order::where('order_code', $orderData['invoice_code'])->first();
                                if (!$checkOrder) {
                                    $orderData['order_code'] = $orderData['invoice_code'];
                                } else {
                                    $orderData['invoice_code'] = $orderData['order_code'];
                                }
                            }

                            $order = Order::create($orderData);
                            if ($order) {
                                $orders[] = $order;
                                app(OrderHistoryService::class)->createOrderHistory(null, $order, [], OrderHistory::IS_TOTAL_ORDER, OrderHistory::TYPE_ORDER_CREATE);
                                app(OrderTrackingService::class)->create($order, []);
                                
                                if (isset($order->receiver) && !empty($order->receiver->receiver_phone)) {
                                    dispatch(new SendSMSJob($order));
                                }

                                $partnerCode = trim((string)$sheet->getCell('T' . $row)->getCalculatedValue());
                                if ($partnerCode) {
                                    if ($partnerCode == Order::CODE_VIETTEL_POST) {
                                        $infoService = app(OrderService::class)->getKeyService($sheet->getCell('J' . $row)->getValue());
                                        $serviceViettel = Service::VIETTEL_POST_SERVICE[$infoService['service_key']] ?? null;
                                        dispatch(new SendOrderViettelPostJob($order, $serviceViettel));
                                    } else if ($partnerCode == Order::CODE_EMS) {
                                        dispatch(new SendOrderEmsJob($order));
                                    }
                                }
                            }
                        }
                        DB::commit();
                    } catch (Exception $e) {
                        DB::rollback();
                        Log::error('Lỗi import excel 2025 dòng ' . $row . ': ' . $e->getMessage());
                    }
                }
                Flash::success('Import dữ liệu 2025 thành công.');
                return view('orders.import', compact('orders', 'message'));
            } else {
                Flash::error('Định dạng file không được hỗ trợ.');
                return back();
            }
        }
        Flash::error('Có lỗi xảy ra.');
        return back();
    }

    public function export(Request $request)
    {

        $formFilter = $request->all();

        if (!array_filter($formFilter)) {
            Flash::error('Bạn vui lòng nhập mục tìm kiếm');
            return back();
        }
        try {
            $file = Excel::download(new OrderExport($formFilter), 'orders.xlsx');
            return $file;
        }
        catch (Exception $exception) {
            Flash::error('Không có đơn nào trong ' . $request->start_date . ' - ' . $request->end_date);
            return back();
        }
    }

    public function importOld(Request $request)
    {
        $file = $request->file('file');
        $message = '';
        if ($file) {
            $partners = Partner::get();
            $orders = [];
            $mimes = array('application/vnd.ms-excel', 'text/xls', 'text/xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            if (in_array($_FILES["file"]["type"], $mimes)) {
                $reader = IOFactory::createReaderForFile($file->getRealPath());
                $reader->setReadDataOnly(true); // Optimize memory by ignoring formatting
                $chunkFilter = new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
                    public function readCell($columnAddress, $row, $worksheetName = '') {
                        $allowedColumns = range('A', 'T');
                        return $row <= 2000 && in_array($columnAddress, $allowedColumns);
                    }
                };
                $reader->setReadFilter($chunkFilter);
                $spreadsheet = $reader->load($file->getRealPath());
                $sheet = $spreadsheet->getActiveSheet();
                $row_limit = $sheet->getHighestDataRow();
                $column_limit = $sheet->getHighestDataColumn();
                $row_range = range(2, $row_limit);
                $column_range = range('O', $column_limit);
                $startcount = 2;
                $consecutive_empty_rows = 0;
                foreach ($row_range as $row) {
                    $hasData = false;
                    foreach (['B', 'C', 'E', 'F', 'G'] as $col) {
                        if (!empty($sheet->getCell($col . $row)->getValue())) {
                            $hasData = true;
                            break;
                        }
                    }
                    
                    if (!$hasData) {
                        $consecutive_empty_rows++;
                        if ($consecutive_empty_rows >= 15) {
                            break; // Stop completely if 15 consecutive empty rows are encountered
                        }
                        continue; // Skip this row
                    }
                    
                    $consecutive_empty_rows = 0;
                    DB::beginTransaction();
                    try {
                        $senderData = [
                            'sender_name' => $sheet->getCell('B' . $row)->getCalculatedValue() ? $sheet->getCell('B' . $row)->getCalculatedValue() : '',
                            'sender_phone' => $sheet->getCell('C' . $row)->getCalculatedValue() ? $sheet->getCell('C' . $row)->getCalculatedValue() : '',
                            'address' => $sheet->getCell('S' . $row)->getCalculatedValue() ?? '',
                        ];
                        // Tự động parse địa chỉ người gửi nếu có
                        if (!empty($senderData['address'])) {
                            $originalSenderAddress = $senderData['address'];
                            $parsedSender = $this->parseAddressToIds($senderData['address']);
                            $senderData = array_merge($senderData, $parsedSender);
                            $senderData['address'] = $parsedSender['address'] ?: $originalSenderAddress;
                        }
                        // dd($senderData);
                        $receiverData = [
                            'receiver_name' => $sheet->getCell('E' . $row)->getCalculatedValue() ? $sheet->getCell('E' . $row)->getCalculatedValue() : '',
                            'address' => $sheet->getCell('F' . $row)->getCalculatedValue() ? $sheet->getCell('F' . $row)->getCalculatedValue() : '',
                            'receiver_phone' => $sheet->getCell('G' . $row)->getCalculatedValue() ? $sheet->getCell('G' . $row)->getCalculatedValue() : '',
                            //                            'receiver_email' => $sheet->getCell( 'H' . $row )->getValue() ? $sheet->getCell( 'H' . $row )->getValue() : '',
                        ];
                        // Tự động parse địa chỉ người nhận nếu có
                        if (!empty($receiverData['address'])) {
                            $originalReceiverAddress = $receiverData['address'];
                            $parsedReceiver = $this->parseAddressToIds($receiverData['address']);
                            $receiverData = array_merge($receiverData, $parsedReceiver);
                            $receiverData['address'] = $parsedReceiver['address'] ?: $originalReceiverAddress;
                        }
                        if ($receiverData['address'] != '' || $senderData['address'] != '') {

                            $sender = Sender::create($senderData);
                            $receiver = Receiver::create($receiverData);
                            $orderData = [
                                'sender_id' => isset($sender) ? $sender->id : 0,
                                'receiver_id' => isset($receiver) ? $receiver->id : 0,
                                'order_date' => $sheet->getCell('A' . $row)->getValue(),
                                'department' => $sheet->getCell('D' . $row)->getValue(),
                                'weight' => $sheet->getCell('I' . $row)->getValue(),
                                'note' => $sheet->getCell('L' . $row)->getValue(),
                                'user_id' => auth()->user()->id,
                                'order_status' => Order::ORDER_BLANK,
                                'delivery_status' => Order::DELIVERY_STATUS_BLANK,
                                'quantity' => $sheet->getCell('O' . $row)->getValue() ?? 1
                            ];
                            if ($sheet->getCell('H' . $row)->getValue()) {
                                $orderData['payment_method'] = app(OrderService::class)->getKeyPaymentMethod($sheet->getCell('H' . $row)->getValue());
                            }
                            if ($sheet->getCell('P' . $row)->getValue()) {
                                $orderData['type'] = app(OrderService::class)->getType($sheet->getCell('P' . $row)->getValue());
                            }
                            if (in_array(auth()->user()->level, [\App\User::LEVEL_ADMIN, \App\User::LEVEL_STAFF])) {
                                if ($sheet->getCell('N' . $row)->getValue()) {
                                    $person_charge = User::where('name', 'LIKE', '%' . $sheet->getCell('N' . $row)->getValue() . '%')->first();
                                    $orderData['person_charge'] = isset($person_charge) ? $person_charge->id : 0;
                                }
                                if ($sheet->getCell('M' . $row)->getCalculatedValue()) {
                                    $orderData['invoice_code'] = $sheet->getCell('M' . $row)->getCalculatedValue();
                                }
                            }

                            if ($orderData['order_date']) {
                                if (gettype($orderData['order_date']) == 'integer') {
                                    $date = intval($orderData['order_date']);
                                    $orderData['order_date'] = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('d/m/Y');
                                }
                                $times = explode('/', $orderData['order_date']);
                                if (count($times) >= 3) {
                                    $convertDate = $times[2] . '-' . $times[1] . '-' . $times[0];
                                    $orderData['order_date'] = $convertDate;
                                }
                            }
                            else {
                                $orderData['order_date'] = date('Y-m-d');
                            }

                            $orderData['order_code'] = app(OrderService::class)->getOrderCode(config('order_manager.prefix_code'));
                            if (isset($orderData['invoice_code'])) {
                                $checkOrder = Order::where('order_code', $orderData['invoice_code'])->first();
                                if (!$checkOrder) {
                                    $orderData['order_code'] = $orderData['invoice_code'];
                                }
                                else {
                                    $message .= 'Mã bill ' . $orderData['invoice_code'] . ' đã tồn tại trên hệ thống nên được thay thế bằng mã bill mới là: ' . $orderData['order_code'] . '<br>';
                                    $orderData['invoice_code'] = $orderData['order_code'];
                                }
                            }
                            $total = $sheet->getCell('Q' . $row)->getValue();
                            $collection = $sheet->getCell('R' . $row)->getValue();
                            $orderData['total'] = (int)$total;
                            $orderData['collection'] = (int)$collection;
                            $order = Order::create($orderData);
                            if ($order) {
                                $orders[] = $order;

                                if (isset($order->receiver) && !empty($order->receiver->receiver_phone)) {
                                    dispatch(new SendSMSJob($order));
                                }
                                $partnerCode = trim((string)$sheet->getCell('T' . $row)->getCalculatedValue());
                                if ($partnerCode) {
                                    if ($partnerCode == Order::CODE_VIETTEL_POST) {
                                        $infoService = app(OrderService::class)->getKeyService($sheet->getCell('J' . $row)->getValue());
                                        // $serviceField = trim($sheet->getCell('J' . $row)->getValue());
                                        // $serviceViettel = isset(Service::VIETTEL_POST_SERVICE_ADD[$serviceField]) ? $serviceField : (Service::VIETTEL_POST_SERVICE[$infoService['service_key']] ?? null);
                                        // dispatch(new SendOrderViettelPostJob($order, $serviceViettel));
                                        $serviceViettel = Service::VIETTEL_POST_SERVICE[$infoService['service_key']] ?? null;
                                        dispatch(new SendOrderViettelPostJob($order, $serviceViettel));
                                    // $sendOrderViettelPost = new SendOrderViettelPostJob($order, $serviceViettel);
                                    // $sendOrderViettelPost->handle();
                                    }
                                    else if ($partnerCode == Order::CODE_EMS) {
                                        // Đẩy qua queue async để không bị timeout khi file lớn.
                                        // Queue worker chạy mỗi phút qua Kernel scheduler.
                                        // Icon lỗi sẽ hiện sau ~1 phút nếu push thất bại.
                                        dispatch(new SendOrderEmsJob($order));
                                    } // end else if CODE_EMS
                                } // end if ($partnerCode)
                                app(OrderTrackingService::class)->create($order, $request->all());
                                app(OrderHistoryService::class)->createOrderHistory(null, $order, null, OrderHistory::NOT_TOTAL_ORDER, OrderHistory::TYPE_ORDER_CREATE, 'IMPORT_EXCEL', ['action_desc' => 'Tạo mới vận đơn qua Import Excel']);
                            }

                            $dataService = [];
                            if ($sheet->getCell('J' . $row)->getValue()) {
                                $infoService = app(OrderService::class)->getKeyService($sheet->getCell('J' . $row)->getValue());
                                if ($infoService && array_key_exists('type', $infoService) && array_key_exists('service_key', $infoService)) {
                                    $dataService[$infoService['type']][] = $infoService['service_key'];
                                }
                            }

                            if ($sheet->getCell('K' . $row)->getValue()) {
                                $service_extra_string = $sheet->getCell('K' . $row)->getValue();
                                $service_extra_array = isset($service_extra_string) ? explode(',', $service_extra_string) : [];
                                foreach ($service_extra_array as $service_name) {
                                    $item = app(OrderService::class)->getKeyService(trim($service_name));
                                    if ($item && array_key_exists('type', $item) && array_key_exists('service_key', $item)) {
                                        $dataService[$item['type']][] = $item['service_key'];
                                    }
                                }
                            }
                            if ($order && !empty($dataService)) {
                                app(OrderService::class)->insertService($dataService, $order->id);
                            }
                        }
                        DB::commit();
                    }
                    catch (Exception $e) {
                        Flash::error($e->getMessage());
                        DB::rollback();
                    }
                    $startcount++;
                }
                if ($message != '') {
                    Flash::success($message);
                }
                return view('orders.import', ['orders' => $orders, 'partners' => $partners]);
            }
            else {
                Flash::error('File đã chọn phải là excel');
                return back();
            }
        }
        else {
            Flash::error('Không có file');
            return back();
        }
    }

    public function showFormImport()
    {
        $partners = Partner::get();
        return view('orders.import', ['orders' => [], 'partners' => $partners]);
    }

    public function showAddressImportTool()
    {
        return view('orders.address_import_tool', [
            'rows' => [],
            'summary' => null,
        ]);
    }

    public function previewAddressImportTool(Request $request)
    {
        $file = $request->file('file');
        if (!$file) {
            Flash::error('Bạn vui lòng chọn file Excel.');
            return redirect()->route('orders.addressImportTool');
        }

        $mimes = [
            'application/vnd.ms-excel',
            'text/xls',
            'text/xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
        if (!in_array($file->getClientMimeType(), $mimes)) {
            Flash::error('File đã chọn phải là Excel.');
            return redirect()->route('orders.addressImportTool');
        }

        $reader = IOFactory::createReaderForFile($file->getRealPath());
        $reader->setReadDataOnly(true);
        $chunkFilter = new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter {
            public function readCell($columnAddress, $row, $worksheetName = '') {
                $allowedColumns = range('A', 'T');
                return $row <= 2000 && in_array($columnAddress, $allowedColumns);
            }
        };
        $reader->setReadFilter($chunkFilter);
        $spreadsheet = $reader->load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rowLimit = $sheet->getHighestDataRow();
        $addressService = app(\App\Services\Address2025Service::class);
        $downloadToken = uniqid('address-check-', true);
        $downloadDir = storage_path('app/address-import-tool');
        if (!File::isDirectory($downloadDir)) {
            File::makeDirectory($downloadDir, 0755, true);
        }
        $originalFileName = $downloadToken . '.' . ($file->getClientOriginalExtension() ?: 'xlsx');
        $originalPath = $downloadDir . DIRECTORY_SEPARATOR . $originalFileName;
        File::copy($file->getRealPath(), $originalPath);

        $rows = [];
        $summary = [
            'total' => 0,
            'new' => 0,
            'old' => 0,
            'mixed' => 0,
            'unknown' => 0,
            'vtp_ready' => 0,
            'ems_ready' => 0,
            'has_warning' => 0,
            'warning_messages' => [],
            'download_token' => $downloadToken,
        ];
        $consecutiveEmptyRows = 0;

        if ($rowLimit < 2) {
            return view('orders.address_import_tool', compact('rows', 'summary'));
        }

        foreach (range(2, $rowLimit) as $rowNumber) {
            $receiverAddress = trim((string)$sheet->getCell('F' . $rowNumber)->getCalculatedValue());
            $receiverName = trim((string)$sheet->getCell('E' . $rowNumber)->getCalculatedValue());
            $receiverPhone = trim((string)$sheet->getCell('G' . $rowNumber)->getCalculatedValue());
            $partnerCode = $this->normalizeImportPartnerCode($sheet->getCell('T' . $rowNumber)->getCalculatedValue());

            if ($receiverAddress === '' && $receiverName === '' && $receiverPhone === '') {
                $consecutiveEmptyRows++;
                if ($consecutiveEmptyRows >= 15) {
                    break;
                }
                continue;
            }
            $consecutiveEmptyRows = 0;

            $receiverAnalysis = $this->analyzeImportAddress($receiverAddress, $addressService);
            $rowType = $receiverAnalysis['type'];
            $partnerReadiness = $this->resolvePartnerReadiness($receiverAnalysis);
            $warnings = $this->buildAddressImportWarnings($receiverAnalysis, $partnerCode, $partnerReadiness);

            $rows[] = [
                'row' => $rowNumber,
                'receiver_name' => $receiverName,
                'receiver_phone' => $receiverPhone,
                'partner_code' => $partnerCode,
                'receiver_address' => $receiverAddress,
                'receiver_analysis' => $receiverAnalysis,
                'type' => $rowType,
                'vtp' => $partnerReadiness['VTP'],
                'ems' => $partnerReadiness['EMS'],
                'warnings' => $warnings,
            ];

            $summary['total']++;
            $summary[$rowType]++;
            if ($partnerReadiness['VTP']['ready']) $summary['vtp_ready']++;
            if ($partnerReadiness['EMS']['ready']) $summary['ems_ready']++;
            if (!empty($warnings)) {
                $summary['has_warning']++;
                foreach ($warnings as $warning) {
                    $summary['warning_messages'][$warning] = ($summary['warning_messages'][$warning] ?? 0) + 1;
                }
            }
        }

        $warningRows = array_values(array_map(function ($row) {
            return $row['row'];
        }, array_filter($rows, function ($row) {
            return !empty($row['warnings']);
        })));

        File::put($downloadDir . DIRECTORY_SEPARATOR . $downloadToken . '.json', json_encode([
            'file' => $originalFileName,
            'warning_rows' => $warningRows,
        ]));

        return view('orders.address_import_tool', compact('rows', 'summary'));
    }

    public function downloadAddressImportTool($token, $type)
    {
        if (!in_array($type, ['all', 'errors'])) {
            abort(404);
        }

        $downloadDir = storage_path('app/address-import-tool');
        $metaPath = $downloadDir . DIRECTORY_SEPARATOR . $token . '.json';
        if (!File::exists($metaPath)) {
            abort(404);
        }

        $meta = json_decode(File::get($metaPath), true);
        $originalPath = $downloadDir . DIRECTORY_SEPARATOR . ($meta['file'] ?? '');
        if (!$meta || !File::exists($originalPath)) {
            abort(404);
        }

        $warningRows = array_map('intval', $meta['warning_rows'] ?? []);
        $spreadsheet = IOFactory::load($originalPath);
        $sheet = $spreadsheet->getActiveSheet();

        if ($type === 'errors') {
            $warningMap = array_flip($warningRows);
            for ($row = $sheet->getHighestDataRow(); $row >= 2; $row--) {
                if (!isset($warningMap[$row])) {
                    $sheet->removeRow($row);
                }
            }
            if ($sheet->getHighestDataRow() >= 2) {
                $sheet->getStyle('A2:T' . $sheet->getHighestDataRow())->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFD6D6');
            }
            $fileName = 'dia-chi-can-sua.xlsx';
        } else {
            foreach ($warningRows as $row) {
                $sheet->getStyle('A' . $row . ':T' . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFD6D6');
            }
            $fileName = 'dia-chi-da-kiem-tra.xlsx';
        }

        $outputPath = $downloadDir . DIRECTORY_SEPARATOR . $token . '-' . $type . '.xlsx';
        (new Xlsx($spreadsheet))->save($outputPath);

        return response()->download($outputPath, $fileName)->deleteFileAfterSend(true);
    }

    private function analyzeImportAddress($address, $addressService)
    {
        $address = trim((string)$address);
        $newParsed = $addressService->parseFullAddress($address);
        $oldParsed = $this->parseAddressToIds($address);

        $oldCity = !empty($oldParsed['city_id']) ? City::find($oldParsed['city_id']) : null;
        $oldDistrict = !empty($oldParsed['district_id']) ? District::find($oldParsed['district_id']) : null;
        $oldWard = !empty($oldParsed['ward_id']) ? Ward::find($oldParsed['ward_id']) : null;
        $newProvince = !empty($newParsed['new_province_id']) ? \App\NewProvince::find($newParsed['new_province_id']) : null;
        $newWard = !empty($newParsed['new_ward_id']) ? \App\NewWard::find($newParsed['new_ward_id']) : null;

        $oldReady = $oldCity && $oldDistrict && $oldWard;
        $newReady = !empty($newParsed['success']) && $newProvince && $newWard;

        if ($newReady && $oldReady) {
            $type = 'mixed';
        } elseif ($newReady) {
            $type = 'new';
        } elseif ($oldReady) {
            $type = 'old';
        } else {
            $type = 'unknown';
        }

        return [
            'input' => $address,
            'type' => $type,
            'new' => [
                'ready' => $newReady,
                'detail_address' => $newParsed['address'] ?? $address,
                'province_id' => $newParsed['new_province_id'] ?? null,
                'province_name' => $newProvince->name ?? null,
                'province_code' => $newProvince->official_code ?? null,
                'ward_id' => $newParsed['new_ward_id'] ?? null,
                'ward_name' => $newWard->name ?? null,
                'ward_code' => $newWard->official_code ?? null,
                'errors' => $newParsed['errors'] ?? [],
            ],
            'old' => [
                'ready' => $oldReady,
                'detail_address' => $oldParsed['address'] ?? $address,
                'city_id' => $oldParsed['city_id'] ?? null,
                'city_name' => $oldCity->city_name ?? null,
                'district_id' => $oldParsed['district_id'] ?? null,
                'district_name' => $oldDistrict->district_name ?? null,
                'ward_id' => $oldParsed['ward_id'] ?? null,
                'ward_name' => $oldWard->ward_name ?? null,
                'vtp' => [
                    'province_code' => $oldCity->city_code ?? null,
                    'district_code' => $oldDistrict->district_code ?? null,
                    'ward_code' => $oldWard->ward_code ?? null,
                ],
                'ems' => [
                    'province_code' => $oldCity->ems_code ?? null,
                    'district_code' => $oldDistrict->ems_code ?? null,
                    'ward_code' => $oldWard->ems_code ?? null,
                ],
            ],
        ];
    }

    private function resolvePartnerReadiness(array $receiverAnalysis)
    {
        $addressService = app(\App\Services\Address2025Service::class);

        $vtp = ['ready' => false, 'mode' => null, 'message' => 'Chưa xác định được địa chỉ người nhận.'];
        $ems = ['ready' => false, 'mode' => null, 'message' => 'Chưa xác định được địa chỉ người nhận.'];

        if ($receiverAnalysis['new']['ready']) {
            $vtpMapping = $addressService->getPartnerMapping($receiverAnalysis['new']['ward_id'], 'VTP');
            $emsMapping = $addressService->getPartnerMapping($receiverAnalysis['new']['ward_id'], 'EMS');

            $vtpReady = $vtpMapping && $vtpMapping->partner_province_code && $vtpMapping->partner_district_code && $vtpMapping->partner_ward_code;
            $emsReady = $emsMapping
                ? ($emsMapping->partner_province_code && $emsMapping->partner_ward_code)
                : ($receiverAnalysis['new']['province_code'] && $receiverAnalysis['new']['ward_code']);

            $vtp = [
                'ready' => (bool)$vtpReady,
                'mode' => 'new',
                'message' => $vtpReady ? 'Đủ mapping VTP từ địa chỉ mới.' : 'Thiếu mapping VTP cho xã/phường mới.',
            ];
            $ems = [
                'ready' => (bool)$emsReady,
                'mode' => 'new',
                'message' => $emsReady ? 'Có thể đẩy EMS theo địa chỉ mới.' : 'Thiếu mapping/mã EMS cho địa chỉ mới.',
            ];
        }

        if (!$vtp['ready'] && $receiverAnalysis['old']['ready']) {
            $codes = $receiverAnalysis['old']['vtp'];
            $ready = $codes['province_code'] && $codes['district_code'] && $codes['ward_code'];
            $vtp = [
                'ready' => (bool)$ready,
                'mode' => 'old',
                'message' => $ready ? 'Đủ mã VTP theo địa chỉ cũ.' : 'Thiếu mã VTP ở tỉnh/huyện/xã cũ.',
            ];
        }

        if (!$ems['ready'] && $receiverAnalysis['old']['ready']) {
            $codes = $receiverAnalysis['old']['ems'];
            $ready = $codes['province_code'] && $codes['district_code'] && $codes['ward_code'];
            $ems = [
                'ready' => (bool)$ready,
                'mode' => 'old',
                'message' => $ready ? 'Đủ mã EMS theo địa chỉ cũ.' : 'Thiếu mã EMS ở tỉnh/huyện/xã cũ.',
            ];
        }

        return ['VTP' => $vtp, 'EMS' => $ems];
    }

    private function buildAddressImportWarnings(array $receiverAnalysis, $partnerCode, array $partnerReadiness)
    {
        $warnings = [];
        if ($receiverAnalysis['type'] === 'unknown') {
            $warnings[] = 'Không nhận diện được địa chỉ người nhận.';
        }

        $partnerCode = strtoupper(trim((string)$partnerCode));
        if ($partnerCode === Order::CODE_VIETTEL_POST && !$partnerReadiness['VTP']['ready']) {
            $warnings[] = 'Dòng chọn VTP nhưng chưa đủ điều kiện đẩy Viettel.';
        }
        if ($partnerCode === Order::CODE_EMS && !$partnerReadiness['EMS']['ready']) {
            $warnings[] = 'Dòng chọn EMS nhưng chưa đủ điều kiện đẩy EMS.';
        }

        return $warnings;
    }

    private function normalizeImportPartnerCode($partnerCode)
    {
        $partnerCode = strtoupper(trim((string)$partnerCode));
        $partnerCode = preg_replace('/\s+/', '', $partnerCode);

        if (in_array($partnerCode, ['VIETTEL', 'VIETTELPOST', 'VIETTEL_POST', 'VT'])) {
            return Order::CODE_VIETTEL_POST;
        }

        if ($partnerCode === 'EMS') {
            return Order::CODE_EMS;
        }

        return $partnerCode;
    }

    public function renderTemplate(Request $request)
    {
        $orders = $request->order;
        $start = $request->start;
        $end = $request->end;
        $orderData = Order::with([
            'services',
            'sender.ward',
            'sender.district',
            'sender.city',
            'sender.newWard',
            'sender.newProvince',
            'receiver.ward',
            'receiver.district',
            'receiver.city',
            'receiver.newWard',
            'receiver.newProvince',
            'getPersonCharge',
            'user'
        ]);
        if ($start && $end) {
            $prefix_code = config('order_manager.prefix_code');
            $orderData = $orderData->where('id', '>=', $start)->where('id', '<=', $end)->where('order_code', 'LIKE', $prefix_code . '%')->get();
        }
        else if (!empty($orders)) {
            $orderData = $orderData->whereIn('id', $orders)->get();
        }
        else {
            $orderData = [];
        }
        $level = $request->number;
        app(OrderHistoryService::class)->insertManyOrderHistory($orderData, OrderHistory::TYPE_ORDER_PRINT);
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
        $file = public_path() . "/file/mau_tao_nhanh_bill.xls";

        $headers = array(
            'Content-Type: application/vnd.ms-excel',
        );
        return Response::download($file, 'mau_tao_nhanh_bill.xls', $headers);
    }

    function deleteMany(Request $request)
    {
        $orderIds = $request->order_ids;
        if (empty($orderIds)) {
            Flash::error('Bạn hãy chọn các vận đơn muốn xóa');
        }
        else {
            foreach($orderIds as $orderId) {
                $order = Order::find($orderId);
                if($order) {
                    app(OrderHistoryService::class)->createOrderHistory(null, $order, null, OrderHistory::NOT_TOTAL_ORDER, OrderHistory::TYPE_ORDER_UPDATE, 'DELETE', ['action_desc' => 'Xóa vận đơn: ' . ($order->order_code ?? $order->invoice_code)]);
                }
            }
            Order::whereIn('id', $orderIds)->delete();
            Flash::success('Xóa vận đơn thành công');
        }
        return route('orders.index');
    }
    function updateMany(Request $request)
    {
        $orderIds = $request->order_ids;
        $delivery_status = $request->delivery_status;
        if (empty($delivery_status)) {
            Flash::error('Bạn hãy chọn các trạng thái muốn cập nhật');
        }
        else if (empty($orderIds)) {
            Flash::error('Bạn hãy chọn các vận đơn muốn cập nhật');
        }
        else {
            foreach($orderIds as $orderId) {
                $order = Order::find($orderId);
                if($order) {
                    $order_old = clone $order;
                    $order->delivery_status = $delivery_status;
                    app(OrderHistoryService::class)->createOrderHistory($order_old, $order, null, OrderHistory::NOT_TOTAL_ORDER, OrderHistory::TYPE_ORDER_UPDATE, 'UPDATE', ['action_desc' => 'Cập nhật trạng thái nhiều vận đơn', 'changes' => ['Trạng thái' => ['old' => Order::DELIVERY_MAP[(int)$order_old->delivery_status] ?? $order_old->delivery_status, 'new' => Order::DELIVERY_MAP[(int)$delivery_status] ?? $delivery_status]]]);
                }
            }
            Order::whereIn('id', $orderIds)->update(['delivery_status' => $delivery_status]);
            app(OrderTrackingService::class)->createMany($orderIds, $delivery_status, $request->all());
            Flash::success('Cập nhật vận đơn thành công');
        }
        return route('orders.index');
    }

    public function resolveLegacyAddresses(Request $request)
    {
        if (!in_array(auth()->user()->level, [User::LEVEL_ADMIN, User::LEVEL_STAFF])) {
            Flash::error('Bạn không có quyền thực hiện thao tác này');
            return route('orders.index');
        }

        $orderIds = $request->order_ids;
        if (empty($orderIds)) {
            Flash::error('Bạn hãy chọn các vận đơn muốn tự gán lại địa chỉ');
            return route('orders.index');
        }

        $orders = Order::with(['sender', 'receiver'])->whereIn('id', $orderIds)->get();
        $updatedOrders = 0;
        $updatedAddresses = 0;

        foreach ($orders as $order) {
            $result = $this->resolveLegacyAddressIdsForOrder($order);
            if ($result['updated_addresses'] > 0) {
                $updatedOrders++;
                $updatedAddresses += $result['updated_addresses'];
            }
        }

        Flash::success("Đã tự gán lại địa chỉ cho {$updatedOrders} vận đơn, {$updatedAddresses} địa chỉ người gửi/người nhận.");
        return route('orders.index');
    }

    public function sendEmail(Request $request)
    {
        $type_email = $request->type_email;
        $orderIds = $request->order_ids;
        if (empty($type_email) || empty($orderIds)) {
            Flash::error('Bạn vui lòng chọn vận đơn và temlate email');
            return route('orders.index');
        }
        $errors = [];
        $success = [];
        $mail = new PHPMailer(true); // notice the \  you have to use root namespace here
        $orderId = null;
        try {
            $orders = Order::whereIn('id', $orderIds)->get();
            foreach ($orders as $order) {
                $orderId = $order->id;
                if ($order && isset($order->sender) && $order->sender->sender_email) {
                    $template = '';
                    if ($type_email == 2) {
                        $template = view('template.email_success', ['order' => $order, 'type_email' => $type_email])->render();
                    }
                    else {
                        $template = view('template.email_confirm', ['order' => $order, 'type_email' => $type_email])->render();
                    }

                    $mail->SMTPOptions = array(
                        'ssl' => array(
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true
                        )
                    );
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
                }
                else {
                    array_push($errors, $order->order_code);
                }
            }
            if (!empty($errors)) {
                Flash::error('Xảy ra lỗi gửi email với các vận đơn: ' . implode(', ', $errors));
            }
            if (!empty($success)) {
                Flash::success('Gửi email thành công với các vận đơn: ' . implode(', ', $success));
            }
        }
        catch (ExceptionMail $e) {
            Flash::error($e->getMessage());
        }
        if ($request->isUpdate && $orderId) {
            return route('orders.edit', [$orderId]);
        }
        return route('orders.index');
    }

    /**
     * Upload ảnh riêng biệt qua AJAX (tách khỏi transaction DB để tối ưu performance)
     */
    public function uploadImage(Request $request, $id)
    {
        try {
            $order = $this->orderRepository->find($id);
            $user = auth()->user();

            if ($user->level == User::LEVEL_USER && $order->user_id != $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            if (!isset($request->image_data)) {
                return response()->json(['error' => 'Không có dữ liệu ảnh'], 400);
            }

            $orderForm = $request->all();
            $order_code = isset($orderForm['invoice_code']) ? $orderForm['invoice_code'] : $order->order_code;

            $fileName = $this->upload(
                $request->image_data,
                $request->type_image,
                $order_code,
                OrderImage::SAVE_SERVER,
                $order,
                false
            );

            return response()->json([
                'success' => true,
                'file_name' => $fileName,
                'message' => 'Upload ảnh thành công'
            ]);
        }
        catch (\Exception $e) {
            Log::error('Lỗi upload ảnh qua AJAX: ' . $e->getMessage(), [
                'order_id' => $id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function upload($image_data, $type_image, $order_code, $type_save, $order, $is_remove = false)
    {
        $dataOrderImage = [];
        try {
            if ($type_save == OrderImage::SAVE_GOOGLE_DRIVE) {
                $fileImage = $this->orderImageService->setUp($image_data, $type_image, $order_code);
                $fileName = $fileImage->getFileName();
                $data = $this->googleDriveService->createFile($fileName, $fileImage->getContentFile(), $fileImage->getMimeType());
                $dataOrderImage['google_drive_id'] = $data->getFolder()->id;
                $dataOrderImage['file_id'] = $data->getFile()->id;
                $dataOrderImage['url'] = config('google_drive.url') . $data->getFile()->id;
            }
            else {
                $folderPath = public_path() . "/uploads/";

                // Đảm bảo thư mục tồn tại
                if (!File::isDirectory($folderPath)) {
                    File::makeDirectory($folderPath, 0755, true);
                }

                $fileName = $order_code . '.jpeg';
                if (File::exists($folderPath . $fileName)) {
                    unlink($folderPath . $fileName);
                }

                if ($type_image == OrderImage::TYPE_IMAGE_FILE) {
                    // Validate file size (max 10MB)
                    if ($image_data->getSize() > 10 * 1024 * 1024) {
                        throw new \Exception('Kích thước ảnh quá lớn. Vui lòng chọn ảnh nhỏ hơn 10MB.');
                    }
                    $fileName = $order_code . '.' . $image_data->getClientOriginalExtension();
                    $image_data->move($folderPath, $fileName);
                }
                else {
                    // Xử lý base64 image với error handling
                    if (empty($image_data) || !is_string($image_data)) {
                        throw new \Exception('Dữ liệu ảnh không hợp lệ.');
                    }

                    // Validate base64 string format
                    if (strpos($image_data, ';base64,') === false) {
                        throw new \Exception('Định dạng ảnh không hợp lệ.');
                    }

                    $image_parts = explode(";base64,", $image_data);
                    if (count($image_parts) < 2) {
                        throw new \Exception('Định dạng ảnh base64 không hợp lệ.');
                    }

                    $image_type_aux = explode("image/", $image_parts[0]);
                    if (count($image_type_aux) < 2) {
                        throw new \Exception('Loại ảnh không được hỗ trợ.');
                    }

                    $image_type = $image_type_aux[1];

                    // Validate base64 data size (max 10MB base64 = ~7.5MB actual)
                    $base64_data = $image_parts[1];
                    if (strlen($base64_data) > 13 * 1024 * 1024) { // ~10MB khi decode
                        throw new \Exception('Kích thước ảnh quá lớn. Vui lòng chụp lại với chất lượng thấp hơn.');
                    }

                    // Decode base64 với error handling
                    $image_base64 = @base64_decode($base64_data, true);
                    if ($image_base64 === false) {
                        throw new \Exception('Không thể giải mã dữ liệu ảnh. Vui lòng thử lại.');
                    }

                    // Validate decoded data
                    if (empty($image_base64)) {
                        throw new \Exception('Dữ liệu ảnh sau khi giải mã rỗng.');
                    }

                    $file = $folderPath . $fileName;
                    $result = @file_put_contents($file, $image_base64);
                    if ($result === false) {
                        throw new \Exception('Không thể lưu ảnh. Vui lòng kiểm tra quyền ghi file.');
                    }
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
        catch (\Exception $e) {
            Log::error('Lỗi upload ảnh: ' . $e->getMessage(), [
                'order_id' => $order->id ?? null,
                'order_code' => $order_code,
                'type_image' => $type_image
            ]);
            throw $e;
        }
    }

    public function sendSMS(Request $request)
    {
        $orderIds = $request->order_ids;
        if (empty($orderIds)) {
            Flash::error('Bạn vui lòng chọn vận đơn');
            return route('orders.index');
        }
        $errors = [];
        $success = [];
        $orderId = null;
        try {
            $orders = Order::whereIn('id', $orderIds)->get();
            foreach ($orders as $order) {
                $orderId = $order->id;
                if ($order && isset($order->receiver) && !empty($order->receiver->receiver_phone)) {
                    $response = app(SendSMSService::class)->sendSMS($order->receiver->receiver_phone, null, $order, true);
                    if ($response->CodeResult == 100) {
                        array_push($success, $order->order_code);
                    }
                    else {
                        array_push($errors, $order->order_code . ': ' . $response->ErrorMessage);
                    }
                }
                else {
                    array_push($errors, $order->order_code);
                }
            }
            if (!empty($errors)) {
                Flash::error('Xảy ra lỗi gửi SMS với các vận đơn: ' . implode(', ', $errors));
            }
            if (!empty($success)) {
                Flash::success('Gửi SMS thành công với các vận đơn: ' . implode(', ', $success));
            }
        }
        catch (Exception $e) {
            Flash::error($e->getMessage());
        }

        if ($request->isUpdate && $orderId) {
            return route('orders.edit', [$orderId]);
        }
        return route('orders.index');
    }

    public function sendZaloZNS(Request $request)
    {
        $orderIds = $request->order_ids;
        if (empty($orderIds)) {
            Flash::error('Bạn vui lòng chọn vận đơn');
            return route('orders.index');
        }

        $errors = [];
        $success = [];
        $orderId = null;

        try {
            $orders = Order::whereIn('id', $orderIds)->get();
            foreach ($orders as $order) {
                $orderId = $order->id;
                if ($order && isset($order->receiver) && !empty($order->receiver->receiver_phone) && !empty($order->receiver->receiver_name)) {
                    $response = $this->zaloService->sendZNS($order, true);
                    if ($response && $response['error'] == ZaloConfig::SUCCESS_CODE) {
                        array_push($success, $order->order_code);
                    }
                    else if ($response) {
                        array_push($errors, $order->order_code . ': ' . $response['message']);
                    }
                }
                else {
                    array_push($errors, $order->order_code);
                }
            }
            if (!empty($errors)) {
                Flash::error('Xảy ra lỗi gửi zalo với các vận đơn: ' . implode(', ', $errors));
            }
            if (!empty($success)) {
                Flash::success('Gửi zalo thành công với các vận đơn: ' . implode(', ', $success));
            }
        }
        catch (Exception $e) {
            Flash::error($e->getMessage());
        }

        if ($request->isUpdate && $orderId) {
            return route('orders.edit', [$orderId]);
        }
        return route('orders.index');
    }

    public function createOrderViettelPost($id)
    {
        $order = Order::findOrFail($id);
        // check đơn trùng trên viettel
        if ($order->order_partner_code) {
            Flash::error('Vận đơn đã có trên Viettel Post.');
            return back();
        }
        // check đơn trùng trên viettel
        $sendOrderViettelPost = new SendOrderViettelPostJob($order);
        $result = $sendOrderViettelPost->handle();
        if ($result['error']) {
            Flash::error('Xảy ra lỗi tạo vận đơn sang Viettel Post: Kiểm tra chi tiết đồng bộ API <a target="_blank" style="color: white" href="https://bill.ht-cargos.com/order-partner-logs">TẠI ĐÂY</a> - ' . ($result['message'] ?? ''));
        }
        else {
            Flash::success('Tạo vận đơn sang Viettel Post thành công.');
        }
        return back();
    }

    public function createViettelPost(Request $request)
    {
        $orderIds = $request->order_ids;
        if (empty($orderIds)) {
            Flash::error('Bạn vui lòng chọn vận đơn');
            return route('orders.index');
        }
        try {
            $orders = Order::with(['sender', 'receiver'])->whereIn('id', $orderIds)->get();
            foreach ($orders as $order) {
                $this->resolveLegacyAddressIdsForOrder($order);
                dispatch(new SendOrderViettelPostJob($order));
            // $sendOrderViettelPost = new SendOrderViettelPostJob($order);
            // $result = $sendOrderViettelPost->handle();
            }
            Flash::success('Vận đơn đã được thêm vào danh sách chờ đẩy lên VIETTEL POST. Kiểm tra <a target="_blank" style="color: white" href="https://bill.ht-cargos.com/order-partner-logs">TẠI ĐÂY</a>');
        }
        catch (Exception $e) {
            Flash::error($e->getMessage());
        }
        return route('orders.index');
    }

    public function createEms(Request $request)
    {
        $orderIds = $request->order_ids;
        if (empty($orderIds)) {
            Flash::error('Bạn vui lòng chọn vận đơn');
            return route('orders.index');
        }
        try {
            $orders = Order::with(['sender', 'receiver'])->whereIn('id', $orderIds)->get();
            foreach ($orders as $order) {
                $this->resolveLegacyAddressIdsForOrder($order);
                dispatch(new SendOrderEmsJob($order));
            // $sendOrderEms = new SendOrderEmsJob($order);
            // $result = $sendOrderEms->handle();
            }
            Flash::success('Vận đơn đã được thêm vào danh sách chờ đẩy lên EMS. Kiểm tra tại đây <a target="_blank" style="color: white" href="https://bill.ht-cargos.com/order-partner-logs">Link</a>');
        }
        catch (Exception $e) {
            Flash::error($e->getMessage());
        }
        return route('orders.index');
    }

    public function createOrderEms($id)
    {
        $order = Order::findOrFail($id);
        // check đơn trùng trên viettel
        if ($order->order_partner_code) {
            Flash::error('Vận đơn đã có trên ' . (Order::MAP_MESSAGE_NOTI_PARTNER[$order->partner_code] ?? '') . '.');
            return back();
        }
        // check đơn trùng trên viettel
        $sendOrderEms = new SendOrderEmsJob($order);
        $result = $sendOrderEms->handle();
        if (isset($result['code']) && $result['code'] == EmsService::STATUS_SUCCESS) {
            Flash::success('Tạo vận đơn sang EMS thành công.');
        }
        else {
            $errorMsg = $result['message'] ?? 'Không rõ lỗi';
            // Thêm chi tiết data từ EMS nếu có
            if (isset($result['data']) && !empty($result['data'])) {
                if (is_array($result['data'])) {
                    $errorMsg .= '<br><ul class="pl-4 mt-2 mb-0" style="list-style-type: disc;">';
                    foreach ($result['data'] as $err) {
                        $param = $err['Parameter'] ?? '';
                        $msg = $err['Message'] ?? '';
                        $errorMsg .= "<li><b>{$param}</b>: {$msg}</li>";
                    }
                    $errorMsg .= '</ul>';
                }
                else {
                    $errorMsg .= '<br>Chi tiết: ' . $result['data'];
                }
            }
            Flash::error('Lỗi đẩy đơn EMS: ' . $errorMsg);
        }
        return back();
    }

    /**
     * Tự động bóc tách Quận/Huyện/Tỉnh/Xã từ chuỗi địa chỉ
     * Format mong muốn: "Số nhà, Phường/Xã, Quận/Huyện, Tỉnh/TP"
     * Returns: ['city_id' => int|null, 'district_id' => int|null, 'ward_id' => int|null, 'address' => string]
     */
    private function parseAddressToIds(string $addressString): array
    {
        $result = ['city_id' => null, 'district_id' => null, 'ward_id' => null, 'address' => $addressString];
        if (empty(trim($addressString)))
            return $result;

        // Tách theo dấu phẩy và loại bỏ khoảng trắng thừa
        $parts = array_map('trim', explode(',', $addressString));
        $parts = array_filter($parts); // loại bỏ phần tử rỗng
        $parts = array_values($parts);
        $count = count($parts);

        if ($count < 2)
            return $result; // Không đủ cấu trúc để tách

        // Cấu trúc: phần cuối = Tỉnh, áp chót = Huyện, kế tiếp = Xã
        $cityStr = $parts[$count - 1] ?? null;
        $districtStr = $parts[$count - 2] ?? null;
        $wardStr = ($count >= 3) ? $parts[$count - 3] : null;

        // Hàm chuẩn hóa chuỗi: bỏ dấu tiếng Việt, viết thường
        $normalize = function ($str) {
            $str = mb_strtolower(trim($str ?? ''));
            $map = [
                'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
                'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
                'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
                'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
                'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
                'ă' => 'a', 'â' => 'a', 'đ' => 'd', 'ð' => 'd', 'Ð' => 'd', 'ê' => 'e', 'ô' => 'o', 'ơ' => 'o', 'ư' => 'u',
                'ạ' => 'a', 'ả' => 'a', 'ấ' => 'a', 'ầ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
                'ắ' => 'a', 'ằ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
                'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ế' => 'e', 'ề' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
                'ỉ' => 'i', 'ị' => 'i',
                'ọ' => 'o', 'ỏ' => 'o', 'ố' => 'o', 'ồ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
                'ớ' => 'o', 'ờ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
                'ụ' => 'u', 'ủ' => 'u', 'ứ' => 'u', 'ừ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
                'ỳ' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
            ];
            $str = strtr($str, $map);
            $str = preg_replace('/[^a-z0-9\s]/u', '', $str);
            return preg_replace('/\s+/', ' ', trim($str));
        };

        // Bảng alias viết tắt tỉnh/thành phố phổ biến
        $cityAliases = [
            'hn' => 'ha noi', 'hà nội' => 'ha noi',
            'hcm' => 'ho chi minh', 'tphcm' => 'ho chi minh', 'sg' => 'ho chi minh', 'sai gon' => 'ho chi minh',
            'hp' => 'hai phong', 'hải phòng' => 'hai phong',
            'dn' => 'da nang', 'đà nẵng' => 'da nang',
            'ct' => 'can tho', 'cần thơ' => 'can tho',
            'bd' => 'binh duong', 'bình dương' => 'binh duong',
            'brvt' => 'ba ria vung tau', 'vung tau' => 'ba ria vung tau', 'vũng tàu' => 'ba ria vung tau',
            'nt' => 'nghe an', 'na' => 'nghe an',
            'bg' => 'bac giang', 'bn' => 'bac ninh',
            'hg' => 'ha giang', 'tb' => 'thai binh',
            'qn' => 'quang ninh', 'cb' => 'cao bang',
        ];

        // Hàm so sánh tên địa phương chính xác hơn
        $isMatch = function($dbNameNorm, $searchNorm) {
            if ($dbNameNorm === $searchNorm) return true;
            
            $prefixes = ['tinh ', 'thanh pho ', 'tp ', 'quan ', 'huyen ', 'thi xa ', 'tx ', 'phuong ', 'xa '];
            $cleanDbName = $dbNameNorm;
            $cleanSearch = $searchNorm;
            
            foreach ($prefixes as $p) {
                if (strpos($cleanDbName, $p) === 0) $cleanDbName = trim(substr($cleanDbName, strlen($p)));
                if (strpos($cleanSearch, $p) === 0) $cleanSearch = trim(substr($cleanSearch, strlen($p)));
            }
            
            if ($cleanDbName === $cleanSearch) return true;

            if (preg_match('/\b' . preg_quote($cleanSearch, '/') . '\b/', $cleanDbName)) return true;
            if (preg_match('/\b' . preg_quote($cleanDbName, '/') . '\b/', $cleanSearch)) return true;

            return false;
        };

        // Tìm Tỉnh
        $cityNorm = $normalize($cityStr);
        // Áp dụng alias nếu có
        $citySearch = $cityAliases[$cityNorm] ?? $cityNorm;

        $city = City::all()->first(function ($c) use ($citySearch, $normalize, $isMatch) {
            $name = $normalize($c->city_name);
            return $isMatch($name, $citySearch);
        });

        if ($city) {
            $result['city_id'] = $city->id;
            $matchedCount = 1;

            // Tìm Huyện trong Tỉnh đã tìm thấy
            if ($districtStr) {
                $districtNorm = $normalize($districtStr);
                $district = District::where('city_id', $city->id)->get()->first(function ($d) use ($districtNorm, $normalize, $isMatch) {
                    $name = $normalize($d->district_name);
                    return $isMatch($name, $districtNorm);
                });
                if ($district) {
                    $result['district_id'] = $district->id;
                    $matchedCount = 2;

                    // Tìm Xã trong Huyện đã tìm thấy
                    if ($wardStr) {
                        $wardNorm = $normalize($wardStr);
                        $ward = Ward::where('district_id', $district->id)->get()->first(function ($w) use ($wardNorm, $normalize, $isMatch) {
                            $name = $normalize($w->ward_name);
                            return $isMatch($name, $wardNorm);
                        });
                        if ($ward) {
                            $result['ward_id'] = $ward->id;
                            $matchedCount = 3;
                        }
                    }
                }
            }

            // Reconstruct the remaining address string (Số nhà, Tên đường)
            // Remove the matched parts from the end
            $remainingParts = array_slice($parts, 0, $count - $matchedCount);
            $result['address'] = implode(', ', $remainingParts);
        }

        return $result;
    }

    private function fillLegacyAddressIdsWhenMissing(array $form, $addressModel): array
    {
        if (($form['address_scheme'] ?? optional($addressModel)->address_scheme) === 'new') {
            return $form;
        }

        $needsParse = empty($form['city_id']) || empty($form['district_id']) || empty($form['ward_id']);
        if (!$needsParse) {
            return $form;
        }

        $address = trim((string)($form['address'] ?? optional($addressModel)->address));
        if ($address === '') {
            return $form;
        }

        $parsed = $this->parseAddressToIds($address);
        foreach (['city_id', 'district_id', 'ward_id'] as $field) {
            if (empty($form[$field]) && !empty($parsed[$field])) {
                $form[$field] = $parsed[$field];
            }
        }

        if (!empty($parsed['city_id']) && !empty($parsed['district_id']) && !empty($parsed['ward_id'])) {
            $form['address'] = $parsed['address'];
        }

        return $form;
    }

    public function resolveLegacyAddressIdsForOrder(Order $order): array
    {
        $updatedAddresses = 0;

        foreach (['sender', 'receiver'] as $relation) {
            $addressModel = $order->{$relation};
            if (!$addressModel || $addressModel->address_scheme === 'new') {
                continue;
            }

            $form = [
                'city_id' => $addressModel->city_id,
                'district_id' => $addressModel->district_id,
                'ward_id' => $addressModel->ward_id,
                'address' => $addressModel->address,
                'address_scheme' => $addressModel->address_scheme,
            ];

            $resolved = $this->fillLegacyAddressIdsWhenMissing($form, $addressModel);
            $changed = false;

            foreach (['city_id', 'district_id', 'ward_id', 'address'] as $field) {
                if (array_key_exists($field, $resolved) && (string)$addressModel->{$field} !== (string)$resolved[$field]) {
                    $addressModel->{$field} = $resolved[$field];
                    $changed = true;
                }
            }

            if ($changed) {
                $addressModel->save();
                $updatedAddresses++;
            }
        }

        return ['updated_addresses' => $updatedAddresses];
    }

    private function hydrateLegacyAddressIdsForDisplay($order): void
    {
        foreach (['sender', 'receiver'] as $relation) {
            $addressModel = $order->{$relation};
            if (!$addressModel || $addressModel->address_scheme === 'new') {
                continue;
            }

            if (!empty($addressModel->city_id) && !empty($addressModel->district_id) && !empty($addressModel->ward_id)) {
                continue;
            }

            $parsed = $this->parseAddressToIds((string)$addressModel->address);
            foreach (['city_id', 'district_id', 'ward_id'] as $field) {
                if (empty($addressModel->{$field}) && !empty($parsed[$field])) {
                    $addressModel->{$field} = $parsed[$field];
                }
            }

            if (!empty($parsed['city_id']) && !empty($parsed['district_id']) && !empty($parsed['ward_id'])) {
                $addressModel->address = $parsed['address'];
            }
        }
    }
}
