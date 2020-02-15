<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Contract;

use Illuminate\Contracts\Auth\Authenticatable;
use ReliqArts\Scavenger\Exception\BadDaemonConfig;

interface ConfigProvider
{
    public const SPECIAL_KEY_PREFIX = '__';
    public const DEFAULT_VERBOSITY = 0;
    public const DEFAULT_HASH_ALGORITHM = 'sha512';

    public function get(): array;

    public function getGuzzleSettings(): array;

    /**
     * Get targets.
     */
    public function getTargets(): array;

    /**
     * Get daemon model name.
     */
    public function getDaemonModelName(): string;

    /**
     * Get daemon model.
     *
     * @return mixed
     */
    public function getDaemonModel();

    /**
     * Get ID property  for daemon.
     */
    public function getDaemonModelIdProp(): string;

    /**
     * Get ID property value for daemon.
     */
    public function getDaemonModelId(): string;

    /**
     * Get attribute values for daemon.
     */
    public function getDaemonInfo(): array;

    /**
     * Get scavenger daemon (user) instance. Creates daemon if he doesn't exist.
     *
     * @throws BadDaemonConfig
     */
    public function getDaemon(): Authenticatable;

    /**
     * Get has algorithm to be used.
     */
    public function getHashAlgorithm(): string;

    public function getLogDir(): string;

    public function getVerbosity(): int;

    /**
     * Whether logging is enabled.
     * Note: critical info. or higher will always be logged regardless of log config.
     */
    public function isLoggingEnabled(): bool;

    /**
     * Get scavenger scraps table.
     */
    public static function getScrapsTable(): string;

    /**
     * Convert config key name to special key.
     */
    public static function specialKey(string $keyName): string;

    /**
     * Check if key name is config key/special key name.
     *
     * @param string $keyName
     */
    public static function isSpecialKey($keyName): bool;
}
