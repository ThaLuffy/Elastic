<?php

namespace ThaLuffy\Elastic;

use Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

use ThaLuffy\Elastic\Commands\CreateIndex;
use ThaLuffy\Elastic\Commands\IndexRecords;
use ThaLuffy\Elastic\Commands\CreateModel;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom($this->__configPath(), 'elastic');

        $this->commands([
            CreateIndex::class,
            IndexRecords::class,
            CreateModel::class,
        ]);

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            $this->__migrationPath() => database_path('migrations')
        ], 'migrations');

        $this->publishes([
            $this->__configPath() => config_path('elastic.php')
        ], 'config');
    }

    /**
     * Set the config path
     *
     * @return string
     */
    private function __migrationPath()
    {
        return __DIR__ . '/../database/migrations/';
    }

    /**
     * Set the config path
     *
     * @return string
     */
    private function __configPath()
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