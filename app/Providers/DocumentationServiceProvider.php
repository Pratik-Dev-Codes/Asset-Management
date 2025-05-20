<?php

namespace App\Providers;

use App\Console\Commands\GenerateDocumentationCommand;
use Illuminate\Support\ServiceProvider;

class DocumentationServiceProvider extends ServiceProvider
{
    protected $commands = [
        GenerateDocumentationCommand::class,
    ];

    public function register()
    {
        $this->commands($this->commands);
    }

    public function boot()
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../../docs' => base_path('docs'),
        ], 'documentation');
    }
}
