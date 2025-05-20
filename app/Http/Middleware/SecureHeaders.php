<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SecureHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip for console commands or if the response is not a Response object
        if (app()->runningInConsole() || !method_exists($response, 'header')) {
            return $response;
        }

        // Generate a nonce for inline scripts and styles
        $nonce = app('encrypter')->encrypt(
            $request->session()->token() . $request->ip()
        );
        $nonce = base64_encode(hash('sha256', $nonce, true));

        // Content Security Policy with nonce
        $csp = [
            // Default policy for all content types
            "default-src 'self';",
            
            // JavaScript sources
            "script-src 'self' 'nonce-{$nonce}' 'strict-dynamic' https: 'unsafe-inline' 'unsafe-eval';",
            
            // Style sources
            "style-src 'self' 'nonce-{$nonce}' 'unsafe-inline' https:;",
            
            // Image sources
            "img-src 'self' data: https:;",
            
            // Font sources
            "font-src 'self' data: https:;",
            
            // Connect sources (XHR, WebSocket, EventSource, etc.)
            "connect-src 'self' https: wss:;",
            
            // Media sources (audio and video)
            "media-src 'self' https:;",
            
            // Frame sources
            "frame-src 'self' https:;",
            "frame-ancestors 'self';",
            
            // Form actions
            "form-action 'self';",
            
            // Object sources (plugins, etc.)
            "object-src 'none';",
            
            // Base URI
            "base-uri 'self';",
            
            // Mixed content
            "block-all-mixed-content;",
            "upgrade-insecure-requests;"
        ];

        // Security headers
        $headers = [
            // Prevent MIME type sniffing
            'X-Content-Type-Options' => 'nosniff',
            
            // Clickjacking protection
            'X-Frame-Options' => 'SAMEORIGIN',
            
            // XSS protection (legacy browsers)
            'X-XSS-Protection' => '1; mode=block',
            
            // Referrer policy
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            
            // Permissions policy
            'Permissions-Policy' => join(', ', [
                'accelerometer=()',
                'ambient-light-sensor=()',
                'autoplay=()',
                'battery=()',
                'camera=()',
                'display-capture=()',
                'document-domain=()',
                'encrypted-media=()',
                'fullscreen=()',
                'geolocation=()',
                'gyroscope=()',
                'magnetometer=()',
                'microphone=()',
                'midi=()',
                'payment=()',
                'picture-in-picture=()',
                'publickey-credentials-get=()',
                'sync-xhr=()',
                'usb=()',
                'screen-wake-lock=()',
                'web-share=()',
                'xr-spatial-tracking=()'
            ]),
            
            // HSTS (HTTPS only)
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
            
            // CSP headers
            'Content-Security-Policy' => implode(' ', $csp),
            'X-Content-Security-Policy' => implode(' ', $csp), // For older browsers
            'X-WebKit-CSP' => implode(' ', $csp), // For older WebKit browsers
            
            // X-Permitted-Cross-Domain-Policies
            'X-Permitted-Cross-Domain-Policies' => 'none',
            
            // Expect-CT (Certificate Transparency)
            'Expect-CT' => 'enforce, max-age=30',
            
            // Cross-Origin Resource Sharing
            'Cross-Origin-Embedder-Policy' => 'require-corp',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-site',
        ];

        // Add headers to response
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        return $response;
    }
}
