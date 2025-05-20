<?php

namespace App\Exceptions;

use App\Exceptions\ApiExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
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
     * @return void
     */
    public function report(Throwable $e)
    {
        parent::report($e);
    }

    /**
     * Determine if the exception should be reported.
     *
     * @return bool
     */
    public function shouldReport(Throwable $e)
    {
        return parent::shouldReport($e);
    }

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
            // Add request details for web requests
            if (app()->runningInConsole()) {
                $context['command'] = request()->server('argv', []);
            } else {
                $context['url'] = request()->fullUrl();
                $context['method'] = request()->method();
                $context['ip'] = request()->ip();
                $context['user_agent'] = request()->userAgent();

                if (auth()->check()) {
                    $context['user_id'] = auth()->id();
                }
            }

            // Log the exception with context
            if ($this->shouldReport($e)) {
                Log::error($e->getMessage(), $context);
            }
        });

        // Custom exception rendering for API
        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($this->shouldReturnJson($request, $e)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The requested resource was not found.',
                    'errors' => [
                        'resource' => ['The requested resource does not exist.'],
                    ],
                ], 404);
            }
        });

        $this->renderable(function (ValidationException $e, $request) {
            if ($this->shouldReturnJson($request, $e)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The given data was invalid.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $this->renderable(function (AuthenticationException $e, $request) {
            if ($this->shouldReturnJson($request, $e)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated.',
                    'errors' => [
                        'authentication' => ['You are not authenticated.'],
                    ],
                ], 401);
            }
        });

        $this->renderable(function (MethodNotAllowedHttpException $e, $request) {
            if ($this->shouldReturnJson($request, $e)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The specified method for the request is invalid.',
                    'errors' => [
                        'method' => ['The requested method is not allowed for this resource.'],
                    ],
                ], 405);
            }
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($this->shouldReturnJson($request, $e)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'The requested resource was not found.',
                    'errors' => [
                        'resource' => ['The requested resource does not exist.'],
                    ],
                ], 404);
            }
        });

        // Handle all other exceptions and HTTP exceptions
        $this->renderable(function (Throwable $e, $request) {
            if ($this->shouldReturnJson($request, $e)) {
                $status = 500;

                if ($e instanceof HttpException) {
                    $status = $e->getStatusCode();
                } elseif ($e instanceof HttpExceptionInterface) {
                    $status = $e->getCode();
                }

                $response = [
                    'status' => 'error',
                    'message' => $e->getMessage() ?: 'An error occurred while processing your request.',
                ];

                // Add debug info in non-production environments
                if (config('app.debug')) {
                    $response['exception'] = get_class($e);
                    $response['file'] = $e->getFile();
                    $response['line'] = $e->getLine();
                    $response['trace'] = $e->getTrace();
                }

                return response()->json($response, $status);
            }

            return redirect()->guest(route('login'));
        });

        // Add debug information in non-production environments for unhandled exceptions
        if (config('app.debug')) {
            $this->renderable(function (Throwable $e, $request) {
                $response = [
                    'debug' => [
                        'message' => $e->getMessage(),
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTrace(),
                    ],
                ];

                return response()->json($response, 500);
            });
        }
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        if ($request->is('api/*') || $request->wantsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions.
     */
    private function handleApiException(Request $request, Throwable $exception): JsonResponse
    {
        $exception = $this->prepareException($exception);

        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($exception instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $exception->errors(),
            ], 422);
        }

        if ($exception instanceof ModelNotFoundException) {
            $model = str_replace('App\\Models\\', '', $exception->getModel());

            return response()->json([
                'success' => false,
                'message' => "{$model} not found.",
            ], 404);
        }

        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'The specified URL cannot be found.',
            ], 404);
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'The specified method for the request is invalid.',
            ], 405);
        }

        if ($exception instanceof HttpException) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $exception->getStatusCode());
        }

        // Default error response
        $statusCode = 500;
        $message = 'Whoops, looks like something went wrong.';

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getCode();
            $message = $exception->getMessage() ?: $message;
        }

        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ];
        }

        return response()->json([
            'success' => false,
            'message' => $message,
        ], $statusCode);
    }
}
