<?php

namespace ReliQArts\Scavenger\DTOs;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 * Result
 */
class Result implements Arrayable, Jsonable
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
     * @var mixed
     */
    private $extra;

    /**
     * @var string
     */
    private $message;

    /**
     * @var mixed
     */
    private $data;

    /**
     * Result constructor.
     *
     * @param bool   $success
     * @param mixed  $error
     * @param mixed  $extra
     * @param string $message
     * @param mixed  $data
     */
    public function __construct(bool $success = false, $error = null, $extra = null, string $message = '', $data = null)
    {
        $this->success = $success;
        $this->error = $error;
        $this->extra = $extra;
        $this->message = $message;
        $this->data = $data;
    }


    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     *
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
     * @param string $error
     *
     * @return Result
     */
    public function addError(string $error): self
    {
        $clone = clone $this;
        $clone->errors[] = $error;

        return $clone;
    }

    /**
     * @return bool
     */
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

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
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
