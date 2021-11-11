<?php

namespace App\Exports;

use App\Models\PaymentHistory;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Excel;

class PaymentHistoryExport implements FromCollection, WithHeadings
{

    public $startDate;
    public $endDate;

    private $writerType = Excel::CSV;
    private $headers = [
        'Content-Type' => 'text/csv',
    ];

    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        DB::statement(DB::raw('set @row=0'));
       return PaymentHistory::leftjoin('users as receiver', 'payment_history.receiver_id', '=', 'receiver.id')
            ->leftjoin('users as remitter', 'payment_history.remitter_id', '=', 'remitter.id' )
            ->join('payments', 'payment_history.payment_id' , '=', 'payments.id')
            ->where('payment_history.date', '>=', $this->startDate)
            ->where('payment_history.date', '<=', $this->endDate)
            ->select(
                DB::raw('@row := @row + 1 AS stt'),
                'receiver.tracking_code',
                'receiver.name as receiver_name',
                'receiver.email',
                'receiver.number_id',
                'receiver.phone',
                'receiver.address',
                'account_number',
                'account_holder',
                'bank_name',
                'bank_branch',
                'remitter.name as remitter_name',
                'total_money',
                'percent_commission',
                'amount_transferred',
                'date',
                'receiver.lang'
            )->get();

    }

    public function headings(): array
    {
        return [
            __('export.stt'),
            __('export.tracking_code'),
            __('export.receiver_name'),
            __('export.email'),
            __('export.number_id'),
            __('export.phone'),
            __('export.address'),
            __('export.account_number'),
            __('export.account_holder'),
            __('export.bank_name'),
            __('export.bank_branch'),
            __('export.remitter_name'),
            __('export.total_money'),
            __('export.percent_commission'),
            __('export.amount_transferred'),
            __('export.date'),
            __('export.lang')
        ];
    }
}
