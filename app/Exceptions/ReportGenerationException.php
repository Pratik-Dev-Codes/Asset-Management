<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Response;

class ReportGenerationException extends Exception
{
    /**
     * HTTP status code to be used when this exception is thrown.
     *
     * @var int
     */
    protected $code = Response::HTTP_INTERNAL_SERVER_ERROR;

    /**
     * Additional error data.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Report the exception.
     *
     * @return void
     */
    public function report()
    {
        // Log the exception with additional context
        \Log::error($this->getMessage(), [
            'exception' => $this,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
            'data' => $this->data,
        ]);
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request)
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
            'error' => [
                'code' => $this->getCode(),
                'message' => $this->getMessage(),
                'details' => $this->data,
            ],
        ];

        if ($request->expectsJson()) {
            return response()->json($response, $this->code);
        }

        return back()
            ->withInput()
            ->withErrors(['report' => $this->getMessage()])
            ->with('error', $this->getMessage())
            ->with('error_details', $this->data);
    }

    /**
     * Set additional error data.
     *
     * @return $this
     */
    public function withData(array $data)
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * Create a new validation exception for report filters.
     *
     * @return static
     */
    public static function validationError(string $message, array $errors = [])
    {
        $exception = new static($message);
        $exception->code = Response::HTTP_UNPROCESSABLE_ENTITY;

        return $exception->withData(['validation_errors' => $errors]);
    }

    /**
     * Create a new not found exception.
     *
     * @return static
     */
    public static function notFound(string $message = 'Report not found')
    {
        $exception = new static($message);
        $exception->code = Response::HTTP_NOT_FOUND;

        return $exception;
    }

    /**
     * Create a new permission denied exception.
     *
     * @return static
     */
    public static function permissionDenied(string $message = 'You do not have permission to access this report')
    {
        $exception = new static($message);
        $exception->code = Response::HTTP_FORBIDDEN;

        return $exception;
    }

    /**
     * Create a new export failed exception.
     *
     * @return static
     */
    public static function exportFailed(string $message = 'Failed to export report', ?string $format = null)
    {
        $exception = new static($message);
        $exception->code = Response::HTTP_INTERNAL_SERVER_ERROR;

        $data = [];
        if ($format) {
            $data['format'] = $format;
        }

        return $exception->withData($data);
    }

    /**
     * Create a new query exception.
     *
     * @return static
     */
    public static function queryError(string $message = 'Database query error', ?string $query = null)
    {
        $exception = new static($message);
        $exception->code = Response::HTTP_INTERNAL_SERVER_ERROR;

        $data = [];
        if ($query) {
            $data['query'] = $query;
        }

        return $exception->withData($data);
    }

    /**
     * Create a new invalid argument exception.
     *
     * @return static
     */
    public static function invalidArgument(string $message, array $data = [])
    {
        $exception = new static($message);
        $exception->code = Response::HTTP_BAD_REQUEST;

        return $exception->withData($data);
    }

    /**
     * Create a new rate limit exceeded exception.
     *
     * @return static
     */
    public static function tooManyRequests(string $message = 'Too many requests', int $retryAfter = 60)
    {
        $exception = new static($message);
        $exception->code = Response::HTTP_TOO_MANY_REQUESTS;

        return $exception->withData(['retry_after' => $retryAfter]);
    }

    /**
     * Create a new too many results exception.
     *
     * @return static
     */
    public static function tooManyResults(string $message, int $maxResults)
    {
        $exception = new static($message);
        $exception->code = Response::HTTP_BAD_REQUEST;

        return $exception->withData([
            'max_results' => $maxResults,
            'suggestion' => 'Try applying more filters to reduce the result set size.',
        ]);
    }
}
