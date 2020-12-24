<?php

namespace ThaLuffy\Elastic;

use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

use ThaLuffy\Elastic\Commands\CreateIndex;
use ThaLuffy\Elastic\Commands\IndexRecords;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom($this->configPath(), 'elastic');

        $this->commands([
            CreateIndex::class,
            IndexRecords::class,
        ]);

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations')
        ], 'migrations');

        $this->publishes([$this->configPath() => config_path('cors.php')], 'cors');
    }

    /**
     * Set the config path
     *
     * @return string
     */
    protected function configPath()
    {
        return __DIR__ . '/../config/elastic.php';
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('elastic.client', function () {
            return ClientBuilder::create()
                ->setHosts(Config::get('elastic.hosts'))
                ->build();
        });
    }
}