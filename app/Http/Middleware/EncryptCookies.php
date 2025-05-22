<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        'XSRF-TOKEN',
        'laravel_session',
        'laravel_token',
        'XSRF-TOKEN',
        'remember_web_*',
        'XSRF-TOKEN',
        'XSRF-TOKEN',
        'XSRF-TOKEN',
    ];
    
    /**
     * Indicates if cookies should be serialized.
     *
     * @var bool
     */
    protected static $serialize = true;
}
