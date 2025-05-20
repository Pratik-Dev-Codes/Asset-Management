<?php

if (! function_exists('swagger_scan_paths')) {
    /**
     * Get the paths to scan for Swagger annotations.
     *
     * @return array
     */
    function swagger_scan_paths()
    {
        return [
            app_path('Http/Controllers/Api'),
            app_path('Models'),
            app_path('Http/Resources'),
        ];
    }
}

if (! function_exists('generate_swagger_docs')) {
    /**
     * Generate Swagger documentation.
     *
     * @return \OpenApi\OpenApi
     */
    function generate_swagger_docs()
    {
        return \OpenApi\Generator::scan(swagger_scan_paths());
    }
}
