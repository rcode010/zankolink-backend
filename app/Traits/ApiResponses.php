<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponses
{
    /**
     * Return a standard 200 OK success response with data.
     */
    protected function ok(string $message, array $data = []): JsonResponse
    {
        return $this->success($message, $data, 200);
    }

    /**
     * Return a 201 Created response when a resource is successfully stored.
     */
    protected function created(string $message, array $data = []): JsonResponse
    {
        return $this->success($message, $data, 201);
    }

    /**
     * Return a 200 OK dedicated helper for successful deletions.
     */
    protected function deleted(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], 200);
    }

    /**
     * Return a 204 No Content response (useful for alternative delete styles).
     */
    protected function noContent(): JsonResponse
    {
        return response()->json([], 204);
    }

    /**
     * Core success json response structure.
     */
    protected function success(string $message, array $data = [], int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Core error json response structure (400, 401, 403, 404, 500).
     */
    protected function error(string $message, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $statusCode);
    }

    protected function noData(string $message, array $data = []): JsonResponse
    {
        return response()->json([
            'message'=>"No data found",
            'success'=>false,
            'data'=>null
        ],200);
    }
}
