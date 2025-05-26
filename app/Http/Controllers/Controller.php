<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Throwable;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * HTTP status codes
     */
    const STATUS_SUCCESS = 'success';
    const STATUS_ERROR = 'error';
    const STATUS_VALIDATION_ERROR = 'validation_error';
    const STATUS_UNAUTHORIZED = 'unauthorized';
    const STATUS_FORBIDDEN = 'forbidden';
    const STATUS_NOT_FOUND = 'not_found';
    const STATUS_TOO_MANY_REQUESTS = 'too_many_requests';
    const STATUS_SERVER_ERROR = 'server_error';

    /**
     * Return a success JSON response
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    /**
     * Return a success JSON response
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    protected function success($data = null, string $message = 'Success', int $code = Response::HTTP_OK): JsonResponse
    {
        $response = [
            'status' => self::STATUS_SUCCESS,
            'message' => $message,
        ];

        if ($data !== null) {
            if ($data instanceof LengthAwarePaginator) {
                $response['data'] = $data->items();
                $response['meta'] = [
                    'total' => $data->total(),
                    'per_page' => $data->perPage(),
                    'current_page' => $data->currentPage(),
                    'last_page' => $data->lastPage(),
                    'from' => $data->firstItem(),
                    'to' => $data->lastItem(),
                    'path' => request()->url(),
                    'links' => [
                        'first' => $data->url(1),
                        'last' => $data->url($data->lastPage()),
                        'prev' => $data->previousPageUrl(),
                        'next' => $data->nextPageUrl(),
                    ],
                ];
            } else {
                $response['data'] = $data;
            }
        }

        return response()->json($response, $code);
    }

    /**
     * Return an error JSON response
     *
     * @param string $message
     * @param int $code
     * @param array $errors
     * @param string|null $errorCode
     * @return JsonResponse
     */
    protected function error(
        string $message = 'Error', 
        int $code = Response::HTTP_INTERNAL_SERVER_ERROR, 
        array $errors = [],
        ?string $errorCode = null
    ): JsonResponse {
        $response = [
            'status' => self::STATUS_ERROR,
            'message' => $message,
            'code' => $errorCode ?? $this->getErrorCode($code)
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        // Include debug info in non-production environments
        if (config('app.env') !== 'production') {
            $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
            $response['debug'] = [
                'file' => $debug[0]['file'] ?? null,
                'line' => $debug[0]['line'] ?? null,
            ];
        }

        return response()->json($response, $code);
    }

    /**
     * Get a standardized error code from HTTP status code
     *
     * @param int $statusCode
     * @return string
     */
    /**
     * Get a standardized error code from HTTP status code
     *
     * @param int $statusCode
     * @return string
     */
    protected function getErrorCode(int $statusCode): string
    {
        $codes = [
            Response::HTTP_BAD_REQUEST => 'bad_request',
            Response::HTTP_UNAUTHORIZED => 'unauthenticated',
            Response::HTTP_FORBIDDEN => 'forbidden',
            Response::HTTP_NOT_FOUND => 'not_found',
            Response::HTTP_METHOD_NOT_ALLOWED => 'method_not_allowed',
            Response::HTTP_UNPROCESSABLE_ENTITY => 'validation_failed',
            Response::HTTP_TOO_MANY_REQUESTS => 'too_many_requests',
            Response::HTTP_INTERNAL_SERVER_ERROR => 'server_error',
            Response::HTTP_SERVICE_UNAVAILABLE => 'service_unavailable',
        ];

        return $codes[$statusCode] ?? 'unknown_error';
    }

    /**
     * Return a validation error response
     *
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    /**
     * Return a validation error response
     * 
     * @param array $errors
     * @param string $message
     * @return JsonResponse
     */
    protected function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->error(
            $message,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $errors,
            'validation_failed'
        );
    }

    /**
     * Return an unauthorized error JSON response
     *
     * @param string $message
     * @return JsonResponse
     */
    /**
     * Return an unauthorized response
     * 
     * @param string $message
     * @return JsonResponse
     */
    protected function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->error($message, Response::HTTP_UNAUTHORIZED, [], 'unauthenticated');
    }

    /**
     * Return a forbidden error JSON response
     *
     * @param string $message
     * @return JsonResponse
     */
    /**
     * Return a forbidden response
     * 
     * @param string $message
     * @return JsonResponse
     */
    protected function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->error($message, Response::HTTP_FORBIDDEN, [], 'forbidden');
    }

    /**
     * Return a not found error JSON response
     *
     * @param string $message
     * @return JsonResponse
     */
    /**
     * Return a not found response
     * 
     * @param string $message
     * @return JsonResponse
     */
    protected function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->error($message, Response::HTTP_NOT_FOUND, [], 'not_found');
    }

    /**
     * Return a too many requests error JSON response
     *
     * @param string $message
     * @return JsonResponse
     */
    /**
     * Return a too many requests response
     * 
     * @param string $message
     * @return JsonResponse
     */
    protected function tooManyRequests(string $message = 'Too many requests'): JsonResponse
    {
        return $this->error(
            $message,
            Response::HTTP_TOO_MANY_REQUESTS,
            [],
            'too_many_requests'
        );
    }

    /**
     * Return a server error JSON response
     *
     * @param string $message
     * @param \Throwable|null $exception
     * @return JsonResponse
     */
    /**
     * Return a server error response
     * 
     * @param string $message
     * @param Throwable|null $exception
     * @return JsonResponse
     */
    protected function serverError(string $message = 'Internal server error', ?Throwable $exception = null): JsonResponse
    {
        if ($exception !== null) {
            Log::error($exception->getMessage(), [
                'exception' => $exception,
                'trace' => $exception->getTraceAsString(),
            ]);

            if (config('app.debug')) {
                return $this->error(
                    $message,
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    [
                        'message' => $exception->getMessage(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                    ],
                    'server_error'
                );
            }
        }

        return $this->error($message, Response::HTTP_INTERNAL_SERVER_ERROR, [], 'server_error');
    }
}
