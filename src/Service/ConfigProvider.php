<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Service;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use ReliqArts\Scavenger\Contract\ConfigProvider as ConfigProviderContract;
use ReliqArts\Scavenger\Exception\BadDaemonConfig;

final class ConfigProvider implements ConfigProviderContract
{
    private const DEFAULT_GUZZLE_SETTINGS = [
        'timeout' => 60,
    ];

    /**
     * Get config.
     */
    public function get(): array
    {
        return Config::get('scavenger', []);
    }

    public function getGuzzleSettings(): array
    {
        return Config::get('scavenger.guzzle_settings', self::DEFAULT_GUZZLE_SETTINGS);
    }

    /**
     * Get targets.
     */
    public function getTargets(): array
    {
        return Config::get('scavenger.targets', []);
    }

    /**
     * Get scavenger daemon (user) instance. Creates daemon if he doesn't exist.
     *
     * @throws BadDaemonConfig
     */
    public function getDaemon(): Authenticatable
    {
        $badDaemonConfigMessage = 'Scavenger daemon does not exist and could not be created. Check database config.';
        $daemon = $this->getDaemonModel()
            ->where(
                $this->getDaemonModelIdProp(),
                $this->getDaemonModelId()
            )
            ->first();

        if (!$daemon) {
            // attempt to create
            try {
                $daemon = $this->getDaemonModel()
                    ->create($this->getDaemonInfo());
            } catch (Exception $exception) {
                // fail, could not create daemon user
                throw new BadDaemonConfig(sprintf('%s %s', $badDaemonConfigMessage, $exception->getMessage()), $exception->getCode(), $exception);
            }
        }

        return $daemon;
    }

    /**
     * Get daemon model.
     *
     * @return mixed
     */
    public function getDaemonModel()
    {
        return resolve($this->getDaemonModelName());
    }

    /**
     * Get daemon model name.
     */
    public function getDaemonModelName(): string
    {
        return Config::get('scavenger.daemon.model', 'App\\User');
    }

    /**
     * Get ID property  for daemon.
     */
    public function getDaemonModelIdProp(): string
    {
        return Config::get('scavenger.daemon.id_prop', 'email');
    }

    /**
     * Get ID property value for daemon.
     */
    public function getDaemonModelId(): string
    {
        return Config::get('scavenger.daemon.id', 'daemon@scavenger.reliqarts.com');
    }

    /**
     * Get attribute values for daemon.
     */
    public function getDaemonInfo(): array
    {
        $infoConfig = Config::get('scavenger.daemon.info', []);
        $info = array_merge($infoConfig, [
            $this->getDaemonModelIdProp() => $this->getDaemonModelId(),
        ]);
        if (!empty($infoConfig['password'])) {
            // hash password
            $info['password'] = Hash::make($infoConfig['password']);
        }

        return $info;
    }

    /**
     * {@inheritdoc}
     */
    public function getHashAlgorithm(): string
    {
        $algorithm = Config::get('scavenger.hash_algorithm');

        if (empty($algorithm)) {
            return self::DEFAULT_HASH_ALGORITHM;
        }

        return $algorithm;
    }

    public function getLogDir(): string
    {
        return $this->get()['storage']['log_dir'] ?? 'scavenger';
    }

    public function getVerbosity(): int
    {
        $verbosity = (int)Config::get('scavenger.verbosity');

        if ($verbosity === 0) {
            return self::DEFAULT_VERBOSITY;
        }

        return $verbosity;
    }

    /**
     * {@inheritdoc}
     */
    public function isLoggingEnabled(): bool
    {
        return (bool)Config::get('scavenger.log');
    }

    /**
     * Get scavenger scraps table.
     */
    public static function getScrapsTable(): string
    {
        return Config::get('scavenger.database.scraps_table', 'scavenger_scraps');
    }

    /**
     * Convert config key name to special key.
     */
    public static function specialKey(string $keyName): string
    {
        return self::SPECIAL_KEY_PREFIX . $keyName;
    }

    /**
     * Check if key name is config key/special key name.
     */
    public static function isSpecialKey(?string $keyName): bool
    {
        if ($keyName === null) {
            return false;
        }

        return strpos($keyName, self::SPECIAL_KEY_PREFIX) === 0;
    }
}
