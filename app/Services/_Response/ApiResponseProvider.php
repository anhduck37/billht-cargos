<?php

namespace App\Services\_Response;

use App\Services\_Response\ApiResponseContract as Contract;
use Illuminate\Http\JsonResponse;

abstract class ApiResponseProvider implements Contract
{
    public function sendErrorResponse($message, $errors = null, $code = 400): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    public function sendSuccessResponse($data, $message = '', $code = 200): JsonResponse
    {
        return response()->json([
            'status_code' => $code,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
