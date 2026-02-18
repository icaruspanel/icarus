<?php
declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     * @param int                  $code
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function item(array $data, array $meta = [], int $code = 200): JsonResponse
    {
        $responseData = [
            'data' => $data,
        ];

        if (! empty($meta)) {
            $responseData['meta'] = $meta;
        }

        return new JsonResponse($responseData, $code);
    }
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     * @param int                  $code
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function error(array $data, array $meta = [], int $code = 500): JsonResponse
    {
        $responseData = [
            'error' => $data,
        ];

        if (! empty($meta)) {
            $responseData['meta'] = $meta;
        }

        return new JsonResponse($responseData, $code);
    }
}
