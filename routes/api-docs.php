<?php

use Illuminate\Support\Facades\Route;
use OpenApi\Generator;

Route::get('/api-docs.json', function () {
    $openapi = Generator::scan([
        app_path('Http/Controllers/Api'),
    ]);
    
    return response()->json(json_decode($openapi->toJson()));
});

Route::get('/api-docs', function () {
    return view('api-docs');
});
