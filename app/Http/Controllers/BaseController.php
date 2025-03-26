<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    /**
     * Success response method.
     *
     * @param mixed $result Typically an array or object containing the response data
     * @param string $message Optional success message
     * @param int $code HTTP status code (default: 200)
     * @return JsonResponse
     */
    protected function sendResponse($result, string $message = '', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'data'    => $result,
        ];

        if (!empty($message)) {
            $response['message'] = $message;
        }

        return response()->json($response, $code);
    }

    /**
     * Error response method.
     *
     * @param string $error Primary error message
     * @param array $errorDetails Additional error details (typically validation errors)
     * @param int $code HTTP status code (default: 400)
     * @return JsonResponse
     */
    protected function sendError(string $error, array $errorDetails = [], int $code = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorDetails)) {
            $response['errors'] = $errorDetails;
        }

        return response()->json($response, $code);
    }

    /**
     * Paginated response method.
     * (Optional addition for consistent pagination responses)
     */
    protected function sendPaginatedResponse($paginatedData, string $message = ''): JsonResponse
    {
        $response = [
            'success' => true,
            'data'    => $paginatedData->items(),
            'pagination' => [
                'current_page' => $paginatedData->currentPage(),
                'total_pages' => $paginatedData->lastPage(),
                'per_page' => $paginatedData->perPage(),
                'total' => $paginatedData->total(),
                'next_page' => $paginatedData->nextPageUrl(),
                'prev_page' => $paginatedData->previousPageUrl(),
            ]
        ];

        if (!empty($message)) {
            $response['message'] = $message;
        }

        return response()->json($response);
    }
}