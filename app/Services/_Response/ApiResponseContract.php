<?php

namespace App\Services\_Response;


use Illuminate\Http\JsonResponse;

interface ApiResponseContract
{
    function sendErrorResponse($message, $errors = null, $code = 400): JsonResponse;

    function sendSuccessResponse($data, $message = '', $code = 200): JsonResponse;
}
