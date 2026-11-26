<?php

namespace NewGeode\WebGuard;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class WebGuardServiceProvider extends ServiceProvider
{
    public function register()
    {

        // Merge package config file.
        $this->mergeConfigFrom(__DIR__.'/../config/webguard.php', 'webguard');
    }

    public function boot()
    {

        // Publish package config file.
        $this->publishes([
            __DIR__.'/../config/webguard.php' => config_path('webguard.php'),
        ], 'webguard-publish-config');


        // Register webguard middleware aliases.
        app('router')->aliasMiddleware(
            'webguard',
            \NewGeode\WebGuard\Http\Middleware\WebGuard::class
        );

        // Automatically define the 'webguard' log channel if it doesn't exist
        if(!Config::has('logging.channels.webguard')){
            Config::set('logging.channels.webguard', [
                'driver' => 'daily', // single
                'path' => storage_path('logs/webguard.log'),
                'level' => 'debug',
                'days' => 14,
            ]);
        }

        // Load package views files.
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'WGResourcesViews');
    }
}