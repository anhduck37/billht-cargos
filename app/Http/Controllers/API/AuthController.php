<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{

    use AuthenticatesUsers;

    public function login(Request $request)
    {
        $this->validateLogin($request);

        $credentials = $request->only('email', 'password');
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        $data = $this->createNewToken($token);
        return $this->sendSuccessResponse($data);
    }

    public function refresh()
    {
        $data = $this->createNewToken(auth('api_jwt')->refresh());
        return $this->sendSuccessResponse($data);
    }

    protected function createNewToken($token)
    {
        return [
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => auth('api_jwt')->factory()->getTTL() * 60,
            // 'user' => auth('api_jwt')->user()
        ];
    }

    public function sendSuccessResponse($data, $message = 'Success', $code = 200): JsonResponse
    {
        return response()->json([
            'status_code' => $code,
            'message' => $message,
            'data' => $data
        ]);
    }
}
