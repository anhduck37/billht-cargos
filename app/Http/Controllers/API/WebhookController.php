<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Services\EmsService;
use App\Services\ViettelPostService;
use Illuminate\Http\Request;

class WebhookController extends AppBaseController
{
    public $viettelPostService;
    public $emsService;

    public function __construct(ViettelPostService $viettelPostService, EmsService $emsService)
    {
        $this->viettelPostService = $viettelPostService;
        $this->emsService = $emsService;
    }
    public function viettelPost(Request $request)
    {
        $data = $request->all();
        $data = $this->viettelPostService->webhookTracking($data);
        return $this->sendSuccess('success');
    }

    public function ems(Request $request)
    {
        $data = $request->all();
        // Webhook v2.1, trường "Data" là 1 string JSON, cần decode:
        if (isset($data['Data']) && is_string($data['Data'])) {
            $parsedData = json_decode($data['Data'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsedData)) {
                // EmsService logic expects structured data. Let's wrap standard format.
                $data['tracking_code'] = $parsedData['ItemCode'] ?? null;
                $data['status_code'] = $parsedData['StatusCode'] ?? null;
                $data['status_name'] = $parsedData['StatusName'] ?? null;
                $data['datetime'] = $parsedData['StatusTime'] ?? ($data['Time'] ?? null);
                $data['note'] = $parsedData['StatusNote'] ?? null;
                $data['locate'] = $parsedData['PosCode'] ?? null;
                $data['money_collect'] = $parsedData['TotalCOD'] ?? null;
                $data['main_fee'] = $parsedData['MainFee'] ?? null;
                $data['total_fee'] = $parsedData['TotalFee'] ?? null;
                $data['total_weight'] = $parsedData['Weight'] ?? null;
            }
        }
        
        $this->emsService->webhookTracking($data);
        return $this->sendSuccess('success');
    }
}
