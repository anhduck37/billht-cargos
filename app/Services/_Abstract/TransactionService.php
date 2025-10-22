<?php

namespace App\Services\_Abstract;

use App\Services\_Exception\AppServiceException;
use App\Services\_Response\ApiResponseProvider;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PHPUnit\Exception;

class TransactionService extends ApiResponseProvider
{

    /**
     * @param $callback
     * @return void
     * @throws AppServiceException
     */
    public function addCallback($callback): void
    {
        try {
            DB::beginTransaction();
            $callback();
            DB::commit();
        }catch (AppServiceException|Exception $exception) {
            DB::rollBack();
            Log::info($exception->getMessage());
            throw new AppServiceException($exception->getMessage());
        }
    }


    /**
     * @param Closure $callback
     * @param string $msg
     * @return JsonResponse
     */
    public function addCallBackWithJsonResponse(Closure $callback, string $msg = "success"): JsonResponse
    {
        try {
            DB::beginTransaction();
            $data = $callback();
            DB::commit();
            return $this->sendSuccessResponse($data, $msg);
        }catch (AppServiceException|Exception $exception) {
            DB::rollBack();
            Log::info($exception->getMessage());
            return $this->sendErrorResponse($exception->getMessage());
        }
    }
}
