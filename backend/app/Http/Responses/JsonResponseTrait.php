<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

/**
 * 统一 JSON 响应格式：
 * {
 *   "code": 0,          // 0 表示成功，非 0 表示业务错误
 *   "message": "success",
 *   "data": ...
 * }
 */
trait JsonResponseTrait
{
    protected function success(mixed $data = null, string $message = 'success', int $status = 200): JsonResponse
    {
        return response()->json([
            'code' => 0,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    protected function error(string $message = 'error', int $code = 1, int $status = 400, mixed $data = null): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
