<?php

namespace App\Shared\Http;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiResponse
{
    /**
     * @param  array<string, mixed>|JsonResource|ResourceCollection|LengthAwarePaginator|mixed  $data
     * @param  array<string, mixed>  $meta
     */
    public static function success(
        mixed $data = null,
        ?string $message = null,
        array $meta = [],
        int $status = 200,
    ): JsonResponse {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => self::normalizeData($data),
            'meta' => $meta ?: null,
            'errors' => null,
        ];

        return response()->json($payload, $status);
    }

    /**
     * @param  array<string, mixed>|null  $errors
     */
    public static function error(
        string $message,
        ?array $errors = null,
        int $status = 400,
        ?array $meta = null,
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'meta' => $meta,
            'errors' => $errors,
        ], $status);
    }

    private static function normalizeData(mixed $data): mixed
    {
        if ($data instanceof LengthAwarePaginator) {
            return self::paginatedPayload($data->items(), $data);
        }

        if ($data instanceof AnonymousResourceCollection && $data->resource instanceof LengthAwarePaginator) {
            /** @var LengthAwarePaginator $paginator */
            $paginator = $data->resource;

            return self::paginatedPayload($data->resolve(), $paginator);
        }

        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            return $data->resolve();
        }

        return $data;
    }

    /**
     * @param  list<mixed>  $items
     */
    private static function paginatedPayload(array $items, LengthAwarePaginator $paginator): array
    {
        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }
}
