<?php

namespace ReliQArts\Scavenger;

use Illuminate\Routing\Router;
use ReliQArts\Scavenger\Models;
use ReliQArts\Scavenger\Contracts;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use ReliQArts\Scavenger\Helpers\RouteHelper;
use ReliQArts\Scavenger\Console\Commands\Seek;

/**
 *  ScavengerServiceProvider.
 */
class ScavengerServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Assets location.
     */
    protected $assetsDir = __DIR__.'/..';

    /**
     * List of commands.
     *
     * @var array
     */
    protected $commands = [
        Seek::class,
    ];

    /**
     * Publish assets.
     *
     * @return void
     */
    protected function handleAssets()
    {
        // ...
    }

    /**
     * Command files.
     */
    private function handleCommands()
    {
        // Register the commands...
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }

    /**
     * Register Configuraion.
     */
    protected function handleConfig()
    {
        // merge config
        $this->mergeConfigFrom("$this->assetsDir/config/config.php", 'scavenger');

        // allow publishing config
        $this->publishes([
            "$this->assetsDir/config/config.php" => config_path('scavenger.php'),
        ], 'scavenger:config');
    }

    /**
     * Migration files.
     */
    private function handleMigrations()
    {
        // Load the migrations...
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // allow publishing of migrations
        $this->publishes([
            "$this->assetsDir/database/migrations/" => database_path('migrations'),
        ], 'scaveneger:migrations');
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        // register config
        $this->handleConfig();
        // load migrations
        $this->handleMigrations();
        // publish assets
        $this->handleAssets();
        // publish commands
        $this->handleCommands();
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $loader = AliasLoader::getInstance();

        // bind contract to service model
        $this->app->bind(
            Contracts\Seeker::class,
            Services\Scavenger::class
        );

        // Register facades...
        $loader->alias('ScavengerService', Services\Scavenger::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            Contracts\Seeker::class,
        ];
    }
}
