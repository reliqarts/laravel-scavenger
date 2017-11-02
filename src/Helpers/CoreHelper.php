<?php

namespace ReliQArts\Scavenger\Helpers;

use Hash;
use Config;
use ReliQArts\Scavenger\Exceptions\DaemonException;

class CoreHelper
{
    /**
     * Get config.
     * 
     * @return array
     */
    public static function getConfig()
    {
        return Config::get('scavenger', []);
    }

    /**
     * Get targets.
     * 
     * @return array
     */
    public static function getTargets()
    {
        return Config::get('scavenger.targets', []);
    }

    /**
     * Get daemon model name.
     * 
     * @return string
     */
    public static function getDaemonModelName()
    {
        return Config::get('scavenger.daemon.model', 'App\\User');
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
    public static function getDaemonModelIdProp()
    {
        return Config::get('scavenger.daemon.id_prop', 'email');
    }

    /**
     * Get ID property value for daemon.
     * 
     * @return string
     */
    public static function getDaemonModelId()
    {
        return Config::get('scavenger.daemon.id', 'daemon@scavenger.reliqarts.com');
    }

    /**
     * Get attribute values for daemon.
     * 
     * @return array
     */
    public static function getDaemonInfo()
    {   
        $infoConfig = Config::get('scavenger.daemon.info', []);
        $info = array_merge($infoConfig, [
            static::getDaemonModelIdProp() => static::getDaemonModelId()
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
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \ReliQArts\Scavenger\Exceptions\DaemonException
     */
    public static function getDaemon()
    {        
        if (!$daemon = self::getDaemonModel()->where(
                self::getDaemonModelIdProp(), 
                self::getDaemonModelId())->first()
            ) {
            // attempt to create
            try {
                $daemon = self::getDaemonModel()->create(self::getDaemonInfo());
            } catch (PDOException $e) {
                // fail, could not create daemon user
                throw new DaemonException('Scavenger daemon does not exist and could not be created. Ensure your database is set up and accessible.');
            }
        }

        return $daemon;
    }
}
