<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponses
{
    protected function ok(string $message,array $data=[]): JsonResponse
    {
        return $this->success($message,$data,200);
    }
    protected function success(string $message,array $data=[],int $statusCode =200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message'=>$message,
            'data'=>$data,
        ],$statusCode);
    }
    protected function error(string $message,int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message'=>$message,
        ],$statusCode);
    }
}
