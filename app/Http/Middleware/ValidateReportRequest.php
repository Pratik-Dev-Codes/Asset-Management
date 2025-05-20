<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ValidateReportRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $validator = Validator::make($request->all(), [
            'report_type' => ['required', 'string', 'in:asset,user,accessory,consumable,license'],
            'format' => ['required', 'string', 'in:csv,xlsx,pdf'],
            'filters' => ['nullable', 'array'],
            'filters.*' => ['required_with:filters', 'array'],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['required', 'string'],
            'sort_by' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'string', 'in:asc,desc'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        return $next($request);
    }
}
