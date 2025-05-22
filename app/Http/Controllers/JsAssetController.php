<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class JsAssetController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('web');
    }

    /**
     * Serve the laravel-data.js file with dynamic content.
     *
     * @return \Illuminate\Http\Response
     */
    public function laravelData()
    {
        $user = null;
        
        if (Auth::check()) {
            $user = [
                'id' => Auth::id(),
                'name' => Auth::user()->name ?? '',
                'email' => Auth::user()->email ?? '',
            ];
        }

        $content = view('js.laravel-data', [
            'user' => $user,
            'csrfToken' => csrf_token()
        ]);

        return response($content)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}