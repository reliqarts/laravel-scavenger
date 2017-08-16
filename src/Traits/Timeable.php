<?php

namespace ReliQArts\Scavenger\Traits;

/**
 * Timeable trait.
 */
trait Timeable
{
    /**
     * Get seconds since a microtime start-time.
     *
     * @param int $startTime Start time in microseconds.
     * @return string Seconds since, to 2 decimal places.
     */
    protected function secondsSince($startTime)
    {
        $duration = microtime(true) - $startTime;
        $hours = (int) ($duration / 60 / 60);
        $minutes = (int) ($duration / 60) - $hours * 60;
        $seconds = $duration - $hours * 60 * 60 - $minutes * 60;

        return number_format((float) $seconds, 2, '.', '');
    }
}
