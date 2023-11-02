<?php

namespace Sonole\LaravelDbSeedRollback;

use Illuminate\Support\ServiceProvider;

class LaravelDbSeedRollbackServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('command.seed', function ($app) {
            $resolver = $app->make(\Illuminate\Database\ConnectionResolverInterface::class);
            return new \Sonole\LaravelDbSeedRollback\Illuminate\Database\Console\Seeds\SeedCommand($resolver);
        });
        $this->commands('command.seed');
    }

}