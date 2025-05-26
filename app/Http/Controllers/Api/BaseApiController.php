<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Facades\Log;

class BaseApiController extends Controller
{
    /**
     * Send a success response.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success($data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data instanceof ResourceCollection) {
            $response = array_merge($response, $data->response()->getData(true));
        } elseif ($data instanceof JsonResource) {
            $response['data'] = $data;
        } elseif ($data instanceof AbstractPaginator) {
            $response = array_merge($response, $this->formatPaginatedData($data));
        } elseif (!is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $code);
    }

    /**
     * Format paginated data.
     *
     * @param  \Illuminate\Pagination\AbstractPaginator  $paginator
     * @return array
     */
<<<<<<< HEAD
    protected function formatPaginatedData(AbstractPaginator $paginator): array
=======
    protected function error(string $message = 'Error', int $code = 500, array $errors = [], ?string $errorCode = null): JsonResponse
>>>>>>> main
    {
        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ];
    }

    /**
     * Send an error response.
     *
     * @param  string  $message
     * @param  int  $code
     * @param  array  $errors
     * @param  mixed  $debug
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error(
        string $message = 'Error',
        int $code = 400,
        array $errors = [],
        $debug = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        if (config('app.debug') && !is_null($debug)) {
            $response['debug'] = $debug;
        }

        return response()->json($response, $code);
    }

    /**
     * Send a not found response.
     *
     * @param  string  $message
     * @param  array  $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function notFound(
        string $message = 'Resource not found',
        array $errors = []
    ): JsonResponse {
        return $this->error($message, 404, $errors);
    }

    /**
     * Send an unauthorized response.
     *
     * @param  string  $message
     * @param  array  $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function unauthorized(
        string $message = 'Unauthorized',
        array $errors = []
    ): JsonResponse {
        return $this->error($message, 401, $errors);
    }

    /**
     * Send a forbidden response.
     *
     * @param  string  $message
     * @param  array  $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function forbidden(
        string $message = 'Forbidden',
        array $errors = []
    ): JsonResponse {
        return $this->error($message, 403, $errors);
    }

    /**
     * Send a validation error response.
     *
     * @param  array  $errors
     * @param  string  $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function validationError(
        array $errors = [],
        string $message = 'Validation failed'
    ): JsonResponse {
        return $this->error($message, 422, $errors);
    }

    /**
     * Send a server error response.
     *
     * @param  string  $message
     * @param  \Throwable|null  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function serverError(
        string $message = 'Internal server error',
        ?\Throwable $exception = null
    ): JsonResponse {
        $debug = null;

        if (config('app.debug') && $exception) {
            $debug = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];

            // Log the exception
            Log::error($message, [
                'exception' => $exception,
                'debug' => $debug,
            ]);
        }


        return $this->error($message, 500, [], $debug);
    }
}
