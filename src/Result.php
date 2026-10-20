<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonException;

/**
 * Result.
 */
final class Result implements Arrayable, Jsonable
{
    /**
     * @param  string[]  $errors
     */
    public function __construct(
        private bool $success = false,
        private array $errors = [],
        private string $message = '',
        private mixed $data = null,
        private mixed $extra = null
    ) {}

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): self
    {
        $clone = clone $this;
        $clone->success = $success;

        return $clone;
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $error): self
    {
        $clone = clone $this;
        $clone->errors[] = $error;

        return $clone;
    }

    /**
     * @param  string[]  $errors
     */
    public function addErrors(array $errors): self
    {
        $clone = clone $this;
        $clone->errors = array_merge($clone->errors, $errors);

        return $clone;
    }

    public function hasErrors(): bool
    {
        return ! empty($this->getErrors());
    }

    public function getExtra(): mixed
    {
        return $this->extra;
    }

    public function setExtra(mixed $extra): self
    {
        $clone = clone $this;
        $clone->extra = $extra;

        return $clone;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $clone = clone $this;
        $clone->message = $message;

        return $clone;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function setData(mixed $data): self
    {
        $clone = clone $this;
        $clone->data = $data;

        return $clone;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return (array) $this;
    }

    /**
     * {@inheritdoc}
     *
     * @param  int  $options
     *
     * @throws JsonException
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
