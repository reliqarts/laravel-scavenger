<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Exception;

use ReliqArts\Scavenger\Result;
use RuntimeException;

/**
 * Base exception for Scavenger.
 */
abstract class Exception extends RuntimeException
{
    public static function fromResult(Result $result): self
    {
        $errors = implode(' ', $result->getErrors());

        return new static("The following errors occurred: ${errors}");
    }
}
