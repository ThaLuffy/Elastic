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
            $this->_migrationPath() => database_path('migrations')
        ], 'migrations');

        $this->publishes([
            $this->_configPath() => config_path('elastic.php')
        ], 'config');
    }

    /**
     * Set the config path
     *
     * @return string
     */
    protected function _migrationPath()
    {
        return __DIR__ . '/../database/migrations/';
    }

    /**
     * Set the config path
     *
     * @return string
     */
    protected function _configPath()
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