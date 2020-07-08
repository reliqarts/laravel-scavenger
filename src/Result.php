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
    private bool $success;
    private string $message;
    private $data;
    private $extra;

    /**
     * @var string[]
     */
    private array $errors;

    /**
     * Result constructor.
     *
     * @param string[]   $errors
     * @param null|mixed $data
     * @param null|mixed $extra
     */
    public function __construct(
        bool $success = false,
        array $errors = [],
        string $message = '',
        $data = null,
        $extra = null
    ) {
        $this->success = $success;
        $this->errors = $errors;
        $this->message = $message;
        $this->data = $data;
        $this->extra = $extra;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return Result
     */
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

    /**
     * @return Result
     */
    public function addError(string $error): self
    {
        $clone = clone $this;
        $clone->errors[] = $error;

        return $clone;
    }

    /**
     * @param string[] $errors
     *
     * @return Result
     */
    public function addErrors(array $errors): self
    {
        $clone = clone $this;
        $clone->errors = array_merge($clone->errors, $errors);

        return $clone;
    }

    public function hasErrors(): bool
    {
        return !empty($this->getErrors());
    }

    public function getExtra()
    {
        return $this->extra;
    }

    public function setExtra($extra): self
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

    public function getData()
    {
        return $this->data;
    }

    public function setData($data): self
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
        return (array)$this;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $options
     *
     * @throws JsonException
     */
    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }
}
