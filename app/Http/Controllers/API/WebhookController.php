<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\AppBaseController;
use App\Services\ViettelPostService;
use Illuminate\Http\Request;

class WebhookController extends AppBaseController
{
    public $viettelPostService;

    public function __construct(ViettelPostService $viettelPostService)
    {
        $this->viettelPostService = $viettelPostService;
    }
    public function viettelPost(Request $request) {
        $data = $request->all();
        $data = $this->viettelPostService->webhookTracking($data);
        return $this->sendSuccess('success');
    }
}
