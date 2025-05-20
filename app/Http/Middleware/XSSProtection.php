<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class XSSProtection
{
    /**
     * The fields that should not be stripped of scripts.
     *
     * @var array
     */
    protected $except = [
        'password',
        'password_confirmation',
        'content',
        'body',
        'description',
        'bio',
        'comment',
        'message',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        
        array_walk_recursive($input, function (&$input, $key) {
            // Skip if the field is in the except array
            if (in_array($key, $this->except, true)) {
                return;
            }
            
            // Skip if the input is not a string
            if (!is_string($input)) {
                return;
            }
            
            // Clean the input
            $input = $this->clean($input);
        });
        
        $request->merge($input);
        
        return $next($request);
    }
    
    /**
     * Clean the input string.
     *
     * @param  string  $value
     * @return string
     */
    protected function clean($value)
    {
        // Remove all HTML tags except those explicitly allowed
        $value = strip_tags($value, 
            '<p><a><br><strong><em><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><pre><code><hr>'
        );
        
        // Remove any attribute starting with "on" or using javascript:
        $value = preg_replace('#(<[^>]+?[\x00-\x20\"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $value);
        
        // Remove javascript: and data: protocols
        $value = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'\"])[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $value);
        $value = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'\"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $value);
        $value = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'\"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $value);
        $value = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'\"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $value);
        $value = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'\"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $value);
        $value = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'\"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $value);
        
        // Remove any unwanted protocols
        $value = str_replace(['<?', '?'.'>'], ['&lt;?', '?&gt;'], $value);
        
        return $value;
    }
}
