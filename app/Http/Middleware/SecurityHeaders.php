<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! method_exists($response, 'header')) {
            return $response;
        }

        // Define nonce for inline scripts and styles
        $nonce = app('encrypter')->encrypt(
            $request->session()->token().$request->ip()
        );
        $nonce = base64_encode(hash('sha256', $nonce, true));

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
            'block-all-mixed-content;',
            'upgrade-insecure-requests;',

            // Report violations to this URI (optional)
            // "report-uri /csp-violation-report-endpoint;",

            // Enable CSP in report-only mode (for testing)
            // "report-uri /csp-violation-report-endpoint; report-to csp-endpoint;",
        ];

        $headers = [
            // Prevent MIME type sniffing
            'X-Content-Type-Options' => 'nosniff',

            // Clickjacking protection
            'X-Frame-Options' => 'SAMEORIGIN',

            // XSS protection (legacy browsers)
            'X-XSS-Protection' => '1; mode=block',

            // Referrer policy
            'Referrer-Policy' => 'strict-origin-when-cross-origin',

            // Feature policy (replaced by Permissions-Policy in newer browsers)
            'Feature-Policy' => "geolocation 'none'; microphone 'none'; camera 'none';",

            // Permissions policy
            'Permissions-Policy' => implode(', ', [
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
                'xr-spatial-tracking=()',
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

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }

        // Remove unwanted headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
