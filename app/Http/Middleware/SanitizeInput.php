<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SanitizeInput
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
        $input = $request->all();
        
        // Skip if no input
        if (empty($input)) {
            return $next($request);
        }
        
        // Sanitize each input value
        array_walk_recursive($input, function (&$value, $key) {
            // Skip password fields and other sensitive data
            if (in_array(strtolower($key), ['password', 'password_confirmation', 'current_password'])) {
                return;
            }
            
            // Only sanitize strings
            if (is_string($value)) {
                $value = $this->clean($value);
            }
        });
        
        // Replace the request input with sanitized data
        $request->replace($input);
        
        return $next($request);
    }
    
    /**
     * Clean the given value.
     *
     * @param  string  $value
     * @return string
     */
    protected function clean($value)
    {
        // Convert special characters to HTML entities
        $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
        
        // Remove any attribute starting with "on" or XML namespaced
        $value = preg_replace('#(<[^>]+?[\x00-\x20\"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $value);
        
        // Remove javascript: and vbscript: protocols
        $value = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'\"])*([\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $value);
        $value = preg_replace('#([a-z]*)[\x00-\x20]*=([\'\"])*([\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:)#iu', '$1=$2novbscript...', $value);
        
        // Remove data: protocols
        $value = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'\"])*([\x00-\x20]*d[\x00-\x20]*a[\x00-\x20]*t[\x00-\x20]*a[\x00-\x20]*:*[\x00-\x20]*)(?:[^\w]|$)#iu', '$1=$2nodata...', $value);
        
        // Remove any attribute starting with "on" or XML namespaced
        $value = preg_replace('#(<[^>]+[\x00-\x20\"\'])(on|xmlns)[^>]*>#iUu', "$1>", $value);
        
        // Remove javascript: and vbscript: protocols
        $value = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'\"])*([\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu', '$1=$2nojavascript...', $value);
        $value = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'\"])*([\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iUu', '$1=$2novbscript...', $value);
        
        // Only allow safe protocols
        $value = str_replace(['%00', '\0'], '', $value);
        
        return $value;
    }
}
