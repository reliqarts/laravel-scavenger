<?php

namespace ReliQArts\Scavenger;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
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
    protected $assetsDir = __DIR__ . '/..';

    /**
     * List of commands.
     *
     * @var array
     */
    protected $commands = [
        Seek::class,
    ];

    /**
     * Perform post-registration booting of services.
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

    /**
     * Publish assets.
     */
    protected function handleAssets()
    {
        // ...
    }

    /**
     * Register Configuraion.
     */
    protected function handleConfig()
    {
        // merge config
        $this->mergeConfigFrom("{$this->assetsDir}/config/config.php", 'scavenger');

        // allow publishing config
        $this->publishes([
            "{$this->assetsDir}/config/config.php" => config_path('scavenger.php'),
        ], 'scavenger:config');
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
     * Migration files.
     */
    private function handleMigrations()
    {
        // Load the migrations...
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // allow publishing of migrations
        $this->publishes([
            "{$this->assetsDir}/database/migrations/" => database_path('migrations'),
        ], 'scaveneger:migrations');
    }
}
