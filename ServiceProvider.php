<?php

namespace App\Libs\ES;

use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('es.client', function () {
            return ClientBuilder::create()
                ->setHosts(Config::get('es.hosts'))
                ->build();
        });
    }
}