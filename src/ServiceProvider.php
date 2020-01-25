<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use ReliqArts\Scavenger\Console\Command\Seek;

/**
 *  Service Provider.
 */
final class ServiceProvider extends BaseServiceProvider
{
    /**
     * @var string
     */
    protected string $assetsDir = __DIR__ . '/..';

    /**
     * List of commands.
     *
     * @var array
     */
    protected array $commands = [
        Seek::class,
    ];

    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
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
    public function register(): void
    {
        $loader = AliasLoader::getInstance();

        // bind contract to service model
        $this->app->bind(
            Contract\Seeker::class,
            Service\Seeker::class
        );

        // Register facades...
        $loader->alias('ScavengerService', Service\Seeker::class);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            Contract\Seeker::class,
        ];
    }

    /**
     * Publish assets.
     */
    protected function handleAssets(): void
    {
        // ...
    }

    /**
     * Register Configuration.
     */
    protected function handleConfig(): void
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
    private function handleCommands(): void
    {
        // Register the commands...
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }

    /**
     * Migration files.
     */
    private function handleMigrations(): void
    {
        // Load the migrations...
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // allow publishing of migrations
        $this->publishes([
            "{$this->assetsDir}/database/migrations/" => database_path('migrations'),
        ], 'scavenger:migrations');
    }
}
