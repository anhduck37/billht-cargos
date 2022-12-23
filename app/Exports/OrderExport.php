<?php

namespace App\Exports;

use App\Models\Order;
use App\Service;
use App\Services\OrderService;
use App\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class OrderExport implements FromCollection, WithHeadings, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public $form_filter;

    public function __construct($form_filter) {
        $this->form_filter = $form_filter;
    }

    public function collection()
    {
        $orders = Order::with(['services']);
        if(isset($this->form_filter['start_date']) && isset($this->form_filter['end_date'])) {
            $start_date = app(OrderService::class)->explodeDate($this->form_filter['start_date']);
            $end_date = app(OrderService::class)->explodeDate($this->form_filter['end_date']);
            $orders->where('orders.order_date', '>=', $start_date)->where('orders.order_date', '<=', $end_date);
        }

        if(isset($this->form_filter['order_code_from']) && isset($this->form_filter['order_code_to'])) {
            $prefix_code = config('order_manager.prefix_code');
            $order_id_from = (int)str_replace($prefix_code,'', $this->form_filter['order_code_from']);
            $order_id_to = (int)str_replace($prefix_code,'',$this->form_filter['order_code_to']);
            $orders->where('orders.id', '>=', $order_id_from)->where('orders.id', '<=',  $order_id_to)->where('order_code', 'LIKE', $prefix_code.'%');
        }

        if(isset($this->form_filter['search'])) {
            $orders->join('senders', 'senders.id', '=', 'orders.sender_id')
                ->join('receivers', 'receivers.id', '=', 'orders.receiver_id')
                ->where(function($q) {
                    $q->orWhere('senders.sender_name', 'LIKE','%' . $this->form_filter['search'] . '%')
                      ->orWhere('senders.sender_phone', 'LIKE','%' . $this->form_filter['search'] . '%')
                      ->orWhere('senders.address', 'LIKE','%' . $this->form_filter['search'] . '%')
                      ->orWhere('receivers.receiver_name', 'LIKE','%' . $this->form_filter['search'] . '%')
                      ->orWhere('receivers.receiver_phone', 'LIKE','%' . $this->form_filter['search'] . '%')
                      ->orWhere('receivers.address', 'LIKE','%' . $this->form_filter['search'] . '%')
                      ->orWhere('orders.order_code', 'LIKE','%' . $this->form_filter['search'] . '%');
                });
        }
        if(isset($this->form_filter['delivery_status'])) {
            $orders->where('orders.delivery_status', $this->form_filter['delivery_status']);
        }

        if(in_array(auth()->user()->level, [User::LEVEL_USER])) {
            $orders->where('orders.user_id', auth()->user()->id);
        }

        $orders = $orders->select('orders.*')->groupBy('orders.id')->get();
        $order = [];
        foreach ($orders as $item) {
            $services = isset($item->services) ? $item->services : [];
            $dataService = [];
            $dataServiceAdd = [];
            foreach ($services as $service) {
                if(array_key_exists($service->type, Service::SERVICE_MAP) && $service->type == Service::SERVICE_DOMESTIC) {
                    $data = Service::SERVICE_MAP[$service->type]['value'];
                    if(array_key_exists($service->service, $data) ){
                        $dataService[] = $data[$service->service];
                    }
                }else if(array_key_exists($service->type, Service::SERVICE_MAP)) {
                    $data = Service::SERVICE_MAP[$service->type]['value'];
                    if(array_key_exists($service->service, $data) ){
                        $dataServiceAdd[] = $data[$service->service];
                    }
                }
            }
            $order[] = array(
                '0' => app(OrderService::class)->implodeDate($item->order_date),
                '1' => $item->order_code,
                '2' => isset($item->sender) ? $item->sender->sender_name : '',
                '3' => isset($item->sender) ? $item->sender->sender_phone : '',
                '4' => $item->department,
                '5' => isset($item->receiver) ? $item->receiver->receiver_name : '',
                '6' => isset($item->receiver) ? $item->receiver->address : '',
                '7' => isset($item->receiver) ? $item->receiver->receiver_phone : '',
                '8' => $item->weight,
                '9' => implode(', ', $dataService),
                '10' => implode(', ', $dataServiceAdd),
                '11' => $item->note,
            );
        }
        return (collect($order));
    }

    public function headings(): array
    {
        return [
            'Ngày gửi',
            'Mã vận đơn',
            'Tên người gửi',
            'SĐT người gửi',
            'Phòng ban',
            'Tên người nhận',
            'Địa chỉ người nhận',
            'SĐT người nhận',
            'Trọng lượng (gram)',
            'Mã dịch vụ',
            'Dịch vụ cộng thêm',
            'Nội dung'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $event->sheet->getDelegate()->getStyle('1')->getFont()->setBold(true);
                $event->sheet->getDelegate()->getRowDimension('1')->setRowHeight(40);
                $event->sheet->getDelegate()->getColumnDimension('A')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('B')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('C')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('D')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('E')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('F')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('G')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('H')->setWidth(75);
                $event->sheet->getDelegate()->getColumnDimension('I')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('J')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('K')->setWidth(15);
                $event->sheet->getDelegate()->getColumnDimension('L')->setWidth(25);
                $event->sheet->getDelegate()->getColumnDimension('M')->setWidth(25);
                $event->sheet->getDelegate()->getStyle('A1:L1')
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
