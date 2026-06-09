<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success(mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            // If it's a paginator or API Resource Collection, handle the nested structure
            if (method_exists($data, 'toArray')) {
                $paginated = $data->toArray(request());

                // Safely grab the data array, falling back to the whole array if 'data' key doesn't exist
                $response['data'] = $paginated['data'] ?? $paginated;

                // Attach pagination metadata if it exists (current_page, last_page, etc.)
                if (isset($paginated['meta'])) {
                    $response['meta'] = $paginated['meta'];
                }
            } else {
                $response['data'] = $data;
            }
        }

        return response()->json($response, $status);
    }

    protected function error(string $message, int $status = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }
}
