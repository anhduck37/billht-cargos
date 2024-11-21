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
        $data = $this->emsService->webhookTracking($data);
        return $this->sendSuccess('success');
    }
}
