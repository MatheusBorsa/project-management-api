<?php

namespace App\Utils;

use App\Dtos\ApiResponseDto;
use Illuminate\Http\JsonResponse;

class ApiResponseUtil
{
    public static function success(
        string $message,
        mixed $data = null,
        int $status = 200
    ): JsonResponse {
        $response = new ApiResponseDto(
            success: true,
            message: $message,
            data: $data,
            status: $status
        );
        return response()->json($response->toArray(), $status);
    }

    public static function error(
        string $message,
        ?array $errors,
        int $status = 400
    ): JsonResponse {
        $response = new ApiResponseDto(
            success: false,
            message: $message,
            errors: $errors,
            status: $status
        );
        return response()->json($response->toArray(), $status);
    }
}