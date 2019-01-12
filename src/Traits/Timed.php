<?php

/*
 * @author    ReliQ <reliq@reliqarts.com>
 * @copyright 2018
 */

namespace ReliQArts\Scavenger\Traits;

/**
 * Timed trait.
 */
trait Timed
{
    /**
     * @var int
     */
    protected $startTime = 0;

    /**
     * Get seconds since a "microtime" start-time.
     *
     * @param null|int $startTime
     *
     * @return string seconds since, to 2 decimal places
     */
    protected function elapsedTime(?int $startTime = null): string
    {
        $startTime = $startTime ?? $this->startTime;
        $duration = microtime(true) - $startTime;
        $hours = (int) ($duration / 60 / 60);
        $minutes = (int) ($duration / 60) - $hours * 60;
        $seconds = $duration - $hours * 60 * 60 - $minutes * 60;

        return number_format((float) $seconds, 2, '.', '') . 's';
    }

    protected function startTimer(): void
    {
        $this->startTime = microtime(true);
    }
}
