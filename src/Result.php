<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * Result.
 */
final class Result implements Arrayable, Jsonable
{
    /**
     * @var bool
     */
    private $success;

    /**
     * @var string[]
     */
    private $errors;

    /**
     * @var string
     */
    private $message;

    /**
     * @var mixed
     */
    private $data;

    /**
     * @var mixed
     */
    private $extra;

    /**
     * Result constructor.
     *
     * @param string[] $errors
     * @param mixed    $data
     * @param mixed    $extra
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

    /**
     * @return mixed
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param mixed $extra
     *
     * @return Result
     */
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

    /**
     * @return Result
     */
    public function setMessage(string $message): self
    {
        $clone = clone $this;
        $clone->message = $message;

        return $clone;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     *
     * @return Result
     */
    public function setData($data)
    {
        $clone = clone $this;
        $clone->data = $data;

        return $clone;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function toArray()
    {
        return (array)$this;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray());
    }
}
