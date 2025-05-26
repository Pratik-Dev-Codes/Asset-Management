<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        AuthenticationException::class,
        AuthorizationException::class,
        ModelNotFoundException::class,
        ValidationException::class,
        HttpException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $e
     * @return void
     */
    public function report(Throwable $e)
    {
        // Don't report these exceptions
        if ($this->shouldntReport($e)) {
            return;
        }

        // Add request context
        $context = [
            'url' => request() ? request()->fullUrl() : 'CLI',
            'method' => request() ? request()->method() : 'CLI',
            'ip' => request() ? request()->ip() : 'CLI',
            'user_agent' => request() ? request()->userAgent() : 'CLI',
        ];

        // Add authenticated user if available
        if (auth()->check()) {
            $context['user_id'] = auth()->id();
        }

        // Log the exception
        Log::error(
            $e->getMessage(),
            array_merge($context, [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ])
        );
    }



    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        // Handle API exceptions
        $this->renderable(function (Throwable $e, $request) {
            if ($this->isApiRequest($request)) {
                return $this->handleApiException($e);
            }
        });
    }

    /**
     * Check if the request is an API request
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function isApiRequest($request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    /**
     * Handle API exceptions
     *
     * @param  \Throwable  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleApiException(Throwable $e): JsonResponse
    {
        // Handle specific exceptions
        if ($e instanceof ValidationException) {
            return $this->handleValidationException($e);
        }

        if ($e instanceof AuthenticationException) {
            return $this->handleAuthenticationException($e);
        }

        if ($e instanceof AuthorizationException) {
            return $this->handleAuthorizationException($e);
        }

        if ($e instanceof ModelNotFoundException) {
            return $this->handleModelNotFoundException($e);
        }

        if ($e instanceof NotFoundHttpException) {
            return $this->handleNotFoundHttpException($e);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->handleMethodNotAllowedHttpException($e);
        }

        if ($e instanceof QueryException) {
            return $this->handleQueryException($e);
        }

        if ($e instanceof TooManyRequestsHttpException) {
            return $this->handleTooManyRequestsHttpException($e);
        }

        if ($e instanceof HttpException) {
            return $this->handleHttpException($e);
        }

        // Default exception handler
        return $this->handleDefaultException($e);
    }

    /**
     * Handle validation exception
     *
     * @param  \Illuminate\Validation\ValidationException  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleValidationException(ValidationException $e): JsonResponse
    {
        return response()->json([
            'status' => 'validation_error',
            'message' => 'The given data was invalid.',
            'errors' => $e->errors(),
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Handle authentication exception
     *
     * @param  \Illuminate\Auth\AuthenticationException  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleAuthenticationException(AuthenticationException $e): JsonResponse
    {
        return response()->json([
            'status' => 'unauthenticated',
            'message' => $e->getMessage() ?: 'Unauthenticated.',
        ], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Handle authorization exception
     *
     * @param  \Illuminate\Auth\Access\AuthorizationException  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleAuthorizationException(AuthorizationException $e): JsonResponse
    {
        return response()->json([
            'status' => 'unauthorized',
            'message' => $e->getMessage() ?: 'This action is unauthorized.',
        ], Response::HTTP_FORBIDDEN);
    }

    /**
     * Handle model not found exception
     *
     * @param  \Illuminate\Database\Eloquent\ModelNotFoundException  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleModelNotFoundException(ModelNotFoundException $e): JsonResponse
    {
        $model = class_basename($e->getModel());
        $model = Str::snake(str_replace('App\\Models\\', '', $model));
        
        return response()->json([
            'status' => 'not_found',
            'message' => "The requested {$model} was not found.",
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * Handle not found HTTP exception
     *
     * @param  \Symfony\Component\HttpKernel\Exception\NotFoundHttpException  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleNotFoundHttpException(NotFoundHttpException $e): JsonResponse
    {
        return response()->json([
            'status' => 'not_found',
            'message' => 'The requested resource was not found.',
        ], Response::HTTP_NOT_FOUND);
    }

    /**
     * Handle method not allowed HTTP exception
     *
     * @param  \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleMethodNotAllowedHttpException(MethodNotAllowedHttpException $e): JsonResponse
    {
        return response()->json([
            'status' => 'method_not_allowed',
            'message' => 'The specified method for the request is invalid.',
            'allowed_methods' => $e->getHeaders()['Allow'] ?? [],
        ], Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Handle query exception
     *
     * @param  \Illuminate\Database\QueryException  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleQueryException(QueryException $e): JsonResponse
    {
        $errorCode = $e->errorInfo[1] ?? null;
        
        // Handle specific SQL errors
        switch ($errorCode) {
            case 1062: // Duplicate entry
                return response()->json([
                    'status' => 'duplicate_entry',
                    'message' => 'A duplicate entry already exists.',
                ], Response::HTTP_CONFLICT);
                
            case 1451: // Foreign key constraint
                return response()->json([
                    'status' => 'constraint_violation',
                    'message' => 'Cannot delete or update a parent row: a foreign key constraint fails.',
                ], Response::HTTP_CONFLICT);
                
            default:
                return $this->handleDefaultException($e);
        }
    }

    /**
     * Handle too many requests HTTP exception
     *
     * @param  \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleTooManyRequestsHttpException(TooManyRequestsHttpException $e): JsonResponse
    {
        $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;
        
        return response()->json([
            'status' => 'too_many_requests',
            'message' => 'Too many attempts. Please try again later.',
            'retry_after' => (int) $retryAfter,
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }

    /**
     * Handle HTTP exception
     *
     * @param  \Symfony\Component\HttpKernel\Exception\HttpException  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleHttpException(HttpException $e): JsonResponse
    {
        $statusCode = $e->getStatusCode();
        
        return response()->json([
            'status' => Str::snake(Response::$statusTexts[$statusCode] ?? 'error'),
            'message' => $e->getMessage() ?: Response::$statusTexts[$statusCode] ?? 'An error occurred',
        ], $statusCode);
    }

    /**
     * Handle default exception
     *
     * @param  \Throwable  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleDefaultException(Throwable $e): JsonResponse
    {
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        
        // Handle HttpException which has getStatusCode
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
        } 
        // Handle other exceptions with status codes
        else {
            $code = $e->getCode();
            if (is_numeric($code) && $code >= 400 && $code < 600) {
                $statusCode = (int) $code;
            }
        }
            
        $response = [
            'status' => 'error',
            'message' => $e->getMessage() ?: 'An error occurred while processing your request.',
        ];

        // Add debug info in non-production environments
        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
            ];
        }

        return response()->json($response, $statusCode);
    }
}
