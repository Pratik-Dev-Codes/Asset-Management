<?php

namespace App\Providers;

use App\Contracts\Asset\AssetRepositoryInterface;
use App\Repositories\Asset\AssetRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            AssetRepositoryInterface::class,
            AssetRepository::class
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
