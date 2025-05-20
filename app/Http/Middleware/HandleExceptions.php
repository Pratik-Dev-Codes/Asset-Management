<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class HandleExceptions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     * @throws \Throwable
     */
    public function handle($request, Closure $next)
    {
        // Start transaction if not in test environment
        if (!app()->environment('testing') && !DB::transactionLevel()) {
            DB::beginTransaction();
        }

        try {
            $response = $next($request);

            // Log 4xx and 5xx responses
            if ($response->isServerError() || $response->isClientError()) {
                $this->logError($request, $response->status());
            }

            // Commit transaction if one was started
            if (DB::transactionLevel() > 0) {
                DB::commit();
            }

            return $response;
        } catch (Throwable $e) {
            // Rollback transaction if one was started
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            
            return $this->handleException($request, $e);
        }
    }

    /**
     * Handle the exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    /**
     * Handle the exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleException($request, Throwable $e)
    {
        // Log the exception
        $this->logError($request, $e);

        // Handle specific exceptions
        if ($e instanceof ModelNotFoundException) {
            return $this->errorResponse(
                'The requested resource was not found.', 
                404, 
                $e
            );
        }

        if ($e instanceof AuthenticationException) {
            return $this->errorResponse(
                'Unauthenticated. Please log in to access this resource.', 
                401,
                $e
            );
        }

        if ($e instanceof AuthorizationException) {
            return $this->errorResponse(
                'You do not have permission to perform this action.', 
                403,
                $e
            );
        }

        if ($e instanceof ValidationException) {
            return $this->errorResponse(
                'The given data was invalid.', 
                422, 
                $e,
                ['errors' => $e->errors()]
            );
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->errorResponse(
                'The specified HTTP method is not allowed for this resource.', 
                405,
                $e
            );
        }

        if ($e instanceof NotFoundHttpException) {
            return $this->errorResponse(
                'The requested URL was not found on this server.', 
                404,
                $e
            );
        }

        if ($e instanceof QueryException) {
            return $this->handleDatabaseException($e);
        }

        if ($e instanceof ThrottleRequestsException || $e instanceof TooManyRequestsHttpException) {
            $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;
            return $this->errorResponse(
                'Too many attempts. Please try again in ' . $retryAfter . ' seconds.', 
                429,
                $e,
                ['retry_after' => $retryAfter]
            );
        }

        // Default error response for unhandled exceptions
        $statusCode = $this->getHttpStatusCode($e);
        $message = config('app.debug') 
            ? $e->getMessage() 
            : 'An error occurred while processing your request. Please try again later.';

        return $this->errorResponse($message, $statusCode, $e);
    }

    /**
     * Get the HTTP status code from the exception.
     *
     * @param  \Throwable  $e
     * @return int
     */
    protected function getHttpStatusCode(Throwable $e)
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }

        return 500; // Internal Server Error
    }

    /**
     * Return a JSON error response.
     *
     * @param  mixed  $message
     * @param  int  $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Handle database exceptions.
     *
     * @param  \Illuminate\Database\QueryException  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleDatabaseException(QueryException $e)
    {
        $errorCode = $e->errorInfo[1] ?? null;
        
        // Handle common database errors
        switch ($errorCode) {
            case 1062: // Duplicate entry
                return $this->errorResponse(
                    'A record with this data already exists.',
                    409,
                    $e
                );
            case 1451: // Foreign key constraint violation
                return $this->errorResponse(
                    'Cannot delete or update a parent row: a foreign key constraint fails.',
                    409,
                    $e
                );
            case 2002: // Connection refused
                return $this->errorResponse(
                    'Could not connect to the database. Please try again later.',
                    503,
                    $e
                );
            default:
                return $this->errorResponse(
                    'A database error occurred. Please try again later.',
                    500,
                    $e
                );
        }
    }

    /**
     * Return a JSON error response.
     *
     * @param  string|array  $message
     * @param  int  $statusCode
     * @param  \Throwable|null  $e
     * @param  array  $additionalData
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse($message, $statusCode, Throwable $e = null, array $additionalData = [])
    {
        $response = array_merge([
            'success' => false,
            'message' => $message,
            'status' => $statusCode,
            'timestamp' => now()->toDateTimeString(),
        ], $additionalData);

        // Add trace in debug mode if exception is provided
        if (config('app.debug') && $e !== null) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
            ];

            // Only include full trace in debug mode for non-production environments
            if (config('app.env') !== 'production') {
                $response['debug']['trace'] = $e->getTraceAsString();
            }
        }

        return response()->json($response, $statusCode, [
            'Content-Type' => 'application/json',
            'X-Error-Code' => $e ? get_class($e) : 'unknown',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Log the error.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $error
     * @return void
     */
    /**
     * Log the error with context.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $error
     * @return void
     */
    protected function logError($request, $error)
    {
        $context = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
            'request_data' => $this->getRequestData($request),
        ];

        if ($error instanceof Throwable) {
            $message = $error->getMessage();
            $context = array_merge($context, [
                'exception' => get_class($error),
                'file' => $error->getFile(),
                'line' => $error->getLine(),
                'code' => $error->getCode(),
                'previous' => $error->getPrevious() ? get_class($error->getPrevious()) : null,
            ]);

            // Only include full trace for non-production or for server errors
            if (config('app.env') !== 'production' || $this->isServerError($error)) {
                $context['trace'] = $error->getTraceAsString();
            }
        } else {
            $message = is_numeric($error) 
                ? "HTTP Error: {$error} " . Response::$statusTexts[$error] ?? ''
                : "Error: {$error}";
        }

        // Use appropriate log level based on error type
        if ($error instanceof Throwable) {
            if ($this->isClientError($error)) {
                Log::warning($message, $context);
            } else {
                Log::error($message, $context);
            }
        } else {
            Log::error($message, $context);
        }
    }

    /**
     * Get sanitized request data for logging.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function getRequestData($request)
    {
        $data = $request->all();
        
        // Remove sensitive data
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'api_token', 'authorization'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***REDACTED***';
            }
        }
        
        return $data;
    }
    
    /**
     * Check if the error is a client error (4xx).
     *
     * @param  mixed  $error
     * @return bool
     */
    protected function isClientError($error)
    {
        if ($error instanceof HttpException) {
            return $error->getStatusCode() >= 400 && $error->getStatusCode() < 500;
        }
        
        if (is_numeric($error)) {
            return $error >= 400 && $error < 500;
        }
        
        return false;
    }
    
    /**
     * Check if the error is a server error (5xx).
     *
     * @param  mixed  $error
     * @return bool
     */
    protected function isServerError($error)
    {
        if ($error instanceof HttpException) {
            return $error->getStatusCode() >= 500 && $error->getStatusCode() < 600;
        }
        
        if (is_numeric($error)) {
            return $error >= 500 && $error < 600;
        }
        
        return !$this->isClientError($error);
    }
}
