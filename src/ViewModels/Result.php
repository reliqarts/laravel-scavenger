<?php

namespace ReliQArts\Scavenger\ViewModels;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

/**
 *	Result view model.
 */
class Result implements Arrayable, Jsonable
{
    public $success = false;
    public $error;
    public $extra;
    public $message;
    public $reused;

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function toArray()
    {
        return (array) $this;
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
