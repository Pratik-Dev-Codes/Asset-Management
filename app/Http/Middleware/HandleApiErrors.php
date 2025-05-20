<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class HandleApiErrors
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response instanceof JsonResponse) {
            return $response;
        }

        $statusCode = $response->getStatusCode();
        
        if ($statusCode >= 400) {
            $message = $this->getErrorMessage($statusCode);
            
            if ($response->exception) {
                $exception = $response->exception;
                
                if ($exception instanceof ValidationException) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => $exception->errors(),
                    ], 422);
                }
                
                if ($exception instanceof ModelNotFoundException) {
                    return response()->json([
                        'message' => 'The requested resource was not found.',
                    ], 404);
                }
                
                if ($exception instanceof AuthorizationException) {
                    return response()->json([
                        'message' => $exception->getMessage() ?: 'This action is unauthorized.',
                    ], 403);
                }
                
                $message = $exception->getMessage() ?: $message;
            }
            
            return response()->json([
                'message' => $message,
                'status' => $statusCode,
            ], $statusCode);
        }
        
        return $response;
    }
    
    protected function getErrorMessage(int $statusCode): string
    {
        $messages = [
            400 => 'Bad Request',
            401 => 'Unauthenticated',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Server Error',
            503 => 'Service Unavailable',
        ];
        
        return $messages[$statusCode] ?? 'An error occurred';
    }
}
