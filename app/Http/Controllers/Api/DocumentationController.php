<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DocumentationController extends Controller
{
    /**
     * Display the API documentation.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('api.documentation.index', [
            'title' => config('api_docs.title'),
            'description' => config('api_docs.description'),
            'versions' => config('api_docs.versions'),
            'defaultVersion' => config('api_docs.default_version'),
        ]);
    }

    /**
     * Display the API documentation for a specific version.
     *
     * @param  string  $version
     * @return \Illuminate\View\View
     */
    public function show($version)
    {
        $version = str_replace('.', '_', $version);
        $filePath = resource_path("views/api/documentation/{$version}.blade.php");

        if (! File::exists($filePath)) {
            abort(404, 'Documentation not found for this version');
        }

        return view("api.documentation.{$version}", [
            'title' => config('api_docs.title')." - v{$version}",
            'description' => config('api_docs.description'),
            'version' => $version,
        ]);
    }

    /**
     * Generate the OpenAPI specification.
     *
     * @param  string  $version
     * @return \Illuminate\Http\JsonResponse
     */
    public function specification($version)
    {
        $version = str_replace('.', '_', $version);
        $filePath = resource_path("api-docs/{$version}.json");

        if (! File::exists($filePath)) {
            return response()->json([
                'message' => 'API specification not found for this version',
            ], 404);
        }

        $spec = json_decode(File::get($filePath), true);

        // Add server information
        $spec['servers'] = array_map(function ($server) use ($version) {
            return [
                'url' => rtrim($server['url'], '/')."/v{$version}",
                'description' => $server['description'],
            ];
        }, config('api_docs.servers'));

        // Add security schemes
        $spec['components']['securitySchemes'] = config('api_docs.security');

        return response()->json($spec);
    }
}
