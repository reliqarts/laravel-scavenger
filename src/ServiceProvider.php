<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger;

use Exception;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Override;
use Psr\Log\LoggerInterface;
use ReliqArts\Scavenger\Console\Command\Seek;
use ReliqArts\Scavenger\Contract\ConfigProvider as ConfigProviderContract;
use ReliqArts\Scavenger\Contract\Seeker as SeekerContract;
use ReliqArts\Scavenger\Facade\Scavenger;
use ReliqArts\Scavenger\Helper\NodeProximityAssistant;
use ReliqArts\Scavenger\Service\ConfigProvider;
use ReliqArts\Scavenger\Service\Seeker;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

/**
 *  Service Provider.
 *
 * @codeCoverageIgnore
 */
class ServiceProvider extends BaseServiceProvider
{
    protected const string ASSET_DIRECTORY = __DIR__.'/..';

    private const string LOG_FILE_PREFIX = 'scavenger-';

    private const string LOGGER_NAME = 'Scavenger.Seeker';

    private const float DEFAULT_HTTP_TIMEOUT = 60;

    /**
     * List of commands.
     */
    protected array $commands = [
        Seek::class,
    ];

    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        $this->handleConfig();
        $this->handleMigrations();
        $this->handleAssets();
        $this->handleCommands();
    }

    /**
     * Register bindings in the container.
     */
    #[Override]
    public function register(): void
    {
        $loader = AliasLoader::getInstance();

        $this->app->singleton(ConfigProviderContract::class, ConfigProvider::class);
        $this->app->bind(
            SeekerContract::class,
            function (): SeekerContract {
                $configProvider = resolve(ConfigProviderContract::class);

                return new Seeker(
                    $this->getLogger($configProvider),
                    new HttpBrowser(HttpClient::create($this->httpClientOptions($configProvider))),
                    $configProvider,
                    new NodeProximityAssistant
                );
            }
        );

        $loader->alias('Scavenger', Scavenger::class);
    }

    /**
     * Get the services provided by the provider.
     */
    #[Override]
    public function provides(): array
    {
        return [
            SeekerContract::class,
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
        $this->mergeConfigFrom(static::ASSET_DIRECTORY.'/config/config.php', 'scavenger');

        // allow publishing config
        $this->publishes(
            [
                static::ASSET_DIRECTORY.'/config/config.php' => config_path('scavenger.php'),
            ],
            'scavenger-config'
        );
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
        $this->loadMigrationsFrom(static::ASSET_DIRECTORY.'/database/migrations');

        // allow publishing of migrations
        $this->publishes(
            [
                static::ASSET_DIRECTORY.'/database/migrations/' => database_path('migrations'),
            ],
            'scavenger-migrations'
        );
    }

    /**
     * @return array{timeout: float, max_duration: float}
     */
    private function httpClientOptions(ConfigProviderContract $configProvider): array
    {
        $timeout = $configProvider->get()['guzzle_settings']['timeout'] ?? self::DEFAULT_HTTP_TIMEOUT;
        $timeout = is_numeric($timeout) ? (float) $timeout : self::DEFAULT_HTTP_TIMEOUT;

        return [
            'timeout' => $timeout,
            'max_duration' => $timeout,
        ];
    }

    /**
     * @throws Exception
     */
    private function getLogger(ConfigProvider $configProvider): LoggerInterface
    {
        $logFilename = self::LOG_FILE_PREFIX.microtime(true);
        $logger = new Logger(self::LOGGER_NAME);

        $logger->pushHandler(
            new StreamHandler(
                storage_path('logs/'.$configProvider->getLogDir()."/{$logFilename}.log"),
                $configProvider->isLoggingEnabled() ? Logger::DEBUG : Logger::CRITICAL
            )
        );

        return $logger;
    }
}
