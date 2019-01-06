<?php

/*
 * @author    ReliQ <reliq@reliqarts.com>
 * @copyright 2018
 */

namespace ReliQArts\Scavenger\Exceptions;

use ReliQArts\Scavenger\DTOs\Result;
use RuntimeException;

/**
 * Base exception for Scavenger.
 */
abstract class Exception extends RuntimeException
{
    /**
     * @param Result $result
     *
     * @return self
     */
    public static function fromResult(Result $result): self
    {
        $errors = implode(' ', $result->getErrors());

        return new static("The following errors occurred: ${errors}");
    }
}
