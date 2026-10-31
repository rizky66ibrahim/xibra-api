<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $status = 200,
        array $headers = []
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
            'error' => null,
        ], $status, $headers);
    }

    public static function error(
        ?string $message = null,
        ?array $errors = null,
        int $status = 400,
        array $headers = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => $message,
            'error' => [
                'code' => $status,
                'details' => $errors,
            ],
        ], $status, $headers);
    }

    public static function created(
        mixed $data = null,
        ?string $message = null,
        array $headers = []
    ): JsonResponse {
        return self::success($data, $message, 201, $headers);
    }
}
