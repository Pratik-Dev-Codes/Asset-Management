<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ApiExceptionHandler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
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
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $e)
    {
        // Only handle API requests
        if ($request->is('api/*') || $request->wantsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleApiException($request, Throwable $e)
    {
        $e = $this->prepareException($e);

        if ($e instanceof AuthenticationException) {
            return $this->unauthenticated($request, $e);
        }

        if ($e instanceof ValidationException) {
            return $this->convertValidationExceptionToResponse($e, $request);
        }

        $response = [];
        $statusCode = 500;

        if (method_exists($e, 'getStatusCode')) {
            $statusCode = $e->getStatusCode();
        } elseif (property_exists($e, 'status')) {
            $statusCode = $e->status;
        }

        switch (true) {
            case $e instanceof ModelNotFoundException:
                $response['message'] = 'The requested resource was not found.';
                $statusCode = 404;
                break;
                
            case $e instanceof NotFoundHttpException:
                $response['message'] = 'The requested endpoint was not found.';
                $statusCode = 404;
                break;
                
            case $e instanceof MethodNotAllowedHttpException:
                $response['message'] = 'The specified method for the request is invalid.';
                $statusCode = 405;
                break;
                
            case $e instanceof HttpException:
                $response['message'] = $e->getMessage() ?: 'An error occurred while processing your request.';
                break;
                
            default:
                $response['message'] = $e->getMessage() ?: 'An error occurred while processing your request.';
                
                // Include debug information in non-production environments
                if (config('app.debug')) {
                    $response['debug'] = [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ];
                }
                break;
        }

        // Add error code if available
        if (method_exists($e, 'getCode') && $e->getCode() > 0) {
            $response['code'] = $e->getCode();
        }

        // Add validation errors if available
        if (isset($e->validator)) {
            $response['errors'] = $e->errors();
        }

        // Add status code to response
        $response['status'] = $statusCode;

        return response()->json($response, $statusCode, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json([
            'message' => 'Unauthenticated.',
            'status' => 401,
        ], 401);
    }

    /**
     * Create a response object from the given validation exception.
     *
     * @param  \Illuminate\Validation\ValidationException  $e
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {
        $errors = $e->errors();
        $message = $e->getMessage();
        
        // If no custom message is set, use the first error message
        if ($message === 'The given data was invalid.') {
            $message = 'Validation failed.';
            
            // Get the first error message
            $firstError = collect($errors)->first();
            if (is_array($firstError) && count($firstError) > 0) {
                $message = $firstError[0];
            }
        }
        
        return response()->json([
            'message' => $message,
            'errors' => $errors,
            'status' => 422,
        ], 422);
    }
}
