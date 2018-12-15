<?php

/*
 * @author    ReliQ <reliq@reliqarts.com>
 * @copyright 2018
 */

namespace ReliQArts\Scavenger\Helpers;

use Doctrine\DBAL\Driver\PDOException;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Config as BaseConfig;
use Illuminate\Support\Facades\Hash;
use ReliQArts\Scavenger\Exceptions\BadDaemonConfig;

class Config extends BaseConfig
{
    /**
     * Directive used for special keys in config.
     */
    private const SPECIAL_KEY_PREFIX = '__';
    private const DEFAULT_GUZZLE_SETTINGS = [
        'timeout' => 60,
    ];

    /**
     * Get config.
     *
     * @return array
     */
    public static function get(): array
    {
        return parent::get('scavenger', []);
    }

    /**
     * @return array
     */
    public static function getGuzzleSettings(): array
    {
        return parent::get('scavenger.guzzle_settings', self::DEFAULT_GUZZLE_SETTINGS);
    }

    /**
     * Get targets.
     *
     * @return array
     */
    public static function getTargets(): array
    {
        return parent::get('scavenger.targets', []);
    }

    /**
     * Get daemon model name.
     *
     * @return string
     */
    public static function getDaemonModelName(): string
    {
        return parent::get('scavenger.daemon.model', 'App\\User');
    }

    /**
     * Get daemon model.
     *
     * @return mixed
     */
    public static function getDaemonModel()
    {
        return resolve(static::getDaemonModelName());
    }

    /**
     * Get ID property  for daemon.
     *
     * @return string
     */
    public static function getDaemonModelIdProp(): string
    {
        return parent::get('scavenger.daemon.id_prop', 'email');
    }

    /**
     * Get ID property value for daemon.
     *
     * @return string
     */
    public static function getDaemonModelId(): string
    {
        return parent::get('scavenger.daemon.id', 'daemon@scavenger.reliqarts.com');
    }

    /**
     * Get attribute values for daemon.
     *
     * @return array
     */
    public static function getDaemonInfo(): array
    {
        $infoConfig = parent::get('scavenger.daemon.info', []);
        $info = array_merge($infoConfig, [
            static::getDaemonModelIdProp() => static::getDaemonModelId(),
        ]);
        if (!empty($infoConfig['password'])) {
            // hash password
            $info['password'] = Hash::make($infoConfig['password']);
        }

        return $info;
    }

    /**
     * Get scavenger daemon (user) instance. Creates daemon if he doesn't exist.
     *
     * @throws \ReliQArts\Scavenger\Exceptions\BadDaemonConfig
     *
     * @return Authenticatable
     */
    public static function getDaemon(): Authenticatable
    {
        $badDaemonConfigMessage = 'Scavenger daemon does not exist and could not be created. Check database config.';

        if (!$daemon = self::getDaemonModel()->where(
            self::getDaemonModelIdProp(),
            self::getDaemonModelId()
        )->first()) {
            // attempt to create
            try {
                $daemon = self::getDaemonModel()->create(self::getDaemonInfo());
            } catch (PDOException | Exception $e) {
                // fail, could not create daemon user
                throw new BadDaemonConfig($badDaemonConfigMessage);
            }
        }

        return $daemon;
    }

    /**
     * Get scavenger scraps table.
     *
     * @return string
     */
    public static function getScrapsTable(): string
    {
        return parent::get('scavenger.database.scraps_table', 'scavenger_scraps');
    }

    /**
     * Convert config key name to special key.
     *
     * @param string $keyName
     *
     * @return mixed
     */
    public static function specialKey($keyName)
    {
        if (!empty($keyName)) {
            $keyName = self::SPECIAL_KEY_PREFIX . $keyName;
        }

        return $keyName;
    }

    /**
     * Check if key name is config key/special key name.
     *
     * @param string $keyName
     *
     * @return bool
     */
    public static function isSpecialKey($keyName): bool
    {
        return strpos($keyName, self::SPECIAL_KEY_PREFIX) === 0;
    }
}
