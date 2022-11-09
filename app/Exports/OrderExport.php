<?php

namespace App\Exports;

use App\Models\Order;
use App\Service;
use App\Services\OrderService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class OrderExport implements FromCollection, WithHeadings, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public $start_date;
    public $end_date;

    public function __construct($start_date, $end_date) {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
    }

    public function collection()
    {
        $orders = Order::where('order_date', '>=', $this->start_date)->where('order_date', '<=', $this->end_date)->get();
        $order = [];
        foreach ($orders as $item) {
            $services = Service::where('order_id', $item->id)->get();
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
                '2' => $item->invoice_code,
                '3' => isset($item->sender) ? $item->sender->sender_name : '',
                '4' => isset($item->sender) ? $item->sender->sender_phone : '',
                '5' => $item->department,
                '6' => isset($item->receiver) ? $item->receiver->receiver_name : '',
                '7' => isset($item->receiver) ? $item->receiver->address : '',
                '8' => isset($item->receiver) ? $item->receiver->receiver_phone : '',
                '9' => $item->weight,
                '10' => implode(', ', $dataService),
                '11' => implode(', ', $dataServiceAdd),
                '12' => $item->note
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
                $event->sheet->getDelegate()->getColumnDimension('M')->setWidth(15);
                $event->sheet->getDelegate()->getStyle('A1:M1')
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}
